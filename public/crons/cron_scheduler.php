<? # $Id: cron_scheduler.php,v 1.64 2010-03-10 12:07:12 v.krutov Exp $
 
chdir("..");
require_once("include/common.inc");
require_once("lib/crossserver.lib");
require_once("lib/money_transfer.lib");
require_once("lib/fight_scheduler.lib");
require_once("lib/activity.lib");

require_once("lib/common.lib");
require_once("lib/afk.lib");

common_define_settings();
ini_set('default_socket_timeout',5);

// ---- Сохранение новостей для главной страницы в кэше
/* $news = @file_get_contents(INFOPORTAL_NEWS_URL);
echo INFOPORTAL_NEWS_URL;
if ($news) {
    $cache = new Cache('INDEX_DATA');
    $cache->update($news,600);
}
$banners = @file_get_contents(INFOPORTAL_BANNERS_URL);
if ($banners) {
    $cache = new Cache('INDEX_BANNERS');
    $cache->update($banners,600);
} */

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) return;

require_once("lib/chat.lib");
require_once("lib/scheduler.lib");
require_once("lib/instance.lib");
require_once("lib/stronghold.lib");
require_once("lib/area.lib");
require_once("lib/arena.lib");
require_once("lib/adv_chaot.lib");
require_once("lib/chat_deleter.lib");
require_once("lib/friend.lib");

set_time_limit(0);

require_once("lib/squest.lib");
squests_cron();

require_once("lib/day_promocode.lib");
day_promocode_cron();

require_once("lib/area_bank.lib");
area_bank_rate_cron();

// ---- ПЛАНИРОВЩИК ----
$task_list = scheduler_task_list(false,sql_pholder(" AND time_start>0 AND period>0 AND cnt>0 AND time_start<=? AND stime+period<=?",time_current(),time_current()));
foreach ($task_list as $task) {
$param=array();    
if ($task["user_level_start"] > 0) $param["user_level_start"] = (int)$task["user_level_start"];
if ($task["user_level_end"] > 0) $param["user_level_end"] = (int)$task["user_level_end"];
$param["user_kind"]=$task['user_kind'];
	switch ($task['type']) {
		case SCHEDULER_TASK_TYPE_BROADCAST:
			chat_msg_send_broadcast($task['msg_text'], $task['user_id'], !($task['flags'] & SCHEDULER_TASK_FLAG_HELP), $task['user_kind'], array_merge($param, array(
				'delay'=>$task['flags'] & SCHEDULER_TASK_FLAG_DELAY
			)));
			break;
		case SCHEDULER_TASK_TYPE_SYSTEM:
			chat_msg_send_system($task['msg_text'],CHAT_CHF_AREA, false, !($task['flags'] & SCHEDULER_TASK_FLAG_HELP), $param);
			break;
		case SCHEDULER_TASK_TYPE_TITLE:
			chat_msg_send_special(CODE_CHANGE_TITLE,CHAT_CHF_AREA,false, array_merge($param, array('msg_text' => $task['msg_text'])));
			break;
	}
	$stime = intval((time_current()-$task['time_start'])/$task['period'])*$task['period'] + $task['time_start'];
	scheduler_task_save(array(
		'id' => $task['id'],
		'cnt' => $task['cnt'] - 1,
		'stime' => $stime,
	));
}


// развоплощение инстансов
$instance_list = instance_list(array('parent_id' => 0, 'bg_active' => 0, 'dun_active' => 0),sql_pholder(" AND dtime>0 AND dtime<=?",time_current()+INSTANCE_WARNING_TIME));
foreach ($instance_list as $instance) {
	$d = $instance['dtime'] - time_current();
	if ($instance['party_id']) {
		$msg_text = $d > 0 ? sprintf(translate('Развоплощение инстанса "%s" будет произведено через %s.'),$instance['title'],html_period_str($d)) : sprintf(translate('Инстанс "%s" был развоплощен.'),$instance['title'],html_period_str($d));
		chat_msg_send_system($msg_text,CHAT_CHF_PARTY,$instance['party_id'],true);
	}
	if ($d > 0) continue;
	if (!instance_lock($instance['id'])) continue;
	instance_delete($instance);
	instance_unlock($instance['id']);
}
instance_user_delete(false," AND dtime>0 AND dtime<=".time_current());


