#ifndef CDCOLLECTION_H
#define CDCOLLECTION_H

/**
@author Yaroslav Rastrigin
*/
class CDCollection : public CDLock {
public:
  CDCollection();
  CDCollection(int newType);
  ~CDCollection();
  CDObject  *find(unsigned int objectId, bool touch = false);
  int        type(int newType = -1) { if (newType != -1) { objectsType = newType; }; return objectsType; };
  CDObject  *add(unsigned int objectId);
  int        del(unsigned int objectId, bool deleteObject = false);
  CDObject  *del(CDObject *object, bool deleteObject = false);
  int        autoinc(int newCnt = -1) { if (newCnt != -1) { autoInc = (unsigned int)newCnt; } ; return autoInc ; };
  int        dump(bool lock = false);
  const unsigned int size() { return objects.size(); };
private:
  t_cdObjMap objects;
  int objectsType;
  int autoInc;
};

#endif
