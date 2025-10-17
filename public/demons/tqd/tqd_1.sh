#!/bin/sh
. ../../param.sh
while [ 1 ] ;
  do
  ./tqd -p${TQDP} -srv ${SERVER_ADD}
##  valgrind --tool=memcheck --leak-check=full --log-file=_tqdval --show-reachable=yes ./tqd -p22122 -d _tqd.log
  done
