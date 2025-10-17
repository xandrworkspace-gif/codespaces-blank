<?php
chdir("..");
require_once("include/common.inc");
require_once("lib/session.lib");

common_init();
require_once("lib/chat.lib");
require_once("lib/scheduler.lib");
require_once("lib/instance.lib");
require_once("lib/stronghold.lib");
require_once("lib/area.lib");
require_once("lib/arena.lib");
require_once("lib/boss_planner.lib");
require_once("lib/chat_deleter.lib");
require_once("lib/friend.lib");
require_once("lib/fight.lib");



global $NODE_NUMS,$function_test_admin,$function_admin_ids,$kind_info;

$function_test_admin = false;
$function_admin_ids = array(1);

set_time_limit(0);

$img_boss = '<img title="Атака босса" src="images/boss_battle.gif" border=0 align="absmiddle">';
$boss_cheduler_list = boss_scheduler_list(false, sql_pholder(" AND (`flags` & ?) != 0",
    BOSS_SCHEDULER_FLAG_ACTIVE
));
if($function_test_admin){
    echo "<h3>Выводим Боссов</h3><pre>";
    print_r($boss_cheduler_list);
    echo '</pre><h3>Выводим время UNIX</h3>'.time();
    echo '<h3>Подробная информация</h3>';
}

$test = false;
function getLocationInfo(){
    $info = debug_backtrace();
    return $info;
}
function _boss_file_log($t){
    global $test;
    $loc = getLocationInfo();
    if($test) logfile(NODE_FILE_LOG, '['.$loc[1]['line'].'] '.(is_array($t) ? print_r($t, true) : $t));
}

function boss_planner_send($text){
    global $function_test_admin, $function_admin_ids;
    if ($function_test_admin) {
        foreach ($function_admin_ids as $user_id) {
            chat_msg_send_system($text, CHAT_CHF_USER, $user_id, true);
        }
    } else {
        chat_msg_send_system($text, CHAT_CHF_AREA);
    }
}

$boss_planner_times = array(
    4,
    5,
    10,
    15,
    60,
    180,
    300,
    600,
);

$boss_planner_times_step = array(
    600 => array('min' => 600, 'max' => 610),
    300 => array('min' => 300, 'max' => 310),
    180 => array('min' => 180, 'max' => 190),
    60 => array('min' => 60, 'max' => 70),
    15 => array('min' => 15, 'max' => 15),
    10 => array('min' => 10, 'max' => 10),
    5 => array('min' => 5, 'max' => 5),
);

