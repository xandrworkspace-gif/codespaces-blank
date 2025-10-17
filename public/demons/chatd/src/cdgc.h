#ifndef CDGC_H
#define CDGC_H

/**
Garbage collector thread

@author Yaroslav Rastrigin
*/
#include <sys/time.h>
#include <pthread.h>
#include <list>

typedef list<CDObject *> t_cdGCObjectList;
typedef t_cdGCObjectList::iterator t_cdGCObjectListPtr;

typedef struct s_cdGCQueue {
  t_cdGCObjectList objList;
  pthread_mutex_t  listLock;
}
t_cdGCQueue;

typedef list<t_cdGCQueue *> t_cdGCMaster;
typedef t_cdGCMaster::iterator t_cdGCMasterPtr;

class CDGC: public CDLock {
public:
  CDGC();
  ~CDGC();
  t_cdGCMasterPtr cleanUp(t_cdGCMasterPtr queue);
  int addObject(CDObject *object);
  int delObject(t_cdGCObjectListPtr ptr, bool lockMaster = true);
  int addWorker(CDWorker *oldWorker);
  int workCycle();
  pthread_t tid(pthread_t newTid = 0) {
    if (newTid != 0) {
      gcTId = newTid;
    }
    ;
    return gcTId;
  };
private:
  int freeWorkers();
  t_cdGCQueue *addGCQueue(bool lockMaster = true);
  int delGCQueue(t_cdGCMaster::iterator quePtr, bool lockMaster = true);
  time_t lastRound;
  time_t roundTime;
  t_cdGCMaster    masterList;
  pthread_mutex_t masterLock;
  pthread_t gcTId;
  t_cdWL  oldWorkerList;
};

#endif
