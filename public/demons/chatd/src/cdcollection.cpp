#include <cdcommon.h>

CDCollection::CDCollection()
:autoInc(0) {
  type(0);
}

CDCollection::CDCollection(int newType)
:autoInc(0) {
  type(newType);
}

CDCollection::~CDCollection() {
  t_cdObjMapPtr htcOMP1;
  for (htcOMP1 = objects.begin(); htcOMP1 != objects.end(); ++htcOMP1) {
    if ((*htcOMP1).second) {
      ((*htcOMP1).second)->cleanUp(true);
      delete(*htcOMP1).second;
    }
  };
}

CDObject *CDCollection::find(unsigned int objectId, bool touch) {
  t_cdObjMapPtr htcOMP1;
  CDObject *hCD1;
  start_read();
  htcOMP1 = objects.find(objectId);
  hCD1 = htcOMP1 == objects.end() ? NULL : (*htcOMP1).second;
  finish_read();
  if (touch && hCD1) {
    hCD1->last(time(NULL));
  };
  return hCD1;
}


CDObject *CDCollection::add (unsigned int objectId) {
  pair<t_cdObjMapPtr, bool> hp1;
  CDObject *hCD1 = NULL;
  
  hCD1 = new CDObject(type(), objectId);
  if (hCD1) {
    start_write();
    hCD1->parent(this);
    if (objectId == 0) {
      objectId = autoinc() + 1;
      hCD1->id(objectId);
      autoinc(objectId);
    } else {
      autoinc(max(autoInc, objectId));
    };
    hp1 = objects.insert(make_pair(objectId, hCD1));
    if (hp1.second) {
      hCD1->pos(hp1.first);
    };
    finish_write();
    if (!hp1.second) {
      delete hCD1;
      hCD1 = NULL;
    } else
      if (g_cdGC) {
        g_cdGC->addObject(hCD1);
      };
    return hCD1;
  };
  return NULL;
}

int CDCollection::del(unsigned int objectId, bool deleteObject) {
  t_cdObjMapPtr htcOMP1;
  CDObject *htCDO1;
  start_write();
  htcOMP1 = objects.find(objectId);
  if (htcOMP1 == objects.end()) {
    finish_write();
    return 0;
  };
  htCDO1 = (*htcOMP1).second;
  objects.erase(htcOMP1);
  htCDO1->parent(NULL);
  if (deleteObject && htCDO1) {
    htCDO1->cleanUp(true);
    delete htCDO1;
  };
  finish_write();
  return 1;
}

CDObject *CDCollection::del(CDObject *object, bool deleteObject) {
  if (!object || (object->parent() != this)) {
    return NULL;
  };
  start_write();
  objects.erase(object->pos());
  finish_write();
  if (deleteObject) {
    object->cleanUp(true);
    delete object;
    return NULL;
  };
  return object;
}

int CDCollection::dump(bool lock) {
  t_cdObjMapPtr htcOMP1;
  start_write(); // We need to block everything exclusively
  for (htcOMP1 = objects.begin(); htcOMP1 != objects.end(); ++htcOMP1) {
    if (lock) {
      (*htcOMP1).second->start_read();
    };
    (*htcOMP1).second->dump();
    if (lock) {
      (*htcOMP1).second->finish_read();
    };
  };
  finish_write();
  return 1;
}
