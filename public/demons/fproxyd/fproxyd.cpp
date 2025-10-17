/* 
 * modifed: igorpauk 2017-18
 */

#define REV "FS HTTP Proxy Daemon, $Rev: 111 $"


#include "common.h"
#include "fproxyd.h"


int                 fp_stime;


fp_sock_c::fp_sock_c(int sock=0, int ipAddr=0, int tcpPort=0)
: inBuf(NULL), outBuf(NULL), inLen(0), outLen(0), inPos(0), outPos(0), sock(sock), iotime(0), flags(0), ipAddr(ipAddr), tcpPort(tcpPort)
{
	iotime = fp_stime;
}

errno_t fp_sock_c::disconnect() {
	if (!sock) return ERR_WRONG_STATE;
	onDisconnect();
	::shutdown(sock,SHUT_RDWR);
	::close(sock);
	sock = 0;
	flags |= FP_SF_DISCONN;
	return OK;
}

bool fp_sock_c::isDisconnected() {
	return !sock || ((flags & FP_SF_DISCONN) > 0);
}

bool fp_sock_c::isNeedless() {
	return isDisconnected();
}

errno_t fp_sock_c::write() {
	int     size;

	if (flags & (FP_SF_DISCONN | FP_SF_IO_RD | FP_SF_IO_CONN)) return ERR_WRONG_STATE;
	if (!outBuf) return OK;
	flags |= FP_SF_IO_WR;
	iotime = fp_stime;
	size = safeWriteNB(sock,outBuf+outPos,outLen-outPos);
	if (size < 0) {	// eof
		disconnect();
		return ERR_EOF;
	}
	outPos += size;
	if (outPos < outLen) return ERR_IO_ERROR;
	delete [] outBuf;
	outBuf = NULL;
	outLen = outPos = 0;
	flags &= ~FP_SF_IO_WR;
	onWrite();
	return OK;
}

fp_sock_c::~fp_sock_c() {
	disconnect();
	delete [] inBuf;
	delete [] outBuf;
}

// ===================================================================================================================================

fp_client_c::fp_client_c(fp_srv_c &srv, int sock=0, int ipAddr=0, int tcpPort=0) 
: fp_sock_c(sock,ipAddr,tcpPort), srv(srv), lastConn(NULL) {
}

bool fp_client_c::isNeedless() {
	return isDisconnected() || (iotime <= (fp_stime - CLIENT_TTL));
}

bool fp_client_c::keepAlive(int state=-1) {
	if (state >= 0) {
		if (state) flags |= FP_SF_KEEPALIVE;
		else flags &= ~FP_SF_KEEPALIVE;
	}
	return (flags & FP_SF_KEEPALIVE) > 0;
}

errno_t fp_client_c::httpIn(string &path, string &sid, string &data) {
	char    *s1, *s2, *s3;
	bool    parseOk = false;

	if (!inBuf) {
		WARN("Input buffer is empty");
		return ERR_WRONG_STATE;
	}
	DEBUG("HTTP IN (sock: %d, keepAlive: %d) [%s]",sock,keepAlive(),inBuf);
	rtrimStr(inBuf);	// taking the very first HTTP request line
	s1 = strchr(inBuf,' ');	// suppose cutting "GET/POST[ ]"
	if (s1) {
		s1++;
		s2 = strrchr(s1,' ');	// suppose cutting "[ ]HTTP/1.x"
		if (s2) {
			*s2 = 0;
			parseOk = true;
			// parsing s1 as [path][?[sid][&data]]
			s2 = strchr(s1,'?');
			if (s2) {
				*s2 = 0;
				s2++;
				s3 = strchr(s2,'&');
				if (s3) {
					*s3 = 0;
					s3++;
					data = s3;
				}
				sid = s2;
			}
			path = s1;
		}
	}
	if (!parseOk) {
		WARN("Request parse error (data: %s)",inBuf);
		return ERR_WRONG_DATA;
	}
	return OK;
}

