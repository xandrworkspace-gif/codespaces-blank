#include <string.h>
#include <stdio.h>

#include <chatd.h>
static
char *s_hexChars[256] = {
                          "00", "01", "02", "03", "04", "05", "06", "07", "08", "09", "0A", "0B", "0C", "0D", "0E", "0F",
                          "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "1A", "1B", "1C", "1D", "1E", "1F",
                          "20", "21", "22", "23", "24", "25", "26", "27", "28", "29", "2A", "2B", "2C", "2D", "2E", "2F",
                          "30", "31", "32", "33", "34", "35", "36", "37", "38", "39", "3A", "3B", "3C", "3D", "3E", "3F",
                          "40", "41", "42", "43", "44", "45", "46", "47", "48", "49", "4A", "4B", "4C", "4D", "4E", "4F",
                          "50", "51", "52", "53", "54", "55", "56", "57", "58", "59", "5A", "5B", "5C", "5D", "5E", "5F",
                          "60", "61", "62", "63", "64", "65", "66", "67", "68", "69", "6A", "6B", "6C", "6D", "6E", "6F",
                          "70", "71", "72", "73", "74", "75", "76", "77", "78", "79", "7A", "7B", "7C", "7D", "7E", "7F",
                          "80", "81", "82", "83", "84", "85", "86", "87", "88", "89", "8A", "8B", "8C", "8D", "8E", "8F",
                          "90", "91", "92", "93", "94", "95", "96", "97", "98", "99", "9A", "9B", "9C", "9D", "9E", "9F",
                          "A0", "A1", "A2", "A3", "A4", "A5", "A6", "A7", "A8", "A9", "AA", "AB", "AC", "AD", "AE", "AF",
                          "B0", "B1", "B2", "B3", "B4", "B5", "B6", "B7", "B8", "B9", "BA", "BB", "BC", "BD", "BE", "BF",
                          "C0", "C1", "C2", "C3", "C4", "C5", "C6", "C7", "C8", "C9", "CA", "CB", "CC", "CD", "CE", "CF",
                          "D0", "D1", "D2", "D3", "D4", "D5", "D6", "D7", "D8", "D9", "DA", "DB", "DC", "DD", "DE", "DF",
                          "E0", "E1", "E2", "E3", "E4", "E5", "E6", "E7", "E8", "E9", "EA", "EB", "EC", "ED", "EE", "EF",
                          "F0", "F1", "F2", "F3", "F4", "F5", "F6", "F7", "F8", "F9", "FA", "FB", "FC", "FD", "FE", "FF"
                        };

bool getLong(char *str, int strl, int *value) {
  char hc1[16], *hc2;
  if (!str || !strl || (strl > 15) || !value) {
    return false;
  };
  memset(hc1, 0, sizeof(hc1));
  memcpy(hc1, str, strl);
  (*value) = strtoul(hc1, &hc2, 16);
  return hc2 != hc1;
}

bool getShort(char *str, int strl, short int *value) {
  char hc1[8], *hc2;
  if (!str || !strl || (strl > 7) || !value) {
    return false;
  };
  memset(hc1, 0, sizeof(hc1));
  memcpy(hc1, str, strl);
  (*value) = (short int)strtoul(hc1, &hc2, 16);
  return hc2 != hc1;
}

bool getFloat(char *str, int strl, float *value) {
  char hc1[24], *hc2;
  if (!str || !strl || (strl > 22) || !value) {
    return false;
  };
  memset(hc1, 0, sizeof(hc1));
  memcpy(hc1, str, strl);
  (*value) = strtof(hc1, &hc2);
  return hc2 != hc1;
}

bool putLong(char *buffer, int value) {
  if (!buffer) {
    return false;
  };
  memcpy(buffer,     s_hexChars[(value & 0xFF000000) >> 24], 2);
  memcpy(buffer + 2, s_hexChars[(value & 0xFF0000) >> 16], 2);
  memcpy(buffer + 4, s_hexChars[(value & 0xFF00) >> 8], 2);
  memcpy(buffer + 6, s_hexChars[(value & 0xFF)], 2);
  return true;
}

bool putShort(char *buffer, short int value) {
  if (!buffer) {
    return false;
  };
  memcpy(buffer    , s_hexChars[(value & 0xFF00) >> 8], 2);
  memcpy(buffer + 2, s_hexChars[(value & 0xFF)], 2);
  return true;
}

