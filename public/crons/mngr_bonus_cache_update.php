<?php

ini_set("display_errors",1);
error_reporting(E_ERROR);

chdir("..");
require_once("include/common.inc");
require_once("lib/bonus.lib");
set_time_limit(0);
ini_set('memory_limit', '1024M');
$stime1 = time();

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) {
	sleep(MNGR_BONUS_CACHE_INTERVAL);
	return;
}

bonus_cache_update_mngr();

$stime2 = time();
$rtime = $stime2-$stime1;
if ($rtime > MNGR_BONUS_CACHE_INTERVAL) error_log("(mngr_bonus_cache_update: ".getmypid()."): Runtime ".$rtime." sec, total_records: ".$total);
sleep(max(MNGR_BONUS_CACHE_INTERVAL-$rtime,0));