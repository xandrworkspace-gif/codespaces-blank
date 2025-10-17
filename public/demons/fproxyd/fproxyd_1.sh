#!/bin/sh
. ../../param.sh
while [ 1 ] ;
  do
  ulimit -c 100000
  ./fproxyd -p${FIGHTCPD} -l0 -s ${GLOBALIP} -c${FIGHTCP} -d _fproxyd.log
  done


