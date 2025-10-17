/*
 * $Id$
 */

#ifndef __TQD_H__
#define __TQD_H__


#define MAX_POLL_SIZE    8192
#define CLIENT_TTL       1800
#define MAX_ID_SIZE      64
#define MAX_IN_BUF       256
#define MAX_PARAM_CNT    10


typedef enum tq_clientFlags_e tq_clientFlags_t;
typedef enum tq_tokenFlags_e tq_tokenFlags_t;
typedef struct tq_client_s tq_client_t;
typedef struct tq_token_s tq_token_t;


enum tq_clientFlags_e {
	TQ_CF_DISCONN     = 0x01,
	TQ_CF_IO_RD       = 0x02,
	TQ_CF_IO_WR       = 0x04,
};

enum tq_tokenFlags_e {
	TQ_TF_REMOVE      = 0x01,
};


struct tq_client_s {
	int               sock, pidx, iotime;
	unsigned long     ipAddr;
	tq_clientFlags_t  flags;

	char              *inBuf, *outBuf;
	int               inLen, outLen, inPos, outPos;

	tq_token_t        *token;
};

struct tq_token_s {
	char              id[MAX_ID_SIZE+1];
	int               num, mtime, timewait, timelock, priority;
	tq_tokenFlags_t   flags;

	tq_token_t        *next, *lower;
	tq_client_t       *client;
};


tq_client_t *tq_clientCreate(int sock);
errno_t tq_clientDelete(tq_client_t *client);
errno_t tq_clientDisconnect(tq_client_t *client);
errno_t tq_clientRead(tq_client_t *client);
errno_t tq_clientWrite(tq_client_t *client);
errno_t tq_clientReq(tq_client_t *client);
errno_t tq_clientResp(tq_client_t *client, char *resp);

tq_token_t *tq_tokenCreate(char *id);
errno_t tq_tokenDelete(tq_token_t *token);

errno_t tq_insertToken(tq_token_t *token);
errno_t tq_updateTokens(void);
errno_t tq_dumpTokens(void);
errno_t tq_clearTokens(void);

errno_t tq_init(char *host, int port);
errno_t tq_run(void);
errno_t tq_accept(int sock);
errno_t tq_done(void);


#endif
