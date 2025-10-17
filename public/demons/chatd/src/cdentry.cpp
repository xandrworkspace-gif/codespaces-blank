#include <cdcommon.h>
#include <cdobjdefs.h>
#include <string.h>
#include <stdio.h>
CDObject::CDObject()
:objectType(0) {
  propArray = NULL;
  id(0);
  type(0);
  lastAccess = time(NULL);
}

CDObject::CDObject(int newType, int newId)
:objectType(0) {
  propArray = NULL;
  id(newId);
  type(newType);
  lastAccess = time(NULL);
}

time_t CDObject::last(time_t newSt) {
  if (newSt != -1) {
    lastAccess = newSt;
  };
  return lastAccess;
}
int CDObject::type(int newType) {
  t_cdValue **newArray = NULL;
  unsigned int propType;
  if ((newType != -1) && (newType <= LAST_OBJDEF) && (newType != objectType)) {
    start_write();
    newArray = (t_cdValue **)malloc(sizeof(t_cdValue) * (g_objectDefs[newType].propAmount + 1));
    memset(newArray, 0, sizeof(t_cdValue *) *(g_objectDefs[newType].propAmount + 1));
    if (propArray != NULL) {
      for (propType = 0; propType < g_objectDefs[objectType].propAmount; propType++) {
        if (propArray[propType]) {
          clearValue(propArray[propType]);
          free(propArray[propType]);
        };
      };
      free(propArray);
      propArray = NULL;
    };
    propArray = newArray;
    objectType = newType;
    finish_write();
  };
  return objectType;
}

int CDObject::cleanUp(bool lock) {
  t_cdLinkMapIter linkMapIter;
  t_cdLinkListIter linkListIter;
  t_cdLinkList *linkList;
  CDCollection *coll;
  CDObject *obj;
  unsigned int propType;
  if (lock) {
    start_write();
  };
  if (propArray != NULL) {
    for (propType = 0; propType < g_objectDefs[type()].propAmount; propType++) {
      if (propArray[propType]) {
        clearValue(propArray[propType]);
        free(propArray[propType]);
      };
    };
    free(propArray);
    propArray = NULL;
  };
  for (linkMapIter = linkMap.begin(); linkMapIter != linkMap.end(); ++linkMapIter) {
    if ((*linkMapIter).second) {
      linkList = (*linkMapIter).second;
      if (linkList) {
        if (g_objectDefs[type()].deleteRecilinks != 0) {
          coll = g_storageMgr.find((*linkMapIter).first);
          if (coll) {
            for (linkListIter = linkList->begin(); linkListIter != linkList->end(); ++linkListIter) {
              obj = coll->find(*linkListIter, true);
              if (obj) {
                obj->delLink(type(), id());
              };
            };
          };
        } else {
          linkList->clear();
          delete linkList;
        };
      };
    };
  };
  linkMap.clear();
  if (lock) {
    finish_write();
  };
  return 1;
}

CDObject::~CDObject() {
  cleanUp(false);
}

t_cdValue *CDObject::getProp(unsigned int propId, bool lock) {
  t_cdValue *htcV1;
  CHECK_PROPID(propId, NULL);
  if (lock) {
    start_read();
  };
  htcV1 = propArray[propId];
  if (lock) {
    finish_read();
  };
  return htcV1;
}

int CDObject::setProp(unsigned int propId, t_cdValue *newValue, bool lock) {
  t_cdValue *htcV1 = NULL;
  CHECK_PROPID(propId, 0);
  if (!newValue) {
    if (lock) {
      start_write();
    };
    if (propArray[propId]) {
      clearValue(propArray[propId]);
      free(propArray[propId]);
      propArray[propId] = NULL;
    };
    if (lock) {
      finish_write();
    };
    return 1;
  };
  htcV1 = (t_cdValue *)malloc(sizeof(t_cdValue));
  if (htcV1) {
    copyValue(htcV1, newValue);
  };
  if (lock) {
    start_write();
  };
  if (propArray[propId]) {
    clearValue(propArray[propId]);
    free(propArray[propId]);
  };
  propArray[propId] = htcV1;
  if (lock) {
    finish_write();
  };
  return 1;
}

