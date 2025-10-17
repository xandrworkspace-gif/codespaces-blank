#!/bin/sh

PHP="php -d memory_limit=256M -d error_log=${PWD}/mngr_phperrors.log"

while [ 1 ] ;
  do
  ${PHP} mngr_hunt_attack.php > /dev/null 2>&1
  done
