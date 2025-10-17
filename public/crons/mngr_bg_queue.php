<?php
chdir("..");
require_once("include/common.inc");

set_time_limit(60);
$stime1 = time();

common_define_settings();
print_r($SERVERS);
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
require_once("lib/global_event.lib");

$copies = defined('MNGR_BG_QUEUE_COPIES') ? MNGR_BG_QUEUE_COPIES : 1;
$copy = intval($_SERVER['argv'][2]);
if ($copy < 1 || $copy > $copies) {
	//sleep(MNGR_BG_INTERVAL);
	die('Copy is not defined!');
}
$copy = $copy - 1;


$bg_user_flags = 0;
$add = '';

$crossserver = false;
if (isset($_SERVER['argv'][1]) && ($_SERVER['argv'][1] == 'crossserver')) {
	$crossserver = true;
	$bg_user_flags |= BG_USER_FLAG_CROSSSERVER;
	$add .= ' and flags & '.$bg_user_flags;
} else {
	$add .= ' and !(flags & '.BG_USER_FLAG_CROSSSERVER.')';
}

$bg_hash = make_hash(bg_list());
$bg_levels = make_hash(bg_level_list(), 'bg_id', true);

if ($crossserver) {
	$unset_keys = array();
	foreach ($bg_hash as $k => $v) {
		if ($v['force_start_server'] && $v['force_start_server'] != SERVER_ID) {
			$unset_keys[$k] = $k;
		}
	}
	foreach($unset_keys as $k) unset($bg_hash[$k], $bg_levels[$k]);
	
	$bound_servers = array();
	foreach ($bg_levels as $bg_id => $bg_levels_data) {
		$playable = false;
		
		foreach ($bg_levels_data as $bg_level_key => $bg_level) {
			$playable_level = false;
			$bg_levels[$bg_id][$bg_level_key]['servers'] = array();
			$bg_level_servers_data = array();
			if ($bg_level['servers_data']) {
				$bg_level_servers_data = explode('#', $bg_level['servers_data']);
				foreach ($bg_level_servers_data as $bg_level_servers_team) {
					$bg_level_bound_servers = array();
					$bg_level_team_servers = bg_level_parse_teams_servers($bg_level_servers_team);
					if (isset($bg_level_team_servers[KIND_HUMAN][SERVER_ID]) || isset($bg_level_team_servers[KIND_MAGMAR][SERVER_ID]))
						$bg_levels[$bg_id][$bg_level_key]['servers'][] = $bg_level_team_servers;
				}
				
				if (empty($bg_levels[$bg_id][$bg_level_key]['servers'])) continue;
				
				$playable = $playable_level = true;
				

			}
			if (!$playable_level) {
				unset($bg_levels[$bg_id][$bg_level_key]);
				continue;
			}
		}
		if (!$playable) {
			unset($bg_hash[$bg_id], $bg_levels[$bg_id]);
			continue;
		}
	}
	
	$add .= $bg_hash ? sql_pholder(' AND bg_id IN (?@) ', array_keys($bg_hash)) : ' AND 0';
	
	foreach ($bg_levels as $bg_id => $bg_levels_data) {
		foreach ($bg_levels_data as $bg_level_key => $bg_level) {
			foreach ($bg_level['servers'] as $server_data_key => $server_data) {
				foreach ($server_data as $kind => $servers) {
					foreach ($servers as $server) {
						if (!isset($bg_levels[$bg_id][$bg_level_key]['bound_servers'])) $bg_levels[$bg_id][$bg_level_key]['bound_servers'] = array();
						$bg_levels[$bg_id][$bg_level_key]['bound_servers'][$server] = $server;
						if (!isset($bg_levels[$bg_id][$bg_level_key]['team_bound_servers'][$kind])) $bg_levels[$bg_id][$bg_level_key]['team_bound_servers'][$kind] = array();
						$bg_levels[$bg_id][$bg_level_key]['team_bound_servers'][$kind][$server] = $server;
						
						if (!isset($bg_levels[$bg_id]['bound_servers'])) $bg_levels[$bg_id]['bound_servers'] = array();
						$bg_levels[$bg_id]['bound_servers'][$server] = $server;
						if (!isset($bg_levels[$bg_id]['team_bound_servers'][$kind])) $bg_levels[$bg_id]['team_bound_servers'][$kind] = array();
						$bg_levels[$bg_id]['team_bound_servers'][$kind][$server] = $server;
						
						$bound_servers[$server][$bg_id] = $bg_id;
					}
				}
			}
		}
	}
}