// ---- КВЕСТЫ ----
// перевод завершенных повторяющихся квестов в режим просмотра
foreach ($NODE_NUMS as $nn) {
	if (!NODE_SWITCH($nn)) continue;
	quest_user_save(array('_mode' => CSMODE_UPDATE, 'status' => QUEST_STATUS_AVAIL, 'dtime' => 0, 'point_num' => 0), ' AND dtime>0 AND dtime<='.time_current().' AND status='.QUEST_STATUS_FINISHED);
}

// ---- Сохранение футера Rambler в кэше
/*if (defined('RAMBLER_FOOTER_URL') && RAMBLER_FOOTER_URL) {
	$data = @file_get_contents(RAMBLER_FOOTER_URL);
	if ($data) {
		$data=preg_replace('/<noscript.*?<\/noscript>/s', '', $data);
		preg_match('/(<div style=.*?<\/div>)/', $data, $matches);
		$rdata['hidden_banner'] = $matches[1];
		preg_match('/<div class="bottomBanner">(.*?)<\/div>/s', $data, $matches);
		$rdata['banner'] = $matches[1];
		preg_match('/(<table width="90%" .*?<\/table>)/s', $data, $matches);
		$rdata['links'] = $matches[1];
		$rdata['links'] = preg_replace('/width="90%"/', 'width="100%"', $rdata['links']);
		$rdata['links'] = preg_replace('/cellpadding="7"/', 'cellpadding="3"', $rdata['links']);
		preg_match('/<td valign="top" nowrap width="100%">(.*?)<\/td>/s', $data, $matches);
		$rdata['rlinks'] = $matches[1];
		preg_match('/>(Copyright.*?)<\/td>/', $data, $matches);
		$rdata['copy'] = $matches[1];
		$cache = new Cache('INDEX_RAMBLER_FOOTER');
		$cache->update(serialize($rdata),600);
	}
}
// ---- Сохранение настроек для регистрации для главной страницы
if ($SITE_CFG) {
	if ($PARTNERS_CFG) {
		foreach($PARTNERS_CFG as $partner=>$data) {
			$SITE_CFG_local = $PARTNERS_CFG[$partner]['site_cfg'];
			$register_info = array();
			foreach($SITE_CFG_local as $site => $info) {
				$data = unserialize(@file_get_contents("http://".$site."/pub/register_status.php"));
				$register_info[$site] = $data;
			}
			if ($register_info) {
				$cache = new Cache('INDEX_REGISTER'.$partner);
				$cache->update($register_info,600);
			}
		}
	} else {
		$register_info = array();
		foreach($SITE_CFG as $site => $info) {
			$data = unserialize(@file_get_contents("http://".$site."/pub/register_status.php"));
			$register_info[$site] = $data;
		}
		if ($register_info) {
			$cache = new Cache('INDEX_REGISTER');
			$cache->update($register_info,600);
		}
	}
}*/

// -------- КРЕПОСТИ (stronghold) ---------
$now_time = time_current();
$strongholds = stronghold_list();
$stronghold_info_hash = make_hash(stronghold_info_list(),'stronghold_id');

$area_ids = array();
$clan_hash = array();
foreach ($strongholds as $stronghold) {
	$area_ids[] = $stronghold['stronghold_area_id'];
	$area_ids[] = $stronghold['yard_area_id'];
}
if ($area_ids) $area_hash = make_hash(area_list(array('id' => $area_ids)));

