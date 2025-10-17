#!/bin/sh

PHP="php -d memory_limit=256M -d error_log=${PWD}/mngr_phperrors.log"

while [ 1 ] ;
  do
  ${PHP} mngr_raid_queue.php  0 1 > /dev/null 2>&1
  ${PHP} mngr_raid.php 1 > /dev/null 2>&1
  done
