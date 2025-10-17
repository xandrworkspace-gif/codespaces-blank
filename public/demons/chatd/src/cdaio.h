#ifndef CDAIO_H
#define CDAIO_H

/**
Asynchronous (input)/output class

@author Yaroslav Rastrigin
*/
typedef list<char *> t_cdAoQueue;
typedef t_cdAoQueue::iterator t_cdAoQueuePtr;

class CDAO {
public:
  CDAO();
  CDAO(string fileName);
  t_commonStatus status(t_commonStatus newSt = ST_INVALID);
  ~CDAO();
  t_commonStatus  start();
  t_commonStatus  stop();
  int             addMessage(char *newMessage);
  pthread_t       tid(pthread_t newTid = 0) { if (newTid != 0) { aoTId= newTid; }; return aoTId; };

private:
  t_commonStatus  initOutput();
  t_commonStatus  workCycle();
  t_commonStatus  sleep();
  string          outputFilename;
  t_cdAoQueue     outputQueue;
  pthread_mutex_t queueLock;
  pthread_cond_t  queueCond;
  t_commonStatus  objectStatus;
  FILE           *outputHandle;
  pthread_t       aoTId;
};

#endif