errno_t fp_client_c::httpOut(int httpStatus, string *content=NULL) {
	int     bufSize, contentLength = 0;
	time_t  stime;

	if (outBuf) return ERR_WRONG_STATE;
	if (content) contentLength = content->size();
	bufSize = 1024 + contentLength;
	if (bufSize > MAX_OUT_BUF) {
		WARN("Data size is too large (need: %d, max: %d)",bufSize,MAX_OUT_BUF);
		return ERR_WRONG_DATA;
	}
	if (httpStatus != 200) keepAlive(false);
	outBuf = new char[bufSize + 1];
	outLen = outPos = 0;
	stime = time(NULL);
	outLen += sprintf(outBuf + outLen, "HTTP/1.1 %s\r\n", (httpStatus == 400 ? "400 Bad Request" : (httpStatus == 404 ? "404 Not Found": "200 OK")));
	outLen += strftime(outBuf + outLen, bufSize - outLen, "Date: %a, %d %b %Y %H:%M:%S %z\r\n", localtime(&stime));
	outLen += sprintf(outBuf + outLen, "Server: " REV "\r\nConnection: %s\r\n", (keepAlive() ? "Keep-Alive": "close"));
	if (httpStatus == 200) {
		outLen += strftime(outBuf + outLen, bufSize - outLen, "Last-Modified: %a, %d %b %Y %H:%M:%S %z\r\n", localtime(&stime));
		outLen += sprintf(outBuf + outLen, "Expires: Mon, 26 Jul 1997 05:00:00 GMT\r\nCache-Control: no-store, no-cache, must-revalidate\r\nCache-Control: post-check=0, pre-check=0\r\nPragma: no-cache\r\nContent-Type: text/plain\r\nContent-Length: %d\r\n", contentLength);
	}
	outLen += sprintf(outBuf + outLen, "\r\n");
	if (contentLength) {
		memcpy(outBuf + outLen, content->data(), contentLength);
		outLen += contentLength;
	}
	*(outBuf + outLen) = 0;
	DEBUG("HTTP OUT (sock: %d, keepAlive: %d, status: %d, length: %d)",sock,keepAlive(),httpStatus,outLen);
	return OK;
}

errno_t fp_client_c::read() {
	int     size, contentPos;
	char    *s1, *s2;

	if (flags & (FP_SF_DISCONN | FP_SF_IO_WR | FP_SF_IO_CONN)) return ERR_WRONG_STATE;
	if (!inBuf) {
		flags |= FP_SF_IO_RD;
		iotime = fp_stime;
		inLen = MAX_IN_BUF;
		inPos = 0;
		inBuf = new char[inLen + 1];
	}
	if (inPos >= inLen) {
		WARN("Insufficient buffer space");
		disconnect();
		return ERR_WRONG_DATA;
	}
	size = safeReadNB(sock,inBuf+inPos,inLen-inPos);
	if (size < 0) {	// eof
		disconnect();
		return ERR_EOF;
	}
	inPos += size;
	if (!(flags & FP_SF_IO_GOTHEAD)) {
		*(inBuf + inPos) = 0;
		contentPos = inPos;
		s1 = strstr(inBuf,"\r\n\r\n");
		if (s1) contentPos = s1 - inBuf + 4;
		else {
			s1 = strstr(inBuf,"\n\n");
			if (s1) contentPos = s1 - inBuf + 2;
			else return ERR_IO_ERROR;
		}
		flags |= FP_SF_IO_GOTHEAD;
		inLen = contentPos;
		*s1 = 0;	// cut the headers
		if (strcasestr(inBuf,"Connection: Keep-Alive")) keepAlive(true);
		s1 = strstr(inBuf,"Content-Length:");	// size = 15 bytes
		if (s1) {
			s1 += 15;
			s2 = strstr(s1,"\r\n");
			if (!s2) s2 = strchr(s1,'\n');
			if (s2) *s2 = 0;
			size = strtol(s1,(char **)NULL,10);
			if (size < 0) {
				WARN("Invalid 'Content-Length' value (value: %d, data: %s)",size,s1);
				disconnect();
				return ERR_WRONG_DATA;
			}
			inLen += size;
			if (inLen > MAX_IN_BUF) {
				WARN("HTTP request is too big (size: %d, max: %d)",inLen,MAX_IN_BUF);
				disconnect();
				return ERR_WRONG_DATA;
			}
		}
		s1 = strstr(inBuf,"\r\n");
		if (!s1) s1 = strchr(inBuf,'\n');
		if (s1) *s1 = 0;	// cut the GET/POST line
	}
	if (flags & FP_SF_IO_GOTHEAD) {
		if (inPos < inLen) return ERR_IO_ERROR;
		flags &= ~FP_SF_IO_GOTHEAD;
	}
	onRead();
	delete [] inBuf;
	inBuf = NULL;
	inLen = inPos = 0;
	flags &= ~FP_SF_IO_RD;
	return OK;
}

