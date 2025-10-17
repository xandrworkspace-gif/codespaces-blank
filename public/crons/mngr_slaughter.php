<? # $Id: mngr_slaughter.php,v 1.50 2010-01-15 09:50:10 p.knoblokh Exp $
/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/

ini_set('memory_limit', '256M');

chdir("..");
require_once("include/common.inc");

set_time_limit(60);
$stime1 = time();

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) {
	sleep(MNGR_SLAUGHTER_INTERVAL);
	return;
}

require_once("lib/slaughter.lib");
require_once("lib/party.lib");
require_once("lib/instance.lib");
require_once("lib/bonus.lib");
require_once("lib/fight.lib");
require_once("lib/global_event.lib");

$slaughter_users = slaughter_user_list(array('status' => array(SLAUGHTER_USER_STATUS_PENDING,SLAUGHTER_USER_STATUS_WAITING)), ' order by id');
print_r($slaughter_users);
// Удаляем просроченные заявки
$user_ids = array();
foreach ($slaughter_users as $k => $user)
	if (($user['dtime'] > 0) && ($user['dtime'] < $stime1) && ($user['status'] == SLAUGHTER_USER_STATUS_WAITING)) {
		$user_ids[] = $user['user_id'];
		// Исключение из списка просроченных
		unset($slaughter_users[$k]);
	}
if ($user_ids) chat_msg_send_system(translate('Время, отведенное на принятие заявки, истекло. Вы не сможете повторно подать заявку в течение часа.'),CHAT_CHF_USER,$user_ids,true);

slaughter_user_delete(array('status' => SLAUGHTER_USER_STATUS_LOCKED),sql_pholder(" AND (stime<=? OR dtime<=?)",(time_current()-SLAUGHTER_LOCK_COOLDOWN),time_current()));

// Берем всех, кто не подтвердил и логируем их, после этого даем пенальти одним запросом.
$slaughter_user_lists = slaughter_user_list(false,sql_pholder(" AND status=? AND dtime BETWEEN 0 AND ?",SLAUGHTER_USER_STATUS_WAITING,$stime1));
foreach ($slaughter_user_lists as $slaughter_user) {
	// лог-сервис -----------------------
	logserv_log_note(array(
		'note' => translate('Не подтвердил участие в Бойнях'),
		'comment' => sprintf('slaughter_id=%d',$slaughter_user['slaughter_id']),
	),$slaughter_user['user_id']);
	// ----------------------------------

	bg_stat_save(array(
		'bg_id' => $slaughter_user['slaughter_id'],
		'user_id' => $slaughter_user['user_id'],
		'stime' => time_current(),
		'user_level' => $slaughter_user['user_level'],
		'kind' => 0,
		'type' => BG_STAT_TYPE_SLAUGHTER,
	));
}
slaughter_user_save(array(
	'_add' => sql_pholder(" AND status=? AND dtime BETWEEN 0 AND ?",SLAUGHTER_USER_STATUS_WAITING,$stime1),
	'_set' => sql_pholder('dtime = ?, status = ?',$stime1+SLAUGHTER_LOCK_ORDER,SLAUGHTER_USER_STATUS_LOCKED),
));

// Исключение из списка просроченных сессий
$user_ids = get_hash($slaughter_users,'user_id','user_id');
$session_hash = array();
if ($user_ids) foreach ($NODE_NUMS as $nn) {
	if ($nn == FRIDGE_NN) continue;
	NODE_PUSH($nn);
	$session_hash = array_merge($session_hash,session_list(null,null,true,sql_pholder(' AND uid IN (?@)',$user_ids),'uid',true));
	NODE_POP();
}
$session_hash = get_hash($session_hash,'uid','uid');
$delete_user_ids = array();
$delete_user_ids_without_penalty = array();
foreach ($slaughter_users as $k => $user)
	if (!$session_hash[$user['user_id']]) {
		if ($user['status'] == SLAUGHTER_USER_STATUS_PENDING)
			$delete_user_ids[] = $user['user_id'];
		else
			$delete_user_ids_without_penalty[] = $user['user_id'];
		unset($slaughter_users[$k]);
		unset($user_ids[$user['user_id']]);
	}
