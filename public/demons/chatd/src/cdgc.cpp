#include <chatd.h>

CDGC::CDGC()
:lastRound(0),roundTime(0) {
  pthread_mutex_init(&masterLock, NULL);
  addGCQueue();
}


CDGC::~CDGC() {
  t_cdGCMasterPtr htcdGCMP1;
  LOCK_MUTEX(&masterLock);
  for (htcdGCMP1 = masterList.begin(); htcdGCMP1 != masterList.end(); ++htcdGCMP1) {
    UNLOCK_MUTEX(&((*htcdGCMP1)->listLock));
    pthread_mutex_destroy(&((*htcdGCMP1)->listLock));
    (*htcdGCMP1)->objList.erase((*htcdGCMP1)->objList.begin(), (*htcdGCMP1)->objList.end());
    delete *htcdGCMP1;
  };
  masterList.erase(masterList.begin(), masterList.end());
  UNLOCK_MUTEX(&masterLock);
  pthread_mutex_destroy(&masterLock);
}

t_cdGCQueue *CDGC::addGCQueue(bool lockMaster) {
  t_cdGCQueue *htcdGCQ1;
  if (lockMaster) {
    LOCK_MUTEX(&masterLock);
  };
  htcdGCQ1 = new t_cdGCQueue;
  if (htcdGCQ1) {
    pthread_mutex_init(&(htcdGCQ1->listLock), NULL);
    masterList.push_back(htcdGCQ1);
  };
  if (lockMaster) {
    UNLOCK_MUTEX(&masterLock);
  };
  return htcdGCQ1;
}

int CDGC::delGCQueue(t_cdGCMaster::iterator quePtr, bool lockMaster) {
  if (lockMaster) {
    LOCK_MUTEX(&masterLock);
  };
  masterList.erase(quePtr);
  if (lockMaster) {
    UNLOCK_MUTEX(&masterLock);
  };
  return true;
}

int CDGC::addObject(CDObject *newObject) {
  t_cdGCQueue *htcdGCQ1;
  if (!newObject) {
    debug << "CDGC::addObject() - NULL object given" << endo;
    return false;
  };
  LOCK_MUTEX(&masterLock);
  if (masterList.size() == 0) {
    htcdGCQ1 = addGCQueue(false);
    if (!htcdGCQ1) {
      UNLOCK_MUTEX(&masterLock);
      return 0;
    };
  };
  htcdGCQ1 = masterList.back();
  LOCK_MUTEX(&(htcdGCQ1->listLock));
  if (htcdGCQ1->objList.size() <= DEFAULT_GCQ_SIZE) {
    UNLOCK_MUTEX(&(masterLock)); //Safe to drop now, we're working with queue only.
  } else { // This queue is full, we need to create a new one
    UNLOCK_MUTEX(&(htcdGCQ1->listLock));
    htcdGCQ1 = addGCQueue(false);
    if (!htcdGCQ1) {
      return 0;
    };
    LOCK_MUTEX(&(htcdGCQ1->listLock));
    UNLOCK_MUTEX(&masterLock);
  };
  htcdGCQ1->objList.push_back(newObject);
  UNLOCK_MUTEX(&(htcdGCQ1->listLock));
  return 1;
}

int CDGC::delObject(t_cdGCObjectListPtr ptr, bool lockMaster) {
  return true;
}

