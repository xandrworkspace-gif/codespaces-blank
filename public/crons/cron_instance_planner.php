<?
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
require_once("lib/instance_planner.lib");
require_once("lib/chat_deleter.lib");
require_once("lib/friend.lib");
require_once("lib/fight.lib");

$function_test_admin = true;
$function_admin_ids = array(1, 2);

global $NODE_NUMS,$function_test_admin,$function_admin_ids,$kind_info;

set_time_limit(0);

$img_boss = '<img title="Рейд" src="images/boss_battle.gif" border=0 align="absmiddle">';
$instance_cheduler_list = instance_scheduler_list(false, sql_pholder(" AND (`flags` & ?) != 0",
    INSTANCE_SCHEDULER_FLAG_ACTIVE
));
if($function_test_admin){
    echo "<h3>Выводим Список инстансов</h3><pre>";
    print_r($instance_cheduler_list);
    echo '</pre><h3>Выводим время UNIX</h3>'.time();
    echo '<h3>Подробная информация</h3>';
}

$test = false;
function getLocationInfo(){
    $info = debug_backtrace();
    return $info;
}
function _instance_file_log($t){
    global $test;
    $loc = getLocationInfo();
    if($test) logfile(NODE_FILE_LOG, '['.$loc[1]['line'].'] '.(is_array($t) ? print_r($t, true) : $t));
}

function instance_planner_send($text){
    global $function_test_admin, $function_admin_ids;
    if ($function_test_admin) {
        foreach ($function_admin_ids as $user_id) {
            chat_msg_send_system($text, CHAT_CHF_USER, $user_id, true);
        }
    } else {
        chat_msg_send_system($text, CHAT_CHF_AREA, null, true);
    }
}

$instance_planner_times = array(
    4,
    5,
    10,
    15,
    60,
    180,
    300,
    600,
);

$instance_planner_times_step = array(
    600 => array('min' => 600, 'max' => 610),
    300 => array('min' => 300, 'max' => 310),
    180 => array('min' => 180, 'max' => 190),
    60 => array('min' => 60, 'max' => 70),
    15 => array('min' => 15, 'max' => 15),
    10 => array('min' => 10, 'max' => 10),
    5 => array('min' => 5, 'max' => 5),
);

