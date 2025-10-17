#include <stdio.h>
#include <string.h>

#include <cdcommon.h>

string endo = "\n";
CDDebug debug, error, info, warning;
t_debugLevel g_debugLevel = DEBUG_LEVEL_DEBUG;

void s_cdDebugTlsCleanup(void *buf) {
  if (buf) {
    if (((t_cdDebugBuffer *)(buf))->buffer != NULL) {
      free(((t_cdDebugBuffer *)(buf))->buffer);
    };
    free(buf);
  };
}

int CDDebug::initTLSKey() {
  int hi1;
  do {
    hi1 = pthread_key_create(&tlsBufferKey, s_cdDebugTlsCleanup);
  } while (hi1 == EAGAIN);
  if (hi1) {
    cerr << "pthread_key_create returns " << hi1 << endl;
  };
  return hi1;
}

CDDebug::CDDebug()
  :debugLevel(DEBUG_LEVEL_ERROR) {
  initTLSKey();
}

CDDebug::CDDebug(const t_debugLevel &dbgLevel)
  :debugLevel(dbgLevel) {
  initTLSKey();
}

CDDebug::CDDebug(CDDebug &msgobj)
  :debugLevel(msgobj.level()) {
  initTLSKey();
}

CDDebug::~CDDebug() {
  if (pthread_key_delete(tlsBufferKey)) {
    cerr << "pthread_key_delete returns not 0" << endl;
  };
}

CDDebug &CDDebug::operator<<(const char *arg) {
  t_cdDebugBuffer *buf;
  if (level() <= g_debugLevel) {
    buf = getLocalBuffer();
    if (buf && (buf->position < buf->length - 1)) {
      buf->position += snprintf(buf->buffer + buf->position, buf->length - buf->position, "%s", arg);
    };
  };
  return *this;
}

CDDebug &CDDebug::operator<<(const void *arg) {
  t_cdDebugBuffer *buf;
  if (level() <= g_debugLevel) {
    buf = getLocalBuffer();
    if (buf && (buf->position < buf->length - 1)) {
      if (sizeof(void *) == sizeof(unsigned int)) {
        buf->position += snprintf(buf->buffer + buf->position, buf->length - buf->position, "0x%08x", (unsigned int*)arg);
      } else
      if (sizeof(void *) == sizeof(unsigned long)) {
        buf->position += snprintf(buf->buffer + buf->position, buf->length - buf->position, "0x%016lx", (unsigned long*)arg);
      }
    };
  };
  return *this;
}

CDDebug &CDDebug::operator<<(const pthread_t arg) {
  t_cdDebugBuffer *buf;
  if (level() <= g_debugLevel) {
    buf = getLocalBuffer();
    if (buf && (buf->position < buf->length - 1)) {
      if (sizeof(pthread_t) == sizeof(unsigned int)) {
        buf->position += snprintf(buf->buffer + buf->position, buf->length - buf->position, "0x%08x", (unsigned int)arg);
      } else
        if (sizeof(pthread_t) == sizeof(unsigned long)) {
          buf->position += snprintf(buf->buffer + buf->position, buf->length - buf->position, "0x%016lx", (unsigned long)arg);
        }
    };
  };
  return *this;
}

CDDebug &CDDebug::operator<<(const int arg) {
  t_cdDebugBuffer *buf;
  if (level() <= g_debugLevel) {
    buf = getLocalBuffer();
    if (buf && (buf->position < buf->length - 1)) {
      buf->position += snprintf(buf->buffer + buf->position, buf->length - buf->position, "%d", arg);
    };
  };
  return *this;
}

CDDebug &CDDebug::operator<<(const unsigned int arg) {
  t_cdDebugBuffer *buf;
  if (level() <= g_debugLevel) {
    buf = getLocalBuffer();
    if (buf && (buf->position < buf->length - 1)) {
      buf->position += snprintf(buf->buffer + buf->position, buf->length - buf->position, "%u", arg);
    };
  };
  return *this;
}

CDDebug &CDDebug::operator<<(const long int arg) {
  t_cdDebugBuffer *buf;
  if (level() <= g_debugLevel) {
    buf = getLocalBuffer();
    if (buf && (buf->position < buf->length - 1)) {
      buf->position += snprintf(buf->buffer + buf->position, buf->length - buf->position, "%ld", arg);
    };
  };
  return *this;
}

CDDebug &CDDebug::operator<<(const string &arg) {
  t_cdDebugBuffer *buf;
  time_t ht1;
  struct tm htm1;
  char *hc1;
  unsigned int  stringLen;
  if (false && (level() <= g_debugLevel)) {
    buf = getLocalBuffer();
    if (buf && (buf->position < buf->length - 1)) {
      if (arg != endo) {
        buf->position += snprintf(buf->buffer + buf->position, buf->length - buf->position, "%s", arg.c_str());
      } else {
        if (buf->position >= MAX_DEBUG_STRING_LENGTH) {
	  cerr << "Debug buffer position overflow (" << buf->position << ")!"  << endl;
	};
        stringLen = min(MAX_DEBUG_STRING_LENGTH, buf->position + 64);
        hc1 = new char[stringLen];
        if (hc1) {
          time(&ht1);
          localtime_r(&ht1, &htm1);
          memset(hc1, 0, stringLen);
          snprintf(hc1, stringLen - 1, "[%02d-%02d-%04d %02d:%02d:%02d][0x%08x] - %s\n",
                   htm1.tm_mday, htm1.tm_mon + 1, htm1.tm_year + 1900, htm1.tm_hour, htm1.tm_min, htm1.tm_sec,
                   (unsigned int)(buf->tid), buf->buffer);
          g_cdMessageAO.addMessage(hc1);
        } else {
          cerr << "Can't allocate output buffer - no memory left ?" << endl;
        };
        memset(buf->buffer, 0, buf->length);
        buf->position = 0;
      };
    };
  };
  return *this;
}

t_cdDebugBuffer *CDDebug::getLocalBuffer() {
  t_cdDebugBuffer *htcdDB1;
  htcdDB1 = (t_cdDebugBuffer *)pthread_getspecific(tlsBufferKey);
  if (!htcdDB1) {
    htcdDB1 = (t_cdDebugBuffer *)malloc(sizeof(t_cdDebugBuffer));
    if (htcdDB1) {
      htcdDB1->buffer = (char *)malloc(MAX_DEBUG_STRING_LENGTH);
      memset(htcdDB1->buffer, 0, MAX_DEBUG_STRING_LENGTH);
      htcdDB1->position = 0;
      htcdDB1->length = MAX_DEBUG_STRING_LENGTH;
      htcdDB1->tid = pthread_self();
    };
    if (pthread_setspecific(tlsBufferKey, htcdDB1)) {
      cerr << "pthread_setspecific() returns not 0" << endl;
      s_cdDebugTlsCleanup(htcdDB1);
      htcdDB1 = NULL;
    };
  };
  return htcdDB1;
}