t_cdGCMasterPtr CDGC::cleanUp(t_cdGCMasterPtr queue) {
  t_cdGCObjectListPtr htcdGCOLP1, htcdGCOLP2;
  t_cdGCQueue *htcdGCQ1 = NULL, *htcdGCQ2 = NULL;
  t_cdGCObjectList htcdGCOL1;
  CDObject *hCDO1 = NULL;
  CDCollection *hCDC1 = NULL;
  time_t ht1;
  t_cdGCMasterPtr htcdGCMP1;
  unsigned int hi1;
  
  htcdGCQ1 = *queue;
  ht1 = time(NULL);
  LOCK_MUTEX(&(htcdGCQ1->listLock));

  if (htcdGCQ1->objList.size() > 0) {
    htcdGCOLP1 = htcdGCQ1->objList.begin();
    while (htcdGCOLP1 != htcdGCQ1->objList.end()) {
      htcdGCOLP2 = htcdGCOLP1;
      htcdGCOLP1++;
      hCDO1 = *htcdGCOLP2;
      if (ht1 - hCDO1->last() > g_objectDefs[hCDO1->type()].idleTime) {
        hCDC1 = hCDO1->parent();
        if (hCDC1) {
          /*
          debug << "Garbage collector: cleaning up object, type: " << hCDO1->type();
          debug << ", id:" << hCDO1->id() << ", last access time: " << hCDO1->last();
          debug << ", now: " << (int)ht1 << ", diff: " << ht1 - hCDO1->last();
          debug << ", idleTime:" <<  g_objectDefs[hCDO1->type()].idleTime << endo;
          */
          hCDC1->del(hCDO1);
        };
        htcdGCQ1->objList.erase(htcdGCOLP2);
        htcdGCOL1.push_back(hCDO1);
      };
    };
    for (hi1 = 1; hi1 <= OBJTYPE_LAST; hi1++) {
      hCDC1 = g_storageMgr.find(hi1);
    };
    if (false && (htcdGCQ1->objList.size() < (DEFAULT_GCQ_SIZE / 2))) {
      /* Try to rebalance. Disabled temporarily */
      LOCK_MUTEX(&masterLock);
      htcdGCQ2 = *(--masterList.end());
      if (htcdGCQ1 != htcdGCQ2) {
        LOCK_MUTEX(&(htcdGCQ2->listLock));
        /* OMG,  holding three mutexes. It will deadlock somehow ... */
        if ((DEFAULT_GCQ_SIZE - htcdGCQ2->objList.size()) > htcdGCQ1->objList.size()) {
          htcdGCQ2->objList.splice(htcdGCQ2->objList.end(), htcdGCQ1->objList);
          htcdGCQ1->objList.clear();
        };
        UNLOCK_MUTEX(&(htcdGCQ2->listLock));
      };
      UNLOCK_MUTEX(&masterLock);
    };
    UNLOCK_MUTEX(&(htcdGCQ1->listLock));
    LOCK_MUTEX(&masterLock);
    queue++;
    UNLOCK_MUTEX(&masterLock);
    if (htcdGCOL1.size() > 0) {
      for (htcdGCOLP1 = htcdGCOL1.begin(); htcdGCOLP1 != htcdGCOL1.end(); ++htcdGCOLP1) {
        hCDO1 = *htcdGCOLP1;
        hCDO1->dump();
        /* Hopefully, noone in his sane mind will use this object, so lock isn't necessary */
        /* Race condition is still possible, though */
        hCDO1->cleanUp(false);
        delete hCDO1;
      };
      htcdGCOL1.clear();
    };
  } else {
    LOCK_MUTEX(&masterLock);
    htcdGCMP1 = queue;
    htcdGCMP1++;
    UNLOCK_MUTEX(&(htcdGCQ1->listLock));
    pthread_mutex_destroy(&(htcdGCQ1->listLock));
    masterList.erase(queue);
    delete htcdGCQ1;
    queue = htcdGCMP1;
    UNLOCK_MUTEX(&masterLock);
  };
  return queue;
}

int CDGC::workCycle() {
  t_cdGCMasterPtr htcdGCMP1;
  htcdGCMP1 = masterList.begin();
  do {
    if (htcdGCMP1 != masterList.end()) {
      htcdGCMP1 = cleanUp(htcdGCMP1);
    };
    if (htcdGCMP1 == masterList.end()) {
      htcdGCMP1 = masterList.begin();
      lastRound = time(NULL);
      freeWorkers();
      usleep(500000);
    };
  } while (!g_cdShutDown);
  return 0;
}

int CDGC::addWorker(CDWorker *oldWorker) {
  LOCK_MUTEX(&masterLock);
  oldWorkerList.push_back(oldWorker);
  UNLOCK_MUTEX(&masterLock);
  return 0;
}

int CDGC::freeWorkers() {
  t_cdWLIter elem;
  unsigned int listSize;
  
  LOCK_MUTEX(&masterLock);
  listSize = oldWorkerList.size();
  UNLOCK_MUTEX(&masterLock);
  if (listSize == 0) {
    return 0;
  };
  do {
    LOCK_MUTEX(&masterLock);
    listSize = oldWorkerList.size();
    if (listSize > 0) {
      elem = oldWorkerList.begin();
      delete *elem;
      oldWorkerList.pop_front();
      listSize--;
      if (listSize == 0) {
        oldWorkerList.clear();
      };
    };
    UNLOCK_MUTEX(&masterLock);    
  } while (listSize > 0);
  return 1;
}
