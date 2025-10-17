#!/bin/sh


PHP="php -d memory_limit=256M"

while [ 1 ] ;
  do
  echo cron_dialy
${PHP} cron_rating_all.php > /dev/null 2>&1
${PHP} cron_cleanup.php > /dev/null 2>&1
${PHP} cron_cleanup_diff.php > /dev/null 2>&1
${PHP} cron_cleanup_4.php > /dev/null 2>&1
${PHP} cron_export.php > /dev/null 2>&1
${PHP} cron_achievement_rating.php > /dev/null 2>&1
${PHP} cron_clan_rating.php > /dev/null 2>&1
${PHP} cron_clan_season.php > /dev/null 2>&1
${PHP} cron_clan_stat.php > /dev/null 2>&1
${PHP} cron_not_finished_reg.php > /dev/null 2>&1
${PHP} cron_soc_update.php > /dev/null 2>&1
${PHP} cron_stat_artifacts.php > /dev/null 2>&1
${PHP} cron_stat_daily.php > /dev/null 2>&1
${PHP} cron_woniu.php > /dev/null 2>&1
sleep 24h
done