int CDObject::addLink(unsigned int objType, unsigned int objId, bool lock) {
  t_cdLinkMapIter mapIter;
  t_cdLinkList  *linkList;
  if ((objType < 1) || (objId < 1)) {
    return 0;
  };
  if (lock) {
    start_write();
  };
  mapIter = linkMap.find(objType);
  if (mapIter == linkMap.end()) {
    linkList = new t_cdLinkList;
    linkMap.insert(make_pair(objType, linkList));
  } else {
    linkList = (*mapIter).second;
  };
  while (linkList->size() >= g_objectDefs[objType].queueLength) {
    linkList->pop_front();
  };
  linkList->push_back(objId);
  if (lock) {
    finish_write();
  };
  return 1;
}

int CDObject::delLink(int objType, int objId, bool lock) {
  t_cdLinkMapIter mapIter;
  t_cdLinkList   *linkList;
  t_cdLinkListIter listIter;
  if ((objType == -1) && (objId != -1)) {
    return 0;
  };
  if (lock) {
    start_write();
  };
  if (objType == -1) {
    for (mapIter = linkMap.begin(); mapIter != linkMap.end(); ++mapIter) {
      if (objId == -1) {
        (*mapIter).second->clear();
      } else {
        linkList = (*mapIter).second;
        listIter = find(linkList->begin(), linkList->end(), objId);
        if (listIter != linkList->end()) {
          linkList->erase(listIter);
        };
      };
    };
  } else {
    mapIter = linkMap.find(objType);
    if (mapIter != linkMap.end()) {
      linkList = (*mapIter).second;
      listIter = find(linkList->begin(), linkList->end(), objId);
      if (listIter != linkList->end()) {
        linkList->erase(listIter);
      };
    };
  };
  if (lock) {
    finish_write();
  };
  return 1;
}

t_cdLinkList *CDObject::getLink(int objType, int objId, bool lock) {
  t_cdLinkList *list, *ret;
  t_cdLinkMapIter  mapIter;
  t_cdLinkListIter listIter;
  
  ret = new t_cdLinkList;
  if (lock) {
    start_read();
  };
  if (objType != -1) {
    mapIter = linkMap.find(objType);
    if (mapIter != linkMap.end()) {
      list = (*mapIter).second;
      if (objId == -1) {
        *ret = *list;
      } else {
        for (listIter = list->begin(); listIter != list->end(); ++listIter) {
          if ((*listIter) > objId) {
            ret->push_back(*listIter);
          };
        };
      };
    };
  };
  if (lock) {
    finish_read();
  };
  if (ret->size() > 100) {
    debug << "Object type " << objType << ", object id " << objId << ", returning " << (unsigned int)ret->size() << "links" << endo;
  };
  return ret;
}

int CDObject::dump(bool lock) {
  int hi2;
  unsigned int propType;
  t_cdValueBuffer htcVB1;
  t_cdValue *htcV1;
  string hs1;
  char hc1[48], *hc2, hc3[sizeof(t_cdValueBuffer) + 1];
  if (lock) {
    start_read();
  };
  snprintf(hc1, 48, "OBJTYPE:%08X\nOBJID:%08X\n", type(), id());
  hs1 = hc1;
  for (propType = 0; propType < g_objectDefs[objectType].propAmount; propType++) {
    htcV1 = getProp(propType, false);
    if (htcV1 != NULL) {
      hc2 = expandValue(htcV1, &htcVB1, &hi2);
      if (hc2) {
        memset(hc3, 0, sizeof(hc3));
        memcpy(hc3, &htcVB1, sizeof(htcVB1));
        hs1 += hc3;
        hs1 += hc2;
        hs1 += "\n";
        free(hc2);
      };
    };
  };
  hc2 = new char[hs1.size() + 1];
  strcpy(hc2, hs1.c_str());
  g_cdGCAO.addMessage(hc2);
  if (lock) {
    finish_read();
  };
  return 0;
}
