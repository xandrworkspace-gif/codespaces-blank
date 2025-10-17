<? # $Id: cron_activity.php,v 1.56 2010-03-05 08:42:24 m.usachev Exp $

chdir("..");
require_once("include/common.inc");

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) return;

require_once("lib/session.lib");
require_once("lib/fight.lib");
require_once("lib/party.lib");
require_once("lib/punishment.lib");
require_once("lib/stat.lib");
require_once("lib/event.lib");
require_once("lib/partner.lib");
require_once("lib/bot.lib");
require_once("lib/phone.lib");
require_once("lib/area_post.lib");
require_once("lib/buildings.lib");
require_once("lib/adv_premium.lib");
require_once("lib/activity.lib");
require_once("lib/simple_action.lib");

set_time_limit(0);
$online = array();
$stats_online = array(
	'max_online' => 0,
	'max_online2' => 0,
	'max_online3' => 0
);

$partners = make_hash($PARTNERS_CFG, 'partner_pid');

foreach ($NODE_NUMS as $nn) {
	NODE_SWITCH($nn);

	// удаляем сессии
	$query_add = sql_pholder(' AND stime<=?',time_current()-$SESSION_TTL);
	$ids = session_list(null, null, null, $query_add, 'uid', true);
	
	if ($ids) {
		$ids = get_hash($ids, 'uid', 'uid');
		// коммуникатор ---------------------
		require_once('lib/game_agent.lib');
		sendGameAgent(array( // оповещение "пользователи вышли из игры"
			'uids' => serializeBag($ids),
			'command' => 'offline',
		));
		// ----------------------------------
		session_delete(false,$query_add);
	}
	$online[$nn] = session_list(null, null, true, '', 'partner_pid');
	foreach ($online[$nn] as $sess) {
		if ( !$sess['partner_pid'] || !isset($partners[$sess['partner_pid']]) || !isset($partners[$sess['partner_pid']]['max_online_field']) ) {
			$stats_online['max_online']++;
		} else {
			$stats_online[$partners[$sess['partner_pid']]['max_online_field']]++;
		}
	}
}

// статистика
foreach ($stats_online as $stat => $value) {
	stat_update($stat, $value, 3);
}

// -------------------------------------------------------------------------------------------------------

$str = sprintf('online: %d (%s)',array_sum($stats_online),implode(', ',$stats_online));
//logfile('crons/activity.log',$str);
$fscl->sendCommand(FS_SCCT_SRV_INFO);
if ($fscl->getAnswer($answer) == FS_SS_OK) {
	$str = sprintf('FS (conn: %d, fght: %d, pers: %d, fprs: %d, info: %d)',$answer[4]['val'],$answer[5]['val'],$answer[6]['val'],$answer[7]['val'],$answer[8]['val']);
	//logfile('crons/activity2.log',$str);
}

bot_plant(); // Рассадка ботов
echo 'ok';

// удаляем просроченные в рюкзаке группы
party_artikul_delete(false, sql_pholder(' AND stime<=?', time_current()-PARTY_LIVE_ARTIFACTS_TIME));

// удаляем неактивные группы
$party_list = party_list(false, sql_pholder(' AND stime<?', time_current()-86400*2));
foreach ($party_list as $party) {
	party_delete($party['id']);
}

// удаляем просроченные наказания
$refresh_user_ids = array();
$punishment_list = punishment_user_list(false, false, sql_pholder(' AND type_id=?#PUNISH_TYPE_TIME AND ftime < ?', time_current()));
foreach ($punishment_list as $item) {
	punishment_user_delete($item['id']);
	$refresh_user_ids[$item['user_id']] = $item['user_id'];
}
foreach ($refresh_user_ids as $user_id) {
	punishment_user_flag_sync($user_id);
}

// удаляем просроченные смски
phone_request_delete(false, sql_pholder(' AND stime<=?', time_current()-1800));

