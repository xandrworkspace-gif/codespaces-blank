#!/bin/sh

PHP="php -d memory_limit=128M -d error_log=${PWD}/mngr_phperrors.log"

while [ 1 ] ;
  do
  ${PHP} mngr_slaughter.php
  done
