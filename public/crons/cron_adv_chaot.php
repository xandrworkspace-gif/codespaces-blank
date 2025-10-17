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
require_once("lib/adv_chaot.lib");
require_once("lib/chat_deleter.lib");
require_once("lib/friend.lib");
require_once("lib/fight.lib");

$function_test_admin = false;
$function_admin_ids = array(1);

global $NODE_NUMS,$function_test_admin,$function_admin_ids,$kind_info;

set_time_limit(0);

//Подразумевается что Хаоты будут проходить не меньше чем каждые 30 минут и не будет 2 разных хаота одновременно!!!

$img_haos = '<img title="Хаотичная битва" src="/images/ico_chaot.png" border=0 align="absmiddle">';
$img_location = '<img title="Локация" src="/images/compass.png" border=0 align="absmiddle">';
$adv_chaot_list = adv_chaot_list(false, sql_pholder(" AND (`flags` & ?) != 0",
    ADV_CHAOT_FLAG_ACTIVE
));
if($function_test_admin){
    echo "<h3>Выводим Хаотичные</h3><pre>";
    print_r($adv_chaot_list);
    echo '</pre><h3>Выводим время UNIX</h3>'.time();
    echo '<h3>Подробная информация</h3>';
}

$test = false;
function getLocationInfo(){
    $info = debug_backtrace();
    return $info;
}
function _chaot_file_log($t){
    global $test;
    $loc = getLocationInfo();
    if($test) logfile(NODE_FILE_LOG, '['.$loc[1]['line'].'] '.(is_array($t) ? print_r($t, true) : $t));
}

function adv_chaot_send($text){
    global $function_test_admin, $function_admin_ids;
    if ($function_test_admin) {
        foreach ($function_admin_ids as $user_id) {
            chat_msg_send_system($text, CHAT_CHF_USER, $user_id, true);
        }
    } else {
        chat_msg_send_system($text, CHAT_CHF_AREA, null, true);
    }
}

$adv_chaot_times = array(
    /*4,
    5,
    10,
    15,*/
    60,
    180,
    300,
    600,
);

$adv_chaot_times_step = array(
    600 => array('min' => 600, 'max' => 610),
    300 => array('min' => 300, 'max' => 310),
    180 => array('min' => 180, 'max' => 190),
    60 => array('min' => 60, 'max' => 70),
    /*15 => array('min' => 15, 'max' => 15),
    10 => array('min' => 10, 'max' => 10),
    5 => array('min' => 5, 'max' => 5),*/
);

