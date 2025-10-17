#!/bin/sh

PHP="php -d memory_limit=256M -d error_log=${PWD}/mngr_phperrors.log"

while [ 1 ] ;
  do
  ${PHP} cron_instance_planner.php 0 1 > /dev/null 2>&1
  done
