#include <cdcommon.h>

CDStorageMgr::CDStorageMgr() {
  int hi1;
  for (hi1 = 0; hi1 < MAX_OBJTYPE; hi1++) {
    storageArray[hi1] = NULL;
  };
}


CDStorageMgr::~CDStorageMgr() {
  int hi1;
  for (hi1 = 0; hi1 < MAX_OBJTYPE; hi1++) {
    if (storageArray[hi1]) {
      delete storageArray[hi1];
    };
  };
}

CDCollection *CDStorageMgr::find(unsigned int objType) {
  CDCollection *hCDC1;
  if (objType > MAX_OBJTYPE) {
    return NULL;
  };
  start_read();
  hCDC1 = storageArray[objType];
  finish_read();
  return hCDC1;
}

CDCollection *CDStorageMgr::add(unsigned int objType) {
  CDCollection *hCDS1;
  if (objType > MAX_OBJTYPE) {
    return NULL;
  };
  start_write();
  if (!storageArray[objType]) {
    hCDS1 = new CDCollection(objType);
    storageArray[objType] = hCDS1;
  } else {
    hCDS1 = storageArray[objType];
  };
  finish_write();
  return hCDS1;
}

int CDStorageMgr::del(unsigned int objType) {
  if (objType > MAX_OBJTYPE) {
    return 0;
  };
  start_write();
  if (storageArray[objType]) {
    delete storageArray[objType];
    storageArray[objType] = NULL;
  };
  finish_write();
  return 1;
}

int CDStorageMgr::dump(unsigned int objType) {
  int hi1, hi2;
  CDCollection *hCDC1;
  if ((objType > MAX_OBJTYPE) && (objType != (unsigned int)-1)) {
    return 0;
  };
  start_write();
  hi1 = objType == (unsigned int)-1 ? 0 : objType;
  hi2 = objType == (unsigned int)-1 ? MAX_OBJTYPE : objType + 1;
  for (; hi1 < hi2 ; hi1++) {
    hCDC1 = storageArray[hi1];
    if (hCDC1) {
      hCDC1->dump();
    };
  };
  finish_write();
  return 1;
}