if ($adv_chaot_list){
    $not_start = false;

    foreach ($adv_chaot_list as $key => $value) {
        if (!($value['week_flags'] & (1 << date("N")-1))) continue; // проверка дня недели
        $start_time = (time() - $value['stime']);
        _chaot_file_log(abs($start_time));

        $area = area_get($value['area_id']);
        $tick_cnt = 0;
        $tick_break = false;

        if(abs($start_time) < 750 && $start_time < 0){
            //За 10 минут выводим сообщения о старте битвы.
            while(abs($start_time) >= 0){
                $snd = false;
                $snd_time = false;

                //V2
                $step = 0;
                for($i = 0; $i < count($adv_chaot_times); $i++){
                    if(abs($start_time) > $adv_chaot_times[$i]){
                        $step = $i;
                    }
                }

                $sleep_sec = abs($start_time) - ($adv_chaot_times[$step]) - 15;

                _chaot_file_log(abs($start_time).' STEP:'.$step.' | '.$sleep_sec);

                if(abs($start_time) <= 75){
                    $sleep_sec = 0;
                }

                if((abs($start_time) % $adv_chaot_times[$step+1] == 0) && abs($start_time) == $adv_chaot_times[$step+1]){
                    $snd = true;
                    $snd_time = $adv_chaot_times[$step+1];
                }
                if($snd){
                    _chaot_file_log('SND!');
                    if(($snd_time == 15 || $snd_time == 10 || $snd_time == 5)){
                        adv_chaot_send('<span class="b grnn"> <b style="color:#000000;">' . html_period_str(abs($start_time)) . '</b> До старта <b style="color:#000000;">«' . $value['title'] . '»</b></span>');
                    }else{
                        /* adv_chaot_send('<span class="b grnn">Через <b style="color:#000000;">' . html_period_str(abs($start_time)) . '</b> начнется <b style="color:#000000;">«' . $img_haos . $value['title'] . '»</b> на <b style="color:#000000;">«' . $area['title'] . '»</b> Для вступления в бой нажмите</span> <a href="#" onclick="top.frames[\'main_frame\'].frames[\'main_hidden\'].location.href = \'haot.php?accept=1\'; return false;"  class="b redd">[СЮДА]</a>'); */
                        // adv_chaot_send('<b style="color:darkred">Начинается подготовка к сражению «' . $value['title'] . '» среди игроков вашей группы. Чтобы принять в нем участие, нажмите</b><b> <a href="#" onclick="top.frames[\'main_frame\'].frames[\'main_hidden\'].location.href = \'haot.php?accept=1\'; return false;"  style="color:darkgreen">СЮДА</a> не позднее, чем через ' . html_period_str(abs($start_time)) . '.</b>');
						adv_chaot_send('<b>'.$img_haos.' Через <b style="color:red">' . html_period_str(abs($start_time)) . '</b> будет сформирована <b style="color:darkred"> «' . $value['title'] . '» </b> среди игроков вашей уровненной группы. Чтобы принять участие в битве, нажмите  <a href="#" onclick="top.frames[\'main_frame\'].frames[\'main_hidden\'].location.href = \'haot.php?accept=1\'; return false;"  style="color:darkblue">[СЮДА]</a>.</b>');
                                       
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

        //Запуск хаота
        $area_id = $area['id'];
        NODE_SWITCH($area['node_num']);

        if($value['flags'] & ADV_CHAOT_FLAG_PROD_TIME && $value['prod_time']){
            //Если нам нужно эту хаочину пускать каждые N секунд xD
            adv_chaot_save(array(
                'id' => $value['id'],
                '_set' => sql_pholder(" stime = ? ", time() + $value['prod_time']),
            ));
        }else{
            adv_chaot_save(array(
                'id' => $value['id'],
                '_set' => sql_pholder(" stime = ? + start_time ", strtotime("+1 day 00:00")),
            ));
        }

        $session_list = session_list($area_id,0,true, '', '*', true);
        if (!$session_list) {
            adv_chaot_send('<b>'.$img_haos.' К сожалению <b style="color:darkred">«'.$value['title'].'»</b> не состоялась по причине отсутствия участников сражения.</span>');
            return;
        }
        $user_ids = get_hash($session_list,'uid','uid');

        /*
        if (!area_lock($area_id)) {
            adv_chaot_send('<span class="b grnn"><b style="color:#000000;">«'.$img_haos.' '.$value['title'].'»</b> >Не стартовала! Причина: Не удалось начать бой!</span>');
            return;
        }
        */

        $user_list = user_list(array('id' => $user_ids));

        //Раздатка битв подключение игроков
        if($value['flags'] & ADV_CHAOT_FLAG_RAZDELENIE){
            //Если нужно разделять и властвовать :)
            $battles = json_decode($value['level_rate'],true);

            $user_battle = array(); //Подготовим список битв с участниками
            $user_hash = make_hash($user_list, 'id', false);

            common_fldsort($user_hash, true, 'level');

            $team_b  = false;
            foreach($battles as $k=>$battle){
                $user_battle[$k] = array();
                $team_w = array();
                foreach($user_hash as $user){
                    if(in_array($user['level'],$battle)){
                        $team = $team_b ? 2 : 1;
                        $team_b = $team_b ? false : true;
                        $user_battle[$k][$user['id']] = array('user_id' => $user['id'], 'team' => $team);
                        $team_w[$team]++;
                    }
                }
                if($team_w[1] > $team_w[2]){
                    //Если перевес участников в первой команде добавить кого-нибудь бота например ко второй команде
                }elseif($team_w[2] > $team_w[1]){
                    //Если перевес участников во второй команде добавить кого-нибудь бота например к первой команде
                }

                //$user_battle[1][] = array('bot_id' => 493, 'team' => 1);
                //$user_battle[2][] = array('bot_id' => 492, 'team' => 2);
            }

            foreach($user_battle as $k=>$battle){
                if(count($battle) < $value['min_user_cnt']){
                    if($function_test_admin) {
                        echo "Не набрано количество ".count($battle)."/".$value['max_user_cnt'];
                    }
                    if($messges_not_started) adv_chaot_send('<b>'.$img_haos.' Не набрано необходимое количество воинов для начала <b style="color:darkred;">«'.$value['title'].' ['.min($battles[$k]).'-'.max($battles[$k]).']»</b>.</b>');

                    //Если не набрали кол-во участников нужно отнести игроков по домам xD
                    $user_list_back = array();
                    foreach ($battle as $user_id=>$val){
                        $user = $user_hash[$val['user_id']];
                        if($user) $user_list_back[$user['id']] = $user;
                    }
                    if($user_list_back) adv_chaot_user_backhome($user_list_back);

                    continue; //Еще не достигли нужного количества игроков.
                }

                $fight = array(
                    'area_id' => $area_id,
                    'title' => $value['title'].' ['.min($battles[$k]).'-'.max($battles[$k]).']',
                    'type' => FIGHT_TYPE_CHAOTIC,
                    'timeout' => ($value['ftime'] ? $value['ftime'] : 10),
                    'level_min' => min($battles[$k]),
                    'level_max' => max($battles[$k]),
                    'fight_flags' => FIGHT_FLAG_BLIND,
                    'adv_chaot_id' => $value['id'],
                );

                $status = fight_start($fight,$battle);

                /*
                area_unlock($area_id);
                */

                if($status){
                    adv_chaot_send('<b>'.$img_haos.'  <a href=\'/fight_info.php?fight_id='.$status.'\' target=\'_blank\'> <b style=color:darkred;>«'.$value['title'].' ['.min($battles[$k]).'-'.max($battles[$k]).']»</b> </a> Началась!</b>');
                }else{
                    adv_chaot_send('<b>'.$img_haos.' <b style="color:darkred;">«'.$value['title'].'»</b> Не состоялась по техническим причинам.</b>');

                    //Если не смогли запустить бой, отправляем домой
                    $user_list_back = array();
                    foreach ($battle as $user_id=>$val){
                        $user = $user_hash[$val['user_id']];
                        if($user) $user_list_back[$user['id']] = $user;
                    }
                    if($user_list_back) adv_chaot_user_backhome($user_list_back);

                }
            }
            /*
            area_unlock($area_id);
            */
        }else{ //Стандартная хаотичка xD
            $fight = array(
                'area_id' => $area_id,
                'title' => 'Хаотичная битва '.$value['title'],
                'type' => FIGHT_TYPE_CHAOTIC,
                'timeout' => ($value['ftime'] ? $value['ftime'] : 10),
                'level_min' => 1,
                'level_max' => 99,
                'fight_flags' => FIGHT_FLAG_BLIND,
                'adv_chaot_id' => $value['id'],
            );
            $pers_data = array();

            $team = 1;
            foreach ($user_list as $user){
                if($value['flags'] & ADV_CHAOT_FLAG_RANDOM_PERS) $team = ($team == 1 ? 2 : 1);
                else $team = rand(1,2);
                $pers_data[] = array('user_id' => $user['id'], 'team' => $team);
            }

            if($value['flags'] & ADV_CHAOT_FLAG_BOTS){
                $bot_list = bot_list(null,0,$area_id,0);
                foreach ($bot_list as $bot) {
                    if ($value['flags'] & ADV_CHAOT_FLAG_RANDOM_BOTS) $team = ($team == 1 ? 2 : 1);
                    else $team = rand(1, 2);
                    $pers_data[] = $pers_data[] = array('bot_id' => $bot['id'], 'team' => $team);
                }
            }

            $status = fight_start($fight,$pers_data);

            /*
            area_unlock($area_id);
            */

            if($status){
                adv_chaot_send('<b>'.$img_haos.'  <a href=\'/fight_info.php?fight_id='.$status.'\' target=\'_blank\'> <b style=color:darkred;>«'.$value['title'].' ['.min($battles[$k]).'-'.max($battles[$k]).']»</b> </a> Началась!</b>');
            }else{
                adv_chaot_send('<b>'.$img_haos.' <b style="color:darkred;">«'.$value['title'].'»</b> Не состоялась по техническим причинам.</b>');
                //Если не смогли запустить бой, отправляем домой
                if($user_list) adv_chaot_user_backhome($user_list);
            }
        }
    }
}

if($not_start) sleep(55);
echo 'Ждем '.$sleep_sec;
sleep(($sleep_sec > 0 ? $sleep_sec : 5));