if ($boss_cheduler_list){
    $not_start = false;

    foreach ($boss_cheduler_list as $key => $value) {
        if (!($value['week_flags'] & (1 << date("N")-1))) continue; // проверка дня недели
        $start_time = (time() - $value['stime']);

        _boss_file_log(abs($start_time));

        $area = area_get(BOSS_PLANNER_PREP_AREA_ID);
        $tick_cnt = 0;
        $tick_break = false;

        if(abs($start_time) < 750 && $start_time < 0){
            $msg_add = '';
            //Подготовим строку с боссом
            if($value['cur_boss_id']){
                $cur_boss = boss_get($value['cur_boss_id']);
                if($cur_boss) $boss_artikul = bot_artikul_get($cur_boss['bot_artikul_id']);
                if($boss_artikul){
                    $boss_artikul['artikul_id'] = $boss_artikul['id'];
                    unset($boss_artikul['id']);
                    $msg_add = ' Текущий босс: '.html_bot_info($boss_artikul).'. ';
                }
            }
            //За 10 минут выводим сообщения о старте битвы.
            while(abs($start_time) >= 0){
                $snd = false;
                $snd_time = false;

                //V2
                $step = 0;
                for($i = 0; $i < count($boss_planner_times); $i++){
                    if(abs($start_time) > $boss_planner_times[$i]){
                        $step = $i;
                    }
                }

                $sleep_sec = abs($start_time) - ($boss_planner_times[$step]) - 15;

                _boss_file_log(abs($start_time).' STEP:'.$step.' | '.$sleep_sec);

                if(abs($start_time) <= 75){
                    $sleep_sec = 0;
                }

                if (isset($boss_planner_times[$step+1]) && $boss_planner_times[$step+1] != 0) {
                    if ((abs($start_time) % $boss_planner_times[$step+1] == 0) && abs($start_time) == $boss_planner_times[$step+1]) {
                        $snd = true;
                        $snd_time = $boss_planner_times[$step+1];
                    }
                } else {
                    _boss_file_log("Ошибка: деление на ноль или несуществующий индекс boss_planner_times[\$step+1]");
                }

                if($snd){
                    _boss_file_log('SND!');
                    if(($snd_time == 15 || $snd_time == 10 || $snd_time == 5)){
                        boss_planner_send('<span class="b" style="color: #009a86;"> <b style="color:#000000;">' . html_period_str(abs($start_time)) . '</b> До прибытия в наши земли <b style="color:#000000;">Босса</b></span>');
                    }else{
                        /* boss_planner_send('<span class="b grnn">Через <b style="color:#000000;">' . html_period_str(abs($start_time)) . '</b> начнется <b style="color:#000000;">«' . $img_haos . $value['title'] . '»</b> на <b style="color:#000000;">«' . $area['title'] . '»</b> Для вступления в бой нажмите</span> <a href="#" onclick="top.frames[\'main_frame\'].frames[\'main_hidden\'].location.href = \'haot.php?accept=1\'; return false;"  class="b redd">[СЮДА]</a>'); */
                        boss_planner_send('<b style="color: #000cb5;">Начинается подготовка к сражению с «Боссом».'.$msg_add.'Чтобы принять в нем участие, нажмите</b><b> <a href="#" onclick="top.frames[\'main_frame\'].frames[\'main_hidden\'].location.href = \'boss.php?accept=1\'; return false;"  style="color:#ff3d00">СЮДА</a> не позднее, чем через ' . html_period_str(abs($start_time)) . '.</b>');
                    }
                }

                if($step == 0) break;

                sleep(1); //Ждем 1 минуту
                $start_time++;
                $tick_cnt++;
                $time_current = time();

                if($sleep_sec > 0){
                    $need_break = false;
                    do{
                        if(abs($start_time) <= 70) break;
                        if(abs($start_time) >= 180 && abs($start_time) <= 190) break;
                        if(abs($start_time) >= 300 && abs($start_time) <= 310) break;
                        if(abs($start_time) >= 600 && abs($start_time) <= 610) break;
                        $need_break = true;
                    }while(0);
                    if($need_break) {
                        $tick_break = true;
                        break;
                    }
                }

                /*if(!(abs($start_time) < 600 && $start_time < 0)){
                    break;
                }*/
            }

            if($tick_break){
                continue;
            }

            //Контрольные 6 секунды
            $s_seconds = 6;
            while(true){
                sleep(1);
                $s_seconds--;
                if($s_seconds == 0) break;
            }
        }
        if((time() - $value['stime']) < 0){
            if($function_test_admin) {
                echo(time() - $value['stime']);
                echo "Старт еще не доступен";
            }
            $not_start = true;
            continue; //Старт еще не возможен.
        }

        //Запуск босса
        $area_id = $area['id'];
        NODE_SWITCH($area['node_num']);

        if($value['flags'] & BOSS_SCHEDULER_FLAG_PROD_TIME && $value['prod_time']){
            //Если нам нужно эту хаочину пускать каждые N секунд xD
            boss_scheduler_save(array(
                'id' => $value['id'],
                '_set' => sql_pholder(" stime = ? ", time() + $value['prod_time']),
            ));
        }else{
            boss_scheduler_save(array(
                'id' => $value['id'],
                '_set' => sql_pholder(" stime = ? + start_time ", strtotime("+1 day 00:00")),
            ));
        }

        $session_list = session_list($area_id,0,true, '', '*', true);
        if (!$session_list) {
            boss_planner_send('<span class="b" style="color:#000cb5;">Атака на <b style="color:#000000;">«Босса»</b> Провалилась, не нашлось отважных воинов!</span>');
            return;
        }
        $user_ids = get_hash($session_list,'uid','uid');

        /*
        if (!area_lock($area_id)) {
            boss_planner_send('<span class="b" style="color:#000cb5;"><b style="color:#000000;">«Босса»</b> >Провалилась, не удалось начать бой.</span>');
            return;
        }
        */

        $user_list = user_list(array('id' => $user_ids));

        $boss_list_hash = make_hash(boss_list(array('boss_scheduler_id' => $value['id'])), 'id');
        $boss_id = array_rand($boss_list_hash); //Значение будущее cur_boss_id

        //Щаманство
        $cur_boss_id = intval($value['cur_boss_id']);
        if(!$cur_boss_id) $cur_boss_id = $boss_id;

        $boss_artikul_id = $boss_list_hash[$cur_boss_id]['bot_artikul_id'];

        $bot_boss_artikul = bot_artikul_get($boss_artikul_id);

        $bot_boss_id = bot_create($boss_artikul_id, 1, BOSS_PLANNER_PREP_AREA_ID, false, array('flags' => BOT_FLAG_TEMP));
        $bot_boss = bot_get($bot_boss_id);
        ////
        $fight = array(
            'area_id' => $area_id,
            'title' => 'Атака босса «'.$bot_boss_artikul['nick'].'»',
            'type' => FIGHT_TYPE_BOSS_PLANNER,
            'timeout' => ($value['ftime'] ? $value['ftime'] : 10),
            'level_min' => 1,
            'level_max' => 99,
            'boss_scheduler_id' => $value['id'],
            'boss_artikul_id' => $cur_boss_id,
        );
        $pers_data = array();

        $team = 1;
        foreach ($user_list as $user){
            $pers_data[] = array('user_id' => $user['id'], 'team' => $team);
        }
        $pers_data[] = array('bot_id' => $bot_boss['id'], 'team' => 2);

        $status = fight_start($fight,$pers_data);

        /*
        area_unlock($area_id);
        */

        if($status){
            boss_planner_send('<span class="b" style="color:#000cb5;"><a href=\'/fight_info.php?fight_id='.$status.'\' target=\'_blank\'> Атака на <b style=color:#000000;>«Босса '.$bot_boss['nick'].'»</b></a> Началась!</span>');

            boss_scheduler_save(array(
                'id' => $value['id'],
                'cur_boss_id' => $boss_id,
            ));

        }else{
            boss_planner_send('<span class="b" style="color:#000cb5;">Атака на <b style="color:#000000;">«Босса»</b> >Провалилась, не удалось начать бой.</span>');
            //Если не смогли запустить бой, отправляем домой
            if($user_list) boss_planner_user_backhome($user_list);
        }
    }
}


if($not_start) sleep(55);
echo 'Ждем '.$sleep_sec;
sleep(($sleep_sec > 0 ? $sleep_sec : 5));
