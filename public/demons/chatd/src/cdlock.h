#ifndef CDLOCK_H
#define CDLOCK_H

/**
@author Yaroslav Rastrigin
*/
class CDLock {
public:
  CDLock();
  ~CDLock();
  void start_read();
  void finish_read();
  void start_write();
  void finish_write();
private:
  pthread_mutex_t stateMutex;
  pthread_cond_t  readCond;
  pthread_cond_t  writeCond;
  int             stateCounter;
};

#endif
