/* 
 * $Id$
 *
 * Token Queue Daemon (Synchronization Daemon)
 * Arseny Vakhrushev (Sen), it-territory, 2007
 *
 */

#define REV "Token Queue Daemon, $Revision$"

#define RESP_OK          "OK\r\n"
#define RESP_ERROR       "ERROR\r\n"
#define RESP_TIMEOUT     "TIMEOUT\r\n"


#include "common.h"
#include "tqd.h"


char           *tq_host = NULL;
int            tq_port = 0;
debugLevel_t   tq_debugLevel = DL_INFO;
char           *tq_debugLog = NULL;

int            tq_ipAddr;
int            tq_sock;
bool           tq_loop = true;
int            tq_ctime, tq_stime;
vector_t       *tq_clientVec = NULL;	// 'tq_client_t' vector
tq_token_t     *tq_tokenList = NULL;


tq_client_t *tq_clientCreate(int sock) {
	tq_client_t  *client;

	if (sock <= 0) {
		WARN("Invalid arguments");
		return NULL;
	}
	client = malloc(sizeof(tq_client_t));
	if (!client) {
		WARN("malloc() failed");
		return NULL;
	}
	memset(client,0,sizeof(tq_client_t));
	client->sock = sock;
	client->pidx = -1;
	client->iotime = tq_stime;
	return client;
}

errno_t tq_clientDelete(tq_client_t *client) {
	if (!client) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (client->token) {
		client->token->client = NULL;
		client->token->flags |= TQ_TF_REMOVE;
	}
	tq_clientDisconnect(client);
	free(client->inBuf);
	free(client->outBuf);
	free(client);
	return OK;
}

errno_t tq_clientDisconnect(tq_client_t *client) {
	if (!client) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (!client->sock) return ERR_WRONG_STATE;
	shutdown(client->sock,SHUT_RDWR);
	close(client->sock);
	client->sock = 0;
	return OK;
}

errno_t tq_clientRead(tq_client_t *client) {
	int     size;

	if (!client) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (client->flags & (TQ_CF_DISCONN | TQ_CF_IO_WR)) return ERR_WRONG_STATE;
	if (!client->inLen) {
		client->flags |= TQ_CF_IO_RD;
		client->iotime = tq_stime;
		client->inBuf = malloc(MAX_IN_BUF + 1);
		client->inLen = MAX_IN_BUF;
		client->inPos = 0;
	}
	if (client->inPos >= client->inLen) {
		WARN("Insufficient buffer space");
		client->flags |= TQ_CF_DISCONN;
		return ERR_WRONG_DATA;
	}
	size = safeReadNB(client->sock,client->inBuf+client->inPos,client->inLen-client->inPos);
	if (size < 0) {	// eof
		client->flags |= TQ_CF_DISCONN;
		return ERR_EOF;
	}
	client->inPos += size;
	*(client->inBuf + client->inPos) = 0;
	if (!strchr(client->inBuf,'\n')) return ERR_IO_ERROR;
	tq_clientReq(client);
	free(client->inBuf);
	client->inBuf = NULL;
	client->inLen = client->inPos = 0;
	client->flags &= ~TQ_CF_IO_RD;
	return OK;
}

errno_t tq_clientWrite(tq_client_t *client) {
	int     size;

	if (!client) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (client->flags & (TQ_CF_DISCONN | TQ_CF_IO_RD)) return ERR_WRONG_STATE;
	if (!client->outBuf) return OK;
	client->flags |= TQ_CF_IO_WR;
	client->iotime = tq_stime;
	size = safeWriteNB(client->sock,client->outBuf+client->outPos,client->outLen-client->outPos);
	if (size < 0) {	// eof
		client->flags |= TQ_CF_DISCONN;
		return ERR_EOF;
	}
	client->outPos += size;
	if (client->outPos < client->outLen) return ERR_IO_ERROR;
	free(client->outBuf);
	client->outBuf = NULL;
	client->outLen = client->outPos = 0;
	client->flags &= ~TQ_CF_IO_WR;
	return OK;
}

