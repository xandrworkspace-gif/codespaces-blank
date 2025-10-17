#!/bin/sh


PHP="php -d memory_limit=64M"

while [ 1 ] ;
  do
  echo cron_hourly
${PHP} cron_artifact.php > /dev/null 2>&1
${PHP} cron_loan.php > /dev/null 2>&1
${PHP} cron_clan_members.php > /dev/null 2>&1
${PHP} cron_clan_stat.php > /dev/null 2>&1
${PHP} cron_fight_stat.php > /dev/null 2>&1
${PHP} cron_logserv.php > /dev/null 2>&1
${PHP} cron_logserv_update.php > /dev/null 2>&1
sleep 1h
 done