$bg_users = bg_user_list(array('status' => array(BG_USER_STATUS_PENDING, BG_USER_STATUS_WAITING)),$add.' ORDER BY id');
foreach($bg_users as $k => $bg_user) {
	$bg_users[$k]['server_id'] = SERVER_ID;
}

// Исключение из списка просроченных сессий
bg_users_unset_inactive($bg_users);

$bg_users_queue = $bg_users;

if ($crossserver) {
	foreach($bound_servers as $server_id => $server_bgs) {
		if ($server_id == SERVER_ID) continue;
		$tmp_users = crossserver_queue_get($server_id);
		foreach ($tmp_users as $tmp_user) {
			$tmp_user['server_id'] = $server_id;
			$bg_users_queue[] = $tmp_user;
			if (!in_array($tmp_user['bg_id'], $server_bgs)) continue;
			$bg_users[] = $tmp_user;
		}
	}
	// Обновим содержимое кэша, чтобы корректно очередь отображать в интерфейсе
	$cache = new Cache('CROSSSERVER_BG_QUEUE');
	if ($cache->tryLock()) {
		$queue_cache = array();
		foreach($bg_users_queue as $bg_user) {
			if ($bg_user['status'] != BG_USER_STATUS_WAITING) continue;
			if ($bg_user['dtime']) continue;
			$queue_cache[] = $bg_user;
		}
		$cache->update($queue_cache,600);
		$cache->freeLock();
	}
}

// Удаляем просроченные заявки
$user_ids = array();
foreach ($bg_users as $k => $user) {
	if (($user['dtime'] > 0) && ($user['dtime'] < $stime1) && ($user['status'] == BG_USER_STATUS_WAITING)) {
		if ($user['server_id'] == SERVER_ID) {
			$user_ids[] = $user['user_id'];
		}
		// Исключение из списка просроченных
		unset($bg_users[$k]);
	}
}
if ($user_ids) {
	chat_msg_send_system(sprintf(translate('Время, отведенное на принятие заявки, истекло. Вы не сможете повторно подать заявку до %s.'), date('H:i', time()+BG_LOCK_ORDER)), CHAT_CHF_USER, $user_ids, true);
}
bg_user_delete(array('status' => BG_USER_STATUS_LOCKED), sql_pholder(' AND (stime<=? OR dtime<=?)', time_current()-BG_LOCK_COOLDOWN, time_current()));
// Берем всех, кто не подтвердил и логируем их, после этого даем пенальти одним запросом.

$bg_user_lists = bg_user_list(false, sql_pholder(' AND status=? AND dtime BETWEEN 0 AND ?', BG_USER_STATUS_WAITING, $stime1));
foreach ($bg_user_lists as $bg_user) {
	// лог-сервис -----------------------
	logserv_log_note(array(
		'note' => translate('Не подтвердил участие в БГ'),
		'comment' => sprintf('bg_id=%d', $bg_user['bg_id']),
	),$bg_user['user_id']);
	// ----------------------------------

	bg_stat_save(array(
		'bg_id' => $bg_user['bg_id'],
		'user_id' => $bg_user['user_id'],
		'stime' => time_current(),
		'user_level' => $bg_user['user_level'],
		'kind' => $bg_user['user_kind'],
		'type' => BG_STAT_TYPE_BATTLEGROUND,
	));
}
bg_user_save(array(
	'_add' => sql_pholder(' AND status=? AND dtime BETWEEN 0 AND ?', BG_USER_STATUS_WAITING, $stime1),
	'_set' => sql_pholder('instance_id = 0, dtime = ?, status = ?', $stime1+BG_LOCK_ORDER, BG_USER_STATUS_LOCKED),
));