errno_t tq_clientReq(tq_client_t *client) {
	char         req[MAX_IN_BUF+1], *params[MAX_PARAM_CNT], *c, *buf;
	int          cnt, i1, i2, i3;
	time_t       tm;
	tq_token_t   *token, *temp;

	if (!client || !client->inBuf) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (client->token) {
		WARN("Client request while waiting on token");
		return ERR_WRONG_STATE;
	}
	memset(req,0,sizeof(req));
	strncpy(req,client->inBuf,sizeof(req));
	rtrimStr(req);
	c = client->inBuf;
	i1 = true;
	cnt = 0;
	while (*c) {
		if (*c <= 0x20) {
			*c = 0;
			i1 = true;
		} else if (i1) {
			if (cnt >= MAX_PARAM_CNT) {
				WARN("Too many parameters: %s",req);
				tq_clientResp(client,RESP_ERROR);
				return ERR_WRONG_DATA;
			}
			params[cnt++] = c;
			i1 = false;
		}
		c++;
	}

	if (cnt <= 0) {
		WARN("Empty request");
		tq_clientResp(client,RESP_ERROR);
		return ERR_WRONG_DATA;
	}
	if (!strcasecmp(params[0],"CAPTURE")) { // CAPTURE <id> <timewait> <timelock> <priority>
		if ((cnt != 5) || !sscanf(params[2],"%d",&i1) || !sscanf(params[3],"%d",&i2) || !sscanf(params[4],"%d",&i3) || (i1 < 0) || (i2 < 0)) {
			WARN("Wrong parameters: %s",req);
			tq_clientResp(client,RESP_ERROR);
			return ERR_WRONG_DATA;
		}
		token = tq_tokenCreate(params[1]);
		token->timewait = i1;
		token->timelock = i2;
		token->priority = i3;
		token->client = client;
		client->token = token;
		tq_insertToken(token);
		DEBUG("insert [%08x] (id: %s, timewait: %d, timelock: %d, priority: %d)",token->num,token->id,token->timewait,token->timelock,token->priority);
	} 
	else if (!strcasecmp(params[0],"RELEASE")) { // RELEASE <id>
		if (cnt != 2) {
			WARN("Wrong parameters: %s",req);
			tq_clientResp(client,RESP_ERROR);
			return ERR_WRONG_DATA;
		}
		token = tq_tokenList;
		while (token && strcmp(token->id,params[1])) token = token->next;
		if (token) {
			token->flags |= TQ_TF_REMOVE;
			tq_clientResp(client,RESP_OK);
		} else tq_clientResp(client,RESP_ERROR);
	} 
	else if (!strcasecmp(params[0],"INFO")) {	// INFO
		i1 = i2 = i3 = 0;
		token = tq_tokenList;
		while (token) {
			i2++;
			temp = token;
			while (token) {
				i1++;
				if (token->client) i3++;
				token = token->lower;
			}
			token = temp->next;
		}
		memset(&tm,0,sizeof(tm));
		memcpy(&tm,&tq_ctime,sizeof(tq_ctime));
		buf = malloc(1024);
		cnt = 0;
		cnt += sprintf(buf+cnt,     "%s\r\n",REV);
		cnt += strftime(buf+cnt,100,"Start time  : %a, %d %b %Y %H:%M:%S %z\r\n",localtime(&tm));
		cnt += sprintf(buf+cnt,     "Connections : %d\r\n",v_size(tq_clientVec));
		cnt += sprintf(buf+cnt,     "Tokens      : %d total, %d captured, %d pending\r\n",i1,i2,i3);
		cnt += sprintf(buf+cnt,     RESP_OK);
		tq_clientResp(client,buf);
		free(buf);
	}
	else if (!strcasecmp(params[0],"DUMP")) {	// DUMP
		tq_dumpTokens();
		tq_clientResp(client,RESP_OK);
	}
	else if (!strcasecmp(params[0],"RESET")) {	// RESET
		tq_clearTokens();
		tq_clientResp(client,RESP_OK);
	}
	else {
		WARN("Wrong command: %s",req);
		tq_clientResp(client,RESP_ERROR);
		return ERR_WRONG_DATA;
	}
	return OK;
}

errno_t tq_clientResp(tq_client_t *client, char *resp) {
	if (!client || !resp) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (client->outBuf) return ERR_WRONG_STATE;
	client->outBuf = strdup(resp);
	client->outLen = strlen(resp);
	client->outPos = 0;
	return OK;
}


// ======================================================================================================================

tq_token_t *tq_tokenCreate(char *id) {
	tq_token_t             *token;
	static unsigned int    tokenNum = 0;

	if (!id) {
		WARN("Invalid arguments");
		return NULL;
	}
	token = malloc(sizeof(tq_token_t));
	if (!token) {
		WARN("malloc() failed");
		return NULL;
	}
	memset(token,0,sizeof(tq_token_t));
	strncpy(token->id,id,MAX_ID_SIZE);
	token->num = ++tokenNum;
	token->mtime = tq_stime;
	return token;
}

errno_t tq_tokenDelete(tq_token_t *token) {
	if (!token) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (token->client) token->client->token = NULL;
	free(token);
	return OK;
}


