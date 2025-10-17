<? # $Id: mngr_bg.php,v 1.127 2010-03-03 11:58:37 p.knoblokh Exp $
/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/
chdir("..");
require_once("include/common.inc");

set_time_limit(60);
$stime1 = time();

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) {
	sleep(MNGR_BG_INTERVAL);
	return;
}

require_once("lib/bg.lib");
require_once("lib/party.lib");
require_once("lib/instance.lib");
require_once("lib/bonus.lib");
require_once("lib/fight.lib");
require_once("lib/crossserver.lib");
require_once("lib/metric.lib");
require_once("lib/activity.lib");


$copies = defined('MNGR_BG_COPIES') ? MNGR_BG_COPIES : 1;
$copy = intval($_SERVER['argv'][1]);
if ($copy < 1 || $copy > $copies) {
	sleep(MNGR_BG_INTERVAL);
	die('Copy is not defined!');
}
$copy = $copy - 1;

$bg_hash = make_hash(bg_list());

// Работа с текущими инстансами
do {
	$add = ' and !(flags & '.INST_FLAG_PREPARED.') AND bg_id > 0 AND id % '.$copies.' = '.$copy;
	
	$instance_hash = make_hash(instance_list(array('parent_id' => 0, 'bg_active' => 1), $add));
	if (!$instance_hash) break;
	$instance_users = make_hash(instance_user_list(array('instance_id' => array_keys($instance_hash))),'user_id');
	if (!$instance_users) break;
	$sessions = array();
	foreach($NODE_NUMS as $nn) {
		if ($nn == FRIDGE_NN) continue;
		NODE_PUSH($nn);
		$tmp_sessions = session_list(null,null,true,sql_pholder(' AND uid IN (?@)',array_keys($instance_users)),'uid',true);
		foreach($tmp_sessions as $tmp_session) {
			$sessions[] = $tmp_session;
		}
		NODE_POP($nn);
	}
	$session_hash = get_hash($sessions,'uid','uid');
	$instance_party_member_hash = get_hash(party_member_list(null, PM_STATUS_ACTIVE, sql_pholder(' AND user_id IN (?@)', array_keys($instance_users))), 'user_id', 'party_id');
	$auth_hash = make_hash(auth_list(false, array('uid' => array_keys($instance_users))),'uid');
	$team_sizes = array();
	// Отключение пользователей
	foreach ($instance_users as $k=>$instance_user) {
		$user_id = $instance_user['user_id'];
		$bg = $bg_hash[$instance_hash[$instance_user['instance_id']]['bg_id']];
		if (!$session_hash[$user_id]) $user = user_get($user_id);
		if ($session_hash[$user_id] || $user['fight_id']) { // не отключаем тех, кто пока в бою
			$team_sizes[$instance_user['instance_id']][$instance_user['team']]++;
			continue;
		}
		instance_user_delete($instance_user['id']);
		if (!($bg['flags'] & BG_FLAG_NO_OUT_PENALTY)) bg_user_save(array(
			'_mode' => CSMODE_REPLACE,
			'bg_id' => $bg['id'],
			'user_id' => $user_id,
			'status' => BG_USER_STATUS_LOCKED,
			'stime' => time_current(),
		));
		user_resurrect($user_id);
		if ($bg['exit_bonus_id']) {
			NODE_PUSH(null, $user_id);
			bonus_apply($user,$bg['exit_bonus_id']);
			NODE_POP();
		}
		if ($auth_hash[$user_id]['server_id'] && ($auth_hash[$user_id]['server_id'] != SERVER_ID)) {
			crossserver_user_return($user_id);
			if (!($bg['flags'] & BG_FLAG_NO_OUT_PENALTY)) {
				crossserver_bg_penalty($auth_hash[$user_id]['server_id'], $auth_hash[$user_id]['original_user_id'], $bg['id']);
			}
		} else {
			$user_current = user_get($user_id);
			$user_param = array(
				'id' => $user_id,
				'instance_id' => 0,
				'area_id' => $user_current['area_id_transfer'] ? $user_current['area_id_transfer'] : $user_current['area_id'],
				'area_id_transfer' => 0,
				'raid_id' => 0,
				'area_ftime' => 0,
			);
			//user_change_chat_channels($user_current, $user_param);
			user_save($user_param);
		}

        //Отключаем глушилку хаотических
        user_save(array(
            'id' => $user_id,
            '_set' => sql_pholder('flags2 = flags2 & ~?',USER_FLAG2_DEAF),
        ));

		unset($instance_users[$k]);
	}
	
	foreach ($instance_hash as $instance) {
		$bg = $bg_hash[$instance['bg_id']];
		if (!$bg || !instance_lock($instance['id'])) continue;
		$dtime = $instance['dtime'];
		$user_ids = array();
		foreach ($instance_users as $instance_user) {
			if ($instance_user['instance_id'] == $instance['id']) {
				$user_ids[] = $instance_user['user_id'];
			}
		}
		
		// Проверяем не стоит ли закрыть БГ на вход
		$teams_count = count($team_sizes[$instance['id']]);
		$bg_closed = ($teams_count < 2) || ($instance['flags'] & INST_FLAG_BGCLOSED);
		if ($bg_closed && !($instance['flags'] & INST_FLAG_BGCLOSED)) {
			$dtime = min($dtime, time_current() + $instance['life_time'] * 0.1);
			instance_save(array(
				'id' => $instance['id'],
				'dtime' => $dtime,
				'flags' => $instance['flags'] | INST_FLAG_BGCLOSED,
			));
			no_translate_push(1);
			chat_msg_send_system(translate('В связи с отсутствием участников в одной из команд битва считается закрытой.'),CHAT_CHF_USER,$user_ids,true,array('do_translate' => true));
			no_translate_pop();
		}
		
		if (defined('GLADIATORS_SERVER') && GLADIATORS_SERVER) {
			$instance_artikul = instance_artikul_get($instance['artikul_id']);
			// Если идет кулдаун локации
			$area_ftime = $instance['dtime'] - $instance_artikul['life_time'] + ($bg['start_delay'] > 0 ? $bg['start_delay'] : 60);
			$time_left = $area_ftime - time_current();
			if (($time_left > 0) && (($time_left % 60) <= MNGR_BG_INTERVAL)) {
				no_translate_push(1);
				chat_msg_send_system(translate('Время до начала поля битвы "{0}": {1}". У вас есть время облачиться в доспехи и приготовиться к бою.'),CHAT_CHF_USER,$user_ids,true,array(
					'do_translate' => true,
					'translate_params' => array($bg['title'],$time_left),
					'translate_params_apply_func' => array(0 => 'translate', 1 => 'html_period_str')
				));
				no_translate_pop();
			}
		}
		
		// Проверяем условие окончания
		$time_left = $dtime - time_current();
		if (($time_left > 0) && ($time_left <= INSTANCE_WARNING_TIME) && (($time_left % 60) <= MNGR_BG_INTERVAL)) {
			no_translate_push(1);
			chat_msg_send_system(translate('До принудительного окончания битвы "{0}" осталось {1}.'),CHAT_CHF_USER,$user_ids,true,array(
				'do_translate' => true,
				'translate_params' => array($bg['title'],$time_left),
				'translate_params_apply_func' => array(0 => 'translate', 1 => 'html_period_str')
			));
			no_translate_pop();
		}
		$skills = get_hash(skill_object_list(OBJECT_CLASS_INSTANCE,$instance," AND (skill_id IN ('".$bg['t1_skill_id']."','".$bg['t2_skill_id']."'))"),'skill_id','value');
		$score = array(1 => intval($skills[$bg['t1_skill_id']]), 2 => intval($skills[$bg['t2_skill_id']]));
		$cond_score = ($bg['max_score'] <= max($score));	// какая-то команда набрала необходимое кол-во очков
		$cond_time = ($time_left <= 0);	// закончилось время
		
		if ($cond_score || $cond_time) {	// условие окончания БГ
			$winner_team = ($score[1] > $score[2] ? 1 : ($score[1] < $score[2] ? 2 : 0));
			if ($bg_closed && !$cond_score && $teams_count < 2) $winner_team = 0;
			no_translate_push(1);
			if ($winner_team) {
				$msg_text = translate('Битва "{0}" окончена со счетом [<b class="team_1">{1}</b> : <b class="team_2">{2}</b>] (победили {3}). <a target="_blank" href="/instance_stat.php?outside=1&instance_id={4}&server_id={5}">Посмотреть результаты</a>.');
			} else {
				$msg_text = translate('Битва "{0}" окончена со счетом [<b class="team_1">{1}</b> : <b class="team_2">{2}</b>] (ничья{3}). <a target="_blank" href="/instance_stat.php?outside=1&instance_id={4}&server_id={5}">Посмотреть результаты</a>.');
			}
			$msg_params = array(
				$bg['title'],
				$score[2],
				$score[1],
				($winner_team ? $kind_info[$winner_team]['title'] : ''),
				$instance['id'],
				SERVER_ID
			);
			no_translate_pop();
			// Суммарная доблесть по командам и массив доблестей по группам
			$instance_team_honor = array();
			$party_honor = array();
			foreach ($instance_users as $instance_user) {
				if ($instance_user['instance_id'] != $instance['id']) continue;
				$instance_team_honor[$instance_user['team']] += $instance_user['honor'];
				if (!isset($instance_party_member_hash[$instance_user['user_id']])) continue;
				$party_id = $instance_party_member_hash[$instance_user['user_id']];
				$party_honor[$party_id][] = $instance_user['honor'];
			}
			// Доблесть участника группы делится поровну между участниками
			$party_member_honor = array();
			foreach ($party_honor as $party_id => $honors) {
				$party_member_honor[$party_id] = array_sum($honors) / count($honors);
			}
			foreach ($instance_users as $k => $instance_user) {
				if (($instance_user['instance_id'] != $instance['id']) || !session_lock($instance_user['user_id'])) continue;
				$user = user_get($instance_user['user_id']);
				if (!$user) {
					session_unlock($instance_user['user_id']);
					continue;
				}
				NODE_PUSH(null, $instance_user['user_id']);
				user_resurrect($instance_user['user_id']);
				unset($instance_users[$k]);
				$bonus_id = $bg['draw_bonus_id'];
				$skill = 'HONOR'; // can become various
				$skill_value = $bg['skill_draw'];
				if ($winner_team) {
					if ($instance_user['team'] == $winner_team) {
						$bonus_id = $bg['win_bonus_id'];
						$skill_value = $bg['skill_win'];
					} else {
						$bonus_id = $bg['loss_bonus_id'];
						$skill_value = $bg['skill_loss'];
					}
				} else if ($teams_count < 2) { // одна из команд сбежала -- "техническая ничья"
					$bonus_id = $bg['tech_draw_bonus_id'];
					$skill_value = $bg['skill_tech_draw'];
				}

				if ($winner_team && ($instance_user['team'] == $winner_team)) {
					// собираем статистику
					require_once('lib/user_stat.lib');
					user_stat_update($user['id'], USER_STAT_TYPE_BG, $instance['bg_id']);
				}

				bonus_apply($user, $bonus_id);
				// Начисление характеристики пропорционально набитой доблести
				if (isset($instance_party_member_hash[$user['id']])) { // игрок был в группе
					$party_id = $instance_party_member_hash[$user['id']];
					$user_honor = $party_member_honor[$party_id];
				} else {
					$user_honor = $instance_user['honor'];
				}
				if ($user_honor && $instance_team_honor[$instance_user['team']]) {
					$skill_multiplier = $user_honor / $instance_team_honor[$instance_user['team']];
					$skill_level_group = ceil($user['level'] / 2);
					$skill_level_coef = max(1, pow(2, ceil($skill_level_group / 2 - 1)) * ($skill_level_group % 2 ? 0.7 : 1.0));
					$user_skill_value = $skill_value * $skill_multiplier * $skill_level_coef;
					if ($skill == 'HONOR') {
						$status = user_stat_update($user['id'], USER_STAT_TYPE_SKILL, USER_STAT_SKILL_HONOR, $user_skill_value);
					} else {
						$status = skill_object_set_value(OBJECT_CLASS_USER, $user['id'], $skill, $user_skill_value, array('relative' => true));
					}

					if ($status) {
						$instance_root = instance_get_root($instance_user['instance_id']);
						instance_user_save(array(
							'_set' => sql_pholder(' honor = honor + ?', intval($user_skill_value)),
							'_add' => sql_pholder(' AND instance_id = ? AND user_id = ?', $instance_root['id'], $user['id']),
						));
					}
				}
				if (!($instance_user['flags'] & INST_USER_FLAG_NO_FINISH_BONUS) && $bg['finish_bonus_id']) {
					bonus_apply($user, $bg['finish_bonus_id']);
				}
				// отменяем бои
				$fight_id = $user['fight_id'];
				if ($fight_id && fight_lock($fight_id)) {
					fight_abort($fight_id);
					fight_unlock($fight_id);
				}
				$auth = array();
				if ($instance['flags'] & INST_FLAG_CROSSSERVER) {
					$auth = auth_get($user['id']);
				}
				if ($auth['server_id'] && ($auth['server_id'] != SERVER_ID)) {
					crossserver_user_return($user['id']);
					$msg = array(
						'type' => CHAT_MSG_TYPE_SYSTEM,
						'msg_text' => $msg_text,
						'param' => array('do_translate' => true, 'translate_params' => $msg_params, 'translate_params_apply_func' => array(0 => 'translate'))
					);
					crossserver_chat_send($auth['server_id'],$msg,CHAT_CHF_USER,array($auth['original_user_id']));
				} else {
					$user_current = user_get($instance_user['user_id']);
					$user_param = array(
						'id' => $instance_user['user_id'],
						'instance_id' => 0,
						'area_id' => $user_current['area_id_transfer'] ? $user_current['area_id_transfer'] : $user_current['area_id'],
						'area_id_transfer' => 0,
						'raid_id' => 0,
						'area_ftime' => 0,
					);
					//user_change_chat_channels($user_current, $user_param);
					user_save($user_param);
				}

                //Отключаем глушилку хаотических
				if($user['id']) {
                    user_save(array(
                        'id' => $user['id'],
                        '_set' => sql_pholder('flags2 = flags2 & ~?', USER_FLAG2_DEAF),
                    ));
                }

				#logfile(DEBUG_FILE_LOG_DEV, print_r($user,true));
                #logfile(DEBUG_FILE_LOG_DEV, print_r($bg,true));
                activity_user_check(ACTIVITY_STAT_BG,$bg['id'],$user,false); //Активность участника BG
                if($instance_user['team'] == $winner_team){
                    //Активность победителя BG
                    activity_user_check(ACTIVITY_STAT_BG_WIN,$bg['id'],$user,false); //Активность участника BG
                }

				NODE_POP();
				session_unlock($user['id']);
			}
			instance_save(array(
				'id' => $instance['id'],
				'bg_active' => 0,
				'dtime' => time_current() + INSTANCE_LEAVE_TIME, // Добавляем время, сколько должна храниться статистика
				'winner_team' => $winner_team,
			));
			// Удаляем данные инста, которые не нужны после его окончания, останется только то, что нужно в отображении статистики
			instance_pre_delete($instance);
			bg_user_save(array(
				'_add' => sql_pholder(' AND instance_id = ?',$instance['id']),
				'_set' => sql_pholder('dtime = NULL, status = ?, instance_id = 0',BG_USER_STATUS_WAITING),
			));
			if ($instance['flags'] & INST_FLAG_CROSSSERVER) {
				$bg_servers = bg_level_parse_teams_servers($instance['bg_level_server_group']);
				
				foreach($SERVERS as $server_id => $server) {
					if ($server_id == SERVER_ID) continue;
					if (!in_array($server_id, $bg_servers[KIND_HUMAN]) && !in_array($server_id, $bg_servers[KIND_MAGMAR])) continue;
					crossserver_clear_bg_queue($server_id, 0, array(), $instance['id']);
				}
			}
			chat_msg_send_system($msg_text,CHAT_CHF_USER,$user_ids,true,array(
				'do_translate' => true,
				'translate_params' => $msg_params,
				'translate_params_apply_func' => array(0 => 'translate'),
			));
			chat_msg_send_special(CODE_REDIRECT,CHAT_CHF_USER,$user_ids,array('url' => 'instance_stat.php?instance_id='.$instance['id'].'&finish=1'));
			$bg_closed = true;
			
			if ($bg['flags'] & BG_FLAG_WRITE_STAT) {
				$metric_level = $instance['level_max'];
				if ($metric_level) metric_group_add(METRIC_TYPES_PVP_INST, array('bg_id' => $bg['id'],'level' => $metric_level), array('pvp_inst_finish' => 1));
			}
		}
		
		instance_unlock($instance['id']);
	}
} while (0);
$stime2 = time();
$rtime = $stime2-$stime1;
if ($rtime > MNGR_BG_INTERVAL) error_log("(mngr_bg ".$copy.": ".getmypid()."): Runtime $rtime sec");

sleep(max(MNGR_BG_INTERVAL-$rtime,0));

?>
