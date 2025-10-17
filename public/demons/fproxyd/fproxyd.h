/*
 * modifed: igorpauk 2017-18
 */

#ifndef __FPROXYD_H__
#define __FPROXYD_H__

using namespace std;


#define MAX_POLL_SIZE    16384 //8192
#define CLIENT_TTL       90 //60
#define CONN_TTL         900 //600
#define CONN_FINISH_TTL  20 //10

#define MAX_IN_BUF       4096
#define MAX_OUT_BUF      200000
#define MAX_IN_PACKET    32768	// max incoming FS packet size
#define MAX_OUT_PACKET   4096	// max outgoing FS packet size


enum fp_sockFlags_t {
	FP_SF_DISCONN     = 0x0001,
	FP_SF_IO_RD       = 0x0002,
	FP_SF_IO_WR       = 0x0004,
	FP_SF_IO_CONN     = 0x0008,
	FP_SF_IO_GOTHEAD  = 0x0010,
	FP_SF_KEEPALIVE   = 0x0020,
	FP_SF_WAIT        = 0x0040,
};


class fp_sock_c;
class fp_client_c;
class fp_conn_c;
class fp_srv_c;

typedef list<fp_client_c *> fp_clientList_t;
typedef fp_clientList_t::iterator fp_clientListPtr_t;
typedef list<fp_conn_c *> fp_connList_t;
typedef fp_connList_t::iterator fp_connListPtr_t;


class fp_sock_c {
	protected:
	char         *inBuf, *outBuf;
	int          inLen, outLen, inPos, outPos;

	public:
	int          sock, iotime;
	unsigned int flags, ipAddr, tcpPort;

	fp_sock_c(int sock, int ipAddr, int tcpPort);
	errno_t disconnect();
	bool isDisconnected();
	virtual bool isNeedless();
	virtual errno_t read()=0;
	virtual errno_t write();
	virtual void onRead() {};
	virtual void onWrite() {};
	virtual void onDisconnect() {};
	virtual ~fp_sock_c();
};

class fp_client_c : public fp_sock_c {
	protected:
	fp_srv_c     &srv;

	public:
	fp_conn_c    *lastConn;

	fp_client_c(fp_srv_c &srv, int sock, int ipAddr, int tcpPort);
	bool keepAlive(int state);
	errno_t httpIn(string &path, string &sid, string &data);
	errno_t httpOut(int httpStatus, string *content);
	virtual bool isNeedless();
	virtual errno_t read();
	virtual void onRead();
	virtual void onWrite();
	virtual ~fp_client_c();
};

class fp_conn_c : public fp_sock_c {
	private:
	char         tbuf[10];
	strList      queue;         

	public:
	string       sid;
	fp_client_c  *lastClient;

	fp_conn_c(string &sid, int sock, int ipAddr, int tcpPort);
	errno_t fsIn(string &data);
	errno_t fsOut(string &data);
	size_t queueSize();
	errno_t getHttpContent(string &content, string *data);
	virtual bool isNeedless();
	virtual errno_t read();
	virtual void onRead();
	virtual void onDisconnect();
	virtual ~fp_conn_c();
};

class fp_srv_c {
	private:
	int               listenSock, ipAddr, tcpPort, fsIpAddr, fsTcpPort, ctime;
	bool              loop, fsAvail;
	struct sockaddr_in fs_sa;

	public:
	fp_clientList_t   clientList;
	fp_connList_t     connList;

	fp_srv_c();
	errno_t fsInit(string &host, int port);
	fp_conn_c *fsConnect(string &sid);
	errno_t init(string &host, int port);
	errno_t run();
	errno_t accept(int sock);
	errno_t done();
	errno_t extraReq(string &path, string &content);
	~fp_srv_c();
};


#endif