errno_t tq_insertToken(tq_token_t *token) {
	tq_token_t   *temp, *prev;

	if (!token) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	temp = tq_tokenList;
	prev = NULL;
	while (temp && strcmp(temp->id,token->id)) {
		prev = temp;
		temp = temp->next;
	}
	if (temp) {	// ID exists
		do {
			prev = temp;
			temp = temp->lower;
		} while (temp && (temp->priority >= token->priority));
		prev->lower = token;
		token->lower = temp;
	}
	else if (prev) prev->next = token;
	else tq_tokenList = token;
	return OK;
}

errno_t tq_updateTokens(void) {
	tq_token_t   *token1, *token2, *prev1, *prev2, *next1, *next2, *temp;

	token1 = tq_tokenList;
	prev1 = NULL;
	while (token1) {
		next1 = token1->next;
		token2 = token1;
		prev2 = NULL;
		while (token2) {
			next2 = token2->lower;
			if (!prev2) {	// top token
				if (token2->client) {
					tq_clientResp(token2->client,RESP_OK);
					token2->mtime = tq_stime;
					token2->client->token = NULL;
					token2->client = NULL;
				}
				if ((token2->mtime + token2->timelock) <= tq_stime) token2->flags |= TQ_TF_REMOVE;
			} else {
				if ((token2->mtime + token2->timewait) <= tq_stime) {
					if (token2->client) tq_clientResp(token2->client,RESP_TIMEOUT);
					token2->flags |= TQ_TF_REMOVE;
				}
			}
			if (token2->flags & TQ_TF_REMOVE) {	// deleting token from the list
				temp = token2;
				if (prev2) {
					prev2->lower = next2;
					token2 = prev2;
				}
				else if (next2) {
					next2->next = next1;
					if (prev1) prev1->next = next2;
					else tq_tokenList = next2;
					token1 = next2;
					token2 = NULL;
				}
				else {
					if (prev1) prev1->next = next1;
					else tq_tokenList = next1;
					token1 = prev1;
					token2 = NULL;
				}
				DEBUG("remove [%08x]",temp->num);
				tq_tokenDelete(temp);
			}
			prev2 = token2;
			token2 = next2;
		}
		prev1 = token1;
		token1 = next1;
	}
	return OK;
}

errno_t tq_dumpTokens(void) {
	tq_token_t   *token, *temp;

	MSG("TOKENS =====================================================");
	token = tq_tokenList;
	while (token) {
		temp = token;
		MSG("[ID: %s]",token->id);
		while (token) {
			MSG("   * [%08x] (id: %s, timewait: %d, timelock: %d, priority: %d)",token->num,token->id,token->timewait,token->timelock,token->priority);
			token = token->lower;
		}
		token = temp->next;
	}
	MSG("============================================================");
	return OK;
}

errno_t tq_clearTokens(void) {
	tq_token_t   *token, *next1, *next2;

	token = tq_tokenList;
	while (token) {
		next1 = token->next;
		while (token) {
			next2 = token->lower;
			tq_tokenDelete(token);
			token = next2;
		}
		token = next1;
	}
	tq_tokenList = NULL;
	return OK;
}


// ======================================================================================================================

errno_t tq_init(char *host, int port) {
	struct hostent     *he;
	struct sockaddr_in sa;
	int                optval[2], i;
	
	if (!port) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (sigHandlerInstall() != OK) return ERR_SYS_ERROR;
	tq_sock = socket(AF_INET,SOCK_STREAM,0);
	if (tq_sock == -1) {
		WARN("socket() failed: %s",strerror(errno));
		return ERR_SYS_ERROR;
	}
	i = fcntl(tq_sock,F_GETFL) | O_NONBLOCK;
	if (fcntl(tq_sock,F_SETFL,i) != 0) {
		WARN("fcntl() failed: %s",strerror(errno));
		return ERR_SYS_ERROR;
	}
	optval[0] = 1;
	if (setsockopt(tq_sock,SOL_SOCKET,SO_REUSEADDR,&optval,sizeof(optval[0])) == -1) {
		WARN("setsockopt(tq_sock,SO_REUSEADDR) failed: %s",strerror(errno));
		return ERR_SYS_ERROR;
	}
	memset(&sa,0,sizeof(sa));
	sa.sin_family = AF_INET;
	if (host) {
		he = gethostbyname(host);
		if (!he) {
			WARN("gethostbyname('%s') failed: %s",host,strerror(h_errno));
			return ERR_SYS_ERROR;
		}
		memcpy(&(sa.sin_addr.s_addr),he->h_addr_list[0],sizeof(sa.sin_addr.s_addr));
		tq_ipAddr = ntohl(sa.sin_addr.s_addr);
	}
	sa.sin_port = htons(port);
	if (bind(tq_sock,(struct sockaddr *)&sa,sizeof(sa))) {
		WARN("bind() failed: %s",strerror(errno));
		return ERR_SYS_ERROR;
	}
	if (listen(tq_sock,SOMAXCONN)) {
		WARN("listen() failed: %s",strerror(errno));
		return ERR_SYS_ERROR;
	}

	tq_ctime = tq_stime = time(NULL);
	tq_clientVec = v_init(NULL);
	return OK;
}

