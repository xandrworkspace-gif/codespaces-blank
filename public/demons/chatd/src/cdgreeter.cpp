#include <stdio.h>
#include <string.h>

#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/ip.h>
#include <chatd.h>

CDGreeter::CDGreeter() {
  debug << "CDGreeter::CDGreeter()" << endo;
  sock(0);
  status(ST_DISCONNECTED);
}

CDGreeter::CDGreeter(int port) {
  debug << "CDGreeter::CDGreeter(" << port << ")" << endo;
  sock(0);
  status(ST_DISCONNECTED);
  if (prepare(port)) {
    status(ST_WAITING);
  };
}

CDGreeter::~CDGreeter() {
  debug << "CDGreeter::~CDGreeter" << endo;
  shutDown();
}

int CDGreeter::sock(int newSocket) {
  if (newSocket != -1) {
    listenSocket = newSocket;
  };
  return listenSocket;
}

int CDGreeter::status(int newSt) {
  if (newSt!=ST_INVALID) {
    greeterStatus=newSt;
  };
  return greeterStatus;
}

pthread_t CDGreeter::tid(pthread_t newTid) {
  if (newTid != 0) {
    greeterTId = newTid;
  };
  return greeterTId;
}

int CDGreeter::shutDown() {
  debug << "CDGreeter::shutDown()" << endo;
  status(ST_DISCONNECTED);
  if (sock()) {
    shutdown(sock(), SHUT_RDWR);
    close(sock());
  };
  sock(0);
  return 0;
}

int CDGreeter::prepare(unsigned short int port) {
  int hi1;
  struct sockaddr_in hsi1;
  hi1 = socket(PF_INET, SOCK_STREAM, 0);
  if (hi1 == -1) {
    error << "socket() failed, errno: " << errno << endo;
    return 0;
  };
  sock(hi1);
  memset(&hsi1, 0, sizeof(hsi1));
  hsi1.sin_port = htons(port);
  hsi1.sin_family = AF_INET;
  if (bind(sock(), (struct sockaddr *)(&hsi1), sizeof(hsi1)) == -1) {
    error << "bind() failed, errno: " << errno << endo;
    shutDown();
    return 0;
  };
  if (listen(sock(), DEFAULT_BACKLOG) == -1) {
    error << "listen() failed, errno: " << errno << endo;
    shutDown();
    return 0;
  };
  status(ST_WAITING);
  return 1;
}

void *s_threadWorker(void *hCDW1) {
  debug << "s_threadWorker()" << endo;
  ((CDWorker *)(hCDW1))->tid(pthread_self());
  ((CDWorker *)(hCDW1))->start();
  debug << "Worker thread " << (void *)pthread_self() << " exiting" << endo;
  return NULL;
}

int CDGreeter::rollOut() {
  fd_set hfd1;
  int hi1, hi2;
  struct timeval htv1;
  struct sockaddr_in hsi1;
  socklen_t hst1;
  t_cdWLIter workerIter;
  CDWorker *worker;
  pthread_t hpt1;
  pthread_attr_t hpat1;
  struct linger lingerInfo;
  
  debug << "CDGreeter::rollOut()" << endo;
  if (status() != ST_WAITING) {
    error << "Invalid greeter state, exiting" << endo;
    return 0;
  };
  hst1 = 0;
  memset(&hsi1, 0, sizeof(hsi1));
  pthread_attr_init(&hpat1);
  pthread_attr_setdetachstate(&hpat1, PTHREAD_CREATE_DETACHED);
  pthread_attr_setschedpolicy(&hpat1, SCHED_RR);
  do {
    status(ST_WAITING);
    htv1.tv_sec = DEFAULT_IO_TIMEOUT;
    htv1.tv_usec = 0;
    FD_ZERO(&hfd1);
    FD_SET(sock(), &hfd1);
    do {
      hi1 = select(sock() + 1, &hfd1, NULL, NULL, &htv1);
    } while ((hi1 == -1) && (errno == EINTR));
    if (hi1 > 0) {
      status(ST_WORKING);
      hi2 = accept(sock(), (struct sockaddr *)(&hsi1), &hst1);
      if (hi2 > 0) {
        lingerInfo.l_onoff = 1;
        lingerInfo.l_linger = 1;
        setsockopt(hi2, SOL_SOCKET, SO_LINGER, &lingerInfo, sizeof(lingerInfo));
        LOCK_MUTEX_D(&g_wlMutex);
        worker = NULL;
        for (workerIter = g_workerList.begin(); workerIter != g_workerList.end(); ++workerIter) {
          worker = *workerIter;
          if (worker && (worker->status() == ST_AVAILABLE)) {
            if (worker->restart(hi2) != 0) {
              UNLOCK_MUTEX_D(&g_wlMutex);
              break;
            } else {
              info << "Failed to restart worker " << worker->tid() << endo;
            };
          };
          worker = NULL;
        };
        if (worker == NULL) {
          worker = new CDWorker(hi2);
          if (worker) {
            g_workerList.push_back(worker);
            UNLOCK_MUTEX_D(&g_wlMutex);
            pthread_create(&hpt1, &hpat1, s_threadWorker, worker);
          } else {
            error << "Creating new CDWorker failed. No memory left ? " << endo;
          };
        };
      } else {
        error << "accept() error," << ", errno: " << errno << endo;
      };
      status(ST_WAITING);
    } else
    if (hi1 < 0) {
      error << "select() error, " << ", errno: " << errno << endo;
      break;
    };
    cleanUp();
  } while (!g_cdShutDown);
  return  0;
}

int CDGreeter::cleanUp() {
  t_cdWLIter workerIter;
  t_cdWL newList;
  CDWorker *worker;
  if (pthread_mutex_trylock(&g_wlCleanupMutex) != 0) {
    return 0;
  };
  LOCK_MUTEX_D(&g_wlMutex);
  for (workerIter = g_workerList.begin(); workerIter != g_workerList.end(); ++workerIter) {
    worker = *workerIter;
    switch (worker->status()) {
      case ST_AVAILABLE:  
        newList.push_front(worker);
        break;
      case ST_DISCONNECTED:
        if (g_cdGC && !g_cdShutDown) {
          g_cdGC->addWorker(worker);
        };
        break;
      default:
        newList.push_back(worker);
        break;
    };
  };
  g_workerList.clear();
  g_workerList = newList;
  info << "There are " << (unsigned int)g_workerList.size() << " total workers" << endo;
  UNLOCK_MUTEX_D(&g_wlMutex);
  pthread_mutex_unlock(&g_wlCleanupMutex);
  return 1;
}
