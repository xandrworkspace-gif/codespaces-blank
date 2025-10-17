#ifndef CDCOMMON_H
#define CDCOMMON_H

typedef enum e_commonStatus
{
  ST_INVALID      = -1,
  ST_DISCONNECTED = 0,
  ST_WAITING      = 1,
  ST_WORKING      = 2,
  ST_AVAILABLE    = 3,
  ST_BUSY         = 4,
  ST_SHUTDOWN     = 5
} t_commonStatus;

typedef union
{
  bool          bVal;
  int           iVal;
  float         fVal;
  char         *cVal;
  void         *pVal;
} t_value;

typedef struct s_cdValue {
  unsigned int        argId;
  unsigned short int  argType;
  unsigned int        argLength;
  t_value             arg;
}
t_cdValue;

typedef struct s_cdCommand {
  unsigned int        sequenceNum;
  unsigned int        objectType;
  unsigned int        objectId;
  unsigned short int  commandId;
  unsigned short int  argCount;
}
t_cdCommand;

typedef struct s_cdValueBuffer {
  char argId[8]     __attribute__((packed));
  char argType[4]   __attribute__((packed));
  char argLength[8] __attribute__((packed));
}
t_cdValueBuffer;

typedef struct s_cdCommandBuffer {
  char sequenceNum[8] __attribute__((packed));
  char commandId[4]   __attribute__((packed));
  char objectType[8]  __attribute__((packed));
  char objectId[8]    __attribute__((packed));
  char argCount[4]    __attribute__((packed));
}
t_cdCommandBuffer ;

typedef enum e_cmd
{
  CMD_INVALID       = 0,
  CMD_NOOP          = 1,
  CMD_SLEEP         = 2,
  CMD_CREATEOBJ     = 3,
  CMD_DESTROYOBJ    = 4,
  CMD_GETOBJPROP    = 5,
  CMD_SETOBJPROP    = 6,
  CMD_UNSETOBJPROP  = 7,
  CMD_GETOBJLINK    = 8,
  CMD_ADDOBJLINK    = 9,
  CMD_DELOBJLINK    = 10,
  CMD_DUMPOBJ       = 11,
  CMD_DEBUGLEVEL    = 12,
  CMD_CHECKOBJ      = 13,
  CMD_UPLOADOBJ     = 14,
  CMD_SETAUTOINC    = 15,
  CMD_GETOBJLIST    = 16,
  CMD_DUMPALL       = 17,
  CMD_SHUTDOWN      = 18,
  CMD_SETOBJTPROPS  = 19
} t_cmd;

typedef enum e_argType
{
  AT_INVALID        = 0,
  AT_BOOL           = 1,
  AT_INT            = 2,
  AT_STRING         = 3,
  AT_PTR            = 4,
  AT_LINKLIST       = 5,
  AT_FLOAT          = 6,
  AT_OBJECT         = 7,
  /* Add new types here */
  AT_ANY            = 16384 /* Last type */
} t_argType;

#define OBJPROP_OBJECT 1

#define MAX_OBJTYPE 1024
#define DEFAULT_GREETERS 5
#define DEFAULT_BACKLOG 20
#define DEFAULT_STARTPORT 4344
#define DEFAULT_IO_TIMEOUT 5
#define DEFAULT_READ_BUFFER_SIZE 1024*4
#define DEFAULT_WRITE_BUFFER_SIZE 1024*2
#define DEFAULT_GCQ_SIZE 128
#define MAX_AO_QUEUE_LENGTH 2
#define DEFAULT_LINKQUEUE_SIZE 20
#include <iostream>
#include <map>
#include <vector>
#include <list>
#include <string>
#include <algorithm>
using namespace std;
#define LOCK_MUTEX(A) pthread_mutex_lock(A)
#define LOCK_MUTEX_D(A) pthread_mutex_lock(A)
#define LOCK_MUTEX_DD(A) do { fprintf(stderr, "%d - %s:%d - %s\n", (unsigned int)pthread_self(), __FILE__, __LINE__, "locking mutex " #A); pthread_mutex_lock(A); fprintf(stderr, "%d - %s:%d - obtained mutex " #A "\n",(unsigned int)pthread_self(), __FILE__, __LINE__); } while (0)
#define UNLOCK_MUTEX(A) pthread_mutex_unlock(A)
#define UNLOCK_MUTEX_D(A) pthread_mutex_unlock(A)
#define UNLOCK_MUTEX_DD(A) do { fprintf(stderr, "%d - %s:%d - %s\n", (unsigned int)pthread_self(), __FILE__, __LINE__, "freeing mutex " #A); pthread_mutex_unlock(A); } while (0)
class CDCollection;
class CDObject;
class CDGreeter;
class CDLock;
class CDStorageMgr;
class CDWorker;
class CDGC;
class CDWriteBack;
class CDAO;

typedef map<int, CDObject *>    t_cdObjMap;
typedef t_cdObjMap::iterator    t_cdObjMapPtr;

typedef list<int>               t_cdLinkList;
typedef t_cdLinkList::iterator  t_cdLinkListIter;
typedef map<int,t_cdLinkList *> t_cdLinkMap;
typedef t_cdLinkMap::iterator   t_cdLinkMapIter;

typedef vector<t_cdValue *> t_cdArgList;

inline int    min(int a, int b) {
  return a<b?a:b;
}
inline int    max(int a, int b) {
  return a>b?a:b;
}

extern void   clearValue(t_cdValue *htcV1);
extern int    copyValue(t_cdValue *dst, t_cdValue *src);
extern char  *expandValue(t_cdValue *value, t_cdValueBuffer *buffer, int *dataLen);

extern bool   getLong(char *str, int strl, int *value);
extern bool   getShort(char *str, int strl, short int *value);
extern bool   getFloat(char *str, int strl, float *value);
extern bool   putLong(char *buffer, int value);
extern bool   putShort(char *buffer, short int value);
extern bool   putFloat(char *buffer, float value);
extern bool   putBytes(char *buffer, char *data, unsigned int dataLen);

#include <cdobjdefs.h>
#include <cdlock.h>
#include <cdentry.h>
#include <cdcollection.h>
#include <cdstoragemgr.h>
#include <cdgreeter.h>
#include <cdworker.h>
#include <cdwriteback.h>
#include <cdgc.h>
#include <cdaio.h>
#include <cddebug.h>
extern CDDebug debug, error, info, warning;
extern CDAO g_cdMessageAO, g_cdGCAO;
extern CDGC *g_cdGC;
extern CDStorageMgr g_storageMgr;
#endif  /* CDCOMMON_H */
