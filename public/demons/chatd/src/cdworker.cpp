#include <stdio.h>
#include <string.h>

#include <chatd.h>

CDStorageMgr g_storageMgr;
//last command getter-setter
time_t  CDWorker::last(time_t lastCmd) {
  if (lastCmd != -1) {
    lastCommand = lastCmd;
  };
  return lastCommand;
}

//worker socket getter-setter
int CDWorker::sock(int newSocket) {
  if (newSocket != -1) {
    if (sock()) {
      shutdown(sock(), SHUT_RDWR);
      close(sock());
    };
    listenSocket = newSocket;
  };
  return listenSocket;
}

//worker status getter-setter
int CDWorker::status(int newSt) {
  if (newSt != ST_INVALID) {
    workerStatus = newSt;
  };
  return workerStatus;
}

//worker thread id getter-setter
pthread_t CDWorker::tid(pthread_t newTid) {
  if (newTid != 0) {
    workerTId = newTid;
  };
  return workerTId;
}

//Resets read buffer
int CDWorker::flushReadBuffer() {
  readPacketSize = readBufferPosition = alreadyRead = 0;
  return 0;
}

CDWorker::CDWorker():listenSocket(0),workerTId(0) {
  debug << "CDWorker::CDWorker()" << endo;
  pthread_mutex_init(&statusMutex, NULL);
  last(time(NULL));
  status(ST_DISCONNECTED);
  memset(o.writeBuffer, 0, sizeof(DEFAULT_WRITE_BUFFER_SIZE));
  writeBufferPtr = 0;
  memset(readBuffer, 0, sizeof(DEFAULT_READ_BUFFER_SIZE));
  readPacketSize = readBufferPosition = alreadyRead = 0;
  pthread_mutex_init(&workerSleepMutex, NULL);
  pthread_cond_init(&workerSleepCond, NULL);
}


CDWorker::CDWorker(int newSock):listenSocket(0),workerTId(0) {
  debug << "CDWorker::CDWorker(" << newSock << ")" << endo;
  pthread_mutex_init(&statusMutex, NULL);

  last(time(NULL));
  sock(newSock);
  status(newSock ? ST_WAITING : ST_DISCONNECTED);
  memset(o.writeBuffer, 0, sizeof(DEFAULT_WRITE_BUFFER_SIZE));
  writeBufferPtr = 0;
  memset(readBuffer, 0, sizeof(DEFAULT_READ_BUFFER_SIZE));
  readPacketSize = readBufferPosition = alreadyRead = 0;
  pthread_mutex_init(&workerSleepMutex, NULL);
  pthread_cond_init(&workerSleepCond, NULL);
}

CDWorker::~CDWorker() {
  debug << "CDWorker::~CDWorker()" << endo;
  if (sock()) {
    debug << "Freeing socket " << sock() << endo;
    shutdown(sock(), SHUT_RDWR);
    close(sock());
  };
  UNLOCK_MUTEX_D(&workerSleepMutex);
  pthread_mutex_destroy(&workerSleepMutex);
  pthread_cond_destroy(&workerSleepCond);
  pthread_mutex_destroy(&statusMutex);
}

int CDWorker::start() {
  debug << "CDWorker::start()" << endo;
  do {
    workCycle();
    if (!g_cdShutDown) {
      this->sleep();
    } else {
      this->stop();
    };
  } while (status() != ST_DISCONNECTED);
  return 1;
}

int CDWorker::restart(unsigned int newSock) {
  int ret = 0;
  debug << "CDWorker::restart(" << newSock << ")" << endo;
  LOCK_MUTEX_D(&workerSleepMutex);
  if (status() == ST_AVAILABLE) {
    sock(newSock);
    status(ST_WAITING);
    pthread_cond_signal(&workerSleepCond);
    ret = 1;
  };
  UNLOCK_MUTEX_D(&workerSleepMutex);
  return ret;
}

