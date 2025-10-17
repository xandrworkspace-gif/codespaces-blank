<?php

ini_set('memory_limit', '256M');

chdir("..");
require_once("include/common.inc");

set_time_limit(60);
$stime1 = microtime(true);

common_define_settings();

require_once("lib/clan_battle.lib");

// Проверка, что проект не остановлен
if ((defined('PROJECT_STOPPED') && PROJECT_STOPPED) || !defined('CLAN_BATTLE_PREP_AREA_ID') || CLAN_BATTLE_PREP_AREA_ID <= 0) {
	sleep(MNGR_CLAN_BATTLE_INTERVAL);
	return;
}

// Номер копии от 1 до $copies
$copies = 1;
$copy = 1;

require_once("lib/bg.lib");
require_once("lib/party.lib");
require_once("lib/bonus.lib");
require_once("lib/fight.lib");
require_once("lib/crossserver.lib");

$area = area_get(CLAN_BATTLE_PREP_AREA_ID);
if (!$area) {
    //logfile(NODE_FILE_LOG, 'no_area');
	sleep(MNGR_CLAN_BATTLE_INTERVAL);
	return;
}

PF_CALL('mngr_clan_battle');

//Разошлём приглашения
$clan_ids = array();

if (date('s') <= MNGR_CLAN_BATTLE_INTERVAL) {
	$clan_battle_invite_list = clan_battle_list(false, sql_pholder(' AND status = ?#CLAN_BATTLE_STATUS_SCHEDULED AND stime between ? and ?', time_current(), time_current()+300));
    //logfile(NODE_FILE_LOG, sql_pholder(' AND status = ?#CLAN_BATTLE_STATUS_SCHEDULED AND stime between ? and ?', time_current(), time_current()+300));
	foreach($clan_battle_invite_list as $clan_battle_invite) {
		if ($clan_battle_invite['first_clan_server_id'] == SERVER_ID) {
			$clan_ids[] = $clan_battle_invite['first_clan_id'];
			$clan_times[$clan_battle_invite['first_clan_id']] = $clan_battle_invite['stime'];
		}
		if ($clan_battle_invite['second_clan_server_id'] == SERVER_ID) {
			$clan_ids[] = $clan_battle_invite['second_clan_id'];
			$clan_times[$clan_battle_invite['second_clan_id']] = $clan_battle_invite['stime'];
		}
	}
}

//logfile(NODE_FILE_LOG, print_r($clan_ids, true));


if ($clan_ids) {
	$invite_user_list = make_hash(user_list(array('clan_id' => $clan_ids), ' and area_id!='.CLAN_BATTLE_PREP_AREA_ID));
	if ($invite_user_list) {
		$user_punishments = make_hash(punishment_user_list(array('user_id'=>array_keys($invite_user_list), 'crime_id' => CRIME_FREEDOM)), 'user_id');
	}
	foreach($invite_user_list as $invite_user) {
		if ($user_punishments[$invite_user['id']]) continue;
		$msg = sprintf(translate('Для того чтобы переместиться в локацию проведения клановых битв, нажмите <b><a href="javascript: moveToClanBattleLobby();">СЮДА</a></b> не позднее <b>%s:00</b>!'), date('H:i', $clan_times[$invite_user['clan_id']]));
		chat_msg_send_system($msg, CHAT_CHF_USER, $invite_user['id']);
	}
}

$clan_battle_list = make_hash(clan_battle_list(false, sql_pholder(' AND status <= ?#CLAN_BATTLE_STATUS_RUNNING AND stime <= ? AND server_id = ?', time_current(), SERVER_ID)), 'status', true);

