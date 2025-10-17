#ifndef CDWRITEBACK_H
#define CDWRITEBACK_H

/**
Write-back long-term storage handler

@author Yaroslav Rastrigin
*/
typedef pair<time_t, string> queryString;
class CDWriteBack: public CDLock {
public:
  CDWriteBack();
  ~CDWriteBack();
  int flush();
private:
  vector <queryString> queries;
  time_t lastRound;
};

#endif
