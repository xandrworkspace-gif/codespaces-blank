<?php
chdir("..");
require_once("include/common.inc");

set_time_limit(0);
$stime1 = time();

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) {
    sleep(MNGR_HUNT_ATTACK_INTERVAL);
    return;
}

require_once("lib/area.lib");
require_once("lib/instance.lib");
require_once("lib/artifact.lib");
require_once("lib/bonus.lib");
require_once("lib/user.lib");
require_once("lib/fight.lib");
require_once("lib/hunt_attack.lib");

hunt_attack_cron();

$stime2 = time();
$rtime = $stime2-$stime1;
if ($rtime > MNGR_HUNT_ATTACK_INTERVAL) error_log("(mngr_hunt_attack: ".getmypid()."): Runtime $rtime sec");
sleep(max(MNGR_HUNT_ATTACK_INTERVAL-$rtime,0));