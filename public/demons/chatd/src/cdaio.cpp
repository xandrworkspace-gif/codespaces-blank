#include <stdio.h>
#include <string.h>
#include <unistd.h>

#include <list>
#include <cdcommon.h>

t_commonStatus CDAO::initOutput() {
  int hi1;
  outputHandle = outputFilename == "" ? fdopen(1, "w") : fopen(outputFilename.c_str(), "a");
  if (!outputHandle) {
    hi1 = errno;
    cerr << "Can't open requested file (" << outputFilename << ") for writing, errno:" << hi1 << endl;
  };
  status(outputHandle ? ST_AVAILABLE : ST_DISCONNECTED);
  if (pthread_mutex_init(&queueLock, NULL)) {
    cerr << "pthread_mutex_init() returns not 0" << endl;
    status(ST_DISCONNECTED);
  };
  if (pthread_cond_init(&queueCond, NULL)) {
    cerr << "pthread_cond_init() returns not 0" << endl;
    status(ST_DISCONNECTED);
  };
  return status();
}

t_commonStatus CDAO::status(t_commonStatus newSt) {
  if (newSt != ST_INVALID) {
    objectStatus = newSt;
  };
  return objectStatus;
}

CDAO::CDAO()
  :outputFilename("") {
  initOutput();
}

CDAO::CDAO(string fileName)
  :outputFilename(fileName) {
  initOutput();
}

CDAO::~CDAO() {
  t_cdAoQueuePtr htcdAQP1;
  LOCK_MUTEX_D(&queueLock);
  for (htcdAQP1 = outputQueue.begin(); htcdAQP1 != outputQueue.end(); ++htcdAQP1) {
    if (*htcdAQP1) {
      cerr << "Message is still in the queue:" << *htcdAQP1 << endl;
      free(*htcdAQP1);
    };
  };
  UNLOCK_MUTEX_D(&queueLock);
  if (status() != ST_DISCONNECTED) {
    if (outputFilename != "") {
      fclose(outputHandle);
    };
    status(ST_DISCONNECTED);
  };
  do { 
  } while (pthread_mutex_trylock(&queueLock) == EBUSY);
  UNLOCK_MUTEX_D(&queueLock);
  pthread_mutex_destroy(&queueLock);
  pthread_cond_destroy(&queueCond);
}

t_commonStatus CDAO::start() {
  do {
    workCycle();
  } while (this->sleep() == ST_AVAILABLE);
  return status();
}

t_commonStatus CDAO::sleep() {
  status(ST_AVAILABLE);
  pthread_cond_wait(&queueCond, &queueLock);
  UNLOCK_MUTEX_D(&queueLock);
  return status();
}

t_commonStatus CDAO::stop() {
  char *msg = new char[strlen("Shutting down async io thread\n") + 1];
  strcpy(msg, "Shutting down async io thread\n");
  addMessage(msg);
  LOCK_MUTEX_D(&queueLock);
  pthread_cond_signal(&queueCond);
  UNLOCK_MUTEX_D(&queueLock);
  status(ST_SHUTDOWN);
  return status();
}

t_commonStatus CDAO::workCycle() {
  unsigned int queueSize;
  int err;
  char *logString;
  if (status() != ST_AVAILABLE) {
    cerr << "Invalid status" << status() << endl;
    return status();
  };
  do {
    LOCK_MUTEX_D(&queueLock);
    queueSize = outputQueue.size();
    UNLOCK_MUTEX_D(&queueLock);
    if (queueSize > 0) {
      LOCK_MUTEX_D(&queueLock);
      logString = outputQueue.front();
      outputQueue.pop_front();
      UNLOCK_MUTEX_D(&queueLock);
      if (logString) {
        if (fwrite(logString, 1, strlen(logString), outputHandle) == 0) {
          err = errno;
          cerr << "Error writing to " << outputFilename << ", errno: " << err << endl;
        };
        delete[] logString;
        logString = NULL;
      };
      queueSize--;
    };
  } while (queueSize > 0);
  fflush(outputHandle);   
  return status();
}

int CDAO::addMessage(char *newMessage) {
  unsigned int qSize;
  if (!newMessage || (status() != ST_AVAILABLE)) {
    return 0;
  };
  LOCK_MUTEX_D(&queueLock);
  outputQueue.push_back(newMessage);
  qSize = outputQueue.size();
  UNLOCK_MUTEX_D(&queueLock);
  if (qSize >= MAX_AO_QUEUE_LENGTH) {
    pthread_cond_signal(&queueCond);
  };
  return 1;
}