void fp_client_c::onRead() {
	string            path, sid, data, content;
	fp_conn_c         *conn;
	fp_connListPtr_t  ptr;

	if (httpIn(path,sid,data) != OK) {
		httpOut(400);
		return;
	}
	if (sid.size()) {	// main request
		conn = NULL;
		if (lastConn && (lastConn->sid == sid)) {
			DEBUG("Taking the last connection used");
			conn = lastConn;
		} else {	// searching the connection pool
			for (ptr = srv.connList.begin(); ptr != srv.connList.end(); ptr++) {
				if ((*ptr)->sid != sid) continue;
				conn = *ptr;
				break;
			}
		}
		if (!conn) {	// can't find any connection
			conn = srv.fsConnect(sid);
			if (!conn) {
				httpOut(404);
				return;
			}
		}
		DEBUG("Using connection (sock: %d)",conn->sock);
		if (!conn->isDisconnected() && data.size()) {	// redirecting request to FS
			if (conn->fsOut(data) != OK) return;	// previous request still pending
			if (lastConn) lastConn->lastClient = NULL;
			if (conn->lastClient) conn->lastClient->lastConn = NULL;
			lastConn = conn;
			conn->lastClient = this;
			flags |= FP_SF_WAIT;
		} else if (!conn->isDisconnected() || conn->queueSize()) {	// either have queued data or empty data received
			conn->getHttpContent(content,NULL);
			httpOut(200,&content);
		} else {
			httpOut(404);
		}
	} else {	// extra request
		if (srv.extraReq(path,content) == OK) httpOut(200,&content);
		else httpOut(400);
	}
}

void fp_client_c::onWrite() {
	if (!keepAlive()) disconnect();
}

fp_client_c::~fp_client_c() {
	if (lastConn) lastConn->lastClient = NULL;
}

// ===================================================================================================================================

fp_conn_c::fp_conn_c(string &sid, int sock=0, int ipAddr=0, int tcpPort=0)
: fp_sock_c(sock,ipAddr,tcpPort), sid(sid), lastClient(NULL) {
}

errno_t fp_conn_c::fsIn(string &data) {
	if (!inBuf) {
		WARN("Input buffer is empty");
		return ERR_WRONG_STATE;
	}
	DEBUG("FS IN (sock: %d) [%s]",sock,inBuf);
	data = inBuf;
	return OK;
}

errno_t fp_conn_c::fsOut(string &data) {
	if (outBuf) return ERR_WRONG_STATE;
	if (data.size() > MAX_OUT_PACKET) {
		WARN("Data size is too large (need: %d, max: %d)",data.size(),MAX_OUT_PACKET);
		return ERR_WRONG_DATA;
	}
	outLen = data.size() + 1;
	outPos = 0;
	outBuf = new char[outLen];
	memcpy(outBuf,data.data(),outLen-1);
	*(outBuf + outLen - 1) = 0;
	DEBUG("FS OUT (sock: %d) [%s]",sock,outBuf);
	return OK;
}

