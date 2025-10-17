#ifndef CDDEBUG_H
#define CDDEBUG_H

/**
@author Yaroslav Rastrigin
*/
typedef enum e_debugLevel
{
  DEBUG_LEVEL_START  = 0,
  DEBUG_LEVEL_SILENT,
  DEBUG_LEVEL_ERROR,
  DEBUG_LEVEL_INFO,
  DEBUG_LEVEL_DEBUG
} t_debugLevel;

#define MAX_DEBUG_STRING_LENGTH 4096
typedef struct s_cdDebugBuffer {
  unsigned int position, length;
  char *buffer;
  pthread_t tid;
} t_cdDebugBuffer;

class CDDebug {
public:
  CDDebug();
  CDDebug(const t_debugLevel &level);
  CDDebug(CDDebug &c);
  ~CDDebug();
  t_debugLevel level(const t_debugLevel newL = DEBUG_LEVEL_START)  { if (newL != DEBUG_LEVEL_START) { debugLevel = newL; }; return debugLevel; };
  CDDebug &operator<<(const void *arg);
  CDDebug &operator<<(const int arg);
  CDDebug &operator<<(const unsigned int arg);
  CDDebug &operator<<(const long int arg);
  CDDebug &operator<<(const pthread_t arg);
  CDDebug &operator<<(const string &arg);
  CDDebug &operator<<(const char *arg);
private:
  int               initTLSKey();
  t_cdDebugBuffer  *getLocalBuffer();
  t_debugLevel      debugLevel;
  pthread_key_t     tlsBufferKey;
};
extern string endo;
extern t_debugLevel g_debugLevel;
#endif