if ($delete_user_ids) slaughter_user_save(array(
	'_add' => sql_pholder(" AND user_id IN (?@)",$delete_user_ids),
	'_set' => sql_pholder('dtime = ?, status = ?',$stime1+SLAUGHTER_LOCK_ORDER,SLAUGHTER_USER_STATUS_LOCKED),
));
if ($delete_user_ids_without_penalty) slaughter_user_delete(array('user_id' => $delete_user_ids_without_penalty));

$slaughter_hash = make_hash(slaughter_list());
$party_member_hash = $user_ids ? get_hash(party_member_list(null,PM_STATUS_ACTIVE,sql_pholder(' AND user_id IN (?@)',$user_ids)),'user_id','party_id') : array();

// Пользователи для подключения к инстансам
$slaughter_users_ready = array();

// Работа с текущими инстансами
do {
	$instance_hash = make_hash(instance_list(array('parent_id' => 0, 'bg_active' => 1), ' AND slaughter_id > 0'));
	if (!$instance_hash) break;
	$instance_users = instance_user_list(array('instance_id' => array_keys($instance_hash)),' ORDER BY death_time DESC, dmg DESC');
	if (!$instance_users) break;
	// Отключение пользователей
	foreach ($instance_users as $k=>$instance_user) {
		$user_id = $instance_user['user_id'];
		$slaughter = $slaughter_hash[$instance_hash[$instance_user['instance_id']]['slaughter_id']];
		$user = user_get($user_id);
		// Не штрафуем тех, кто уже не в инстансе или в другом инстансе
		if (!$user['instance_id']) continue;
		$instance = instance_get_root($user['instance_id']);
		if ($instance['id'] != $instance_user['instance_id']) continue;
		$is_online = user_is_online($user_id, true);
		if ($is_online || $user['fight_id']) continue; // не отключаем тех, кто пока в бою
		NODE_PUSH(null, $user['id']);
		bonus_apply($user,$slaughter['exit_bonus_id']);
		NODE_POP();
		instance_user_delete($instance_user['id']);
		slaughter_user_save(array(
			'_mode' => CSMODE_REPLACE,
			'slaughter_id' => $slaughter['id'],
			'user_id' => $user_id,
			'status' => SLAUGHTER_USER_STATUS_LOCKED,
			'stime' => time_current(),
		));
		//user_change_chat_channels($user, array('instance_id' => 0, 'raid_id' => 0));
		user_save(array(
			'id' => $user_id,
			'_set' => sql_pholder('area_ftime = 0, instance_id = 0, area_id = ?, area_id_transfer = 0, raid_id = 0',
						$user['area_id_transfer'] ? $user['area_id_transfer'] : $user['area_id']),
		));

        //Отключаем глушилку
        user_save(array(
            'id' => $user_id,
            '_set' => sql_pholder('flags = flags & ~?',USER_FLAG_DEAF),
        ));

        user_resurrect($user_id);
		unset($instance_users[$k]);
	}
	
	foreach ($instance_hash as $instance) {
		$slaughter = $slaughter_hash[$instance['slaughter_id']];
		if (!$slaughter) continue;
		// || !slaughter_lock($slaughter['id'])) continue;
		$dtime = $instance['dtime'];
		$user_ids = array();
		$user_ids_active = array();
		foreach ($instance_users as $instance_user) {
			if ($instance_user['instance_id'] == $instance['id']) {
				$user_ids[] = $instance_user['user_id'];
				if (!$instance_user['death_cnt']) $user_ids_active[] = $instance_user['user_id'];
			}
		}
		
		// Проверяем условие окончания
		$time_left = $dtime - time_current();
		if (($time_left > 0) && ($time_left <= INSTANCE_WARNING_TIME) && (($time_left % 60) <= MNGR_SLAUGHTER_INTERVAL)) {
			chat_msg_send_system(sprintf(translate('До принудительного окончания битвы "%s" осталось %s.'),$slaughter['title'],html_period_str($time_left)),CHAT_CHF_USER,$user_ids_active,true);
		}
		$cond_fights = false; //Достигнут предел на количество боёв
		$instance_locations = get_hash(instance_list(array('parent_id' => $instance['id'])));
		$cond_fights = (fight_count(null, array_keys($instance_locations), FIGHT_STATUS_FINISHED) >= SLAUGHTER_MAX_FIGHTS);
		$cond_time = ($time_left <= 0);	// закончилось время
		if ($cond_fights || $cond_time) {	// условие окончания БГ
			$user_last = $chat_text = array();
			$win_position = 1;
			$winner_teams = ''; // сюда положим номера победивших пользователей, склеив как строку, а сохраним как число
			common_fldsort($instance_users, true, 'honor');
			foreach ($instance_users as $k => $instance_user) {
				if (($instance_user['instance_id'] != $instance['id']) || !session_lock($instance_user['user_id'])) continue;
				unset($instance_users[$k]);
				$user = user_get($instance_user['user_id']);
				if (!$user) {
					session_unlock($instance_user['user_id']);
					continue;
				}
				NODE_PUSH(null,$instance_user['user_id']);
				
				$user_instance = instance_get($user['instance_id']);
				if ($user_instance['root_id'] == $instance['id']) {
					// Если пользователь в инстансе
					if ($cond_fights && ($win_position <= 5)) {
						// Выдаём призовые бонусы пятёрке пользователей, набившей больше всего доблести
						if ($win_position == 1) $user_last = $user;
						else $chat_text[] = html_user_info($user);
						bonus_apply($user,$slaughter['win_bonus_id_'.$win_position]);
						$winner_teams .= $instance_user['team'];
						++$win_position;
					}
					//user_change_chat_channels($user, array('instance_id' => 0, 'raid_id' => 0));
					user_save(array(
						'id' => $user['id'],
						'_set' => sql_pholder('area_ftime = 0, instance_id = 0, area_id = ?, area_id_transfer = 0, raid_id = 0',
								$user['area_id_transfer'] ? $user['area_id_transfer'] : $user['area_id']),
					));

                    user_save(array(
                        'id' => $user['id'],
                        '_set' => sql_pholder('flags = flags & ~?',USER_FLAG_DEAF),
                    ));

					user_resurrect($user);
					// отменяем бои
					$fight_id = $user['fight_id'];
					if ($fight_id && fight_lock($fight_id)) {
						fight_abort($fight_id);
						fight_unlock($fight_id);
					}
				}
				// Выдача бонуса всем участникам инстанса
				bonus_apply($user,$slaughter['finish_bonus_id']);
				NODE_POP();
				session_unlock($user['id']);
			}
			instance_save(array(
				'id' => $instance['id'],
				'bg_active' => 0,
				'dtime' => time_current() + INSTANCE_LEAVE_TIME, // Добавляем время, сколько должна храниться статистика
				'winner_team' => (int)$winner_teams,
			));
			// Удаляем данные инста, которые не нужны после его окончания, останется только то, что нужно в отображении статистики
			instance_pre_delete($instance);

			if ($slaughter['flags'] & SLAUGHTER_FLAG_WRITE_STAT) {
				$metric_level = $instance['level_max'];
				if ($metric_level) metric_group_add(METRIC_TYPES_PVP_INST, array('slaughter_id' => $slaughter['id'],'level' => $metric_level), array('slaughter_inst_finish' => 1));
			}

			if ($cond_time) {
				$msg_text = sprintf(translate('Время, отведенное на битву "<a href="javascript: show_slaughter_stat(%d,1)">%s</a>" , истекло.'),$instance['id'],$slaughter['title']);
			} else {
				$msg_text = sprintf(translate('Битва "<a href="javascript: show_slaughter_stat(%d,1)">%s</a>" окончена. Победителем признан %s. Завидную стойкость проявили: %s.'),$instance['id'],$slaughter['title'],html_user_info($user_last),implode(', ',$chat_text));
			}
			chat_msg_send_system($msg_text,CHAT_CHF_USER,$user_ids,true);
			chat_msg_send_special(CODE_REDIRECT,CHAT_CHF_USER,$user_ids_active,array('url' => 'instance_stat.php?instance_id='.$instance['id'].'&finish=1'));
		}
//		slaughter_unlock($slaughter['id']);
	}
} while(0);
// Формирование списка игроков по возможным инстансам
$slaughter_user_hash = array();
foreach($slaughter_users as $slaughter_user) $slaughter_user_hash[$slaughter_user['slaughter_id'].'_'.$slaughter_user['user_level']][] = $slaughter_user;

