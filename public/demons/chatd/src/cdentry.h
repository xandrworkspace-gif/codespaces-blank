#ifndef CDENTRY_H
#define CDENTRY_H

/**
Basic unit - single data entry
@author Yaroslav Rastrigin
*/
class CDObject: public CDLock {
public:
  CDObject();
  CDObject(int newType, int newId);
  ~CDObject();
  int           type(int newType = -1);
  int           id(int newId = -1) { if (newId != -1) { objectId = newId; } ; return objectId; };
  t_cdValue    *getProp(unsigned int propId, bool lock = true);
  int           setProp(unsigned int propId, t_cdValue *propValue, bool lock = true);
  int           addLink(unsigned int objType, unsigned int objectId, bool lock = true);
  int           delLink(int objType = -1, int objId = -1, bool lock = true);
  t_cdLinkList *getLink(int objType = -1, int objId = -1, bool lock = true);
  int           dump(bool lock = true);
  int           cleanUp(bool lock = true);
  time_t        last(time_t newSt = -1);
  void          parent(CDCollection *newParent) { parentCollection = newParent; };
  CDCollection *parent() { return parentCollection; };
  void          pos(t_cdObjMapPtr newPos) { parentListPosition = newPos; };
  t_cdObjMapPtr pos() { return parentListPosition; };
private:
  time_t      lastAccess;
  t_cdValue **propArray;
  t_cdLinkMap linkMap;
  int         objectType;
  int         objectId;
  CDCollection *parentCollection;
  t_cdObjMapPtr parentListPosition;
};
#define CHECK_PROPID(A, B) if (((unsigned int)(A)) >= g_objectDefs[type()].propAmount) { return B; };
#endif /* CDENTRY_H */
