<?php
chdir("..");
require_once("include/common.inc");

set_time_limit(0);
$stime1 = time();

common_define_settings();



// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) {
    sleep(MNGR_DUNGEONS_INTERVAL);
    return;
}

require_once("lib/area.lib");
require_once("lib/instance.lib");
require_once("lib/artifact.lib");
require_once("lib/bonus.lib");
require_once("lib/user.lib");
require_once("lib/dungeons.lib");
require_once("lib/rolling_item.lib");

dungeon_queue_control();
rolling_control();
instance_user_kick_cron();

$stime2 = time();
$rtime = $stime2-$stime1;
if ($rtime > MNGR_DUNGEONS_INTERVAL) error_log("(mngr_dungeon: ".getmypid()."): Runtime $rtime sec");
sleep(max(MNGR_DUNGEONS_INTERVAL-$rtime,0));