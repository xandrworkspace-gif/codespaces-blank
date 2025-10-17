#ifndef CDSTORAGEMGR_H
#define CDSTORAGEMGR_H

/**
@author Yaroslav Rastrigin
*/
class CDStorageMgr: public CDLock {
public:
  CDStorageMgr();
  ~CDStorageMgr();
  CDCollection *find(unsigned int objType);
  CDCollection *add(unsigned int objType);
  int           del(unsigned int objType);
  int           dump(unsigned int objType = (unsigned int) -1);
private:
  CDCollection *storageArray[MAX_OBJTYPE];
};

#endif
