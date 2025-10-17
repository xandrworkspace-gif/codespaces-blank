<? # $Id: cron_cleanup.php,v 1.68 2010-03-05 13:05:02 p.knoblokh Exp $

chdir("..");
require_once("include/common.inc");
require_once("lib/bg.lib");
require_once("lib/fight.lib");
require_once("lib/bot.lib");
require_once("lib/clan.lib");
require_once("lib/area_post.lib");
require_once("lib/area_store.lib");
require_once("lib/area_casino.lib");
require_once("lib/stronghold.lib");
require_once("lib/trade.lib");
require_once("lib/ticket.lib");
require_once("lib/log.lib");
require_once("lib/skill.lib");
require_once("lib/alliance.lib");
require_once("lib/area_bank.lib");
require_once("lib/logserv.lib");
require_once("lib/partner.lib");
require_once("lib/soc_stat.lib");
require_once("lib/money_stat.lib");
require_once("lib/clan_battle.lib");
require_once("lib/session_stat.lib");
require_once("lib/crossserver.lib");
require_once("lib/metric.lib");
require_once("lib/user_metric.lib");
require_once("lib/global_skill.lib");
require_once("lib/log_stat_uniform.lib");

require_once("include/pf.inc");
require_once("include/log.inc");

define('CRON_CLEANUP_FIGHTS_PORTION', 1000);
define('CRON_CLEANUP_FIGHTS_DELAY', 50000);
define('CRON_CLEANUP_BOTS_PORTION', 1000);
define('CRON_CLEANUP_BOTS_DELAY', 50000);
define('CRON_CLEANUP_SKILLS_PORTION', 1000);
define('CRON_CLEANUP_SKILLS_DELAY', 100000);
define('CRON_CLEANUP_USER_ONLINE_PORTION', 1000);
define('CRON_CLEANUP_USER_ONLINE_DELAY', 100000);
define('CRON_CLEANUP_AREA_BANK_CELL_LOG', 100000);
define('CRON_CLEANUP_USER_ONLINE_DELAY', 100000);
define('CRON_CLEANUP_OTHER_PORTION', 1000);
define('CRON_CLEANUP_OTHER_DELAY', 50000);

set_time_limit(0);

// Поддерживаем соединение с MySQL
global $cleanup_stime;
$cleanup_stime = time_current();
function ticks_handler() {
	global $cleanup_stime;

	if ((time() - $cleanup_stime) > 60) {
		global $db, $db_2, $db_3, $db_4, $db_auth, $db_diff, $db_nodes;
		
		$rating_stime = time();

		$sql = 'SELECT 1';
		$db->execSql($sql);
		$db_2->execSql($sql);
		$db_3->execSql($sql);
		$db_4->execSql($sql);
		$db_diff->execSql($sql);
		$db_auth->execSql($sql);
		foreach($db_nodes as $db_node) $db_node->execSql($sql);
	}
}
register_tick_function('ticks_handler');
declare(ticks = 100000);

PF_CALL('cron_cleanup_diff');

PF_CALL('crossserver logserv buffer');

while(logserv_buffer_crossserver_delete(false,sql_pholder(" AND ctime <= ? LIMIT ?#CRON_CLEANUP_OTHER_PORTION",(time_current()-86400*5)))) {
	usleep(CRON_CLEANUP_OTHER_DELAY);
}
PF_RET(false,'crossserver logserv buffer');

PF_CALL('tickets');
// удаляем просроченные тикеты
ticket_purge_old(time_current() - TICKET_EXPIRED_LIFETIME);
PF_RET(false,'tickets');

PF_CALL('action logs');
// Удаляем Логи действий старше  месяца
while(log_user_action_delete(false, sql_pholder(" AND stime<=? LIMIT ?#CRON_CLEANUP_OTHER_PORTION",(time_current()-30*86400)))){
	usleep(CRON_CLEANUP_OTHER_DELAY);
}
PF_RET(false,'action logs');

PF_CALL('quest logs');
// Удаляем Логи квестов старше 2-х месяцев
while(log_quest_user_delete(false, sql_pholder(" AND stime<=? LIMIT ?#CRON_CLEANUP_OTHER_PORTION",(time_current()-60*86400)))){
	usleep(CRON_CLEANUP_OTHER_DELAY);
}
PF_RET(false, 'quest logs');

PF_CALL('fight stats');
// Удаляем инфу о PVE боях старше 2-х месяцев
while(fight_stat_delete(false, sql_pholder(" AND stime<=? LIMIT ?#CRON_CLEANUP_OTHER_PORTION",(time_current()-60*86400)))){
	usleep(CRON_CLEANUP_OTHER_DELAY);
}
PF_RET(false, 'fight stats');

PF_CALL('access logs');
// Удаляем Логи просмотров старше 3-х месяцев
while(logserv_access_log_delete(false, sql_pholder(" AND stime<=? LIMIT ?#CRON_CLEANUP_OTHER_PORTION",(time_current()-90*86400)))){
	usleep(CRON_CLEANUP_OTHER_DELAY);
}
PF_RET(false, 'access logs');

PF_CALL('ip_history');
// удаляем историю входов IP
while(user_ip_history_delete(false,time_current() - IP_HISTORY_EXPIRED_LIFETIME, sql_pholder(' LIMIT ?#CRON_CLEANUP_OTHER_PORTION'))) {
	usleep(CRON_CLEANUP_OTHER_DELAY);
}
PF_RET(false, 'ip_history');

// удаляем просроченные логи
PF_CALL('bg_stats');
while(bg_stat_delete(false, sql_pholder(' AND stime <= ? LIMIT ?#CRON_CLEANUP_OTHER_PORTION', time_current() - BG_STAT_TTL))){
	usleep(CRON_CLEANUP_OTHER_DELAY);
}
PF_RET(false, 'bg_stats');