bool putFloat(char *buffer, float value) {
  char hc1[18];
  if (!buffer) {
    return false;
  };
  memset(hc1, 0, sizeof(hc1));
  snprintf(hc1, sizeof(hc1), "%016.4f", value);
  memcpy(buffer, hc1, 16);
  return true;
}

bool putBytes(char *buffer, char *data, unsigned int dataLen) {
  if (!buffer) {
    return false;
  };
  while (dataLen > 0) {
    memcpy(buffer, s_hexChars[(unsigned char)(*data)], 2);
    data++;
    dataLen--;
    buffer+= 2;
  };
  return true;
}

void clearValue(t_cdValue *htcV1) {
  if (!htcV1) {
    return;
  };
  switch (htcV1->argType) {
    case
        AT_INT:
    case
        AT_BOOL:
    case
        AT_FLOAT:
      break;
    case
        AT_STRING:
    case
        AT_OBJECT:
      free(htcV1->arg.cVal);
      htcV1->arg.cVal = NULL;
      break;
    case
        AT_PTR:
      free(htcV1->arg.pVal);
      htcV1->arg.pVal = NULL;
      break;
    case
        AT_LINKLIST:
      ((t_cdLinkList *)(htcV1->arg.pVal))->clear();
      delete(t_cdLinkList *)(htcV1->arg.pVal);
      htcV1->arg.pVal = NULL;
    default
        :
      break;
  };
  htcV1->argType = AT_INVALID;
  return;
}

int copyValue(t_cdValue *htcV1, t_cdValue *newValue) {
  if (!htcV1 || !newValue) {
    return 0;
  };
  *htcV1 = *newValue;
  if ((htcV1->argType == AT_STRING) || (htcV1->argType == AT_OBJECT)) {
    htcV1->arg.cVal = (char *)malloc(htcV1->argLength + 1);
    memset(htcV1->arg.cVal, 0, htcV1->argLength + 1);
    memcpy(htcV1->arg.cVal, newValue->arg.cVal, htcV1->argLength + 1);
  };
  return 1;
}

char *expandValue(t_cdValue *value, t_cdValueBuffer *buffer, int *dataLen) {
  char *hc1 = NULL;
  char hc2[20];
  char *hc3;
  t_cdLinkListIter linkIter;
  t_cdLinkList   *linkList;
  if (!value || !buffer || !dataLen) {
    return hc1;
  };
  putLong(buffer->argId, value->argId);
  putShort(buffer->argType, value->argType);
  switch (value->argType) {
    case
        AT_INT:
    case
        AT_BOOL:
    case
        AT_INVALID:
      *dataLen = 8;
      putLong(buffer->argLength, *dataLen);
      hc1 = (char *)malloc((*dataLen) + 1);
      memset(hc1, 0, (*dataLen) + 1);
      putLong(hc1, value->arg.iVal);
      break;
    case
        AT_FLOAT:
      *dataLen = 16;
      putLong(buffer->argLength, *dataLen);
      hc1 = (char *)malloc((*dataLen) + 1);
      memset(hc1, 0, (*dataLen) + 1);
      putFloat(hc1, value->arg.fVal);
      break;
    case
        AT_STRING:
    case
        AT_OBJECT:
      *dataLen = value->argLength;
      putLong(buffer->argLength, *dataLen);
      hc1 = (char *)malloc((*dataLen) + 1);
      memset(hc1, 0, (*dataLen) + 1);
      memcpy(hc1, value->arg.cVal, value->argLength);
      break;
    case
        AT_PTR:
      *dataLen = value->argLength * 2;
      putLong(buffer->argLength, *dataLen);
      hc1 = (char *)malloc((*dataLen) + 1);
      memset(hc1, 0, (*dataLen) + 1);
      putBytes(hc1, (char *)(value->arg.pVal), value->argLength);
      break;
    case
        AT_LINKLIST:
      linkList = (t_cdLinkList *)(value->arg.pVal);
      *dataLen = linkList->size() * 18;
      putLong(buffer->argLength, *dataLen);
      hc1 = (char *)malloc((*dataLen) + 1);
      memset(hc1, 0, (*dataLen) + 1);
      memset(hc2, 0, sizeof(hc2));
      hc3 = hc1;
      for (linkIter  = linkList->begin(); linkIter != linkList->end(); ++linkIter) {
        snprintf(hc2, sizeof(hc2), "%08X:%08X;", value->argId, *linkIter);
        memcpy(hc3, hc2, 18);
        hc3+=18;
      };
      break;
  };
  return hc1;
}
