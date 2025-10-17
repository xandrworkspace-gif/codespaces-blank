#!/bin/sh

PHP="php -d memory_limit=32M"

while [ 1 ] ;
  do
  echo cron_24min
${PHP} cron_send_mail.php > /dev/null 2>&1
sleep 24m
done