// удаляем просроченные логи партнерки
PF_CALL('partner_event_error_log');
while(partner_event_error_log_delete(false, sql_pholder(' AND ctime < ? LIMIT ?#CRON_CLEANUP_OTHER_PORTION', time_current() - 60*60*24*30))){
	usleep(CRON_CLEANUP_OTHER_DELAY);
}
PF_RET(false, 'partner_event_error_log');

// сохраняем сколько у нас пользователей каждой социалки
PF_CALL('soc_user_count');
foreach($soc_systems as $soc_system_id => $soc_system) {
	$cnt = soc_user_count(false, sql_pholder(' and uid>0 and soc_system_id=?', $soc_system_id));
	$result = soc_stat_save(array(
		'_mode' => CSMODE_REPLACE,
		'day_start' => strtotime(date('Y-m-d')),
		'soc_system_id' => $soc_system_id,
		'stat_type' => SOC_STAT_TYPE_COUNT,
		'value' => $cnt,
	));
}
PF_RET(false, 'soc_user_count');

PF_CALL('balance_history');
log_stat_uniform::cashflow();
balance_history_generate();
while(balance_history_delete(false,sql_pholder(' AND stime < ?  LIMIT ?#CRON_CLEANUP_OTHER_PORTION', time_current() - 60*60*24*30*3))) {
	usleep(CRON_CLEANUP_OTHER_DELAY);
}
PF_RET(false, 'balance_history');

PF_CALL('session_stat');
while(session_stat_delete(false,sql_pholder(' AND time_logout < ?  LIMIT ?#CRON_CLEANUP_OTHER_PORTION', time_current() - 60*60*24*30))) {
	usleep(CRON_CLEANUP_OTHER_DELAY);
}
PF_RET(false, 'session_stat');

PF_CALL('crossserver_users');
// Удалить старые данные игроков, которые вернулись домой
$ref = array(
	'status' => CROSSSERVER_STATUS_RETURN,
);
$add = sql_pholder(' AND ctime <= UNIX_TIMESTAMP(NOW() - INTERVAL 1 WEEK) LIMIT ?#CRON_CLEANUP_OTHER_PORTION');
while (crossserver_user_delete($ref, false, $add)) {
	usleep(CRON_CLEANUP_OTHER_DELAY);	
}
PF_RET(false, 'crossserver_users');

// Удаление просроченных данных по пользовательским метрикам
PF_CALL('user_metric_cleanup');
user_metric_cleanup();
PF_RET('user_metric_cleanup');

// Запись статов в пользователей
PF_CALL('user_metric_apply');

$total_profile_users = array();

$user_metrics_honor = user_metric_aggregate_list(USER_METRIC_TYPE_HONOR);
$i = 0;
while ($part = array_slice($user_metrics_honor, $i * CRON_CLEANUP_OTHER_PORTION, CRON_CLEANUP_OTHER_PORTION)) {
	$user_hash = make_hash(user_list(array('id' => get_hash($part, 'user_id', 'user_id'))));
	if (!$user_hash) continue;
	foreach ($part as $metric_user) {
		$user = $user_hash[$metric_user['user_id']];
		if (!$user) continue;
		// эта проверка значит, что пользователь не считается пвпшником, если не набивает по крайней мере 1000*уровень доблы в неделю
		if ($metric_user['value'] < ($user['level'] * 1000)) continue;
		if (!NODE_SWITCH(null, $metric_user['user_id'])) continue;
		
		$profile_value = 100;
		skill_object_set_value(OBJECT_CLASS_USER, $metric_user['user_id'], 'PROFILE_PVP', $profile_value);
		if ($profile_value > 50) {
			if (!isset($total_profile_users[$user['level']])) $total_profile_users[$user['level']] = 0;
			$total_profile_users[$user['level']]++;
		}
	}
	usleep(CRON_CLEANUP_OTHER_DELAY);
	$i++;
}
foreach ($total_profile_users as $level => $cnt) {
	if ($cnt) metric_group_add(METRIC_TYPES_PVP, array('level' => $level), array('pvp_users' => $cnt));
}
PF_RET('user_metric_apply');

//Статистика по убийствам в виликих битвах
$date = date('Y-m-d', time_current() - 60*60*24); // пишем в метрику за предыдущий день
$settings = common_get_db_settings(array('GREAT_WIN_CUR_1','GREAT_WIN_CUR_2'));
$wins_human = intval($settings['GREAT_WIN_CUR_1']);
$wins_magmar = intval($settings['GREAT_WIN_CUR_2']);
metric_group_add(METRIC_TYPES_GREAT_FIGHT, array('kind_id' => KIND_HUMAN), array('great_fight_wins' => $wins_human), $date);
metric_group_add(METRIC_TYPES_GREAT_FIGHT, array('kind_id' => KIND_MAGMAR), array('great_fight_wins' => $wins_magmar), $date);
common_save_db_settings(array('GREAT_WIN_CUR_1' => 0,'GREAT_WIN_CUR_2' => 0));

PF_CALL('global_skill');
$global_skill_value_list = global_skill_value_list(array('stime' => 0));
foreach ($global_skill_value_list as $global_skill_value) {
	$param = $global_skill_value;
	unset($param['id']);
	$param['stime'] = strtotime(strftime('%Y-%m-%d', time_current() - 60*60*24));
	$param['_noerr'] = true;
	global_skill_value_save($param);
}
PF_RET(false, 'global_skill');

PF_RET(false, 'cron_cleanup_diff');