if (isset($clan_battle_list[CLAN_BATTLE_STATUS_SCHEDULED])) {
	PF_CALL('mngr_clan_battle_proc_scheduled');
	$server_clans = array();
	foreach ($clan_battle_list[CLAN_BATTLE_STATUS_SCHEDULED] as $clan_battle) {
		$server_clans[$clan_battle['first_clan_server_id']][$clan_battle['first_clan_id']] = $clan_battle['first_clan_id'];
		$server_clans[$clan_battle['second_clan_server_id']][$clan_battle['second_clan_id']] = $clan_battle['second_clan_id'];
	}

	$ready_users_by_server_clans = crossserver_clan_battle_ready_users_list($server_clans);

	foreach ($clan_battle_list[CLAN_BATTLE_STATUS_SCHEDULED] as $clan_battle) {
		if (!clan_battle_lock($clan_battle['id'])) continue;
		do {
			error_log('process '.$clan_battle['id']);
			$ready_users = array(
				'first' => $ready_users_by_server_clans[$clan_battle['first_clan_server_id']][$clan_battle['first_clan_id']],
				'second' => $ready_users_by_server_clans[$clan_battle['second_clan_server_id']][$clan_battle['second_clan_id']]
			);

			$second_clan_enough = count($ready_users['second']['users']) >= CLAN_BATTLE_MIN_COUNT;
			$first_clan_enough = count($ready_users['first']['users']) >= CLAN_BATTLE_MIN_COUNT;	

			if (!$second_clan_enough && !$first_clan_enough) {
				clan_battle_log(sprintf('clan battle id %d auto finished [%s] - team1 count %d - team2 count %d', $clan_battle['id'], 'AUTO DRAW', count($ready_users['first']['users']), count($ready_users['second']['users'])));
				clan_battle_finish($clan_battle, false);
				break;
			} elseif (!$second_clan_enough || !$first_clan_enough) {
				clan_battle_log(sprintf('clan battle id %d auto finished [%s] - team1 count %d - team2 count %d', $clan_battle['id'], 'AUTO WIN', count($ready_users['first']['users']), count($ready_users['second']['users'])));
				clan_battle_finish($clan_battle, $second_clan_enough ? CLAN_BATTLE_WINNER_SECOND : CLAN_BATTLE_WINNER_FIRST);
				break;
			}

			$instance_id = instance_create(CLAN_BATTLE_INSTANCE_ID,array('bg_active' => 1, 'clan_battle_id' => $clan_battle['id']));
			
			if (!$instance_id) {
				error_log('Cant create instance for clan_battle: '.$clan_battle['id']);
				break;
			}

			foreach (array_keys($ready_users) as $num) {
				error_log('process '.$clan_battle['id'].' ready users '.$num.' cnt '.count($ready_users[$num]['users']).($clan_battle[$num.'_clan_server_id'] != SERVER_ID ? ' (foreign)' : ''));
				if ($clan_battle[$num.'_clan_server_id'] != SERVER_ID) {
					$new_ready_users = array();
					$new_supervisors = array();
					foreach($ready_users[$num]['users'] as $clan_user) {
						$recieved_user = crossserver_user_recieve($clan_battle[$num.'_clan_server_id'], $clan_user['id'], array(), $instance_id);
						if (!$recieved_user) {
							error_log('process '.$clan_battle['id'].' ready user not recieved '.$clan_user['id']);
							continue;
						}
						$new_ready_users[] = array('id' => $recieved_user);
						if (isset($ready_users[$num]['supervisors'][$clan_user['id']])) $new_supervisors[$recieved_user] = $recieved_user;
					}
					$ready_users[$num]['users'] = $new_ready_users;
					$ready_users[$num]['supervisors'] = $new_supervisors;
				}
			}

			$ready_users_ids = get_hash(array_merge($ready_users['second']['users'], $ready_users['first']['users']), 'id', 'id');
			foreach(array('first', 'second') as $num) {
				foreach($ready_users[$num]['users'] as $ready_user) {
					if (!session_lock($ready_user['id'])) continue;
					instance_user_save(array(
						'_mode' => CSMODE_REPLACE,
						'instance_id' => $instance_id,
						'user_id' => $ready_user['id'],
						'team' => $num == 'first' ? 1 : 2
					));
					clan_battle_user_save(array(
						'clan_battle_id' => $clan_battle['id'],
						'user_id' => $ready_user['id'],
						'clan_num' => $num == 'first' ? 1 : 2,
						'room' => isset($ready_users[$num]['supervisors'][$ready_user['id']]) ? -1 : 0
					));
					$user = user_get($ready_user['id']);
					$raid_id = $instance_id.($num == 'first' ? 1 : 2);
					//user_change_chat_channels($user, array('instance_id' => $instance_id, 'raid_id' => $raid_id));
					user_save(array(
						'id' => $ready_user['id'],
						'_set' => sql_pholder('instance_id = ?, raid_id = ?, area_ftime = 0, invisibility_time = 0', $instance_id, $raid_id),
					));
					session_unlock($ready_user['id']);
				}
			}
			chat_msg_send_special(CODE_CALL_JSFUNC,CHAT_CHF_USER,$ready_users_ids,array('func' => "top.frames['main_frame'].frames['main'].location.href='area.php'",));

			$params = array(
				'id' => $clan_battle['id'],
				'instance_id' => $instance_id,
				'status' => CLAN_BATTLE_STATUS_PREPARATION
			);
			clan_battle_save($params, sql_pholder(' AND server_id = ?', SERVER_ID));
			chat_msg_send_system(sprintf(translate('Бой начался, у вас есть %d минут что бы распределить бойцов по островам!'), floor(CLAN_BATTLE_TIME_TO_BUFF/60)), CHAT_CHF_RAID, array('raid_id' => array($instance_id.'1', $instance_id.'2')));
		} while (0);
		clan_battle_unlock($clan_battle['id']);
	}
	PF_RET(false, 'mngr_clan_battle_proc_scheduled');
}

