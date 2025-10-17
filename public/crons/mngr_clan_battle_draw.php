<?php

ini_set('memory_limit', '256M');

chdir("..");
require_once("include/common.inc");

set_time_limit(60);
$stime1 = microtime(true);

common_define_settings();
if (!defined('SERVER_ID') || !defined('CLAN_BATTLE_SEASON_SERVER_ID') || (SERVER_ID != CLAN_BATTLE_SEASON_SERVER_ID)) {
	error_log('Attempt to run mngr_clan_battle_draw on a slave server! This manager can only be run on the master server defined by CLAN_BATTLE_SEASON_SERVER_ID constant!');
	sleep(MNGR_CLAN_BATTLE_INTERVAL);
	return;
}

require_once("lib/clan_battle.lib");
// Проверка, что проект не остановлен
if (!(constant('CLAN_BATTLE_IS_ON')) || (defined('PROJECT_STOPPED') && PROJECT_STOPPED) || !defined('CLAN_BATTLE_PREP_AREA_ID') || CLAN_BATTLE_PREP_AREA_ID <= 0) {
	sleep(MNGR_CLAN_BATTLE_INTERVAL);
	return;
}
require_once("lib/bg.lib");
require_once("lib/party.lib");
require_once("lib/bonus.lib");
require_once("lib/fight.lib");
require_once("lib/crossserver.lib");

global $online_prep_area_user_ids;
$online_prep_area_user_ids = array();
$area = area_get(CLAN_BATTLE_PREP_AREA_ID);
if (!$area) {
	sleep(MNGR_CLAN_BATTLE_INTERVAL);
	return;
}

