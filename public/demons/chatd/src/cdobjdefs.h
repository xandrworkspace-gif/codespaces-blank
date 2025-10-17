#ifndef CDOBJDEFS_H
#define CDOBJDEFS_H

typedef enum e_knownObjects
{
  MSG_OBJTYPE       = 1,
  AREA_OBJTYPE      = 2,
  USER_OBJTYPE      = 3,
  CLAN_OBJTYPE      = 4,
  USR1_OBJTYPE      = 5,
  USR2_OBJTYPE      = 6,
  USR3_OBJTYPE      = 7,
  USR4_OBJTYPE      = 8,
  USR5_OBJTYPE      = 9,
  USR6_OBJTYPE      = 10,
  OBJTYPE_LAST      = MAX_OBJTYPE
} t_knownObjects;

#define LAST_OBJDEF 10
typedef struct s_objDef {
  unsigned int propAmount;
  time_t       idleTime;
  unsigned int queueLength;
  unsigned int deleteRecilinks;
}
t_objDef;

extern
  t_objDef g_objectDefs[];
#endif /* CDOBJDEFS_H */