foreach ($strongholds as $stronghold) {
	// Создание записи о крепости в info, если такой ещё нет
	$stronghold_info = $stronghold_info_hash[$stronghold['id']];
	if (!$stronghold_info) {
		stronghold_info_save(array(
			'stronghold_id' => $stronghold['id'],
			'siege_start_time' => $stronghold['siege_start_time'],
			'siege_duration' => $stronghold['siege_duration'],
			'siege_delay' => $stronghold['siege_delay'],
		));
		continue;
	}

	// Проверяем установлено ли время начала осады
	if (!$stronghold_info['siege_start_time']) continue;
	// Проверяем установлены ли параметры локации крепости и её двора
	if (!$stronghold['stronghold_area_id'] || !$stronghold['yard_area_id']) continue;
	// Проверяем существуют ли локации такие вообще
	if (!$area_hash[$stronghold['stronghold_area_id']] || !$area_hash[$stronghold['yard_area_id']]) continue;
	// Проверяем указаны ли артикулы ботов защитника и нападающего
	if (!$stronghold['defender_bot_id'] || !$stronghold['attacker_bot_id']) continue;

	// Не запускаем крепости с истекшим временем начала осады
	if ($stronghold_info['siege_start_time'] + $stronghold_info['siege_duration'] <= $now_time) continue;

	// Проверяем есть ли не завершённые бои в локации крепости
	if (fight_count($stronghold['stronghold_area_id'], null, FIGHT_STATUS_RUNNING, sql_pholder(' AND flags & ?#FIGHT_FLAG_STRONGHOLD '))) continue;

	// Проверяем не пора ли запускать очередную осаду крепости
	if (($now_time > $stronghold_info['siege_start_time']) && ($now_time < $stronghold_info['siege_start_time'] + $stronghold_info['siege_duration'])) {
		// Лочим крепость, чтобы не подавали больше пока заявки
		if (!stronghold_lock($stronghold['id'])) continue;
		do {
			// Выкидываем из крепости ее прежних владельцев
			if ((int)$stronghold_info['owner_clan_id']) stronghold_unman($stronghold, $stronghold_info);
			// обновляем фарм
			stronghold_farm_refresh($stronghold);

			// Определяем кланы, получающие право на осаду, как первых из списка подавших заявки
			$stronghold_clan_attacker = array();
			$stronghold_clan_defender = array();

			$stronghold_clans = make_hash(stronghold_clan_list(array('stronghold_id' => $stronghold['id']), ' AND queue_time > 0','id,stronghold_id,clan_id, last_siege_time'),'clan_id');
			$clans_members = get_hash_grp(clan_member_list(array_keys($stronghold_clans), CM_STATUS_ACTIVE), 'clan_id', 'user_id', 'user_id');
			
			// выпиливаем из выборки кланы, у которых не хватает онлайна, они не будут участвовать в осаде
			foreach($stronghold_clans as $stronghold_clan_id => $stronghold_clan) {
				$clan_online_count = 0;
				$clan_members_online_state_hash = user_is_online_global($clans_members[$stronghold_clan_id]);
				foreach($clan_members_online_state_hash as $state) if($state) $clan_online_count++;
				if ($clan_online_count < $stronghold['fight_size']) {
					unset($stronghold_clans[$stronghold_clan_id]);
					chat_msg_send_system(sprintf(translate('Для участия в битве за крепость необходимо не менее %d пользователей онлайн!'), $stronghold['fight_size']), CHAT_CHF_CLAN, array('clan_id' => $stronghold_clan_id), true);
				}
			}
			
			// если кланов в очереди меньше двух, очевидно, ничего не будет
			if(count($stronghold_clans) >= 2) {
				// Расчет очков
				$clan_scores = array();
				foreach ($stronghold_clans as $stronghold_clan_id => $stronghold_clan) {
					$clan_scores[$stronghold_clan_id] = 0;
					// бонусы за длительное неучастие
					if(!$stronghold_clan['last_siege_time']) {
						$clan_scores[$stronghold_clan_id] += STRONGHOLD_QUEUE_MAX_TIME_BONUS;
					} else {
						$clan_delay = intval((time_current() - $stronghold_clan['last_siege_time']) / (60 * 60 * 24));
						if($clan_delay >= STRONGHOLD_QUEUE_MAX_TIME_BONUS) {
							$clan_scores[$stronghold_clan_id] += STRONGHOLD_QUEUE_MAX_TIME_BONUS;
						} else {
							$clan_scores[$stronghold_clan_id] += intval($clan_delay);
						}
					}
					// рандомный бонус для встряски очереди
					if (defined('STRONGHOLD_QUEUE_MAX_RANDOM_BONUS') && STRONGHOLD_QUEUE_MAX_RANDOM_BONUS) {
						$clan_scores[$stronghold_clan_id] += mt_rand(1, STRONGHOLD_QUEUE_MAX_RANDOM_BONUS);
					}
				}

				// первый проход, выбираем клан - защитника
				$score_groups_hash = array();
				foreach($clan_scores as $clan_id => $score) $score_groups_hash[$score][] = $clan_id;
				
				ksort($score_groups_hash);
				$winner_group = array_pop($score_groups_hash);
				if(count($winner_group) > 1) {
					shuffle($winner_group);
				}
				$stronghold_clan_defender = $stronghold_clans[$winner_group[0]];
				unset($clan_scores[$stronghold_clan_defender['clan_id']]);
				
				// второй проход, выбираем клан - нападающего
				$defender_clan_leader = user_get(clan_leader_id_get($stronghold_clan_defender['id']));
				foreach($clan_scores as $clan_id => $score) {
					// нужно добавить очков всем кланам, которые противоположной дефендеру расы
					$clan_leader = user_get(clan_leader_id_get($clan_id));
					if($clan_leader['kind'] != $defender_clan_leader['kind']) $clan_scores[$clan_id] += STRONGHOLD_QUEUE_MAX_RACE_BONUS;
				}
				
				$score_groups_hash = array();
				foreach($clan_scores as $clan_id => $score) $score_groups_hash[$score][] = $clan_id;
				
				ksort($score_groups_hash);
				$winner_group = array_pop($score_groups_hash);
				if(count($winner_group) > 1) {
					shuffle($winner_group);
				}
				$stronghold_clan_attacker = $stronghold_clans[$winner_group[0]];
			}

			// Обнуляем остальные заявки на эту крепость
			stronghold_clan_save(array('stronghold_id' => $stronghold['id'], 'queue_time' => 0), 'stronghold_id');
			if (!$stronghold_clan_attacker || !$stronghold_clan_defender) {
				$msg_text = sprintf(translate('Бой за крепость %s не состоялся.'), $stronghold['title']);
				$msg = array(
					'type' => CHAT_MSG_TYPE_BROADCAST,
					'msg_text' => $msg_text,
					'urgent' => true,
				);
				chat_msg_send($msg, CHAT_CHF_AREA);
				break;
			}
			// Запоминаем атакующий клан
			$stronghold_info['attacker_clan_id'] = $stronghold_clan_attacker['clan_id'];
			// и выставляем ему время осады
			$stronghold_clan_attacker['last_siege_time'] = time_current();
			$stronghold_clan_attacker['queue_time'] = 0;
			stronghold_clan_save($stronghold_clan_attacker);
			
			// Запоминаем защищающийся клан
			$stronghold_info['defender_clan_id'] = $stronghold_clan_defender['clan_id'];
			// и выставляем ему время осады
			$stronghold_clan_defender['last_siege_time'] = time_current();
			$stronghold_clan_defender['queue_time'] = 0;
			stronghold_clan_save($stronghold_clan_defender);
			
			// Создаём ботов
			$bot_param = array('flags' => BOT_FLAG_TEMP);
			$bot_param['kind'] = STRONGHOLD_TEAM_DEFENDERS;
			$defender_bot_id = bot_create($stronghold['defender_bot_id'], 1, $stronghold['stronghold_area_id'], false, $bot_param);
			$bot_param['kind'] = STRONGHOLD_TEAM_ATTACKERS;
			$attacker_bot_id = bot_create($stronghold['attacker_bot_id'], 1, $stronghold['stronghold_area_id'], false, $bot_param);
			$bot_hash = make_hash(bot_list(null, null, null, null, sql_pholder(' AND id IN (?@) ', array($defender_bot_id, $attacker_bot_id))));
			$defender_bot = $bot_hash[$defender_bot_id];
			$attacker_bot = $bot_hash[$attacker_bot_id];
			// Стартуем бой за крепость
			$fight = array(
				'area_id' => $stronghold['stronghold_area_id'],
				'instance_id' => 0,
				'title' => translate('Осада крепости!'),
				'type' => FIGHT_TYPE_DUEL,
				'timeout' => 20,
				'level_min' => min($defender_bot['level'], $attacker_bot['level']),
				'level_max' => max($defender_bot['level'], $attacker_bot['level']),
				'team_max' => 0,
				'flags' => 0,
			);
			// Ставим флаг, что это бой за крепость
			$fight['flags'] |= FIGHT_FLAG_STRONGHOLD;
			$pers_data = array(
				array($object_table_info[$defender_bot['object_class']]['link'] => $defender_bot['id'], 'team' => STRONGHOLD_TEAM_DEFENDERS),
				array($object_table_info[$attacker_bot['object_class']]['link'] => $attacker_bot['id'], 'team' => STRONGHOLD_TEAM_ATTACKERS),
			);
			$stronghold_info['siege_fight_id'] = fight_start($fight, $pers_data, $param);
			if (!$stronghold_info['siege_fight_id']) break;
			$stronghold_clan_hash = make_hash(clan_list(array('id' => array($stronghold_info['defender_clan_id'], $stronghold_info['attacker_clan_id']))));
			$clan_defender = $stronghold_clan_hash[$stronghold_info['defender_clan_id']];
			$clan_attacker = $stronghold_clan_hash[$stronghold_info['attacker_clan_id']];
			$msg_text = sprintf(translate('Начался <a href="#" onClick="showFightInfo(%d);return false;">бой за крепость</a> между кланами %s <a href="#" onclick="showClanInfo(%d)"><img src="%s" width=13 height=13 border=0 align="absmiddle"></a> и %s <a href="#" onclick="showClanInfo(%d)"><img src="%s" width=13 height=13 border=0 align="absmiddle"></a>.'), $stronghold_info['siege_fight_id'], $clan_attacker['title'], $clan_attacker['id'], PATH_IMAGE_CLANS.$clan_attacker['picture'], $clan_defender['title'], $clan_defender['id'], PATH_IMAGE_CLANS.$clan_defender['picture']);
			$msg = array(
				'type' => CHAT_MSG_TYPE_BROADCAST,
				'msg_text' => $msg_text,
				'urgent' => true,
			);
			chat_msg_send($msg, CHAT_CHF_AREA);
		} while (0);
		$stronghold_info['siege_start_time'] = $stronghold_info['siege_start_time'] + $stronghold_info['siege_duration'] + $stronghold_info['siege_delay'];
		stronghold_info_save($stronghold_info);
		stronghold_unlock($stronghold['id']);
	}
}

