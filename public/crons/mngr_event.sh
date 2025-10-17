#!/bin/sh

PHP="php -d memory_limit=64M -d error_log=${PWD}/mngr_phperrors.log"

while [ 1 ] ;
  do
  ${PHP} mngr_event.php
  done
