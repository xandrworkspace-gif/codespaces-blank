/* 
 * modifed: igorpauk 2017-18
 */

#ifndef __COMMON_H__
#define __COMMON_H__


#include <iostream>
#include <string>
#include <list>

#include <string.h>
#include <errno.h>
#include <stdlib.h>
#include <stdio.h>
#include <stdarg.h>
#include <assert.h>
#include <math.h>
#include <unistd.h>
#include <fcntl.h>
#include <signal.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <sys/select.h>
#include <sys/poll.h>
#include <sys/time.h>
#include <netinet/in.h>
#include <netinet/ip.h>
#include <netdb.h>
#include <arpa/inet.h>
#include <pthread.h>

using namespace std;


#define MIN(A, B) ((A) < (B) ? (A): (B))
#define MAX(A, B) ((A) > (B) ? (A): (B))

typedef list<string> strList;
typedef strList::iterator strListPtr;

enum errno_t {
	OK                =    0,
	ERR_WRONG_ARGS    =   -1,
	ERR_INIT          =   -2,
	ERR_CONFIG        =   -3,
	ERR_NO_MEM        =   -4, 
	ERR_SYS_ERROR     =   -5, 
	ERR_IO_ERROR      =   -6,
	ERR_WRONG_STATE   =   -7,
	ERR_WRONG_DATA    =   -8,
	ERR_EOF           =   -9,
	ERR_GENERAL       = -100
};

enum sigFlags_t {
	CSF_CLEAN    = 0x0,
	CSF_SIGPIPE  = 0x1,
	CSF_SIGINT   = 0x2,
	CSF_SIGCHLD  = 0x4,
	CSF_SIGUSR1  = 0x8
};


extern int     signals;


errno_t safeRead(int fd, char *buf, int size);
errno_t safeWrite(int fd, char *buf, int size);
int safeReadNB(int fd, char *buf, int size_max);
int safeWriteNB(int fd, char *buf, int size_max);

errno_t blockSignal(int signum);
errno_t unblockSignal(int signum);
errno_t sigHandlerInstall();

void rtrimStr(char *s);
int hexToInt(char *s);
char *strIpAddr(unsigned long ipAddr, char *buf);
int randInt(int min, int max, unsigned int *seed);
double randDouble(double min, double max, unsigned int *seed);
bool randRoll(double p, unsigned int *seed);


double round(double);	// gcc 4.4.1 warning fix


#include "debug.h"


#endif