/**
 * "#90173 - Клановые битвы. Приглашение на перемещение в локацию."
 * тут рассылаем приглашения кланам для телепорта в стартовую локу и подтверждение участия.
 * т.к. спамим каждую минуту, то флаг служит признаком, когда спамили - взводим его каждую нечетную минуту, 
 * и убираем каждую четную.
 */
require_once("lib/clan_battle.lib");
require_once("lib/punishment.lib");
require_once("lib/clan_battle_season.lib");

// Определение сколько бриллиантов осталось в пуле
if (
	defined('GOLDPOOL_ACTION_ON') && GOLDPOOL_ACTION_ON &&
	defined('GOLDPOOL_MAIN_SERVER_ID') && GOLDPOOL_MAIN_SERVER_ID && 
	defined('GOLDPOOL_GOLD_TOTAL') && GOLDPOOL_GOLD_TOTAL
	) {
	// Акция активна
	if (GOLDPOOL_MAIN_SERVER_ID == SERVER_ID) {
		//if (date('i')%10) exit; // оперативный костыль, чтобы на мастер-сервере раз в 10 минут инфа обновлялась
		// Если это главный сервер акции - пробежимся по всем серверам и соберём стату
		$summ = 0;
		foreach(common_get_servers('GP') as $server_id => $server_params) {
			if ($server_id == SERVER_ID) {
				$money_stat = money_transfer_list(false, strtotime(GOLDPOOL_START_TIME), false, ' and money_type='.MONEY_TYPE_GOLD);
				foreach($money_stat as $operation) {
					$summ += $operation['amount'];
				}
				continue;
			}
			$params = array(
				'server_id' => SERVER_ID,
				'action' => 'get_action_payments',
			);
			$params['sign'] = crossserver_signature($params, SERVER_ID);
			$context  = stream_context_create(array('http' => array(
				'method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => common_build_request($params),
				'timeout' => 1,
			)));
			
			$url = $server_params['url'].'private/user_transfer.php';
			$result = file_get_contents($url,false, $context);
			if (!$result) exit;
			$summ += floatval($result);
		}
		
		$avail = GOLDPOOL_GOLD_TOTAL-$summ;
		$cache = new Cache('GOLDPOOL_GOLD_AVAIL');

		$cache_data = $cache->get();
		$old_avail = $cache_data['GOLDPOOL_GOLD_AVAIL'];

		if ((!$cache->isValid() || $old_avail<=0) && $avail>0) {
			error_log(sprintf('briliant event started : %s',date('Y-m-d H:i:s',time_current())));
		}

		if ($old_avail>0 && $avail<=0){
			error_log(sprintf('briliant event stoped : %s',date('Y-m-d H:i:s',time_current())));
		}
		if ($cache->tryLock()) {
			$data = array('GOLDPOOL_GOLD_AVAIL' => $avail);
			$cache->update($data,3600);
			$cache->freeLock();
		}
	} else {
		// Иначе сходим на главный сервер за текущим остатком
		$params = array(
			'server_id' => SERVER_ID,
			'action' => 'get_settings',
			'setting_params' => serialize(array('GOLDPOOL_GOLD_AVAIL')), 
		);
		
		$params['sign'] = crossserver_signature($params, SERVER_ID);
		$context  = stream_context_create(array('http' => array(
			'method'  => 'POST',
			'header'  => 'Content-type: application/x-www-form-urlencoded',
			'content' => common_build_request($params),
			'timeout' => 1,
		)));
			
		$url = $SERVERS[GOLDPOOL_MAIN_SERVER_ID]['url'].'private/user_transfer.php';
		$result = file_get_contents($url,false, $context);
		
		if (!$result) exit;
		$new_settings = unserialize($result);
		if ($new_settings['GOLDPOOL_GOLD_AVAIL']) {
			$cache = new Cache('GOLDPOOL_GOLD_AVAIL');
			if ($cache->tryLock()) {
				$data = array('GOLDPOOL_GOLD_AVAIL' => $new_settings['GOLDPOOL_GOLD_AVAIL']);			
				$cache->update($data,3600);
				$cache->freeLock();
			}
		}
	}
}