if ($instance_cheduler_list){
    $not_start = false;

    foreach ($instance_cheduler_list as $key => $value) {
        if (!($value['week_flags'] & (1 << date("N")-1))) continue; // проверка дня недели
        $start_time = (time() - $value['stime']);

        _instance_file_log(abs($start_time));

        $area = area_get($value['area_id']);
        $tick_cnt = 0;
        $tick_break = false;

        if(abs($start_time) < 750 && $start_time < 0) {
            $msg_add = '';
            //За 10 минут выводим сообщения о старте битвы.
            while(abs($start_time) >= 0) {
                $snd = false;
                $snd_time = false;

                //V2
                $step = 0;
                for($i = 0; $i < count($instance_planner_times); $i++){
                    if(abs($start_time) > $instance_planner_times[$i]){
                        $step = $i;
                    }
                }

                $sleep_sec = abs($start_time) - ($instance_planner_times[$step]) - 15;

                _instance_file_log(abs($start_time).' STEP:'.$step.' | '.$sleep_sec);

                if(abs($start_time) <= 75){
                    $sleep_sec = 0;
                }

                if((abs($start_time) % $instance_planner_times[$step+1] == 0) && abs($start_time) == $instance_planner_times[$step+1]){
                    $snd = true;
                    $snd_time = $instance_planner_times[$step+1];
                }
                if($snd) {
                    _instance_file_log('SND!');
                    if(($snd_time == 15 || $snd_time == 10 || $snd_time == 5)){
                        instance_planner_send('<span class="b" style="color: #009a86;"> <b style="color:#000000;">' . html_period_str(abs($start_time)) . '</b> До начала Рейда <b style="color:#000000;">'.$value['title'].'</b></span>');
                    }else{
                        /* instance_planner_send('<span class="b grnn">Через <b style="color:#000000;">' . html_period_str(abs($start_time)) . '</b> начнется <b style="color:#000000;">«' . $img_haos . $value['title'] . '»</b> на <b style="color:#000000;">«' . $area['title'] . '»</b> Для вступления в бой нажмите</span> <a href="#" onclick="top.frames[\'main_frame\'].frames[\'main_hidden\'].location.href = \'haot.php?accept=1\'; return false;"  class="b redd">[СЮДА]</a>'); */
                        instance_planner_send('<b style="color: #000cb5;">Начинается подготовка к сражению в Рейде «'.$value['title'].'».'.$msg_add.'Чтобы принять в нем участие, нажмите</b><b> <a href="#" onclick="top.frames[\'main_frame\'].frames[\'main_hidden\'].location.href = \'inst_plan.php?id='.$value['id'].'\'; return false;"  style="color:#ff3d00">СЮДА</a> не позднее, чем через ' . html_period_str(abs($start_time)) . '.</b>');
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
            while(true) {
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

        if($value['flags'] & INSTANCE_SCHEDULER_FLAG_PROD_TIME && $value['prod_time']){
            //Если нам нужно эту хаочину пускать каждые N секунд xD
            instance_scheduler_save(array(
                'id' => $value['id'],
                '_set' => sql_pholder(" stime = ? ", time() + $value['prod_time']),
            ));
        }else{
            instance_scheduler_save(array(
                'id' => $value['id'],
                '_set' => sql_pholder(" stime = ? + start_time ", strtotime("+1 day 00:00")),
            ));
        }

        $session_list = session_list($area_id,0,true, '', '*', true);
        if (!$session_list) {
            instance_planner_send('<span class="b" style="color:#000cb5;">Рейд «'.$value['title'].'» не состоялся, Не набрано необходимое кол-во участников!</span>');
            continue; //Старт еще не возможен.
        }
        $user_ids = get_hash($session_list,'uid','uid');

        $user_list = user_list(array('id' => $user_ids));

        $instance_artikul = instance_artikul_get($value['instance_artikul_id']);

        if(count($user_list) < $instance_artikul['member_cnt_min']) {
            instance_planner_send('<span class="b" style="color:#000cb5;">Рейд «'.$value['title'].'» не состоялся! Не набрано необходимое кол-во участников!</span>');
            continue; //Старт еще не возможен.
        }

        $instance_id = instance_create($instance_artikul['id'], array('dun_active' => 1,));
        if(!$instance_id) {
            instance_planner_send('<span class="b" style="color:#000cb5;">Рейд «'.$value['title'].'» не состоялся! Внутренняя ошибка #1!</span>');
            continue; //Не запустили.
        }
        $instance = instance_get($instance_id);
        if(!$instance) {
            instance_planner_send('<span class="b" style="color:#000cb5;">Рейд «'.$value['title'].'» не состоялся! Внутренняя ошибка #2!</span>');
            continue; //WHY?
        }

        foreach ($user_list as $k=>$user) {
            $user_id = $user['id'];
            do{
                instance_user_save(array(
                    '_mode' => CSMODE_REPLACE,
                    'instance_artikul_id' => $instance_artikul['id'],
                    'instance_id' => $instance_id,
                    'user_id' => $user_id,
                    'area_link_id' => 0,
                    'dtime' => $instance['dtime'] + $instance_artikul['cooldown_time'],
                ));
                user_save(array(
                    'id' => $user_id,
                    'instance_id' => $instance_id,
                    'invisibility_time' => 0,
                    'area_ftime' => 0,
                )); //Кидаем человека в инстанс.

                chat_msg_send_special(CODE_REDIRECT,CHAT_CHF_USER,$user_id,array('url' => 'instance.php'));
            }while(0);
        }
    }
}

if($not_start) sleep(55);
echo 'Ждем '.$sleep_sec;
sleep(($sleep_sec > 0 ? $sleep_sec : 5));