// Пользователи для подключения к инстансам
$bg_users_ready = array();
$bg_users_link = array();

$current_bg_active = 0;
// Блок добивания в текущие инсты
do {
	$add = ' and !(flags & '.INST_FLAG_PREPARED.') AND bg_id > 0 AND id % '.$copies.' = '.$copy;
	if ($crossserver) {
		$add .= sql_pholder(' and flags & ?', INST_FLAG_CROSSSERVER);
	} else {
		$add .= sql_pholder(' and !(flags & ?)', INST_FLAG_CROSSSERVER);
	}
	if (defined('MAX_BG_CNT') && MAX_BG_CNT) {
		$current_bg_active += instance_count(array('parent_id' => 0, 'bg_active' => 1));
	}
	$instance_hash = make_hash(instance_list(array('parent_id' => 0, 'bg_active' => 1), $add));
	if (!$instance_hash) break;
	$instance_users = make_hash(instance_user_list(array('instance_id' => array_keys($instance_hash))),'user_id');
	if (!$instance_users) break;
	$team_sizes = array();
	foreach ($instance_users as $k=>$instance_user) {
		$team_sizes[$instance_user['instance_id']][$instance_user['team']]++;
	}
	
	foreach ($instance_hash as $instance) {
		if ($crossserver) $instance_srv_group = bg_level_parse_teams_servers($instance['bg_level_server_group']);
		$bg = $bg_hash[$instance['bg_id']];
		// в межсерверные инсты затягиваем только межсерверным кроном
		if ($crossserver && !($instance['flags'] & INST_FLAG_CROSSSERVER)) continue;
		if (!$bg || !instance_lock($instance['id'])) continue;
		$black_party_list = array();
		// Подключим потвердивших участие в этом инсте
		foreach($bg_users as $bg_user_id => $bg_user) {
			if (($bg_user['instance_id'] == $instance['id']) && ($bg_user['status'] == BG_USER_STATUS_PENDING)) {
				// Теоретически возможна ситуация, что инст на самом деле полный :(
				if ($bg['team_size']<=$team_sizes[$instance['id']][$bg_user['user_kind']]) {
					$user_ids = array($bg_user['user_id']);
					// Ещё возможна ситуация что кого-то из группы уже включили
					if ($bg_user['party_id']) {
						foreach($bg_users_ready as $ready_id => $ready_user) {
							if ($ready_user['party_id'] == $bg_user['party_id']) {
								unset($bg_users_ready[$ready_id]);
								$team_sizes[$instance['id']][$bg_user['user_kind']]--;
								$user_ids[] = $ready_user['user_id'];
							}
						}
						$black_party_list[] = $bg_user['party_id'];
					}
					if ($bg_user['server_id'] == SERVER_ID) {
						chat_msg_send_system(translate('Для начала битвы не были выполнены необходимые условия'), CHAT_CHF_USER, $user_ids);
						bg_user_save(array(
							'_add' => sql_pholder(' AND bg_id = ? AND user_id in (?@)', $bg['id'], $user_ids),
							'_set' => sql_pholder('dtime = NULL, status = ?, instance_id = 0, server_id = 0', BG_USER_STATUS_WAITING),
						));
					} else {
						crossserver_clear_bg_queue($bg_user['server_id'], $bg['id'], $user_ids);
					}
					
				} else {
					if (!in_array($bg_user['party_id'], $black_party_list)) {
						$bg_users_ready[] = $bg_user;
						$team_sizes[$instance['id']][$bg_user['user_kind']]++;
					}
				}
				unset($bg_users[$bg_user_id]);
			}
		}
		
		// Пробежимся по тем, кому уже отправлено приглашение в этот инст и увеличим team_size соответственно
		foreach($bg_users as $bg_user_id => $bg_user) {
			if (($bg_user['instance_id'] == $instance['id']) && ($bg_user['status'] == BG_USER_STATUS_WAITING)) {
				$team_sizes[$instance['id']][$bg_user['user_kind']]++;
			}
		}
		
		// если инст закрыт для входа - больше не отправляем приглашения
		if ($instance['flags'] & INST_FLAG_BGCLOSED) {
			instance_unlock($instance['id']);
			continue;
		}
		foreach (array_keys($kind_info) as $kind) {
			$avail = $bg['team_size'] - $team_sizes[$instance['id']][$kind];
			if ($avail <= 0) continue;
			// соберём всех желающих
			$orders = array();
			foreach($bg_users as $bg_user_id => $bg_user) {
				if (
					(!$crossserver || (isset($instance_srv_group[$kind][$bg_user['server_id']]))) &&
					($bg_user['status'] == BG_USER_STATUS_WAITING) &&
					($bg['id'] == $bg_user['bg_id']) && 
					!$bg_user['dtime'] &&
					!$bg_user['instance_id'] &&
					($instance['level_min'] <= $bg_user['user_level']) &&
					($instance['level_max'] >= $bg_user['user_level']) && 
					($bg_user['user_kind'] == $kind)
				) {
					$orders[] = $bg_user+array('bg_user_id' => $bg_user_id);
				}
			}
			// вначале попытаемся добить игроками с этого сервера
			$orders_local = array();
			foreach($orders as $order) {
				if ($order['server_id'] == SERVER_ID) {
					$orders_local[] = $order;
				}
			}
			$local_queue = bg_queue_perform($orders_local,$avail);
			foreach($local_queue as $order) {
				// цепляем народ, убираем из очереди
				$order['instance_id'] = $instance['id'];
				$bg_users_link[] = $order;
				unset($bg_users[$order['bg_user_id']]);
			}
			
			$avail -= count($local_queue);
			if (($avail <= 0) || !$crossserver) continue;
			// Тут нужно попытаться подобрать народ с других серверов
			$orders_ext = array();
			foreach($orders as $order) {
				if ($order['server_id'] != SERVER_ID) {
					$orders_ext[] = $order;
				}
			}
			$ext_queue = bg_queue_perform($orders_ext,$avail);
			foreach($ext_queue as $order) {
				// цепляем народ, убираем из очереди
				$order['instance_id'] = $instance['id'];
				$bg_users_link[] = $order;
				unset($bg_users[$order['bg_user_id']]);
			}
		}
		instance_unlock($instance['id']);
	}
} while (0);

