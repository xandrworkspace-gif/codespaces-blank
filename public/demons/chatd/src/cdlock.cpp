#include <cdcommon.h>

CDLock::CDLock() {
  stateCounter = 0;
  pthread_mutex_init(&stateMutex, NULL);
  pthread_cond_init(&readCond, NULL);
  pthread_cond_init(&writeCond, NULL);
}

CDLock::~CDLock() {
  do {
    /* Spin madly until mutex is free */
  } while (pthread_mutex_trylock(&stateMutex) == EBUSY);
  stateCounter = 0;
  UNLOCK_MUTEX(&stateMutex);
  pthread_mutex_destroy(&stateMutex);
  pthread_cond_destroy(&readCond);
  pthread_cond_destroy(&writeCond);
}

void CDLock::start_read() {
  LOCK_MUTEX(&stateMutex);
  return;
  if (stateCounter >=0) {
    stateCounter++;
    UNLOCK_MUTEX(&stateMutex);
    return;
  };
  do {
    debug << __FILE__ << ":" << __LINE__ << " - " << "Sleeping on cond &readCond and mutex &stateMutex" << endo;
    pthread_cond_wait(&readCond, &stateMutex);
    if (stateCounter >= 0) {
      stateCounter++;
      UNLOCK_MUTEX(&stateMutex);
      break;
    };
  } while (1);
  return;
}

void CDLock::finish_read() {
  UNLOCK_MUTEX(&stateMutex);
  return;
  LOCK_MUTEX(&stateMutex);
  stateCounter--;
  if (stateCounter < 0) {
    error << "Negative state counter on finish_read !" << endo;
  };
  if (stateCounter == 0) {
    pthread_cond_signal(&writeCond);
  };
  UNLOCK_MUTEX(&stateMutex);
  return;
}

void CDLock::start_write() {
  LOCK_MUTEX(&stateMutex);
  return;
  if (stateCounter == 0) {
    stateCounter--;
    UNLOCK_MUTEX(&stateMutex);
    return;
  };
  do {
    debug << __FILE__ << ":" << __LINE__ << " - " << "Sleeping on cond &writeCond and mutex &stateMutex" << endo;
    pthread_cond_wait(&writeCond, &stateMutex);
    if (stateCounter == 0) {
      stateCounter--;
      UNLOCK_MUTEX(&stateMutex);
      break;
    };
  } while (1);
  return;
}

void CDLock::finish_write() {
  UNLOCK_MUTEX(&stateMutex);
  return;
  LOCK_MUTEX(&stateMutex);
  stateCounter++;
  if (stateCounter > 0) {
    error << "Positive state counter on finish_write !" << endo;
  };
  if (stateCounter == 0) {
    /* Give another waiting writer slightly better chance to continue */
    pthread_cond_signal(&writeCond);
    pthread_cond_broadcast(&readCond);
  };
  UNLOCK_MUTEX(&stateMutex);
  return;
}
