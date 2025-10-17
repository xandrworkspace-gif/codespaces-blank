#include <cdcommon.h>
t_objDef g_objectDefs[MAX_OBJTYPE] =
  {
    { 0,  0,            0,  1 },
    { 2, 30,           20,  0 },    /* 2 properties, 30 seconds lifetime, 20 elements of this type , don't delete recilinks                */
    { 2, 60,   0xFFFFFFFF,  0 },    /* 2 properties, 60 seconds lifetime, unlimited link queue length, don't delete reciprocal links */
    { 2, 60,   0xFFFFFFFF,  0 },    /* 2 properties, 60 seconds lifetime, unlimited link queue length, don't delete reciprocal links */
    { 2, 60,   0xFFFFFFFF,  0 },    /* 2 properties, 60 seconds lifetime, unlimited link queue length, don't delete reciprocal links */
    { 2, 60,   0xFFFFFFFF,  0 },    /* 2 properties, 60 seconds lifetime, unlimited link queue length, don't delete reciprocal links */
    { 2, 60,   0xFFFFFFFF,  0 },    /* 2 properties, 60 seconds lifetime, unlimited link queue length, don't delete reciprocal links */
    { 2, 60,   0xFFFFFFFF,  0 },    /* 2 properties, 60 seconds lifetime, unlimited link queue length, don't delete reciprocal links */
    { 2, 60,   0xFFFFFFFF,  0 },    /* 2 properties, 60 seconds lifetime, unlimited link queue length, don't delete reciprocal links */
    { 2, 60,   0xFFFFFFFF,  0 },    /* 2 properties, 60 seconds lifetime, unlimited link queue length, don't delete reciprocal links */
    { 2, 60,   0xFFFFFFFF,  0 },    /* 2 properties, 60 seconds lifetime, unlimited link queue length, don't delete reciprocal links */
  };
