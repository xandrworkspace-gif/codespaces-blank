#ifndef CDWORKER_H
#define CDWORKER_H

/**
@author Yaroslav Rastrigin
*/
typedef list<CDWorker *>  t_cdWL;
typedef t_cdWL::iterator  t_cdWLIter;

class CDWorker: public CDLock {
public:
  CDWorker();
  CDWorker(int newSock);
  ~CDWorker();
  time_t      last(time_t lastCmd = -1);
  int         sock(int newSocket = -1);
  int         status(int newSt = ST_INVALID);
  pthread_t   tid(pthread_t newTid = 0);
  int         start();
  int         stop();
  int         restart(unsigned int newSock);

private:
  ssize_t     safe_read(size_t size, void *buf);
  ssize_t     safe_write(size_t size, void *buf);
  ssize_t     buffered_read(size_t size, void *buf);
  ssize_t     buffered_write(size_t size, void *buf, bool flush = false);
  int         flushReadBuffer();
  int         workCycle();
  int         getCommand(t_cdCommand *cmdPtr);
  t_cdValue  *getNextArg();
  int         cleanArgs(t_cdArgList *argV);
  int         processCmd(t_cdCommand *command, t_cdArgList *argV);
  int         sendResults(t_cdCommand *cmd, t_cdArgList *ret);
  int         sleep();

  int         listenSocket;
  int         workerStatus;
  time_t      lastCommand;
  pthread_t   workerTId;

  pthread_mutex_t workerSleepMutex;
  pthread_cond_t  workerSleepCond;
  pthread_mutex_t statusMutex;
  char          readBuffer[DEFAULT_READ_BUFFER_SIZE];
  unsigned int  readPacketSize, readBufferPosition, alreadyRead;
  struct {
    char        writePacketSize[8];
    char        writeBuffer[DEFAULT_WRITE_BUFFER_SIZE];
  } o;
  int           writeBufferPtr;
};

#endif
