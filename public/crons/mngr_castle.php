<? # $Id: mngr_castle.php,v 1.95 2010-01-15 09:50:10 p.knoblokh Exp $

ini_set('memory_limit', '256M');

chdir("..");
require_once("include/common.inc");
require_once("lib/castle_tower.lib");

set_time_limit(300);

define('CASTLE_UPLOAD_LIMIT',50);
define('CASTLE_PRIZE_WINNERS', 3);

$stime1 = time_current();

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) {
	sleep(MNGR_CASTLE_INTERVAL);
	return;
}	

require_once("lib/instance.lib");
require_once("lib/castle.lib");

// Если пришло время осады, то запускаем эту шарманку
$castles = castle_list();
$castle_info_hash = make_hash(castle_info_list(),'castle_id');
$area_ids = array();
foreach($castles as $castle) $area_ids[] = $castle['attack_area_id'];
$areas = make_hash(area_list(array('id' => $area_ids)));
// Создаём инстанс осады, если он ещё не создан
foreach ($castles as $castle) {
	// Создание записи о замке, если такой пока нет
	if (!$castle_info_hash[$castle['id']]) {
		castle_info_save(array(
			'castle_id' => $castle['id'],
			'stime' => $castle['siege_start'],
		));
		continue;
	}
	if ($stime1 <= $castle_info_hash[$castle['id']]['stime']) continue;
	if (!$castle['attack_area_id']) continue;
	if (!$areas[$castle['attack_area_id']]) continue;
	do {
		$castle_instance = instance_get(array('castle_id' => $castle['id'], 'bg_active' => 1));
		if ($castle_instance) break;
		$instance_id = instance_create($castle['inst_artikul_id'],array('bg_active' => 1, 'castle_id' => $castle['id']));
		if (!$instance_id) {
			error_log("(cron_castle: ".getmypid()."): Can't create castle siege: ".$castle['id']);
			break;
		}
		clan_save(array(
			'_add' => sql_pholder(' AND castle_id=?',$castle['id']),
			'_set' => 'castle_id=0',
		));

// Сохраняем время создания инстанса, чтобы в одну неделю не сделать несколько осад одного замка
	} while (0);
}
foreach ($castles as $castle) {
	if ($stime1 <= $castle_info_hash[$castle['id']]['stime']) continue;
	if (!$castle['attack_area_id']) continue;
	if (!$areas[$castle['attack_area_id']]) continue;
	if (!castle_lock($castle['id'])) continue;
	do {
		$instance = instance_get(array('castle_id' => $castle['id'], 'bg_active' => 1));
		if (!$instance) break;
		if (!instance_lock($instance['id'])) break;
		// Отключение пользователей
		$instance_user_list = make_hash(instance_user_list(array('instance_id' => $instance['id'])),'user_id');
		$session_hash = array();
		if ($instance_user_list) {
			$sessions = array();
			foreach($NODE_NUMS as $nn) {
				if ($nn == FRIDGE_NN) continue;
				NODE_PUSH($nn);
				$tmp_sessions = session_list(null,null,true,sql_pholder(' AND uid IN (?@)',array_keys($instance_user_list)),'uid',true);
				foreach($tmp_sessions as $tmp_session) {
					$sessions[] = $tmp_session;
				}
				NODE_POP($nn);
			}
			$session_hash = get_hash($sessions,'uid','uid');
		}
		if ($instance_user_list) foreach ($instance_user_list as $k=>$instance_user) {
			$user_id = $instance_user['user_id'];
			$user_x = user_get($user_id);
			if (!$session_hash[$user_id]) $user = $user_x;
			$instance_root = $user['instance_id'] ? instance_get_root($user['instance_id']) : 0;
			if ($session_hash[$user_id] || $user['fight_id'] || ($instance['id'] != $instance_root['id'])) continue; // не отключаем тех, кто пока в бою или не в этом инстансе
			NODE_PUSH(null, $user_id);
			bonus_apply($user,$castle['exit_bonus_id']); // Радуем бонусом вышедшего
			$user_param = array(
				'id' => $user_id,
				'instance_id' => 0,
				'raid_id' => 0,
				'area_ftime' => 0,
			);
			//user_change_chat_channels($user_x, $user_param);
			user_save($user_param);
			user_resurrect($user_id);
			if ($castle['dead_bonus_id']) bonus_apply($user,$castle['dead_bonus_id']);
			NODE_POP();
			unset($instance_users[$k]);
		}
		// Нужно проверить, не выиграл ли уже кто-нибудь
		$attack_skill = skill_object_get(OBJECT_CLASS_INSTANCE,$instance,array('skill_id' => $castle['attack_skill_id']));
		if ($attack_skill) $attack_skill = $attack_skill['value'];
		if (($attack_skill && ($attack_skill >= $castle['max_score'])) || ($stime1 > ($castle_info_hash[$castle['id']]['stime'] + $castle['siege_duration']))) {
			// Выбор кланов, которые получат награду
			if ($attack_skill && ($attack_skill >= $castle['max_score'])) {
				// Выбор трех самых крутых по урону кланов
				$instance_clan_stats = instance_user_list(array('instance_id' => $instance['id'], 'team' => CASTLE_TEAM_ATTACKERS), ' AND clan_id > 0 GROUP BY 1 ORDER BY 2 desc LIMIT 8 ', 'clan_id, sum(dmg) as dmg');
				$max_dmg = $instance_clan_stats[0]['dmg'];
				$clans_text = $clans_text2 = array();
				
				$clan_score_hash = array();
				$clan_hash = make_hash(clan_list(array('id' => get_hash($instance_clan_stats, 'clan_id', 'clan_id'))));
				foreach ($instance_clan_stats as $stat) {
					$clan_score = $stat['dmg'] + rand(0, $max_dmg);
					$clan_score_hash[$stat['clan_id']] = $clan_score;
					$clan = $clan_hash[$stat['clan_id']];
					$clans_text[] = sprintf(translate('%s (%d урона)'),html_clan_info($clan), $stat['dmg']);
					$clans_text2[] = sprintf(translate('%s (%d), общая сумма %d'),html_clan_info($clan), $clan_score - $stat['dmg'], $clan_score);
				}
				arsort($clan_score_hash);
				$winner_clan_ids = array_keys($clan_score_hash);
				
				if ($castle_info_hash[$castle['id']] && $castle_info_hash[$castle['id']]['money']) {
					$reward = $castle_info_hash[$castle['id']]['money'] / CASTLE_PRIZE_WINNERS;
					castle_info_save(array(
						'id' => $castle_info_hash[$castle['id']]['id'],
						'money' => 0,
					));
					
					$clan_info_list = array();
					for ($i = 0; $i < CASTLE_PRIZE_WINNERS; $i++) {
						if (!($clan_id = $winner_clan_ids[$i])) break; // случилась печаль, кланов меньше, чем надо
						$clan_leader_id = clan_leader_id_get($clan_id);
						$operations = array(MONEY_STAT_OPERATION_RECEIVE,MONEY_STAT_OPERATION_PURE_RECEIVE);
						user_make_payment(MONEY_TYPE_GAME, $clan_leader_id, $reward,'',false,$operations);
						
						$clan_info_list[] = html_clan_info($clan_hash[$clan_id]);
					}

					chat_msg_send_system(sprintf(translate('В ходе осады больше всего урона нанесли кланы %s. Чтобы определить владельца казны, мы прибавим к урону каждого из фаворитов случайно выпавшее в диапазоне от 0 до %d (максимальный нанесенный за осаду урон) число. Победителем будет объявлен тот клан, итоговое число которого, получившееся после сложения, окажется больше, чем у соперников. Кланам выпали следующие значения: %s.'), implode(', ', $clans_text), $max_dmg, implode(', ', $clans_text2)), CHAT_CHF_AREA);
					chat_msg_send_system(sprintf(translate('После длительной и кровопролитной осады воины могущественных кланов %s сумели одержать верх над защитниками замка и завладели казной. Каждый клан получил %s.'), implode(', ', $clan_info_list), html_money_str(MONEY_TYPE_GAME, $reward)),CHAT_CHF_AREA);
				} else {
					chat_msg_send_system(translate('Казна замка оказалась пуста! Никто не получил награды!'),CHAT_CHF_AREA);
				}
				
				skill_object_delete(OBJECT_CLASS_AREA,$castle['castle_area_id']);
				$winner_team = CASTLE_TEAM_ATTACKERS;
			} else {
				chat_msg_send_system(translate('Ни одной из команд-захватчиков не удалось отбить золото у поганой нежити. Дракон захватил казну и празднует победу!'),CHAT_CHF_AREA);
				$winner_team = CASTLE_TEAM_DEFENDERS;
			}
			
			// Инст завершается - обновим статус башень
			// Если завершился только 1 инст - поставим статус ожидания второго
			
			$tower = castle_tower_get(false, ' and 1');
			if ($tower['status'] == CT_STATUS_STANDART) {
				// сбросим владельца и поставим статус
				castle_tower_save(array(
					'_set' => sql_pholder('stage=0,clan_id=0,status=?', CT_STATUS_WAITING_OPP),
					'_add' => sql_pholder(' and status=?', CT_STATUS_STANDART),
				));
				castle_tower_area_save(array(
					'_set' => sql_pholder('tower_id=0'),
					'_add' => ' and 1',
				));
			} elseif ($tower['status'] == CT_STATUS_WAITING_OPP) {
				// показатель того что нужно всем замкам поставить одинаковое время следующей осады
				$sync_castles = true;
				
				// если только нас и ждали, ставим статус и время осады.
				// распределим в каких локациях будут проходить битвы за какие башни
				castle_tower_save(array(
					'_set' => sql_pholder('next_stage_time=?,status=?, clan_id=0', $stime1+CASTLE_TOWERS_TIMEOUT, CT_STATUS_RUNNING),
					'_add' => sql_pholder(' and status=?', CT_STATUS_WAITING_OPP),
				));
				
				$towers = castle_tower_list();
				global $kind_info;
				foreach($towers as $tower) {
					//берём нераспределённые локации и распределяем их по башням 
					$area_ids = array();
					foreach($kind_info as $kind_id => $kind) {
						$area = castle_tower_area_get(array('tower_id' => 0, 'kind_id' => $kind_id),' order by rand()');
						if (!$area) {
							error_log("(mngr_castle: ".getmypid()."): No areas for tower");
							break 2;
						}
						$area_ids[] = $area['area_id'];				
					}
					castle_tower_area_save(array(
						'_set' => sql_pholder('tower_id=?', $tower['id']),
						'_add' => sql_pholder(' and area_id in (?@)', $area_ids),
					));
					// проставляем в заявках за какие башни будут биться кланы
					castle_tower_request_save(array(
						'_set' => sql_pholder('tower_id=?', $tower['id']),
						'_add' => sql_pholder(' and area_id in (?@)', $area_ids),
					));
					
					foreach($area_ids as $area_id) {
						bot_clear_from_area($area_id, true);
					}
				}
				$clans = make_hash(castle_tower_request_list(),'clan_id');
				if ($clans) {
					$msg = translate('Волны нежити атакуют подземелье Вашего замка! Спешите на защиту!');
					chat_msg_send_system($msg,CHAT_CHF_CLAN,array_keys($clans));
				}
			}
			
			if (!$clans) $clans = make_hash(castle_tower_request_list(),'clan_id');
			foreach($clans as $clan_id => $request) {
				$transfer_users = array();
				//берём всех пользователей клана
				
				$clan_user_list = user_list(array('clan_id'=>$clan_id),' and instance_id>0');
				//выбираем, тех которые в инсте замка
				
				foreach($clan_user_list as $clan_user) {
					$user_instance = instance_get_root($clan_user['instance_id']);
					if ($user_instance['castle_id']) {
						$transfer_users[] = $clan_user['id'];
					}
				}
				
				if (!$transfer_users) continue;
				
				// перемещаем всех в локации для обороны
				$params = array(
					'_set' => sql_pholder(' area_id = ?', $request['area_id']),
					'_add' => sql_pholder(' and id in (?@)', $transfer_users),
				);
				common_save($db_3,TABLE_USERS,$params);
			}
					
			$user_ids = array();
			if ($instance_user_list) {
				foreach ($instance_user_list as $instance_user) $user_ids[] = $instance_user['user_id'];
				$users = make_hash(user_list(array('id' => $user_ids)));
				foreach ($instance_user_list as $instance_user) {
					$user = $users[$instance_user['user_id']];
					if (!$user) continue;
// Отменяем бои, если пользователь на данный момент в инстансе замка
					if ($user['instance_id']) {
						$user_instance = instance_get_root($user['instance_id']);
						if (($user_instance['id'] == $instance['id'])) {
							if ($user['fight_id'] && fight_lock($user['fight_id'])) {
								fight_abort($user['fight_id']);
								fight_unlock($user['fight_id']);
							}
// Выбрасывание пользователя из инстанса замка, если он в нём
							$user_param = array(
								'id' => $user['id'],
								'instance_id' => 0,
								'raid_id' => 0,
								'area_ftime' => 0,
							);
							//user_change_chat_channels($user, $user_param);
							user_save($user_param);
							user_resurrect($user);
						}
					}
// Раздача слонов
					NODE_PUSH(null,$user['id']);
					bonus_apply($user,($winner_team == CASTLE_TEAM_ATTACKERS) ? $castle['winner_bonus_id'] : $castle['loser_bonus_id']);
					NODE_POP();
				}
			}
			
			castle_user_delete(array('castle_id' => $castle['id']));
			instance_save(array(
				'id' => $instance['id'],
				'bg_active' => 0,
				'dtime' => time_current() + INSTANCE_LEAVE_TIME, // Добавляем время, сколько должна храниться статистика (неделя)
			));
			if ($sync_castles) {
				castle_info_save(array('_add' => ' and 1', 'stime' => ((($castle_info_hash[$castle['id']]['stime'] + $castle['siege_delay']) > $stime1) ? $castle_info_hash[$castle['id']]['stime'] : $stime1 )  + $castle['siege_delay']));
			} else {
				castle_info_save(array('id' => $castle_info_hash[$castle['id']]['id'], 'stime' => ((($castle_info_hash[$castle['id']]['stime'] + $castle['siege_delay']) > $stime1) ? $castle_info_hash[$castle['id']]['stime'] : $stime1 )  + $castle['siege_delay']));
			}
			chat_msg_send_special(CODE_REDIRECT,CHAT_CHF_USER,$user_ids,array('url' => 'instance_stat.php?instance_id='.$instance['id'].'&finish=1'));
			break;
		}
		$instance_hash = get_hash(instance_list(array('root_id' => $instance['id'])));
		$users_in_instance_cnt = make_hash(user_list(array('instance_id' => array_keys($instance_hash)),' GROUP BY 1', false, sql_pholder('raid_id - ? as team, count(*) as cnt',$instance['id'])),'team');
		
// Определение точек входа для команд
		$start_ids = array();
		foreach ($castle_hash as $number => $team) {
			$artikul_id = $castle[$team['name'].'_start_id'];
			if (!$artikul_id) continue;
			if ($instance['artikul_id'] == $artikul_id) {
				$start_ids[$number] = $instance['id'];
				continue;
			}
			$inst = instance_get(array('artikul_id' => $artikul_id, 'root_id' => $instance['id']));
			$start_ids[$number] = $inst['id'];
		}
		$user_ids = array();
		foreach (castle_user_list(array('castle_id' => $castle['id'])," order by stime LIMIT ".CASTLE_UPLOAD_LIMIT) as $castle_user) {
			if (!user_is_online($castle_user['user_id'], true)) {
				castle_user_delete($castle_user['id']);
				continue;
			}
			$team = $castle_user['team'];
// Добавляем в очередь заявку, если есть вакантные места в инстансе
			if (($castle[$castle_hash[$team]['name'].'ers_cnt'] - $users_in_instance_cnt[$team]['cnt'] - count($queues[$team])) > 0) {
				$instance_user = instance_user_get(array('instance_id' => $instance['id'], 'user_id' => $castle_user['user_id']));
				$user = user_get($castle_user['user_id']);
				if (!$instance_user) instance_user_save(array(
						'_mode'			=> CSMODE_REPLACE,
						'instance_artikul_id' => $instance['artikul_id'],
						'instance_id'	=> $instance['id'],
						'user_id'		=> $castle_user['user_id'],
						'clan_id'		=> $user['clan_id'],
						'team'			=> $team,
					)); 
				
					
				$user_param = array(
					'id' => $castle_user['user_id'],
					'invisibility_time' => 0, // сбрасываем невидимость
					'instance_id' => $start_ids[$team] ? $start_ids[$team] : $instance['id'],
					'raid_id' => $instance['id'] + $team,	// рейд-чат
					'area_ftime' => 0,
				);
				//user_change_chat_channels($user, $user_param);
				user_save($user_param);
				
				chat_msg_send_special(CODE_CALL_JSFUNC, CHAT_CHF_USER, $castle_user['user_id'], array('func' => "updateSwf({'lvl':''})"));
				
				user_resurrect($castle_user['user_id']);
				castle_user_delete($castle_user['id']);
				$user_ids[] = $castle_user['user_id'];
				$users_in_instance_cnt[$team]['cnt']++;
			}
		}
// Сообщение участникам, что скоро закончится осада (в оба канала)
		$time_left = $instance['dtime'] - $stime1;
		if (($time_left > 0) && ($time_left <= INSTANCE_WARNING_TIME) && (($time_left % 60) <= MNGR_CASTLE_INTERVAL)) {
			chat_msg_send_system(sprintf(translate('До принудительного окончания осады замка осталось %s.'),html_period_str($time_left)),CHAT_CHF_RAID,array($instance['id'],$instance['id']+1),true);
		}
		instance_unlock($instance['id']);
// Перекидываем в инстанс новых бойцов
		if ($user_ids) {
			chat_msg_send_special(CODE_REDIRECT,CHAT_CHF_USER,$user_ids,array('url' => 'instance.php', 'flag' => '__instance_php__'));
			chat_msg_send_special(CODE_CALL_JSFUNC,CHAT_CHF_USER,$user_ids,array('func' => "updateSwf({'lvl':''})"));
		}
	} while (0);
	castle_unlock($castle['id']);
}