// Формирование новых инстансов
foreach ($slaughter_user_hash as $slaughter_info => $slaughter_users_cur) {
	$slaughter_info_list = preg_split("/_/",$slaughter_info);
	if ($slaughter_info_list[0]) $slaughter = $slaughter_hash[$slaughter_info_list[0]]; else continue;
	if (!$slaughter || !slaughter_lock($slaughter['id'])) continue;
	$level = $slaughter_users_cur[0]['user_level'];
	$slaughter_start = $slaughter_init = false;
	$orders_checked = 0;
	// Проверка, не пора ли стартовать БГ
	foreach ($slaughter_users_cur as $k => $slaughter_user) {
		if ($slaughter_user['dtime']) $slaughter_init = true;
		if ($slaughter_user['status'] != SLAUGHTER_USER_STATUS_PENDING) continue;
		$orders_checked++;
		if ($slaughter_user['dtime'] && ($slaughter_user['dtime'] <= $stime1)) {
			$slaughter_start = true;
		}
	}
	if ($orders_checked == $slaughter['team_number']) $slaughter_start = true;
	// Создаём новый инстанс и подключаем к нему пользователей
	if ($slaughter_start) {
		if ($orders_checked >= ($slaughter['team_number'] - 1)) {
			// Начинаем инстанс с теми кто есть
			$instance_id = instance_create($slaughter['inst_artikul_id'],array('bg_active' => 1, 'slaughter_id' => $slaughter['id'], 'level_min' => $level, 'level_max' => $level));
			$instance_hash[$instance_id] = instance_get($instance_id);
			if (!$instance_id) {
				error_log("(cron_slaughter: ".getmypid()."): Can't create battleground: ".$slaughter['id']);
				slaughter_unlock($slaughter['id']);
				continue;
			}

			foreach ($slaughter_users_cur as $slaughter_user)
				if ($slaughter_user['status'] == SLAUGHTER_USER_STATUS_PENDING) {
					$slaughter_user['instance_id'] = $instance_id;
					$slaughter_users_ready[] = $slaughter_user;
				}
		} else {
		// Не подтвердило необходимое количество участников
			$user_ids = array();
			foreach ($slaughter_users_cur as $slaughter_user)
				if ($slaughter_user['dtime']) $user_ids[] = $slaughter_user['user_id'];
			if ($user_ids) {
				chat_msg_send_system(translate('Для начала битвы не были выполнены необходимые условия'), CHAT_CHF_USER, $user_ids);
				slaughter_user_save(array(
					'_add' => sql_pholder(' AND slaughter_id = ? AND user_level = ? AND status = ?', $slaughter['id'], $level, SLAUGHTER_USER_STATUS_PENDING),
					'_set' => sql_pholder('dtime = NULL, status = ?', SLAUGHTER_USER_STATUS_WAITING),
				));
			}
		}
	} elseif (!$slaughter_init) {
		// Рассылаем приглашения в новый инстанс, если есть свободные места
		$queue = array();
		foreach ($slaughter_users_cur as $k => $slaughter_user) {
			if (!$slaughter_user['dtime']) $queue[] = $slaughter_user;
			if (count($queue) == $slaughter['team_number']) break;
		}
		
		// Рассылка приглашений
		if (count($queue) == $slaughter['team_number']) {
			foreach($queue as $order) {
				slaughter_user_save(array('id' => $order['id'], 'dtime' => time_current() + SLAUGHTER_PENDING_TTL));
				$user = user_get($order['user_id']);
				chat_msg_send_system(sprintf(translate('<b class="redd">Для того, чтобы подтвердить свое участие в поле битвы, нажмите</b> <b><a href="javascript: confirm_slaughter(%s)">СЮДА</a></b>'),$slaughter['t'.$user['kind'].'_area_id']), CHAT_CHF_USER, $order['user_id']);
			}
		}
	}
	slaughter_unlock($slaughter['id']);
}
	