int CDWorker::sleep() {
  int hi1, hi2;
  struct timeval htv1;
  struct timespec htv2;
  LOCK_MUTEX_D(&workerSleepMutex);
  status(ST_AVAILABLE);
  hi1 = 0;
  do {
    gettimeofday(&htv1, NULL);
    htv2.tv_sec = htv1.tv_sec + 3;
    htv2.tv_nsec = 0;
    do {
      hi2 = pthread_cond_timedwait(&workerSleepCond, &workerSleepMutex, &htv2);
    } while (hi2 == EINTR);
    if (hi2 == ETIMEDOUT) {
      hi1++;
    };
  } while ((hi1 < 5) && (hi2 == ETIMEDOUT));
  if (hi2 == ETIMEDOUT) {
    debug << "Final timeout, going to shut down this worker" << endo;
    status(ST_DISCONNECTED);
  };
  UNLOCK_MUTEX_D(&workerSleepMutex);
  return 1;
}

int CDWorker::stop() {
  debug << "CDWorker::stop()" << endo;
  LOCK_MUTEX_D(&workerSleepMutex);
  if (sock()) {
    debug << "Freeing socket " << sock() << endo;
    shutdown(sock(), SHUT_RDWR);
    close(sock());
    sock(0);
  };
  status(ST_DISCONNECTED);
  pthread_cond_signal(&workerSleepCond);
  UNLOCK_MUTEX_D(&workerSleepMutex);
  return 1;
}

ssize_t CDWorker::safe_read(size_t size, void *buf) {
  fd_set hfd1;
  struct timeval htv1;
  int hi1;
  char *hc1 = NULL;
  char *hc2 = NULL;
  if (!sock() || !buf || (size <= 0) || (size > DEFAULT_READ_BUFFER_SIZE)) {
    debug << "CDWorker::safe_read() - Invalid arguments : sock()=" << sock() <<", size = "<< (unsigned int)size << endo;
    return 0;
  };
  hc1 = (char *)buf;
  do {
    htv1.tv_sec = DEFAULT_IO_TIMEOUT;
    htv1.tv_usec = 0;
    FD_ZERO(&hfd1);
    FD_SET(sock(), &hfd1);
    do {
      hi1 = select(sock() + 1, &hfd1, NULL, NULL, &htv1);
    } while ((hi1 == -1) && (errno == EINTR));
    if (hi1 <= 0) {
      hc2 = "select() error ";
      break;
    };
    do {
      hi1 = read(sock(), hc1, size);
    } while ((hi1 == -1) && (errno == EINTR));
    if (hi1 < 0) { 
      hc2 = "read() error ";
      break;
    };
    hc1+= hi1;
    size-= hi1;
  } while ((size > 0) && (hi1 > 0));
  if (hi1 < 0) {
    hc2 = hc2 ? hc2 : (char *)"Unknown error, ";
    debug << "Socket " << sock() << " " << hc2 << errno << strerror(errno) << endo;
  };
  return size;
}

ssize_t CDWorker::buffered_read(size_t size, void *buf) {
  unsigned int hi1, hi2;
  char hc1[16];
  
  memset(hc1, 0, sizeof(hc1));
  if (!buf || !size) {
    debug << "CDWorker::buffered_read() - Invalid arguments" << endo;
    return 0;
  };
  if (readPacketSize == 0) {
    readBufferPosition = 0;
    alreadyRead = 0;
    memset(readBuffer, 0, DEFAULT_READ_BUFFER_SIZE);
    hi1 = safe_read(8, hc1);
    if (hi1 != 0) {
      return -1;
    };
    getLong(hc1, 8, (int *)(&readPacketSize));
    hi1 = min(readPacketSize, DEFAULT_READ_BUFFER_SIZE);
    hi2 = safe_read(hi1, readBuffer);
    if (hi2 != 0) {
      error << "Can't prefill read buffer " << endo;
      return -1;
    };
  };
  if (size > (readPacketSize - alreadyRead)) {
    return size;
  };
  do {
    hi1 = min(size, DEFAULT_READ_BUFFER_SIZE - readBufferPosition);
    memcpy(buf, readBuffer + readBufferPosition, hi1);
    size -= hi1;
    buf = (char *)buf + hi1;
    alreadyRead += hi1;
    readBufferPosition += hi1;
    if (readBufferPosition == DEFAULT_READ_BUFFER_SIZE) {
      readBufferPosition = 0;
      hi1 = readPacketSize - alreadyRead;
      memset(readBuffer, 0, DEFAULT_READ_BUFFER_SIZE);
      if (hi1 > 0) {
        hi2 = safe_read(min(hi1, DEFAULT_READ_BUFFER_SIZE), readBuffer);
        if (hi2 != 0) {
          error << "Can't read another part of the packet" << endo;
          break;
        };
      } else {
        readPacketSize = 0;
        alreadyRead = 0;
      };
    };
  } while (size > 0);
  return size;
}