// Формирование списка игроков по возможным инстансам
$bg_user_hash = array();

foreach($bg_users as $bg_user) {
	$level_key = bg_level_get_key($bg_user['user_level'], $bg_levels[$bg_user['bg_id']]);
	$queue_key = $bg_user['bg_id'].'_'.($level_key);
	$bg_user_hash[$queue_key][] = $bg_user;
}

print_r($bg_user_hash);

foreach ($bg_user_hash as $bg_data => $bg_users_cur) {
	if ($copy) continue;
	// настройка максимального количества одновременно идущих инстов (для гладиаторского в основном)
	if (defined('MAX_BG_CNT') && MAX_BG_CNT && (MAX_BG_CNT<=$current_bg_active)) {
		continue;
	}
	$bg_data_list = preg_split("/_/",$bg_data);
	if ($bg_data_list[0]) $bg = $bg_hash[$bg_data_list[0]]; else continue;
	if (!$bg || !bg_lock($bg['id'])) continue;
	
	$levels = bg_level_get_info($bg_users_cur[0]['user_level'], $bg_levels[$bg['id']]);
	$level_max = intval($levels['level_max']);
	$level_min = intval($levels['level_min']);
	
	print_r($levels);
	
	$queues = $crossserver ? $levels['servers'] : array(array(KIND_HUMAN => array(SERVER_ID=>SERVER_ID), KIND_MAGMAR => array(SERVER_ID=>SERVER_ID)));
	shuffle($queues);
	
	foreach ($queues as $try_queue) {
		$srv_group_key = bg_level_serialize_teams_servers($try_queue);
		
		$bg_users_waiting = array();
		foreach ($bg_users_cur as $bg_user_id => $bg_user) {
			if (($bg_user['status'] == BG_USER_STATUS_WAITING) && !$bg_user['instance_id'] && isset($try_queue[$bg_user['user_kind']][$bg_user['server_id']])) {
				$bg_users_waiting[$bg_user['user_kind']][] = $bg_user;
			}
		}
		
		$queue = array();
		foreach (array_keys($kind_info) as $kind) {
			$queue[$kind] = bg_queue_perform($bg_users_waiting[$kind], $bg['team_size']);
			// если не нашлось вообще подходящей команды, то для этой уровневой группы все плохо, чо
			if (!$queue[$kind]) {
				bg_unlock($bg['id']);
				continue 2;
			}
		}
		
		/* if (!$bg['force_start_server']) {
			$transfer_count = array();
			foreach (common_get_servers('INST') as $server_id_check => $server_info) {
				$transfer_count[$server_id_check] = 0;
				foreach ($queue as $kind => $kind_queue) {
					foreach ($kind_queue as $bg_user) {
						if ($bg_user['server_id'] != $server_id_check) $transfer_count[$server_id_check]++;
					}
				}
			}
	
			krsort($transfer_count);
			asort($transfer_count);
			
			$server_start = each($transfer_count);
			$server_start = $server_start['key'];
		} else {
			$server_start = $bg['force_start_server'];
		}
		
		if ($server_start != SERVER_ID) {
			bg_unlock($bg['id']);
			continue;
		} */

		if ((count($queue[KIND_HUMAN]) == $bg['team_size']) && (count($queue[KIND_MAGMAR]) == $bg['team_size'])) {
			$inst_flags = INST_FLAG_PREPARED;
			$bg_level_server_group = '';
			if ($crossserver) {
				$inst_flags |= INST_FLAG_CROSSSERVER;
				$bg_level_server_group = $srv_group_key;
			}
			$event = bg_get_current_event($bg);
			if ($event) {
				if ($event['no_break'])
					$inst_flags |= INST_FLAG_NO_ARTIFACT_BREAK;
				if ($event['no_injury'])
					$inst_flags |= INST_FLAG_NO_INJURY;
			}
			// Создаём новый инст c флагом PREPARED линкуем игроков и отправляем приглашения
			$instance_id = instance_create($bg['inst_artikul_id'],array('bg_active' => 1, 'bg_id' => $bg['id'], 'bg_level_server_group' => $bg_level_server_group, 'level_min' => $level_min, 'level_max' => $level_max, 'flags' => $inst_flags));
			$instance_hash[$instance_id] = instance_get($instance_id);
			if (!$instance_id) {
				error_log("(cron_bg: ".getmypid()."): Can't create battleground: ".$bg['id']);
				bg_unlock($bg['id']);
				continue;
			}
			foreach(array_merge($queue[KIND_HUMAN],$queue[KIND_MAGMAR]) as $order) {
				$order['instance_id'] = $instance_id;
				$bg_users_link[] = $order;
			}
			
			break;
		}
	}
	bg_unlock($bg['id']);
}