if (defined('CLAN_BATTLE_STIME') && CLAN_BATTLE_STIME > 0 && (time_current() - CLAN_BATTLE_STIME + CLAN_BATTLE_PREPARE_PERIOD) > 0) { // Пора определять соперников
	$next_start = -1;
	if (defined('CLAN_BATTLE_SCHED') && CLAN_BATTLE_SCHED) {
		$sched = unserialize(CLAN_BATTLE_SCHED);
		
		if ($sched) {
			$cur_week_day = date('N');
			for ($date_diff = 0; $date_diff <= 7; $date_diff++) {
				$check_week_day = (($cur_week_day - 1 + $date_diff) % 7) + 1;
				if (!isset($sched[$check_week_day]) || !$sched[$check_week_day]['on'] || !$sched[$check_week_day]['time']) continue;
				
				$day_time = explode(':', $sched[$check_week_day]['time']);
				if (!isset($day_time[1])) continue;
				$day_time[0] = intval($day_time[0]);
				$day_time[1] = intval($day_time[1]);
				if ($day_time[0] < 0 || $day_time[0] > 23) continue;
				if ($day_time[1] < 0 || $day_time[1] > 59) continue;
				$day_time = $day_time[0]*3600 + $day_time[1]*60;
				
				$next_start = strtotime(date('Y-m-d 00:00:00')) + $date_diff*86400 + $day_time;
				if ($next_start < time()+CLAN_BATTLE_PREPARE_PERIOD) {
					$next_start = -1;
					continue;
				} else break;
			}
		}
	}
	crossserver_clan_battle_lock_requests($next_start);
	
	// обнулим счетчик изменений в рейтинге
	$active_season = clan_battle_season_get_active();
	if ($active_season) {
		clan_battle_season_clan_save(
			array('_set' => 'rating_place_diff = 0',),
			sql_pholder(' AND season_id = ? ', $active_season['id'])
		);
	}
	

	$clan_list = crossserver_clan_battle_get_requests();
	
	foreach ($clan_list as $clan_key => $clan) {
		if ($clan['online'] < CLAN_BATTLE_MIN_COUNT) {
			error_log('Clan '.$clan['id'].' has low online ('.$clan['online'].')');
			unset($clan_list[$clan_key]);
			$msg = translate('Вашему клану не был найден противник, так как количество участников недостаточно!');
			$channel_data = array('clan_id' => $clan['id']);
			if ($clan['server_id'] == SERVER_ID) chat_msg_send_system($msg, CHAT_CHF_CLAN, $channel_data);
			else crossserver_chat_send($clan['server_id'], $msg, CHAT_CHF_CLAN, $channel_data, true);
		}
	}
	
	error_log(var_export($clan_list, true));
	
	// нужно будет после жеребьевки для выдачи штрафов
	$participant_hash = $clan_list;
	
	$rating_sort_func = create_function('$a,$b', '
		if ($a["u"] == $b["u"]) {
			return 0;
		}
		return ($a["u"] > $b["u"]) ? -1 : 1;
	');
	
	$shuffle_groups = array();
	$looser = false; // неудачник :-)
	if ($active_season['status'] < CLAN_BATTLE_SEASON_STATUS_RUNNING_PLAY) {
		usort($clan_list, $rating_sort_func);
		
		$shuffle_groups = array_chunk($clan_list, 6);
		
		foreach ($shuffle_groups as $shuffle_group_key => $shuffle_group) {
			if (count($shuffle_group)%2 > 0) $looser = array_pop($shuffle_groups[$shuffle_group_key]);
		}
	} else {
		$season_clans = array();
		$tmp = clan_battle_season_clan_list(array('season_id' => $active_season['id']));
		$shuffle_groups = array();
		$shuffle_groups_tmp = array();
		foreach ($tmp as $c) {
			if (isset($clan_list[$c['clan_id'].'_'.$c['server_id']])) $shuffle_groups_tmp[intval(($c['league_id']<<4) + $c['copy_id'])][$c['clan_id'].'_'.$c['server_id']] = $clan_list[$c['clan_id'].'_'.$c['server_id']];
		}
		
		ksort($shuffle_groups_tmp);
		foreach ($shuffle_groups_tmp as $shuffle_group_key => $shuffle_group) {
			error_log('clan_group - '.$shuffle_group_key);
			if ($looser) {
				error_log('looser clan '.var_export($looser, true).' goes to '.$shuffle_group_key);
				array_unshift($shuffle_groups_tmp[$shuffle_group_key], $looser);
				$looser = false;
			}
			usort($shuffle_groups_tmp[$shuffle_group_key], $rating_sort_func);
			if (count($shuffle_groups_tmp[$shuffle_group_key])%2 > 0) {
				$looser = array_pop($shuffle_groups_tmp[$shuffle_group_key]);
				error_log('new looser clan '.var_export($looser, true).' from '.$shuffle_group_key);
			}
		}
	}
	
	$k = 0;
	foreach ($shuffle_groups_tmp as $shuffle_group_key => $shuffle_group) {
		if (!$k++) {
			$shuffle_groups[] = $shuffle_groups_tmp[$shuffle_group_key];
		} else {
			$shuffle_group_chunks = array_chunk($shuffle_groups_tmp[$shuffle_group_key], 6);
			foreach ($shuffle_group_chunks as $shuffle_group_chunk) $shuffle_groups[] = $shuffle_group_chunk;
		}
	}
	
	if ($looser) {
		error_log('absolute looser clan '.var_export($looser, true));
		$msg = translate('При жеребьевке не был найден соперник для вашего клана.');
		$channel_data = array('clan_id' => $looser_clan['id']);
		if ($looser_clan['server_id'] == SERVER_ID) chat_msg_send_system($msg, CHAT_CHF_CLAN, $channel_data);
		else crossserver_chat_send($looser_clan['server_id'], $msg, CHAT_CHF_CLAN, $channel_data, true);
	}
	
	foreach ($shuffle_groups as $k => $sg) shuffle($shuffle_groups[$k]);
	
	$time_delta = 0;
	
	foreach ($shuffle_groups as $k => $clan_list) {
		$groups = array();
		while (count($clan_list) >= 2) {
			$clan_1 = array_pop($clan_list);
			$clan_2 = array_pop($clan_list);
			error_log('Battle '.$clan_1['id'].' vs '.$clan_2['id']);
			$groups[] = array(1 => $clan_1, 2 => $clan_2);
		}

		foreach ($groups as $group) {
			$clan_battle = array(
				'first_clan_id' => $group[1]['id'],
				'first_clan_server_id' => $group[1]['server_id'],
				'second_clan_id' => $group[2]['id'],
				'second_clan_server_id' => $group[2]['server_id'],
				'server_id' => $group[1]['online'] > $group[2]['online'] ? $group[1]['server_id'] : $group[2]['server_id'],
				'stime' => CLAN_BATTLE_STIME+$time_delta,
				'status' => CLAN_BATTLE_STATUS_SCHEDULED,
			);

			$msg = sprintf(translate('Вам подобран противник, сражение начнется в %s:00!'), date('H:i', CLAN_BATTLE_STIME+$time_delta));
			
			if ($group[1]['server_id'] != $group[2]['server_id']) {
				$time_delta += min($group[1]['online'], $group[2]['online'])*2;
			}
			
			$channel_data = array('clan_id' => $group[1]['id']);
			if ($group[1]['server_id'] == SERVER_ID) chat_msg_send_system($msg, CHAT_CHF_CLAN, $channel_data);
			else crossserver_chat_send($group[1]['server_id'], $msg, CHAT_CHF_CLAN, $channel_data, true);

			$channel_data = array('clan_id' => $group[2]['id']);
			if ($group[2]['server_id'] == SERVER_ID) chat_msg_send_system($msg, CHAT_CHF_CLAN, $channel_data);
			else crossserver_chat_send($group[2]['server_id'], $msg, CHAT_CHF_CLAN, $channel_data, true);

			crossserver_clan_battle_save($clan_battle, true);
		}
	}
	crossserver_clan_battle_free_requests();
	
	// выдача штрафов за неучастие в КВ
	if (CLAN_BATTLE_SEASON_STATUS_RUNNING_PLAY == $active_season['status']) {
		$season_clans = clan_battle_season_clan_list(array('season_id' => $active_season['id']));
		
		clan_battle_log(sprintf('Clan battle decay: total clans in season = %d, participants = %d, total eligible for decay = %d', count($season_clans), count($participant_hash), count($season_clans) - count($participant_hash)));
		// фильтруем список, убираем все кланы, которые молодцы и участвуют в сегодняшних КВ
		$server_clans = array();
		foreach ($season_clans as $season_clan) {
			$clan_id = $season_clan['clan_id'];
			$server_id = $season_clan['server_id'];
			if (isset($participant_hash[$clan_id.'_'.$server_id])) continue;
			$server_clans[$server_id][$clan_id] = $season_clan;
		}
		
		foreach ($server_clans as $server_id => $season_clan_list) {
			$clan_ids = array_keys($season_clan_list);
			if (!$clan_ids) continue;
			
			clan_battle_season_clan_save(array(
				'_set' => sql_pholder(' last_decay = ? ', time_current()),
				'_add' => sql_pholder(' AND season_id = ? AND server_id = ? AND clan_id IN (?@) ', $active_season['id'], $server_id, $clan_ids),
			));
			
			if ($server_id == SERVER_ID) $clan_info_hash = make_hash(clan_info_list(array('clan_id' => $clan_ids), '', 'clan_id, last_clan_battle'), 'clan_id');
			else $clan_info_hash = make_hash(crossserver_clan_info_list($server_id, array('clan_id' => $clan_ids), '', 'clan_id, last_clan_battle'), 'clan_id');
			
			if (!$clan_info_hash) continue;
			
			foreach ($season_clan_list as $season_clan) {
				// если последняя клановая битва клана была до распределения по лигам, считаем датой последней клановой битвы дату распределения по лигам
				$season_clan['last_clan_battle'] = max($clan_info_hash[$season_clan['clan_id']]['last_clan_battle'], $active_season['lock_time']);
				$skill_diff = clan_battle_apply_decay($season_clan);
				if ($skill_diff < 0) clan_battle_season_league_demote($season_clan['clan_id'], $season_clan['server_id'], $season_clan['season_id']);
			}
		}
	}
}

$stime2 = microtime(true);
$rtime = $stime2-$stime1;

if ($rtime > MNGR_CLAN_BATTLE_INTERVAL) error_log("(mngr_clan_battle: ".getmypid()."): Runtime $rtime sec");
sleep(max(MNGR_CLAN_BATTLE_INTERVAL-$rtime,0));