ssize_t CDWorker::safe_write(size_t size, void *buf) {
  fd_set hfd1;
  struct timeval htv1;
  int hi1;
  char *hc1 = NULL, *hc2 = NULL;
  if (!sock() || !buf) {
    debug << "CDWorker::safe_write() - Invalid arguments" << endo;
    return 0;
  };
  if (size == 0) {
    /* Let's shorten the path */
    return 0;
  };
  hc1 = (char *)buf;
  do {
    htv1.tv_sec = DEFAULT_IO_TIMEOUT;
    htv1.tv_usec = 0;
    FD_ZERO(&hfd1);
    FD_SET(sock(), &hfd1);
    do {
      hi1 = select(sock() + 1, NULL, &hfd1, NULL, &htv1);
    } while ((hi1 == -1) && (errno == EINTR));
    if (hi1 <= 0) { 
      hc2 = hi1 < 0 ? (char *)"select() error " : (char *)"select() timeout, write would block";
      break;
    };
    if (hi1 > 0) {
      do {
        hi1 = write(sock(), hc1, size);
      } while ((hi1 == -1) && (errno == EINTR));
      if (hi1 < 0) { 
        hc2 = "write() error ";
        break;
      };
      hc1+= hi1;
      size-= hi1;
    };
  } while ((size > 0) && (hi1 > 0));
  if (hi1 <= 0) {
    hc2 = hc2 ? hc2 : (char *)"Unknown error, ";
    debug << "CDWorker::safe_write() - " << hc2 << " " << errno << "(" << strerror(errno) << ")" << endo;
  };
  return size;
}

ssize_t CDWorker::buffered_write(size_t size, void *buf, bool flush) {
  char hc1[10], *hc2;
  unsigned int hi1;
  if ((size == 0) && !flush) {
    return 0;
  };
  hc2 = (char *)buf;
  do {
    hi1 = min(size, DEFAULT_WRITE_BUFFER_SIZE - writeBufferPtr);
    if (hi1 > 0) {
      memcpy((char *)(o.writeBuffer + writeBufferPtr), hc2, hi1);
      hc2 += hi1;
      writeBufferPtr += hi1;
      size -= hi1;
    };
    if (flush || (writeBufferPtr == DEFAULT_WRITE_BUFFER_SIZE)) {
      sprintf(hc1, "%08X", writeBufferPtr);
      memcpy(o.writePacketSize, hc1, sizeof(o.writePacketSize));
      if (safe_write(writeBufferPtr + sizeof(o.writePacketSize), &o) > 0) {
        memset(o.writeBuffer, 0, sizeof(o.writeBuffer));
        writeBufferPtr = 0;
        break;
      };
      memset(o.writeBuffer, 0, sizeof(o.writeBuffer));
      writeBufferPtr = 0;
    };
  } while (size > 0);
  return size;
}

