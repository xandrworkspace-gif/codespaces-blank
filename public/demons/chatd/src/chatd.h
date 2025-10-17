#ifndef CHATD_H
#define CHATD_H
#include <pthread.h>

#include <cdcommon.h>
extern  t_cdWL   g_workerList;
extern  t_cdGreeterList  g_greeterList;
extern  pthread_mutex_t  g_wlMutex, g_wlCleanupMutex;
extern  bool             g_cdShutDown;
#endif /* CHATD_H */
