#ifdef HAVE_CONFIG_H
#include <config.h>
#endif
#include <unistd.h>
#include <signal.h>
#include <iostream>
#include <cstdlib>
#include <chatd.h>

using namespace std;

static unsigned int sg_startPort;
static unsigned int sg_greeters;
bool                g_cdShutDown = false;
t_cdWL              g_workerList;
t_cdGreeterList     g_greeterList;
pthread_mutex_t     g_wlMutex;
pthread_mutex_t     g_wlCleanupMutex;
CDGC               *g_cdGC = NULL;

CDAO g_cdMessageAO("chatd.log");
CDAO g_cdGCAO("chatd_gc.log");
static void s_cdSigPipeHandler(int sigNo);
static void s_cdSigTermHandler(int sigNo);

int parseCmdLine(int argc, char *argv[]) {
  int hi1;
  while ((hi1 = getopt(argc, argv, "p:n:")) != -1) {
    switch (hi1) {
      case 'p'
          :
        sg_startPort = atoi(optarg);
        if (!sg_startPort) {
          sg_startPort = DEFAULT_STARTPORT;
        };
        break;
      case 'n'
          :
        sg_greeters = atoi(optarg);
        if (!sg_greeters) {
          sg_greeters = DEFAULT_GREETERS;
        };
        break;
      default
          :
        break;
    };
  };
  return 0;
}

void *sg_threadGreeter(void *hCDG1) {
  ((CDGreeter *)hCDG1)->tid(pthread_self());
  ((CDGreeter *)hCDG1)->rollOut();
  return NULL;
}

void *sg_threadGC(void *hCDGC) {
  ((CDGC *)hCDGC)->tid(pthread_self());
  ((CDGC *)hCDGC)->workCycle();
  return NULL;
}

void *sg_threadAO(void *hCDAO) {
  ((CDAO *)hCDAO)->tid(pthread_self());
  ((CDAO *)hCDAO)->start();
  return NULL;
}

void s_cdSigPipeHandler(int sigNo) {
  return;
}

void s_cdSigTermHandler(int sigNo) {
  g_cdShutDown = true;
}

void installSigHandlers() {
  signal(SIGPIPE, s_cdSigPipeHandler);
  signal(SIGTERM, s_cdSigTermHandler);
  signal(SIGINT, s_cdSigTermHandler);
}

int main(int argc, char *argv[]) {
  CDGreeter *hCDG1;
  pthread_t hpt1;
  t_cdGreeterListPtr hvCDGi1;
  t_cdWLIter  hvCDWi1;
  int hi1;
  installSigHandlers();
  sg_greeters = DEFAULT_GREETERS;
  sg_startPort = DEFAULT_STARTPORT;
  // Launch async output threads early
  pthread_create(&hpt1, NULL, sg_threadAO, &g_cdMessageAO);
  pthread_create(&hpt1, NULL, sg_threadAO, &g_cdGCAO);
  pthread_mutex_init(&g_wlMutex, NULL);
  pthread_mutex_init(&g_wlCleanupMutex, NULL);
  info.level(DEBUG_LEVEL_INFO);
  debug.level(DEBUG_LEVEL_DEBUG);
  error.level(DEBUG_LEVEL_ERROR);
  parseCmdLine(argc, argv);
  hi1  = 0;
  while (g_greeterList.size() < sg_greeters) {
    hCDG1 = new CDGreeter();
    if (hCDG1 && hCDG1->prepare(sg_startPort + hi1)) {
      g_greeterList.push_back(hCDG1);
    } else {
      delete hCDG1;
    };
    hi1++;
  };
  g_cdGC = new CDGC();
  if (g_cdGC) {
    pthread_create(&hpt1, NULL, sg_threadGC, g_cdGC);
  };
  for (hvCDGi1 = g_greeterList.begin(); hvCDGi1 != g_greeterList.end(); ++hvCDGi1) {
    hCDG1 = *hvCDGi1;
    pthread_create(&hpt1, NULL, sg_threadGreeter, hCDG1);
  };
  do {
    sleep(1);
  } while (!g_cdShutDown);

  for (hvCDGi1 = g_greeterList.begin(); hvCDGi1 != g_greeterList.end(); ++hvCDGi1) {
    pthread_join((*hvCDGi1)->tid(), NULL);
    delete *hvCDGi1;
  };
  g_greeterList.erase(g_greeterList.begin(), g_greeterList.end());
  for (hvCDWi1 = g_workerList.begin(); hvCDWi1 != g_workerList.end(); ++hvCDWi1) {
    (*hvCDWi1)->stop();
  };
  pthread_join(g_cdGC->tid(), NULL);
  delete g_cdGC;
  g_cdGCAO.stop();
  pthread_join(g_cdGCAO.tid(), NULL);
  g_cdMessageAO.stop();
  pthread_join(g_cdMessageAO.tid(), NULL);
  return 0;
}