int CDWorker::workCycle() {
  t_cdCommandBuffer htCB1;
  t_cdCommand htC1;
  t_cdValue *htcV1;
  t_cdValueBuffer htVB1;
  t_cdArgList hvtCV1;
  t_cdArgList::iterator hvtCVi1;
  
  if (status() != ST_WAITING) {
    error << "CDWorker::workCycle() - invalid thread status" << endo;
    return 0;
  };
  memset(&htCB1, 0, sizeof(htCB1));
  memset(&htVB1, 0, sizeof(htVB1));
  status(ST_BUSY);
  do {
    flushReadBuffer();
    if (!getCommand(&htC1)) {
      break;
    };
    last(time(NULL));
    while (hvtCV1.size() != htC1.argCount) {
      htcV1 = getNextArg();
      if (htcV1 == NULL) {
        break;
      };
      hvtCV1.push_back(htcV1);
    };
    if (hvtCV1.size() != htC1.argCount) {
      debug << "workCycle() - wrong number of arguments (" << (unsigned int)hvtCV1.size() << " vs. " << htC1.argCount << "), going to sleep " << endo;
      break;
    };
    if (!processCmd(&htC1, &hvtCV1)) {
      if (htC1.commandId != CMD_SLEEP) {
        debug << "workCycle() - command processing failed, going to sleep " << endo;
      };
      break;
    };
    cleanArgs(&hvtCV1);
  } while (true);
  cleanArgs(&hvtCV1);
  status(ST_WAITING);
  return 0;
}

int CDWorker::getCommand(t_cdCommand *cmdPtr) {
  t_cdCommandBuffer htCB1;
  int hi1;
  if ((status() != ST_BUSY) || !cmdPtr) {
    debug << "getCommand() - invalid arguments "<<endo;
    return 0;
  };
  memset(&htCB1, 0, sizeof(htCB1));
  hi1 = buffered_read(sizeof(htCB1), &htCB1);
  if (hi1 > 0) { 
    error << "Needed " << (unsigned int)sizeof(htCB1) << " bytes, received " << (unsigned int)(sizeof(htCB1) - hi1) << " bytes " << endo;
    return 0;
  };
  if (!getLong(htCB1.sequenceNum, sizeof(htCB1.sequenceNum), (int *)(&(cmdPtr->sequenceNum))) ||
      !getShort(htCB1.commandId,  sizeof(htCB1.commandId), (short int *)(&(cmdPtr->commandId)))   ||
      !getLong(htCB1.objectType,  sizeof(htCB1.objectType), (int *)(&(cmdPtr->objectType)))  ||
      !getLong(htCB1.objectId,    sizeof(htCB1.objectId), (int *)(&(cmdPtr->objectId)))    ||
      !getShort(htCB1.argCount,   sizeof(htCB1.argCount), (short int *)(&(cmdPtr->argCount)))
     ) {
    return 0;
  };
  return 1;
}