print_r($bg_users_link);

$server_users = array();
$pending_time = time_current() + BG_PENDING_TTL;
foreach($bg_users_link as $bg_user) {
	if ($bg_user['server_id'] == SERVER_ID) {
		bg_user_send_invite($bg_user['user_id'],$bg_user['bg_id'],$bg_user['instance_id'], $pending_time);
	} else {
		crossserver_bg_invite_send($bg_user['server_id'],$bg_user['user_id'], $bg_user['bg_id'], $pending_time, $bg_user['instance_id']);
	}
	
}

$instances_start = array();

// Теперь обрабатываем инсты в статусе PREPARED
$add = ' and flags & '.INST_FLAG_PREPARED.' AND bg_id > 0 AND id % '.$copies.' = '.$copy;
if ($crossserver) {
	$add .= sql_pholder(' and flags & ?', INST_FLAG_CROSSSERVER);
} else {
	$add .= sql_pholder(' and !(flags & ?)', INST_FLAG_CROSSSERVER);
}
$instance_hash = make_hash(instance_list(array('parent_id' => 0, 'bg_active' => 1), $add));

foreach($instance_hash as $instance) {
	$bg = $bg_hash[$instance['bg_id']];
	
	// Берём готовых пользователей
	$ready = array();
	$ready_cnt = array();
	foreach($bg_users as $bg_user) {
		if (($bg_user['instance_id'] == $instance['id']) && ($bg_user['status'] == BG_USER_STATUS_PENDING)) {
			$ready_cnt[$bg_user['user_kind']]++;
			$ready[] = $bg_user;
		}
	}
	$bg_start = false;
	if (($ready_cnt[1] == $bg['team_size']) && ($ready_cnt[2] == $bg['team_size'])) $bg_start = true;
	if ($instance['dtime']-$instance['life_time']+BG_PENDING_TTL<time_current()) $bg_start = true;
	
	if (!$bg_start) continue;
	
	if (!$ready_cnt[1] || !$ready_cnt[2]) {
		// В одной из команд никто не подтвердил, поэтому заявки для всех обнуляются
		$server_users = $user_ids = array();
		foreach($ready as $bg_user) {
			$server_users[$bg_user['server_id']][] = $bg_user['user_id'];
		}
		
		if ($server_users) {
			foreach($server_users as $server_id => $user_ids) {
				if ($server_id == SERVER_ID) {
					chat_msg_send_system(translate('Для начала битвы не были выполнены необходимые условия'), CHAT_CHF_USER, $user_ids);
					bg_user_save(array(
						'_add' => sql_pholder(' AND bg_id = ? AND user_id in (?@)', $bg['id'], $user_ids),
						'_set' => sql_pholder('dtime = NULL, status = ?, instance_id = 0, server_id = 0', BG_USER_STATUS_WAITING),
					));
				} else {
					crossserver_clear_bg_queue($server_id, $bg['id'], $user_ids);
				}
			}
		}
		continue;
	}
	
	$instances_start[$instance['id']] = $instance['id'];
	
	foreach($ready as $bg_user) {
		$bg_users_ready[] = $bg_user;
	}
}

