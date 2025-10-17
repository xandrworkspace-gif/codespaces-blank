<?php

exit; //Хуита!

ini_set('memory_limit', '128M');

chdir("..");
require_once("include/common.inc");

set_time_limit(60);
$stime1 = microtime(true);

common_define_settings();

require_once("lib/boss_planner.lib");
// Проверка, что проект не остановлен
if (!(constant('BOSS_PLANNER_IS_ON')) || (defined('PROJECT_STOPPED') && PROJECT_STOPPED) || !defined('BOSS_PLANNER_PREP_AREA_ID') || BOSS_PLANNER_PREP_AREA_ID <= 0) {
    sleep(MNGR_BOSS_PLANNER_INTERVAL);
    return;
}
require_once("lib/bonus.lib");
require_once("lib/fight.lib");

global $online_prep_area_user_ids;
$online_prep_area_user_ids = array();
$area = area_get(BOSS_PLANNER_PREP_AREA_ID);
if (!$area) {
    sleep(MNGR_BOSS_PLANNER_INTERVAL);
    return;
}

$boss_planner_active = boss_scheduler_get(array('active' => 1));

/**
 * Необходимо выводить сообщения с сюдахой за 10 минут примерно!
 */

$time_to_cht = array(
    1 => 600,
    2 => 300,
    3 => 180,
    4 => 60,
);

function mngr_boss_chat_msg_send($message){
    chat_msg_send_system($message, CHAT_CHF_USER, 1);
}

$seconds_to_start = $boss_planner_active['stime'] - time_current();
$seconds_to_start = ($seconds_to_start > 0 ? $seconds_to_start : 0);
if($seconds_to_start <= 600 + intval(MNGR_BOSS_PLANNER_INTERVAL / 2)){
    $c_t_c_id = $boss_planner_active['time_to_cht'] + 1;
    $time_info = $time_to_cht[$c_t_c_id];
    if($time_info && $time_info < $seconds_to_start){
        //  i = 615; i > 600; i--
        $control_seconds = $seconds_to_start;
        for($i = $control_seconds; $i > $time_info; $i--){
            $time_current = time();
            $seconds_to_start--;
            sleep(1);
        }
        boss_scheduler_save(array(
            'id' => $boss_planner_active['id'],
            'time_to_cht' => $c_t_c_id,
        ));
        $msg = html_period_str('Через '.html_period_str($seconds_to_start).' босс нападет!');
        mngr_boss_chat_msg_send($msg);
    }
}

if ($boss_planner_active['stime'] > 0 && (time_current() - $boss_planner_active['stime'] + $boss_planner_active['prepare_time']) > 0) { // Пора определять соперников

    //Установим на новую дату!
    boss_scheduler_save(array(
        'id' => $boss_planner_active['id'],
        'time_to_cht' => 0,
        'stime' => time_current() + $boss_planner_active['period'],
    ));

}

$stime2 = microtime(true);
$rtime = $stime2-$stime1;

//if ($rtime > MNGR_BOSS_PLANNER_INTERVAL) error_log("(mngr_clan_battle: ".getmypid()."): Runtime $rtime sec");
sleep(max(MNGR_BOSS_PLANNER_INTERVAL-$rtime,0));
