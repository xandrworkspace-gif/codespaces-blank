#!/bin/sh


PHP="php -d memory_limit=64M"

while [ 1 ] ;
  do
  echo cron_10min
php -d memory_limit=256M cron_activity.php > /dev/null 2>&1
${PHP} cron_filelist.php > /dev/null 2>&1
${PHP} cron_mail.php > /dev/null 2>&1
${PHP} cron_auction.php 1 > /dev/null 2>&1
${PHP} cron_blindauction.php 1 > /dev/null 2>&1
${PHP} cron_generate_cache.php 1 > /dev/null 2>&1
${PHP} cron_party_requests.php 1 > /dev/null 2>&1
${PHP} cron_session_stat.php 1 > /dev/null 2>&1

sleep 10m
  done