// кэшируем количество онлайн пользователй в локациях
$cache = new Cache('ONLINEUSERS');
if (!$cache->isValid() && $cache->tryLock()) {
	$data = array();
	foreach ($NODE_NUMS as $nn) {
		NODE_SWITCH($nn);
		// считаем сессии в узле
		$session_list = session_list(null,null,true,'','*',true);
		foreach ($session_list as $session) {
			if ($session['instance_id']) $data['instances'][$session['instance_id']]++;
			else $data['areas'][$session['area_id']]++;
		}
	}
	$cache->update($data,600);
	$cache->freeLock();

	// Пиковое значение пользователей в узлах для отчёта
	require_once('lib/metric.lib');
	$date = date('Y-m-d', time_current());
	$metric_hash = get_hash(metric_list(array('type' => METRIC_TYPE_AREA_VISIT, 'sdate' => $date)), 'artikul_id', 'value');
	foreach ($data['areas'] as $area_id => $cnt) {
		if ($cnt > @$metric_hash[$area_id]) {
			metric_save(array(
				'type' => METRIC_TYPE_AREA_VISIT,
				'sdate' => $date,
				'object_key' => $area_id,
				'value' => $cnt,
				'_mode' => CSMODE_REPLACE,
			));
		}
	}
}

// Удаляем просроченные дополнительные ячейки
$expired_addcells = area_bank_addcell_list(false, sql_pholder(' and rtime<?', time_current()));
global $post_areas;
$subject = translate('Возврат вещи из закрытой арендованной ячейки');
foreach($expired_addcells as $expired_addcell) {
	$area = area_get($expired_addcell['area_id']);
	$text = sprintf(translate('Срок аренды ячейки в банке "%s" истек. Содержимое ячейки выслано Вам почтой.'), $area['title']);
	$user = user_get($expired_addcell['user_id']);
	if (!$user) {
		area_bank_addcell_delete(array('id' => $expired_addcell['id']));
		continue;
	}
	$cell_artifact_hash = make_hash(array_reverse(area_get_artifact_list($expired_addcell['area_id'], $user['id'], ' AND clan_id=0 AND slot_num=0')));
	$cell = area_bank_cell_get(array('area_id' => $expired_addcell['area_id'], 'user_id' => $user['id']));
	$current_addcels = area_bank_addcell_list(array('area_id' => $expired_addcell['area_id'], 'user_id' => $user['id']), sql_pholder(' and rtime>?', time_current()));
	while(count($current_addcels) + $cell['num'] < count($cell_artifact_hash)) {
		$send_artifact = array_shift($cell_artifact_hash);
		$param = array(
				'from_id' => 0,
				'to_id' => $user['id'],
				'subject' => $subject,
				'text' => mb_substr($text, 0, 1024),
				'unread' => 1,
				'type_id' => MSG_TYPE_SYS_NORMAL,
				'stime' => time_current(),
				'rtime' => time_current() + area_post_message_ttl(MSG_TYPE_SYS_NORMAL, true),
				'artifact_id' => $send_artifact['id'],
		);
		artifact_save_safe(array(
			'id' => $send_artifact['id'],
			'user_id' => 0,
			'owner_id' => $user['id'],
			'area_id' => $post_areas[$user['kind']],
		));
		area_post_message_save($param);
	}
	area_bank_addcell_delete(array('id' => $expired_addcell['id']));
}

// Отправляем данные по партнерке
require_once('lib/partner.lib');
partner_sendEvent();

//Премиум
global $db_path;
$prem_list = adv_premium_list(false, ' AND time_end != 0 AND time_end < '.time_current());
foreach($prem_list as $premium){
	$user = user_get($premium['user_id']);
	NODE_SWITCH(false, $user['id']);
	skill_object_delete(OBJECT_CLASS_USER,$user,array('skill_id' => 'PREMIUM'));
	adv_premium_delete($premium['id']);
}
adv_dp_premium_delete(false, sql_pholder(' AND dtime < ?', time_current()));

if(defined('BESTIARY_CACHE_GEN') && BESTIARY_CACHE_GEN){
	require_once('lib/bestiary_cache.lib');
    generate_cache_bestiary();
}

if ((defined('CUBE_RECEPIE_CACHE_GEN') && CUBE_RECEPIE_CACHE_GEN)) {
    require_once('lib/cube_recepie_cache.lib');
    cube_recepie_generate_cache();
}


//Активности
activity_check_user();
//Действия
echo "HUI";
simple_action_cron();

require_once("lib/lite_pass.lib");
lite_pass_cron();

require_once("lib/clan.lib");
clan_head_request_control();


?>