// Подключение персонажей к инстансам
$user_ids = $bg_users_for_instance_hash = $server_users = $party_links = array();
foreach ($bg_users_ready as $bg_user) {
	$bg_users_for_instance_hash[$bg_user['instance_id']][] = $bg_user;
	if ($crossserver) {
		$server_users[$bg_user['server_id']][] = $bg_user['user_id'];
	} else {
		$user_ids[] = $bg_user['user_id'];
	}
}

foreach ($bg_users_for_instance_hash as $instance_id => $bg_users_cur) {
	if (!instance_lock($instance_id)) continue;
	$instance = instance_get($instance_id);
	if (!$instance['bg_active']) {
		instance_unlock($instance_id);
		continue;
	}
	$instance_artikul = instance_artikul_get($instance['artikul_id']);
	$area_ftime = 0;
	$bg = $bg_hash[$instance['bg_id']];
	if ($instance && $instance_artikul) {
		if ($instance['flags'] & INST_FLAG_PREPARED) {
			$instance['dtime'] = time_current()+$instance['life_time'];
		}
		$area_ftime = $instance['dtime']-$instance_artikul['life_time']+($bg['start_delay'] > 0 ? $bg['start_delay'] : 60);
	}
	
	$start_ids = array();
	foreach (array_keys($kind_info) as $kind) {
		$artikul_id = $bg['t'.$kind.'_start_id'];
		if (!$artikul_id) continue;
		if ($instance['artikul_id'] == $artikul_id) {
			$start_ids[$kind] = $instance['id'];
			continue;
		}
		$inst = instance_get(array('artikul_id' => $artikul_id, 'root_id' => $instance['id']));
		$start_ids[$kind] = $inst['id'];
	}

	$bg_user_ids = array();

	foreach ($bg_users_cur as $bg_user) {
		if ($crossserver && ($bg_user['server_id'] != SERVER_ID)) {
			$recieved_user = crossserver_user_recieve($bg_user['server_id'], $bg_user['user_id']);
			if (!$recieved_user) continue;
			$bg_user['user_id'] = $recieved_user;
			$new_user = user_get($recieved_user);
			if ($bg_user['party_id'] && !isset($party_links[$bg_user['party_id']])) {
				// если новой группы ещё нет - создадим
				$error = false;
				$party_id = party_create($new_user, $error);
				if ($party_id) {
					$party_links[$bg_user['party_id']] = $party_id;
				} 
			} elseif ($bg_user['party_id']) {
				party_member_save(array(
					'user_id' => $recieved_user,
					'party_id' => $party_links[$bg_user['party_id']],
					'status' => PM_STATUS_ACTIVE,
					'stime' => time_current(),
				));
				$party = party_get($party_links[$bg_user['party_id']]);
				if ($party['level_max']<$new_user['level']) {
					party_save(array(
						'id' => $party_links[$bg_user['party_id']],
						'level_max' => $new_user['level'],
						'leader_id' => $recieved_user,
					));
				}
			}
		}
		instance_user_save(array(
			'_mode' => CSMODE_REPLACE,
			'instance_artikul_id' => $instance['artikul_id'],
			'instance_id' => $instance_id,
			'user_id' => $bg_user['user_id'],
			'team' => $bg_user['user_kind'],
			'flags' => 0,
		));

		$start_id = $start_ids[$bg_user['user_kind']];
		
		$user_current = user_get($bg_user['user_id']);

        //Выдадим бонус входа
        if($bg['enter_bonus_id']) bonus_apply($user_current, $bg['enter_bonus_id']);

		$user_param = array(
			'id' => $bg_user['user_id'],
			'invisibility_time' => 0, // сбрасываем невидимость
			'instance_id' => $start_id ? $start_id : $instance_id,
			'raid_id' => $instance_id + $bg_user['user_kind'],	// рейд-чат
			'area_ftime' => $area_ftime,
		);
		
		//user_change_chat_channels($user_current, $user_param);
		
		user_save($user_param);

        //Включаем тушилку xD
        if($bg['flags'] & BG_FLAG_NO_CHAT) {
            user_save(array(
                'id' => $bg_user['user_id'],
                '_set' => sql_pholder('flags2 = flags2 | ?', USER_FLAG2_DEAF),
            ));
        }

        chat_msg_send_special(CODE_CALL_JSFUNC, CHAT_CHF_USER, $bg_user['user_id'], array('func' => "updateSwf({'lvl':''})"));
		
		user_resurrect($bg_user['user_id']);

        $bg_user_ids[$bg_user['user_id']] = $bg_user['user_id'];

	}
	// Если это новый инст снимем с него флаг PREPARED
	
	if ($instances_start[$instance_id]) {
		instance_save(array(
			'id' => $instance_id,
			'flags' => $instance['flags'] & ~ INST_FLAG_PREPARED,
			'dtime' => time_current()+$instance['life_time'],
		));
	}

    //Участие в Пб триггер
    _global_event_trigger(GLOBAL_EVENT_TYPE_GO_PB, 1, $bg_user_ids);

	instance_unlock($instance_id);
}

foreach($party_links as $new_party_id) {
	party_member_sync($new_party_id);
}

if ($crossserver) {
	foreach($server_users as $server_id => $server_user_ids) {
		if ($server_id == SERVER_ID) {
			$user_ids = $server_user_ids;
			continue;
		} else {
			crossserver_bg_users_delete($server_id,$server_user_ids);
		}
	}
}
if ($user_ids) {
	bg_user_delete(array('user_id' => $user_ids));
	chat_msg_send_special(CODE_REDIRECT,CHAT_CHF_USER,$user_ids,array('url' => 'instance.php', 'flag' => '__instance_php__'));
	chat_msg_send_special(CODE_CALL_JSFUNC,CHAT_CHF_USER,$user_ids,array('func' => "updateSwf({'lvl':''})"));
}

$stime2 = time();
$rtime = $stime2-$stime1;
if ($rtime > MNGR_BG_INTERVAL) error_log("(mngr_bg_queue ".$copy.": ".getmypid()."): Runtime $rtime sec");
sleep(max(MNGR_BG_INTERVAL-$rtime,0));