// обработаем башни за которые идёт битва
$castle_towers = castle_tower_list(' and status='.CT_STATUS_RUNNING);

foreach($castle_towers as $tower) {
	castle_tower_log('tower_id=? stage=?', $tower['id'], $tower['stage']);
	
	//Сразу проверим есть ли соперничество, если на башню никто не претендует - меняем статус
	$castle_requests = make_hash(castle_tower_request_list(sql_pholder(' and tower_id=?',$tower['id'])), 'kind_id');
	if (!$castle_requests) {
		castle_tower_log(' no requests found');
		castle_tower_save(array(
			'_set' => sql_pholder('clan_id=0,stage=0,status=?',CT_STATUS_STANDART),
			'_add' => sql_pholder(' and id=?',$tower['id']),
		));
		continue;
	}
	
	$request = reset($castle_requests);
	// Если на башню претендует 1 клан, то он сразу её и получает
	if (count($castle_requests) == 1) {
		castle_tower_save(array(
			'_set' => sql_pholder('clan_id=?,status=?',$request['clan_id'],CT_STATUS_STANDART),
			'_add' => sql_pholder(' and id=?',$tower['id']),
		));
		
		$msg = translate('Поздравляем, Ваш клан захватил подземелье башни');
		chat_msg_send_system($msg,CHAT_CHF_CLAN,array('clan_id' => $request['clan_id']));
		
		castle_tower_request_delete(array('tower_id'=>$tower['id']));
		
		castle_tower_log('win(no requests) clan_id=?', $request['clan_id']);
		continue;
	}
	
	// если ещё не пришло время первой волны 
	if (!$tower['stage'] && ($tower['next_stage_time'] > $stime1)) {
		castle_tower_log(' waiting for stage=? sec=?', $tower['stage'], $tower['next_stage_time'] - $stime1);
		continue;
	}

	// если new_stage станет true значит нужно переходить на следующую стадию
	$new_stage = false;
	$winner_kind = 0;
	
	// сортировка на случай если одновременно убьют волну - победитель тот, кто первый свалил дракона
	$order = ($request['kind_id'] == KIND_HUMAN) ? 'asc' : 'desc';
	$tower_areas = castle_tower_area_list(sql_pholder(' and tower_id=? order by kind_id '.$order, $tower['id']));
		
	// может быть пора запустить следующую волну по времени
	if (!$new_stage) {
		if ($stime1>$tower['next_stage_time']) {
			$new_stage = true;	
		}
		castle_tower_log(' time to next wave sec=?', $tower['next_stage_time'] - $stime1);
	}
	
	// пробежимся по всем локациям и посчитаем количество мобов системного события
	// если в какой-то локации ботов не окажется, значит всех убили и нужно запустить следующий этап
	if ($tower['stage'] > 0) {
		$bot_current_count = array();
		foreach($tower_areas as $tower_area) {
			$area_id = $tower_area['area_id'];
			if (!isset($areas[$area_id]))	$areas[$area_id] = area_get($area_id);
			$bot_artikuls = make_hash(area_bot_list(array('area_id' => $area_id, 'event_artikul_id' => CASTLE_EVENT_ID)), 'bot_artikul_id');
			$bots  =  ($bot_artikuls) ? bot_list(array_keys($bot_artikuls), null, $area_id) : array();
			
			if (count($bots) == 0) {
				$new_stage = true;
			}
			
			$bot_current_count[$tower_area['kind_id']] = count($bots);
			
			castle_tower_log('area_id=? new_stage=? bots=?', $area_id, $new_stage ? 1 : 0, count($bots));
		}
	}

	if ($new_stage) {
		castle_tower_log(' processing stage=?', $tower['stage']);
		
		// берём следующий этап, чтобы понять завершилась ли битва или нужно рассадить ботов
		$event_point = event_artikul_point_get(array('artikul_id' => CASTLE_EVENT_ID), sql_pholder(' and point_num>? order by point_num', $tower['stage']));
		
		// если не нашли, значит пишем победителя, сбрасываем статус
		if (!$event_point) {
			// победителей может и не быть, а лузерков может быть несколько
			$winner_kind = 0;
			$winner_clan_id = 0;
			$loser_clan_ids = array();
			
			// выбираем победителя
			if ($bot_current_count[KIND_HUMAN] < $bot_current_count[KIND_MAGMAR]) $winner_kind = KIND_HUMAN;
			elseif ($bot_current_count[KIND_HUMAN] > $bot_current_count[KIND_MAGMAR]) $winner_kind = KIND_MAGMAR;
			
			if ($winner_kind == 0) {
				if (mt_rand(1,100) >= 50) $winner_kind = KIND_HUMAN;
				else $winner_kind = KIND_MAGMAR;
			}
			
			foreach($castle_requests as $request) {
				if ($request['kind_id'] == $winner_kind) {
					$winner_clan_id = $request['clan_id'];
				} else {
					$loser_clan_ids[] = $request['clan_id'];
				}
			}
			
			castle_tower_save(array(
				'_set' => sql_pholder('stage=0,clan_id=?,status=?', $winner_clan_id, CT_STATUS_STANDART),
				'_add' => sql_pholder(' and id=?',$tower['id']),
			));
			
			if ($winner_clan_id) {
				$loser_clan_info = html_clan_info(clan_get(intval(reset($loser_clan_ids))));
				$msg = sprintf(translate('Поздравляем, Ваш клан захватил подземелье башни. Вашим противником был клан %s'), $loser_clan_info);
				chat_msg_send_system($msg,CHAT_CHF_CLAN,array('clan_id' => $winner_clan_id));
				// и выдадим всем участникам бонус победителя
				if (defined('CASTLE_TOWER_WINNER_BONUS')) {
					$tower_area = castle_tower_area_get(array('tower_id'=>$tower['id'], 'kind_id' => $winner_kind));
					$winners_list = user_list(array('clan_id'=>$winner_clan_id, 'area_id'=>intval($tower_area['area_id'])));
					foreach($winners_list as $user) {
						bonus_apply($user, CASTLE_TOWER_WINNER_BONUS);
					}
				}
			}
			$winner_clan_info = html_clan_info(clan_get($winner_clan_id));
			$msg = sprintf(translate('К сожалению, Вашему клану не удалось отстоять подземелье башни. Вашим противником был клан %s'), $winner_clan_info);
			chat_msg_send_system($msg,CHAT_CHF_CLAN,$loser_clan_ids);
			
			// удаляем заявки
			castle_tower_request_delete(array('tower_id'=>$tower['id']));
			
			// нужно выкинуть лузерков из локаций, которые они не отстояли
			$tower_areas_clean = make_hash(castle_tower_area_list(sql_pholder(' and tower_id=? and kind_id!=? ',$tower['id'], $winner_kind)),'area_id');
			foreach($tower_areas_clean as $tower_area) {
				// выдадим бонус проигравшего
				if (defined('CASTLE_TOWER_LOSER_BONUS')) {
					$losers_list = user_list(array('clan_id'=>$loser_clan_ids, 'area_id'=>$tower_area['area_id']));
					foreach($losers_list as $user) {
						bonus_apply($user, CASTLE_TOWER_LOSER_BONUS);
					}
				}
				
				// выгоняем всех из неотбитой локации
				$params = array(
					'_set' => sql_pholder(' area_id = ?, instance_id = 0, raid_id = 0 ', $kind_info[$tower_area['kind_id']]['city_area_id']),
					'_add' => sql_pholder(' and area_id=?', $tower_area['area_id']),
				);
				common_save($db_3,TABLE_USERS,$params);
			}
			
			castle_tower_log('win clan_id=?', $winner_clan_id);
			
			// и переходим к обсчёту следующих башень
			continue;
		}
		
		// меням stage и рассаживаем ботов
		foreach($tower_areas as $tower_area) {
			$area_id = $tower_area['area_id'];
			if (!isset($areas[$area_id])) $areas[$area_id] = area_get($area_id);
			
			if ($tower['stage'] == 0) bot_clear_from_area($area_id);
			$bots2plant = area_bot_list(array('area_id' => $area_id, 'event_artikul_id' => CASTLE_EVENT_ID, 'event_point_id'=> $event_point['point_id']));
			foreach($bots2plant as $bot) {
				$result = bot_create($bot['bot_artikul_id'],$bot['cnt_max'],$area_id, false, array('flags' => BOT_FLAG_IGNORE_CONDITIONS));
			}
			
			$cache = new Cache('HUNT'.$area_id.'_0');
			if ($cache->tryLock()) {
				$cache->remove();
				$cache->freeLock();
			}
			
			$num_hash = array(
				1 => translate('Первая'),
				2 => translate('Вторая'),
				3 => translate('Третья'),
				4 => translate('Четвертая'),
				5 => translate('Пятая'),
				6 => translate('Шестая'),
			);
			
			$msg = sprintf(translate('%s волна монстров появилась в подземелье башни'), $num_hash[$tower['stage']+1]);
			chat_msg_send_system($msg,CHAT_CHF_CLAN,array('clan_id' => $castle_requests[$tower_area['kind_id']]['clan_id']));
		}
		
		$point = event_point_get($event_point['point_id']);
		$next_stage_time = time()+$point['duration'];
		
		castle_tower_save(array(
			'_set' => sql_pholder('stage=stage+1, next_stage_time=?', $next_stage_time),
			'_add' => sql_pholder(' and id=?',$tower['id']),
		));
	}
}

$stime2 = time();
$rtime = $stime2-$stime1;
if ($rtime > MNGR_CASTLE_INTERVAL) error_log("(mngr_castle: ".getmypid()."): Runtime $rtime sec");
sleep(max(MNGR_CASTLE_INTERVAL-$rtime,0));

function castle_tower_log() {
	$args = func_get_args();
	
	if (!$args) {
		return;
	} elseif (count($args) == 1) {
		$msg = $args[0];
	} else {
		$format = str_replace('?', '%s' ,array_shift($args));
		$msg = vsprintf($format, $args);
	}
		
	logfile(SERVER_ROOT.PATH_LOGS.'castle_tower.log', $msg);
}

?>