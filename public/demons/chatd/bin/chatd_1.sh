#!/bin/sh
. ../../../param.sh
while [ 1 ] ;
    do
    ulimit -c 10000000 -n 4446
    ./chatd -p ${CHATDP} > /dev/null 2>&1
    done
    
