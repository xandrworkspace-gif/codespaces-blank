#!/bin/sh


PHP="php -d memory_limit=64M"

while [ 1 ] ;
  do
  echo cron_1min
${PHP} cron_m_casino.php > /dev/null 2>&1
sleep 1m
done