if (isset($clan_battle_list[CLAN_BATTLE_STATUS_PREPARATION])) {
	PF_CALL('mngr_clan_battle_proc_preparation');
	$clan_battle_room_numbers = array_keys($clan_battle_rooms_hash);
	$clan_battle_room_numbers = array_combine($clan_battle_room_numbers, $clan_battle_room_numbers);
	foreach ($clan_battle_list[CLAN_BATTLE_STATUS_PREPARATION] as $clan_battle) {
		if (!clan_battle_lock($clan_battle['id'])) continue;
		do {
			if (
				!($clan_battle['flags'] & CLAN_BATTLE_FLAG_READY_TEAM_1 && $clan_battle['flags'] & CLAN_BATTLE_FLAG_READY_TEAM_2)
				&& ($clan_battle['stime'] + CLAN_BATTLE_TIME_TO_BUFF) > time_current()
			) break;
			
			$clan_battle_users = clan_battle_user_list(array('clan_battle_id' => $clan_battle['id']));
			$clan_battle_users_by_rooms = array();
			$clan_battle_user_counts = array(
				1 => 0,
				2 => 0
			);
			foreach($clan_battle_users as $clan_battle_user) {
				$clan_battle_users_by_rooms[$clan_battle_user['room']][$clan_battle_user['clan_num']][] = $clan_battle_user;
				$clan_battle_user_counts[$clan_battle_user['clan_num']]++;
			}
			ksort($clan_battle_users_by_rooms);
			$supervisors = isset($clan_battle_users_by_rooms[-1]) ? $clan_battle_users_by_rooms[-1] : false;
			unset($clan_battle_users_by_rooms[-1]);
			$clan_battle_users_by_rooms[-1] = $supervisors;
			$unprocessed_rooms = array(1 => $clan_battle_room_numbers, 2 => $clan_battle_room_numbers);

			// Найдем пустующие комнаты и дораспределим в них людей
			$auto_moved_users = array();
			foreach($clan_battle_users as $clan_battle_user) {
				if (isset($unprocessed_rooms[$clan_battle_user['clan_num']][$clan_battle_user['room']])) unset($unprocessed_rooms[$clan_battle_user['clan_num']][$clan_battle_user['room']]);
			}

			srand();
			foreach (array(1, 2) as $clan_num) {
				foreach ($unprocessed_rooms[$clan_num] as $empty_room) {
					foreach (array_keys($clan_battle_users_by_rooms) as $room) {
						if (in_array($room, $unprocessed_rooms[$clan_num])) continue;
						if (count($clan_battle_users_by_rooms[$room][$clan_num]) >= 2 || (!isset($clan_battle_rooms_hash[$room]) && $room != -1 && count($clan_battle_users_by_rooms[$room][$clan_num]) >= 1)) {
							shuffle($clan_battle_users_by_rooms[$room][$clan_num]);
							$moved_user = array_shift($clan_battle_users_by_rooms[$room][$clan_num]);
							$moved_user['room'] = $empty_room;
							$clan_battle_users_by_rooms[$empty_room][$clan_num][] = $moved_user;
							$auto_moved_users[$empty_room][] = $moved_user['id'];
							continue 2;
						}
					}
				}

				// Раскидаем резерв до допустимого значения
				$reserv_cnt = intval(count($clan_battle_users_by_rooms[-1][$clan_num])) + intval(count($clan_battle_users_by_rooms[0][$clan_num]));
				$max_reserv_cnt = floor($clan_battle_user_counts[$clan_num] * CLAN_BATTLE_MAX_RESERV);
				while ($reserv_cnt > $max_reserv_cnt && (count($clan_battle_users_by_rooms[0][$clan_num]) > 0 || count($clan_battle_users_by_rooms[-1][$clan_num]) > 1)) {
					$reserv_cnt--;
					$new_room_number = rand(1,5);
					$from_room = intval(count($clan_battle_users_by_rooms[0][$clan_num])) > 0 ? 0 : -1;
					$moved_user = array_pop($clan_battle_users_by_rooms[$from_room][$clan_num]);
					$moved_user['room'] = $new_room_number;
					$clan_battle_users_by_rooms[$new_room_number][$clan_num][] = $moved_user;
					$auto_moved_users[$new_room_number][] = $moved_user['id'];
				}
			}

			foreach($auto_moved_users as $room => $clan_battle_user_ids) if (!empty($clan_battle_user_ids)) clan_battle_user_save(array(
				'_set' => sql_pholder('room = ?', $room),
				'_add' => sql_pholder(' AND id IN (?@)', $clan_battle_user_ids)
			));

			foreach($clan_battle_users_by_rooms as $room => $clan_battle_users_by_room_clan) {
				if ($room <= 0) continue;
				if (count($clan_battle_users_by_room_clan[1]) == 0 && count($clan_battle_users_by_room_clan[2]) == 0) continue;

				if (count($clan_battle_users_by_room_clan[1]) == 0 || count($clan_battle_users_by_room_clan[2]) == 0) {
					$winner_num = count($clan_battle_users_by_room_clan[1]) == 0 ? 2 : 1;
					clan_battle_room_save(array(
						'clan_battle_id' => $clan_battle['id'],
						'room' => $room,
						'winner_clan_num' => $winner_num,
						'fight_id' => 0
					));
					continue;
				}

				$pers_data = array();
				foreach (array(1, 2) as $clan_num) {
					foreach($clan_battle_users_by_room_clan[$clan_num] as $clan_battle_user) {
						$pers_data[] = array(
							'user_id' => $clan_battle_user['user_id'],
							'team' => $clan_battle_user['clan_num'],
						);
					}
				}
				$user_ids = get_hash($pers_data, 'user_id', 'user_id');
				if (empty($user_ids)) continue;

				$fight = array(
					'area_id' => 0,
					'instance_id' => $clan_battle['instance_id'],
					'title' => $clan_battle_rooms_hash[$room]['title'],
					'type' => FIGHT_TYPE_DUEL,
					'timeout' => 20,
					'team_max' => 0,
					'flags' => 0,
				);
				$fight_id = fight_start($fight, $pers_data);
				if ($fight_id) {
					clan_battle_room_save(array(
						'clan_battle_id' => $clan_battle['id'],
						'room' => $room,
						'fight_id' => $fight_id
					));
				}
			}


			$params = array(
				'id' => $clan_battle['id'],
				'status' => CLAN_BATTLE_STATUS_RUNNING
			);
			clan_battle_save($params, sql_pholder(' AND server_id = ?', SERVER_ID));
		} while(0);
		clan_battle_unlock($clan_battle['id']);
	}
	PF_RET(false, 'mngr_clan_battle_proc_preparation');
}

