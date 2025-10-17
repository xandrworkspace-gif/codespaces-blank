#!/bin/sh

PHP="php -d memory_limit=1024M -d error_log=${PWD}/mngr_phperrors.log"

while [ 1 ] ;
  do
  ${PHP} mngr_bonus_cache_update.php
  done