size_t fp_conn_c::queueSize() {
	return queue.size();
}

errno_t fp_conn_c::getHttpContent(string &content, string *data=NULL) {
	strListPtr   ptr;

	content.clear();
	content.push_back('|');
	for (ptr=queue.begin(); ptr != queue.end(); ptr++) {
		if (ptr != queue.begin()) content.push_back(':');
		content.append(*ptr);
	}
	if (data && data->size()) {
		if (!queue.empty()) content.push_back(':');
		content.append(*data);
	}
	content.push_back('|');
	queue.clear();
	return OK;
}

bool fp_conn_c::isNeedless() {
	return (isDisconnected() && (iotime <= (fp_stime - CONN_FINISH_TTL))) || (iotime <= (fp_stime - CONN_TTL));
}

errno_t fp_conn_c::read() {
	int     size;

	if (flags & (FP_SF_DISCONN | FP_SF_IO_WR | FP_SF_IO_CONN)) return ERR_WRONG_STATE;
	if (!inBuf) {	// reading packet size
		flags |= FP_SF_IO_RD;
		iotime = fp_stime;
		size = safeReadNB(sock,tbuf+inPos,4-inPos);
		if (size < 0) {	// eof
			disconnect();
			return ERR_EOF;
		}
		inPos += size;
		if (inPos < 4) return ERR_IO_ERROR;
		*(tbuf + inPos) = 0;
		size = hexToInt(tbuf);
		if ((size <= 0) || (size > MAX_IN_PACKET)) {
			WARN("Invalid incoming packet size (size: %d, max size: %d, data: %s)",size,MAX_IN_PACKET,tbuf);
			disconnect();
			return ERR_WRONG_DATA;
		}
		size++;	// Plus extra \0 at the end since we read C-strings 
		inPos = 4;
		inLen = inPos + size;
		inBuf = new char[inLen];
		memcpy(inBuf,tbuf,inPos);
	}
	if (inPos >= inLen) {
		WARN("Insufficient buffer space");
		disconnect();
		return ERR_WRONG_DATA;
	}
	size = safeReadNB(sock,inBuf+inPos,inLen-inPos);
	if (size < 0) {	// eof
		disconnect();
		return ERR_EOF;
	}
	inPos += size;
	if (inPos < inLen) return ERR_IO_ERROR;
	*(inBuf + inPos - 1) = 0;
	onRead();
	delete [] inBuf;
	inBuf = NULL;
	inLen = inPos = 0;
	flags &= ~FP_SF_IO_RD;
	return OK;
}

void fp_conn_c::onRead() {
	string  data, content;

	if (fsIn(data) != OK) return;
	if (lastClient && (lastClient->flags & FP_SF_WAIT)) {	// some client is waiting for response
		getHttpContent(content,&data);
		lastClient->httpOut(200,&content);
		lastClient->flags &= ~FP_SF_WAIT;
	} else {	// buffering data
		queue.push_back(data);
		DEBUG("buffering...");
	}
}

void fp_conn_c::onDisconnect() {
	string  content;

	if (!lastClient || !(lastClient->flags & FP_SF_WAIT)) return;
	if (queueSize()) {
		getHttpContent(content,NULL);
		lastClient->httpOut(200,&content);
	} else {
		lastClient->httpOut(404);
	}
}

fp_conn_c::~fp_conn_c() {
	if (lastClient) lastClient->lastConn = NULL;
}

// ===================================================================================================================================

fp_srv_c::fp_srv_c()
: listenSock(0), ipAddr(0), tcpPort(0), fsIpAddr(0), fsTcpPort(0), ctime(0), loop(true), fsAvail(true) 
{
}