// планировщик боев
$fight_scheduler_list = fight_scheduler_list(false, sql_pholder(" AND (`flags` & ?) != 0 AND stime <= ? ",
	FIGHT_SCHEDULER_FLAG_ACTIVE,
	time_current()
));
if ($fight_scheduler_list){
	foreach ($fight_scheduler_list as $key => $value) {
		if (!($value['week_flags'] & (1 << date("N")-1))) continue; // проверка дня недели 
		$area = area_get($value['area_id']);
		$param=array();
		if ($value['flags'] & FIGHT_SCHEDULER_FLAG_NO_GREAT) $param['fight_flags'] |= FIGHT_FLAG_NO_GREAT;
		$bot1_id = bot_create($value['bot1_artikul_id'],1,$value['area_id'], false, array('flags'=>BOT_FLAG_TEMP));
		$bot2_id = bot_create($value['bot2_artikul_id'],1,$value['area_id'], false, array('flags'=>BOT_FLAG_TEMP));
		$bot1 = bot_get($bot1_id);
		$bot2 = bot_get($bot2_id);
		$status = fight_attack($bot1, $bot2, $param);
	}
	fight_scheduler_save(array(
		'_set' => sql_pholder(" stime = ? + time ", strtotime("+1 day 00:00")),
		'_add' => sql_pholder(" AND id IN (?@) ", array_keys(make_hash($fight_scheduler_list))),
	));
}