t_cdValue *CDWorker::getNextArg() {
  t_cdValue *htcV1 = NULL;
  t_cdValueBuffer htcVB1;
  int hi1;
  bool hb1;
  char *hc1 = NULL;
  float htf1;
  
  memset(&htcVB1, 0, sizeof(htcVB1));
  if ((status() != ST_BUSY) || !sock()) {
    cerr << "CDWorker::getNextArg() - invalid arguments " << status() << ":" << sock() << endo;
    return NULL;
  };
  hi1 = buffered_read(sizeof(htcVB1), &htcVB1);
  if (hi1 > 0) {
    error << "Needed " << (unsigned int)sizeof(htcVB1) << " bytes, received " << (unsigned int)(sizeof(htcVB1) - hi1) << " bytes " << endo;
    return NULL;
  };
  htcV1 = (t_cdValue *)malloc(sizeof(t_cdValue));
  memset(htcV1, 0, sizeof(t_cdValue));
  if (!getLong(htcVB1.argId, sizeof(htcVB1.argId), (int *)(&(htcV1->argId))) ||
      !getShort(htcVB1.argType, sizeof(htcVB1.argType), (short int *)(&(htcV1->argType))) ||
      !getLong(htcVB1.argLength, sizeof(htcVB1.argLength), (int *)(&(htcV1->argLength)))) {
    free(htcV1);
    return NULL;
  };
  hi1 = 0;
  hc1 = NULL;
  if ((int)(htcV1->argLength) > 0) {
    hc1 = (char *)malloc(htcV1->argLength);
    if (!hc1) {
      error << "Can't allocate " << htcV1->argLength << " bytes, exiting" << endo;
      free(htcV1);
    };
    memset(hc1, 0, htcV1->argLength);
    hi1 = buffered_read(htcV1->argLength, hc1);
  };
  if (hi1 > 0) {
    error << "Needed " << htcV1->argLength << "bytes, received " << htcV1->argLength - hi1 << " bytes " << endo;
    free(hc1);
    free(htcV1);
    return NULL;
  };
  hi1 = 0;
  hb1 = false;
  switch (htcV1->argType) {
    case
        AT_BOOL:
      if (getShort(hc1, htcV1->argLength, (short *)&hi1)) {
        hb1 = true;
        htcV1->arg.bVal = hi1 != 0;
      };
      break;
    case
        AT_INT:
    case
        AT_INVALID:
      if (!hc1 || getLong(hc1, htcV1->argLength, (int *)&hi1)) {
        hb1 = true;
        htcV1->arg.iVal = hi1;
      };
      break;
    case
        AT_FLOAT:
      if (getFloat(hc1, htcV1->argLength, &htf1)) {
        hb1 = true;
        htcV1->arg.fVal = htf1;
      };
      break;
    case
        AT_STRING:
    case
        AT_OBJECT:
      htcV1->arg.cVal = (char *)malloc(htcV1->argLength + 1);
      if (htcV1->arg.cVal) {
        memset(htcV1->arg.cVal, 0, htcV1->argLength + 1);
        memcpy(htcV1->arg.cVal, hc1, htcV1->argLength);
        hb1 = true;
      };
      break;
    default
        :
      cerr << "Unknown argument type " << htcV1->argType << endl;
      break;
  };
  if (hc1) {
    free(hc1);
  };
  if (!hb1) {
    free(htcV1);
    htcV1 = NULL;
  };
  return htcV1;
}


int CDWorker::cleanArgs(t_cdArgList *argV) {
  t_cdArgList::iterator htcALi1;
  t_cdValue *htcV1;
  if (!argV) {
    cerr << "CDWorker::cleanArgs() - invalid arguments" << endl;
    return 0;
  };
  for (htcALi1 = argV->begin(); htcALi1 != argV->end(); ++htcALi1) {
    htcV1 = *htcALi1;
    if (htcV1) {
      clearValue(htcV1);
      free(htcV1);
    };
  };
  argV->clear();
  return 1;
}