errno_t tq_run(void) {
	tq_client_t       *client;
	struct pollfd     ufds[MAX_POLL_SIZE];
	int               nfds, ecnt;

	MSG("ready");
	while (1) {	// main server loop
		tq_stime = time(NULL);
		ufds[0].fd = tq_sock;
		ufds[0].events = POLLIN | POLLPRI;
		ufds[0].revents = 0;
		nfds = 1;
		v_reset(tq_clientVec,0);
		while (client = v_each(tq_clientVec,0)) {
			tq_clientWrite(client);
			if ((client->flags & TQ_CF_DISCONN) || (client->iotime <= (tq_stime - CLIENT_TTL))) {
				v_remove(tq_clientVec,client);
				tq_clientDelete(client);
				continue;
			}
			if (nfds >= MAX_POLL_SIZE) {
				WARN("Maximum poll size reached");
				break;
			}
			ufds[nfds].fd = client->sock;
			ufds[nfds].events = POLLIN | POLLPRI | (client->flags & TQ_CF_IO_WR ? POLLOUT : 0);
			ufds[nfds].revents = 0;
			client->pidx = nfds;
			nfds++;
		}
		if (!tq_loop) break;
		ecnt = poll(ufds,nfds,1000);
		if ((ecnt == -1) && (errno != EINTR)) WARN("poll() failed: %s",strerror(errno));
		if (ecnt > 0) {
			v_reset(tq_clientVec,0);
			while (client = v_each(tq_clientVec,0)) {
				if ((client->pidx < 0) || (client->pidx >= MAX_POLL_SIZE)) continue;
				if (ufds[client->pidx].revents & (POLLERR | POLLHUP | POLLNVAL)) client->flags |= TQ_CF_DISCONN;
				if (!(ufds[client->pidx].revents & (POLLIN | POLLPRI))) continue;
				tq_clientRead(client);
			}
			if (ufds[0].revents & (POLLIN | POLLPRI)) tq_accept(tq_sock);
		}
		tq_updateTokens();
		if (signals & CSF_SIGINT) {
			blockSignal(SIGINT);
			tq_loop = false;
			signals &= ~CSF_SIGINT;
			unblockSignal(SIGINT);
		}
	}
	return OK;
}

errno_t tq_accept(int sock) {
	struct sockaddr_in  sa;
	socklen_t           sl;
	int                 s, i;
	tq_client_t         *client;

	sl = sizeof(sa);
	do {
		s = accept(sock,(struct sockaddr *)(&sa),&sl);
	} while ((s == -1) && (errno == EINTR));
	if (s == -1) {
		WARN("accept() failed: %s",strerror(errno));
		return ERR_SYS_ERROR;
	}
	i = fcntl(s,F_GETFL) | O_NONBLOCK;
	if (fcntl(s,F_SETFL,i) != 0) {
		WARN("fcntl() failed: %s",strerror(errno));
		return ERR_SYS_ERROR;
	}
	client = tq_clientCreate(s);
	if (!client) {
		WARN("Can't create client");
		return ERR_INIT;
	}
	client->ipAddr = ntohl(sa.sin_addr.s_addr);
	v_push(tq_clientVec,client);
	return OK;
}

errno_t tq_done(void) {
	tq_client_t  *client;

	MSG("shutting down...");
	shutdown(tq_sock,SHUT_RDWR);
	close(tq_sock);
	while (client = v_pop(tq_clientVec)) tq_clientDelete(client);
	v_free(tq_clientVec);
	tq_clearTokens();
	return OK;
}


// ======================================================================================================================

void parseCmdLine(int argc, char *argv[]) {
	int opt;
	while ((opt = getopt(argc, argv, "h:p:l:d:")) != -1) {
		switch ((unsigned char)opt) {
			case 'h':
				tq_host = strdup(optarg);
				break;
			case 'p':
				tq_port = atoi(optarg);
				break;
			case 'l':
				tq_debugLevel = atoi(optarg);
				break;
			case 'd':
				tq_debugLog = strdup(optarg);
				break;
		}
	}
}

int main(int argc, char *argv[]) {
	parseCmdLine(argc,argv);
	DEBUG_INIT(tq_debugLevel,tq_debugLog,REV);
	if (!tq_port) {
		MSG("Usage: %s [-h <host>] -p <port> [-l <debug level>] [-d <debug log>]",argv[0]);
	} else {
		if (tq_init(tq_host,tq_port) == OK) {
			tq_run();
		}
		tq_done();
	}
	DEBUG_DONE();
	free(tq_debugLog);
	return 0;
}