// Подключение персонажей к инстансам
$user_ids = $slaughter_users_for_instance_hash = array();
foreach ($slaughter_users_ready as $slaughter_user) {
	$slaughter_users_for_instance_hash[$slaughter_user['instance_id']][] = $slaughter_user;
	$user_ids[] = $slaughter_user['user_id'];
}
$user_hash = $user_ids ? make_hash(user_list(array('id' => $user_ids))) : array();

foreach ($slaughter_users_for_instance_hash as $instance_id => $slaughter_users_cur) {
	if (!instance_lock($instance_id)) continue;
	$instance = instance_get($instance_id);
	$slaughter = $slaughter_hash[$instance['slaughter_id']];
	
	$instances = instance_list(array('root_id' => $instance_id));
	foreach($instances as $k => $instance) $instances[$k]['object_class'] = OBJECT_CLASS_INSTANCE;
	skill_objects_list($instances," AND skill_id = 'INITROOMSPACE'");
	// Поиск в локациях инстанса скилла INITROOMSPACE, который указывает на допустимое количество пользователей в локации
	$places = array();
	$places_cnt = 0;
	foreach($instances as $instance) {
		if (!count($instance['artifact_skills']) || !$instance['artifact_skills']['INITROOMSPACE']['value']) continue;
		$places[$instance['id']] = $instance['artifact_skills']['INITROOMSPACE']['value'];
		$places_cnt += $instance['artifact_skills']['INITROOMSPACE']['value'];
	}

    $slaughter_user_ids = array();
	foreach ($slaughter_users_cur as $k => $slaughter_user) {
		$user = $user_hash[$slaughter_user['user_id']];
		// Проверка ограничений и если не проходят, то выставить флаг не выполнять бонус окончания
		$slaughter['object_class'] = OBJECT_CLASS_SLAUGHTER;
		$out_restriction = restriction_check(0,array($slaughter),array($user));

		// Определение местоположения в инстансе
		$position = rand(1,$places_cnt);
		foreach ($places as $place => $num) {
			$position -= $num;
			if ($position <= 0) {
				$place_id = $place;
				$places[$place]--;
				$places_cnt--;
				break;
			}
		}

		instance_user_save(array(
			'_mode' => CSMODE_REPLACE,
            'instance_artikul_id' => $instance['artikul_id'],
			'instance_id' => $instance_id,
			'user_id' => $slaughter_user['user_id'],
			'team' => ($k + 1),
			'flags' => ($out_restriction['status'] == RESTRICTION_STATUS_ALLOW) ? 0 : INST_USER_FLAG_NO_FINISH_BONUS,
		));
		
		//user_change_chat_channels($user, array('instance_id' => $place_id, 'raid_id' => $instance_id + $k));
		user_save(array(
			'id' => $slaughter_user['user_id'],
			'_set' => sql_pholder('instance_id = ?, raid_id = ?, area_ftime = 0, invisibility_time = 0',$place_id,$instance_id + $k),
		));

        //Ставим глухого
        user_save(array(
            'id' => $slaughter_user['user_id'],
            '_set' => sql_pholder('flags = flags | ?',USER_FLAG_DEAF),
        ));

        $user_current = user_get($slaughter_user['user_id']);
        //Выдадим бонус входа
        if($slaughter['enter_bonus_id']) bonus_apply($user_current, $slaughter['enter_bonus_id']);
		
		chat_msg_send_special(CODE_CALL_JSFUNC, CHAT_CHF_USER, $slaughter_user['user_id'], array('func' => "updateSwf({'lvl':''})"));
		
		user_resurrect($slaughter_user['user_id']);

        $slaughter_user_ids[$slaughter_user['user_id']] = $slaughter_user['user_id'];
	}

    //Участие в Пб триггер
    _global_event_trigger(GLOBAL_EVENT_TYPE_GO_PB, 1, $slaughter_user_ids);

	instance_unlock($instance_id);
}
if ($user_ids) {
	slaughter_user_delete(array('user_id' => $user_ids));
	chat_msg_send_special(CODE_REDIRECT,CHAT_CHF_USER,$user_ids,array('url' => 'instance.php', 'flag' => '__instance_php__'));
	chat_msg_send_special(CODE_CALL_JSFUNC,CHAT_CHF_USER,$user_ids,array('func' => "updateSwf({'lvl':''})"));
}

$stime2 = time();
$rtime = $stime2-$stime1;
if ($rtime > MNGR_SLAUGHTER_INTERVAL) error_log("(mngr_slaughter: ".getmypid()."): Runtime $rtime sec");
sleep(max(MNGR_SLAUGHTER_INTERVAL-$rtime,0));

?>