//Авто-хаоты

ini_set("memory_limit", '512M');

//Удалеение сообщений
chat_deleter_cron();

require_once("lib/user_glory.lib");
user_glory_cron();

require_once("lib/magic_mirror.lib");
magic_mirror_cron();

friend_gift_cron();
friend_gift_token_cron();

//afk
$afk_users = afk_get_users();
//print_r($afk_users);
foreach($afk_users as $afk_user){
    //echo $afk_user['user_id']." : "." Статус: ";
    $status = afk_check_exit($afk_user);
    if(!$status){
        //echo "Активен<br>";
    }else{
        if($status['exit_afk'] == 1){
            //echo "Вышел из локации<br>";
        }elseif($status['exit_afk'] == 2){
            //echo "Вышел из игры<br>";
        }
    }
}

require_once("lib/global_event.lib");
global_event_main_control_func();

require_once("lib/provocation.lib");
provocation_cron();


require_once("lib/auto_action.lib");

auto_action_cron();

require_once('lib/havens_gift.lib');
havens_gift_cron();
dice_part_stage1_cron();
dice_part_stage2_cron();

require_once('lib/buildings.lib');
building_kennel_cron();
estate_user_work_today_cron();

require_once('lib/adv_minigame.lib');
adv_minigame_cron();

//Удаление всех огранок по магазину по времени удаления
require_once("lib/area_store.lib");
area_store_user_buy_limits_delete(false, ' AND remove_time < '.time());

require_once("lib/adv_case.lib");
adv_case_cron();

require_once("lib/chat_event.lib");
chat_event_cron();

require_once("lib/adv_user_bonus.lib");
adv_user_bonus_cron();

require_once("lib/exchange.lib");
exchange_cron();

require_once("lib/area_bank.lib");
area_bank_rate_cron();
require_once("lib/area_event.lib");
area_event_main_control_func();

require_once("lib/day_promocode.lib");
day_promocode_cron();
require_once("lib/area_teleport.lib");
area_teleport_cron();

require_once("lib/clan.lib");
clan_stock_limit_cron();
clan_cfight_cron();
clan_gift_cron();

require_once("lib/area_teleport.lib");
area_teleport_cron();

rent_user_save(array(
    '_add' => sql_pholder(' AND rent_dtime = 0 OR rent_dtime < ?', time_current()),
    '_set' => sql_pholder(' rent_cnt = 0, rent_dtime = ?', mktime(23, 59, 59) + 1),
));