if (isset($clan_battle_list[CLAN_BATTLE_STATUS_RUNNING])) {
	PF_CALL('mngr_clan_battle_proc_running');
	foreach ($clan_battle_list[CLAN_BATTLE_STATUS_RUNNING] as $clan_battle) {
		PF_CALL('mngr_clan_battle_proc_running_lock_start');
		if (!clan_battle_lock($clan_battle['id'])) continue;
		PF_RET(false, 'mngr_clan_battle_proc_running_lock_start');
		PF_CALL('mngr_clan_battle_proc_running_lock');
		do {
			PF_CALL('mngr_clan_battle_proc_running_reserv');
			$continue = false;
			$clan_battle_rooms = false;
			foreach (array(1, 2) as $clan_num) {
				$reserv_time = $clan_battle[($clan_num == 1 ? 'first' : 'second').'_clan_reserv_use_time'];
				if (!($clan_battle['flags'] & constant('CLAN_BATTLE_FLAG_RESERV_DISPATCHED_'.$clan_num)) && $reserv_time > 0 && $reserv_time + CLAN_BATTLE_TIME_TO_USE_RESERV - time_current() < 0) {
					if (!$clan_battle_rooms) $clan_battle_rooms = clan_battle_room_list(array('clan_battle_id' => $clan_battle['id'], 'winner_clan_num' => 0));
					if ($clan_battle_rooms) {
						$free_users_rooms = make_hash(clan_battle_user_list(array('clan_battle_id' => $clan_battle['id'], 'room' => array(0, -1), 'clan_num' => $clan_num)), 'room', true);

						srand();
						foreach ($free_users_rooms as $room => $free_users) {
							$i = 0;
							foreach ($free_users as $clan_battle_user) {
								if ($room == -1 && ++$i == count($free_users)) continue;

								shuffle($clan_battle_rooms);
								$new_room = reset($clan_battle_rooms);

								$fight = fight_get($new_room['fight_id']);
								if (!$fight || $fight['status'] != FIGHT_STATUS_RUNNING) {
									error_log(sprintf('[clan_battle %s] not able to get fight %d at room %s', $clan_battle['id'], $new_room['fight_id'], $new_room['room']));
									continue;
								}

								$user = user_get($clan_battle_user['user_id']);

								$status = false;
								if (fight_lock($fight['id'])) {
									if (instance_lock($clan_battle['instance_id'])) {
										$status = fight_join($fight,$user,$clan_num);
										instance_unlock($clan_battle['instance_id']);
									}
									fight_unlock($fight['id']);
								}

								if ($status) {
									clan_battle_user_save(array(
										'_set' => sql_pholder('room = ?', $new_room['room']),
										'_add' => sql_pholder(' AND id = ?', $clan_battle_user['id']),
									));
								} else {
									error_log(sprintf('[clan_battle %s] not able to join fight %d at room %s', $clan_battle['id'], $new_room['fight_id'], $new_room['room']));
								}
							}
						}
					}

					clan_battle_save(array('id' => $clan_battle['id'], '_set' => 'flags = flags | '.constant('CLAN_BATTLE_FLAG_RESERV_DISPATCHED_'.$clan_num)), sql_pholder(' AND server_id = ?', SERVER_ID));
					$continue = true;
				}
			}
			PF_RET(false, 'mngr_clan_battle_proc_running_reserv');
			if ($continue) break;

			$current_rooms = clan_battle_room_count(array(
				'clan_battle_id' => $clan_battle['id'],
				'winner_clan_num' => 0
			));
			if ($current_rooms) continue;
			
			$rooms = clan_battle_room_list(array('clan_battle_id' => $clan_battle['id']));
			$total_rooms = count($rooms);
			$scores = array(1 => 0, 2 => 0);
			foreach ($rooms as $r) {
				$scores[$r['winner_clan_num']]++;
			}

			$winner = $scores[1] > $scores[2] ? 1 : ($scores[1] < $scores[2] ? 2 : false);

			clan_battle_finish($clan_battle, $winner);
		} while(0);
		PF_RET(false, 'mngr_clan_battle_proc_running_lock');
		clan_battle_unlock($clan_battle['id']);
	}
	PF_RET(false, 'mngr_clan_battle_proc_running');
}


$stime2 = microtime(true);
$rtime = $stime2-$stime1;

PF_RET(false, 'mngr_clan_battle');

if ($rtime > MNGR_CLAN_BATTLE_INTERVAL) error_log("(mngr_clan_battle: ".getmypid()."): Runtime $rtime sec");
sleep(max(MNGR_CLAN_BATTLE_INTERVAL-$rtime,0));