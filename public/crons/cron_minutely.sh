#!/bin/sh

PHP="php -d memory_limit=32M"

while [ 1 ] ;
  do
  echo cron_1min
${PHP} cron_scheduler.php > /dev/null 2>&1
${PHP} cron_legendary.php > /dev/null 2>&1
${PHP} cron_skill_update.php > /dev/null 2>&1
${PHP} cron_stat_online.php > /dev/null 2>&1
${PHP} cron_adv_message.php > /dev/null 2>&1
sleep 1m
done