errno_t fp_srv_c::fsInit(string &host, int port) {
	struct hostent     *he;

	memset(&fs_sa,0,sizeof(fs_sa));
	if (!port) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	fs_sa.sin_family = AF_INET;
	he = gethostbyname(host.c_str());
	if (!he) {
		WARN("gethostbyname() failed: %s",strerror(h_errno));
		return ERR_SYS_ERROR;
	}
	memcpy(&(fs_sa.sin_addr.s_addr),he->h_addr_list[0],sizeof(fs_sa.sin_addr.s_addr));
	fsIpAddr = ntohl(fs_sa.sin_addr.s_addr);
	fsTcpPort = port;
	fs_sa.sin_port = htons(port);
	return OK;
}

fp_conn_c *fp_srv_c::fsConnect(string &sid) {
	int                sock, i;
	fp_conn_c          *conn;
	bool               delayed = false;

	sock = socket(AF_INET,SOCK_STREAM,0);
	if (sock == -1) {
		WARN("socket() failed: %s",strerror(errno));
		return NULL;
	}
	i = fcntl(sock,F_GETFL) | O_NONBLOCK;
	if (fcntl(sock,F_SETFL,i) != 0) {
		WARN("fcntl() failed: %s",strerror(errno));
		return NULL;
	}
	do {
		i = connect(sock,(struct sockaddr *)&fs_sa,sizeof(fs_sa));
	} while ((i == -1) && (errno == EINTR));
	if (i == -1) {
		if (errno != EINPROGRESS) {
			if (fsAvail) WARN("connect() failed: %s",strerror(errno));
			fsAvail = false;
			close(sock);
			return NULL;
		}
		delayed = true;
	}
	fsAvail = true;
	DEBUG("New connection (sock: %d)",sock);
	conn = new fp_conn_c(sid,sock,fsIpAddr,fsTcpPort);
	if (delayed) conn->flags |= FP_SF_IO_CONN;	// non-blocking connect
	connList.push_back(conn);
	return conn;
}

errno_t fp_srv_c::init(string &host, int port) {
	struct hostent     *he;
	struct sockaddr_in sa;
	int                optval[2], i;
	
	if (!port) {
		WARN("Invalid arguments");
		return ERR_WRONG_ARGS;
	}
	if (sigHandlerInstall() != OK) return ERR_SYS_ERROR;
	listenSock = socket(AF_INET,SOCK_STREAM,0);
	if (listenSock == -1) {
		WARN("socket() failed: %s",strerror(errno));
		return ERR_SYS_ERROR;
	}
	i = fcntl(listenSock,F_GETFL) | O_NONBLOCK;
	if (fcntl(listenSock,F_SETFL,i) != 0) {
		WARN("fcntl() failed: %s",strerror(errno));
		return ERR_SYS_ERROR;
	}
	optval[0] = 1;
	if (setsockopt(listenSock,SOL_SOCKET,SO_REUSEADDR,&optval,sizeof(optval[0])) == -1) {
		WARN("setsockopt(listenSock,SO_REUSEADDR) failed: %s",strerror(errno));
		return ERR_SYS_ERROR;
	}
	memset(&sa,0,sizeof(sa));
	sa.sin_family = AF_INET;
	if (!host.empty()) {
		he = gethostbyname(host.c_str());
		if (!he) {
			WARN("gethostbyname() failed: %s",strerror(h_errno));
			return ERR_SYS_ERROR;
		}
		memcpy(&(sa.sin_addr.s_addr),he->h_addr_list[0],sizeof(sa.sin_addr.s_addr));
		ipAddr = ntohl(sa.sin_addr.s_addr);
	}
	sa.sin_port = htons(port);
	if (bind(listenSock,(struct sockaddr *)&sa,sizeof(sa))) {
		WARN("bind() failed: %s",strerror(errno));
		return ERR_SYS_ERROR;
	}
	if (listen(listenSock,SOMAXCONN)) {
		WARN("listen() failed: %s",strerror(errno));
		return ERR_SYS_ERROR;
	}

	ctime = time(NULL);
	tcpPort = port;
	return OK;
}