int CDWorker::processCmd(t_cdCommand *command, t_cdArgList *argV) {
  CDCollection *hCDS1 = NULL;
  CDObject *hCDO1 = NULL;
  t_cdArgList::iterator htcALi1;
  t_cdArgList htcAL1;
  t_cdValue *htcV1 = NULL, *htcV2 = NULL;
  int hi1;
  t_objDef htoD1;

  if ((status() != ST_BUSY) || (!command || !argV)) {
    debug << "CDWorker::processCmd() - invalid arguments: status()=" << status() << ", command=" << command << ", argV: " << argV << endo;
    return 0;
  };
  switch (command->commandId) {
    case
        CMD_NOOP:
      return 1;
      break;
    case
        CMD_GETOBJLIST:
    case
        CMD_DUMPALL:
    case
        CMD_SHUTDOWN:
    case
        CMD_SETOBJTPROPS:
      break;
    case
        CMD_CREATEOBJ:
    case
        CMD_DESTROYOBJ:
    case
        CMD_GETOBJPROP:
    case
        CMD_SETOBJPROP:
    case
        CMD_UNSETOBJPROP:
    case
        CMD_GETOBJLINK:
    case
        CMD_ADDOBJLINK:
    case
        CMD_DELOBJLINK:
    case
        CMD_DUMPOBJ:
    case
        CMD_CHECKOBJ:
    case
        CMD_UPLOADOBJ:
    case
        CMD_SETAUTOINC:
      hCDS1 = g_storageMgr.find(command->objectType); /* Read lock */
      break;
    case
        CMD_DEBUGLEVEL:

    case
        CMD_INVALID:
    case
        CMD_SLEEP:
    default
        :
      return 0;
      break;
  };
  switch (command->commandId) {
    case
        CMD_SETAUTOINC:
      htcV1 = (t_cdValue *)malloc(sizeof(t_cdValue));
      htcV1->argId = 0;
      htcV1->argType = AT_INVALID;
      htcV1->argLength = 0;
      htcV1->arg.iVal = 0;
      if (!hCDS1) {
        hCDS1 = g_storageMgr.add(command->objectType); /* Write lock */
      };
      if (hCDS1) {
        hCDS1->autoinc(command->objectId);
        htcV1->argType = AT_INT;
        htcV1->arg.iVal = command->objectId;
      };
      htcAL1.push_back(htcV1);
      break;
    case
        CMD_CREATEOBJ:
    case
        CMD_CHECKOBJ:
      htcV1 = (t_cdValue *)malloc(sizeof(t_cdValue));
      htcV1->argId = 0;
      htcV1->argType = AT_INVALID;
      htcV1->argLength = 0;
      htcV1->arg.iVal = 0;
      if (!hCDS1) {
        hCDS1 = g_storageMgr.add(command->objectType); /* Write lock */
      };
      if (hCDS1) {
        hCDO1 = command->commandId == CMD_CREATEOBJ ?
                hCDS1->add
                (command->objectId) :       /* Write lock */
                hCDS1->find(command->objectId, true); /* Read lock */
        htcV1->argType = hCDO1 ? AT_INT : AT_INVALID;
        htcV1->arg.iVal = hCDO1 ? hCDO1->id() : 0;
      };
      htcAL1.push_back(htcV1);
      break;
    case
        CMD_DESTROYOBJ:
      htcV1 = (t_cdValue *)malloc(sizeof(t_cdValue));
      htcV1->argId = 0;
      htcV1->argType = AT_INVALID;
      htcV1->argLength = 0;
      htcV1->arg.iVal = 0;
      if (hCDS1) {
        if (hCDS1->del(command->objectId)) {
          htcV1->argType = AT_INT;
          htcV1->arg.iVal = command->objectId;
        };
      };
      htcAL1.push_back(htcV1);
      break;
    case
        CMD_GETOBJPROP:
      if (hCDS1) {
        hCDO1 = hCDS1->find(command->objectId); /* Read lock */
        if (hCDO1) {
          for (htcALi1 = argV->begin(); htcALi1 != argV->end(); htcALi1++) {
            htcV1 = (t_cdValue *)malloc(sizeof(t_cdValue));
            htcV1->argId = (*htcALi1)->argId;
            htcV1->argLength = 0;
            hCDO1->start_read(); /* Read lock */
            htcV1->argType = copyValue(htcV1, hCDO1->getProp((*htcALi1)->argId, false)) ?
                             htcV1->argType : AT_INVALID ;
            hCDO1->finish_read();
            htcAL1.push_back(htcV1);
          };
        };
      };
      break;
    case
        CMD_SETOBJPROP:
    case
        CMD_UNSETOBJPROP:
      if (hCDS1) {
        hCDO1 = hCDS1->find(command->objectId); /* Read lock */
        if (hCDO1) {
          hCDO1->start_write(); /* Write lock. Locking until all specified props are stored, to emulate atomic UPDATE */
          for (htcALi1 = argV->begin(); htcALi1 != argV->end(); htcALi1++) {
            htcV1 = (t_cdValue *)malloc(sizeof(t_cdValue));
            htcV1->argId = 0;
            htcV1->argType = hCDO1->setProp((*htcALi1)->argId,
                                            command->commandId == CMD_SETOBJPROP ? *htcALi1 : NULL)
                             ? AT_INT : AT_INVALID;
            htcV1->arg.iVal = htcV1->argType == AT_INT ? 1 : 0;
            htcV1->argId = (*htcALi1)->argId; /* propId */
            htcV1->argLength = 0;
            htcAL1.push_back(htcV1);
          };
          hCDO1->finish_write();
        };
      };
      break;
    case
        CMD_GETOBJLINK:
      if (hCDS1) {
        hCDO1 = hCDS1->find(command->objectId); /* Read lock */
        if (hCDO1) {
          for (htcALi1 = argV->begin(); htcALi1 != argV->end(); htcALi1++) {
            htcV1 = (t_cdValue *)malloc(sizeof(t_cdValue));
            htcV1->argId = (*htcALi1)->argId;
            hCDO1->start_read(); /* Read lock */
            htcV1->arg.pVal = hCDO1->getLink((*htcALi1)->argId, (*htcALi1)->arg.iVal, false);
            hCDO1->finish_read();
            htcV1->argType = AT_LINKLIST;
            htcV1->argLength = 0;
            htcAL1.push_back(htcV1);
          };
        };
      };
      break;
    case
        CMD_ADDOBJLINK:
    case
        CMD_DELOBJLINK:
      if (hCDS1) {
        hCDO1 = hCDS1->find(command->objectId); /* Read lock */
        if (hCDO1) {
          for (htcALi1 = argV->begin(); htcALi1 != argV->end(); htcALi1++) {
            htcV1 = (t_cdValue *)malloc(sizeof(t_cdValue));
            hCDO1->start_write(); /* Write lock */
            htcV1->argType = command->commandId == CMD_ADDOBJLINK ?
                             hCDO1->addLink((*htcALi1)->argId, (*htcALi1)->arg.iVal, false) :
                             hCDO1->delLink((*htcALi1)->argId, (*htcALi1)->arg.iVal, false) ;
            hCDO1->finish_write(); /* Write lock */
            htcV1->argType = htcV1->argType ? AT_INT : AT_INVALID;
            htcV1->argId = (*htcALi1)->argId;
            htcV1->argLength = 0;
            htcV1->arg.iVal = (*htcALi1)->arg.iVal;
            htcAL1.push_back(htcV1);
          };
        };
      };
      break;
    case
        CMD_UPLOADOBJ:
      if (!hCDS1) {
        hCDS1 = g_storageMgr.add(command->objectType); /* Write lock */
      };
      if (hCDS1) {
        bool bLinks = false;
        hCDO1 = command->objectId > 0 ? hCDS1->find(command->objectId) : NULL;
        if (!hCDO1) {
          hCDO1 = hCDS1->add
                  (command->objectId);
        };
        if (hCDO1) {
          hCDO1->start_write();
          for (htcALi1 = argV->begin(); htcALi1 != argV->end(); htcALi1++) {
            htcV2 = *htcALi1;
            if ((htcV2->argId == AT_INVALID) && (htcV2->arg.iVal == AT_INVALID)) {
              bLinks = true;
              hCDO1->delLink(-1, -1, false);
              continue;
            };
            if (!bLinks) {
              hCDO1->setProp(htcV2->argId, htcV2->argType == AT_INVALID ? NULL : htcV2, false);
            } else {
              hCDO1->addLink(htcV2->argId, htcV2->arg.iVal, false);
            };
          };
          hCDO1->finish_write();
        };
        htcV1 = (t_cdValue *)malloc(sizeof(t_cdValue));
        htcV1->argId = 0;
        htcV1->argType = hCDO1 ? AT_INT : AT_INVALID;
        htcV1->arg.iVal = hCDO1 ? hCDO1->id() : 0;
        htcV1->argLength = 0;
        htcAL1.push_back(htcV1);
      };
      break;
    case
        CMD_GETOBJLIST:
      hi1 = 0; /* Last processed objectType */
      hCDS1 = NULL;
      for (htcALi1 = argV->begin(); htcALi1 != argV->end(); htcALi1++) {
        htcV2 = *htcALi1;
        if (htcV2->argId != (unsigned int)hi1) {
          hCDS1 = g_storageMgr.find(htcV2->argId); /* Read lock */
          hi1 = hCDS1 ? hCDS1->type() : hi1;
        };
        if (hCDS1) {
          hCDO1 = hCDS1->find(htcV2->arg.iVal);
          if (hCDO1) {
            hCDO1->start_read();
            htcV1 = (t_cdValue *)malloc(sizeof(t_cdValue));
            htcV1->argLength = 0;
            htcV1->argType = copyValue(htcV1, hCDO1->getProp(OBJPROP_OBJECT, false)) ?
                             htcV1->argType : AT_INVALID ;
            htcV1->argId = hCDO1->id();
            htcAL1.push_back(htcV1);
            hCDO1->finish_read();
          };
        };
      };
      break;
    case
        CMD_DUMPOBJ:
      htcV1 = (t_cdValue *)malloc(sizeof(t_cdValue));
      if (htcV1) {
        htcV1->argType = AT_INVALID;
        htcV1->argId = 0;
        htcV1->arg.iVal = 0;
      };
      if (hCDS1) {
        hCDO1 = hCDS1->find(command->objectId);
        if (hCDO1) {
          hCDO1->start_read();
          hCDO1->dump(false);
          hCDO1->finish_read();
          if (htcV1) {
            htcV1->argLength = 0;
            htcV1->argType = AT_INT;
            htcV1->argId = hCDO1->type();
            htcV1->arg.iVal = hCDO1->id();
          };
        };
      };
      htcAL1.push_back(htcV1);
      break;
    case
        CMD_SHUTDOWN:
      g_cdShutDown = true;
    case
        CMD_DUMPALL:
      g_storageMgr.dump();
      break;
    case
        CMD_SETOBJTPROPS:
      htcV1 = (t_cdValue *)malloc(sizeof(t_cdValue));
      if (htcV1) {
        htcV1->argType = AT_INVALID;
        htcV1->argLength = 0;
        htcV1->arg.iVal = 0;
      };
      if ((argV->size() == 4) && (command->objectType < MAX_OBJTYPE) && (g_objectDefs[command->objectType].propAmount == 0)) {
        htcALi1 = argV->begin();
        htoD1.propAmount = (*htcALi1)->arg.iVal;
        htcALi1++;
        htoD1.idleTime = (*htcALi1)->arg.iVal;
        htcALi1++;
        htoD1.queueLength = (*htcALi1)->arg.iVal;
        htcALi1++;
        htoD1.deleteRecilinks = (*htcALi1)->arg.iVal;
        g_objectDefs[command->objectType] = htoD1;
        htcV1->argType = AT_INT;
        htcV1->arg.iVal = g_objectDefs[command->objectType].propAmount;
      };
      htcAL1.push_back(htcV1);
      break;
  };
  sendResults(command, &htcAL1);
  cleanArgs(&htcAL1);
  return 1;
}

