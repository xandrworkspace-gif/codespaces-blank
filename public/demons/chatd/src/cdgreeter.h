#ifndef CDGREETER_H
#define CDGREETER_H

#include <errno.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <sys/select.h>
#include <sys/time.h>
#include <unistd.h>
#include <pthread.h>
/**
Handles incoming network connections

@author Yaroslav Rastrigin
*/
typedef vector<CDGreeter *>       t_cdGreeterList;
typedef t_cdGreeterList::iterator t_cdGreeterListPtr;

class CDGreeter: public CDLock {
public:
  CDGreeter();
  CDGreeter(int port);
  ~CDGreeter();
  int sock(int newSocket = -1);
  int status(int newSt = ST_INVALID);
  pthread_t tid(pthread_t newTid = 0);
  int rollOut();
  int shutDown();
  int prepare(unsigned short int port);
  int cleanUp();
private:
  int listenSocket;
  int greeterStatus;
  pthread_t greeterTId;
};

#endif