errno_t fp_srv_c::run() {
	struct pollfd     ufds[MAX_POLL_SIZE];
	int               nfds, ecnt, mfd, i;
	fp_sock_c         *sock, *usocks[MAX_POLL_SIZE];
	fp_client_c       *client;
	fp_conn_c         *conn;
	fp_clientListPtr_t ptr1;
	fp_connListPtr_t  ptr2;

	MSG("ready");
	while (1) {	// main server loop
		fp_stime = time(NULL);
		ufds[0].fd = listenSock;
		ufds[0].events = POLLIN | POLLPRI;
		ufds[0].revents = 0;
		nfds = mfd = 1;
		for (ptr1 = clientList.begin(); (ptr1 != clientList.end()) && (nfds < MAX_POLL_SIZE); ) {
			client = *ptr1;
			client->write();
			if (client->isNeedless()) {
				ptr1 = clientList.erase(ptr1);
				delete client;
				continue;
			} else ptr1++;
			if (client->isDisconnected()) continue;
			usocks[nfds++] = client;
		}
		for (ptr2 = connList.begin(); (ptr2 != connList.end()) && (nfds < MAX_POLL_SIZE); ) {
			conn = *ptr2;
			conn->write();
			if (conn->isNeedless()) {
				ptr2 = connList.erase(ptr2);
				delete conn;
				continue;
			} else ptr2++;
			if (conn->isDisconnected()) continue;
			usocks[nfds++] = conn;
		}
		if (nfds >= MAX_POLL_SIZE) {
			WARN("Maximum poll size reached");
			break;
		}
		for (i = mfd; i<nfds; i++) {
			sock = usocks[i];
			ufds[i].fd = sock->sock;
			ufds[i].events = POLLIN | POLLPRI | (sock->flags & (FP_SF_IO_WR | FP_SF_IO_CONN) ? POLLOUT : 0);
			ufds[i].revents = 0;
		}
		if (!loop) break;
		ecnt = poll(ufds,nfds,1000);
		if ((ecnt == -1) && (errno != EINTR)) WARN("poll() failed: %s",strerror(errno));
		if (ecnt > 0) {
			for (i = mfd; i<nfds; i++) {
				sock = usocks[i];
				if (ufds[i].revents & (POLLERR | POLLHUP | POLLNVAL)) sock->disconnect();
				if ((sock->flags & FP_SF_IO_CONN) && (ufds[i].revents & POLLOUT)) sock->flags &= ~FP_SF_IO_CONN;	// non-blocking connect completed
				if (!(ufds[i].revents & (POLLIN | POLLPRI))) continue;
				sock->read();
			}
			if (ufds[0].revents & (POLLIN | POLLPRI)) accept(listenSock);
		}
		if (signals & CSF_SIGINT) {
			blockSignal(SIGINT);
			loop = false;
			signals &= ~CSF_SIGINT;
			unblockSignal(SIGINT);
		}
	}
	return OK;
}

errno_t fp_srv_c::accept(int sock) {
	struct sockaddr_in  sa;
	socklen_t           sl;
	int                 newSock, i;
	fp_client_c         *client;

	sl = sizeof(sa);
	do {
		newSock = ::accept(sock,(struct sockaddr *)(&sa),&sl);
	} while ((newSock == -1) && (errno == EINTR));
	if (newSock == -1) {
		WARN("accept() failed: %s",strerror(errno));
		return ERR_SYS_ERROR;
	}
	i = fcntl(newSock,F_GETFL) | O_NONBLOCK;
	if (fcntl(newSock,F_SETFL,i) != 0) {
		WARN("fcntl() failed: %s",strerror(errno));
		return ERR_SYS_ERROR;
	}
	client = new fp_client_c(*this,newSock,ntohl(sa.sin_addr.s_addr),ntohs(sa.sin_port));
	clientList.push_back(client);
	return OK;
}

