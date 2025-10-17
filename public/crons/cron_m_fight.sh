#!/bin/sh

PHP="php -d memory_limit=64M"

while [ 1 ] ;
  do
  echo cron_3sec
${PHP} cron_m_fight.php > /dev/null 2>&1
sleep 3
  done