int CDWorker::sendResults(t_cdCommand *cmd, t_cdArgList *ret) {
  t_cdCommandBuffer htcCB1;
  t_cdArgList::iterator htcALi1;
  t_cdValueBuffer htcVB1;
  char hc1[10], *hc2;
  int hi1;
  if (!cmd || !ret) {
    error << "Invalid arguments " << endo;
    return 0;
  };
  memset(&htcCB1, 0, sizeof(htcCB1));
  putLong(htcCB1.sequenceNum, cmd->sequenceNum);
  putShort(htcCB1.commandId, cmd->commandId);
  putLong(htcCB1.objectType, cmd->objectType);
  putLong(htcCB1.objectId, cmd->objectId);
  putShort(htcCB1.argCount, ret->size());

  if (buffered_write(sizeof(htcCB1), &htcCB1) > 0) {
    error << " Sending results - command write failed " << endo;
    return 0;
  };
  memset(&htcVB1, 0, sizeof(htcVB1));
  for (htcALi1 = ret->begin(); htcALi1 != ret->end(); ++htcALi1) {
    hc2 = expandValue(*htcALi1, &htcVB1, &hi1);
    if ((buffered_write(sizeof(htcVB1), &htcVB1) > 0) ||
        (buffered_write(hi1, hc2) > 0)) {
      error << " Sending results - result write failed " << endo;
      return 0;
    };
    free(hc2);
  };
  if (buffered_write(0, hc1, true) > 0) {
    error << " Sending results - buffer flush failed " << endo;
    return 0;
  };
  return 1;
}