errno_t fp_srv_c::done() {
	MSG("shutting down...");
	shutdown(listenSock,SHUT_RDWR);
	close(listenSock);
	return OK;
}

errno_t fp_srv_c::extraReq(string &path, string &content) {
	char    tbuf[256], ipbuf[16], ipbuf2[16];
	time_t  tm;

	if (path == "/info2019") {
		content = REV "\r\n";
		memset(&tm,0,sizeof(tm));
		memcpy(&tm,&ctime,sizeof(ctime));
		strftime(tbuf,sizeof(tbuf),"Start time       : %a, %d %b %Y %H:%M:%S %z\r\n",localtime(&tm));
		content += tbuf;
		sprintf(tbuf,"HTTP connections : %d\r\n",(int)clientList.size());
		content += tbuf;
		sprintf(tbuf,"FS connections   : %d\r\n",(int)connList.size());
		content += tbuf;
		strIpAddr(ipAddr,ipbuf);
		strIpAddr(fsIpAddr,ipbuf2);
		sprintf(tbuf,"Proxy setup      : HTTP %s:%d <-> FS %s:%d (%s)\r\n",ipbuf,tcpPort,ipbuf2,fsTcpPort,(fsAvail ? "available" : "not available"));
		content += tbuf;
	} else if (path == "/info") {
		content = "ARON GAD\r\n";
	} else if (path == "/rev") {
		content = "ARON GAD\r\n";
	} else if (path == "/crossdomain.xml") {
		content = "<?xml version=\"1.0\"?>\r\n<cross-domain-policy>\r\n<allow-access-from domain=\"*\" />\r\n</cross-domain-policy>\r\n";
	} else return ERR_WRONG_DATA;
	return OK;
}

fp_srv_c::~fp_srv_c() {
	while (!clientList.empty()) {
		delete clientList.front();
		clientList.pop_front();
	}
	while (!connList.empty()) {
		delete connList.front();
		connList.pop_front();
	}
}


// ===================================================================================================================================

string         fp_host;
int            fp_port = 0;
string         fp_fsHost;
int            fp_fsPort = 0;
debugLevel_t   fp_debugLevel = DL_INFO;
string         fp_debugLog;

void parseCmdLine(int argc, char *argv[]) {
	int opt;
	while ((opt = getopt(argc, argv, "h:p:s:c:l:d:")) != -1) {
		switch ((unsigned char)opt) {
			case 'h':
				fp_host = optarg;
				break;
			case 'p':
				fp_port = atoi(optarg);
				break;
			case 's':
				fp_fsHost = optarg;
				break;
			case 'c':
				fp_fsPort = atoi(optarg);
				break;
			case 'l':
				fp_debugLevel = (debugLevel_t)atoi(optarg);
				break;
			case 'd':
				fp_debugLog = optarg;
				break;
		}
	}
}

int main(int argc, char *argv[]) {
	fp_srv_c *srv;

	parseCmdLine(argc,argv);
	DEBUG_INIT(fp_debugLevel,(!fp_debugLog.empty() ? fp_debugLog.c_str() : NULL),REV);
	if (!fp_port || fp_fsHost.empty() || !fp_fsPort || (fp_port == fp_fsPort)) {
		MSG("Usage: %s [-h <host>] -p <port> -s <fs_host> -c <fs_port> [-l <debug level>] [-d <debug log>]",argv[0]);
	} else {
		srv = new fp_srv_c;
		if ((srv->init(fp_host,fp_port) == OK) && (srv->fsInit(fp_fsHost,fp_fsPort) == OK)) {
			srv->run();
		}
		srv->done();
		delete srv;
	}
	DEBUG_DONE();
	return 0;
}
