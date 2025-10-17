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
require_once("lib/stat.lib");
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

PF_CALL('cron_cleanup');

// Статистика по ушедшим из игры
$auth_count = auth_count(false, sql_pholder(' AND time_login BETWEEN ? AND ?', time_current()-60*60*24*31, time_current()-60*60*24*30 ));
stat_update('left_users_cnt', $auth_count);

// ---- БОИ ----
PF_CALL('old clan battles');
// Удаляем старые бои
do {
	$clan_battle_list_portion = clan_battle_list(false, sql_pholder(" AND server_id = ? AND stime<=? LIMIT ?#CRON_CLEANUP_FIGHTS_PORTION", SERVER_ID, time_current()-FIGHT_TTL));
	if (!$clan_battle_list_portion) break;
	foreach ($clan_battle_list_portion as $clan_battle) {
		clan_battle_delete(array('id' => $clan_battle['id'], 'server_id' => $clan_battle['server_id']));
	}
	usleep(CRON_CLEANUP_FIGHTS_DELAY);
} while (1);
while(common_delete($db_2,TABLE_CLAN_BATTLE_REQUESTS,false,sql_pholder(' AND stime < ? LIMIT ?#CRON_CLEANUP_OTHER_PORTION',time_current()-86400))) {
	usleep(CRON_CLEANUP_OTHER_DELAY);
}
PF_RET(false,'old clan battles');


PF_CALL('clans');
// чистка кланов
$warn_time = ($clan_warning_time_hash[1]['days'])*24*60*60;
$clan_list = clan_list(false,sql_pholder(' AND rtime<? AND !(flags & ?)', time_current()+$warn_time,CLAN_FLAG_PERMANENT));
foreach ($clan_list as $clan) {
	$rest_days = intval((($clan['rtime'] - time_current()) / (24*60*60)));
	if (($clan['rtime'] + CLAN_FRIDGE_TIME < time_current()) && ($clan['flags'] & CLAN_FLAG_IN_FRIDGE)) { // удалить клан
		clan_send_warning_message($clan['id'], translate('Расформирование клана'), translate('Ваш клан был расформирован за неуплату налога на содержание клана.'));
		clan_delete($clan['id']);
		continue;
	}

	if (($clan['rtime'] < time_current()) && !($clan['flags'] & CLAN_FLAG_IN_FRIDGE)) { // поместить клан в заморозку
		$old_clan_leader_id = clan_leader_id_get($clan['id']);
		$clan_leader = user_get($old_clan_leader_id);

		$data = clan_stat_list(array('clan_id' => $clan['id']));

		$stat_artikul_ids = get_hash($data, 'clan_stat_artikul_id', 'clan_stat_artikul_id');
		$stat_artikul_hash = $stat_artikul_ids ? make_hash(clan_stat_artikul_list(array('id' => $stat_artikul_ids))) : array();

		foreach ($data as $item) {
			$artikul = $stat_artikul_hash[$item['clan_stat_artikul_id']];
			$stats[] =  sprintf("%s : %d",$artikul['title'], $item['total_value']);

		}
		logserv_log_note(array(
			'src_code' => 1020,
			'note' => 'Заморозка клана',
			'comment' => sprintf('Ступень %d, %s',$clan['level'], implode(', ',$stats)),
		),$clan_leader);
		// ----------------------------------

		#100663 - Удаление кл.тотемов у персов, при расформировании клана.
		$clan_members = clan_member_list($clan['id']);
		$user_ids = array();
		foreach ($clan_members as $member) $user_ids[] = $member['user_id'];
		if ($user_ids) {
			$clan_members = user_list(array('id' => $user_ids));
			foreach ($clan_members as $user) {
			    clan_user_move_artifact($user);
                clan_user_leave($user);
            }
		}
		
		clan_member_delete(false,$clan['id']);
		clan_member_sync($clan['id']);
		
		$params = array(
				'id' => $clan['id'],
				'_set' => sql_pholder('flags = flags | ? , clan_head_id = ?', CLAN_FLAG_IN_FRIDGE, $old_clan_leader_id),
		);
		clan_save($params);
	}
	
	foreach ($clan_warning_time_hash as $warn_time) {
		if ($clan['warn_num'] >= $warn_time['id']) { // уже отсылали это письмо
			continue;
		}
		if ($warn_time['days'] >= $rest_days) {
			clan_save(array(
				'id' => $clan['id'],
				'warn_num' => $warn_time['id'],
			));
			clan_send_warning_message($clan['id'], translate('Налог на клан'), sprintf(translate('Напоминаем Вам, что в случае неуплаты налога, Ваш клан будет расформирован в течении %d дней. Оплатить налог Вы можете в регистрационной палате города.'),$rest_days));
			break;
		}
	}
}

PF_RET(false, 'clans');
PF_CALL('clan money log');
// логирование клановой казны
while(area_bank_cell_log_delete(false, sql_pholder(" AND stime <= ? LIMIT ?#CRON_CLEANUP_OTHER_PORTION",(time_current()-86400*30)))){
	usleep(CRON_CLEANUP_OTHER_DELAY);
}
PF_RET(false,'clan money log');

PF_CALL('ally');
// заявки на альянсы
while(common_delete($db_2,TABLE_ALLIANCE_CLANS,$ref,sql_pholder(" AND dtime<=? LIMIT ?#CRON_CLEANUP_OTHER_PORTION",(time_current()-86400)))) {
	usleep(CRON_CLEANUP_OTHER_DELAY);
}


PF_CALL(false,'ally');

PF_CALL('recycle bin shop');
// корзина магазина
while(area_store_cart_delete(false, sql_pholder(" AND stime<=? LIMIT ?#CRON_CLEANUP_OTHER_PORTION",(time_current()-86400)))){
	usleep(CRON_CLEANUP_OTHER_DELAY);
}
PF_RET(false,'recycle bin shop');

PF_CALL('exchange bonus');
// бонусы размена
while (area_bank_exchange_delete(false, " AND sdate<='".date("Y-m-d", mktime(0,0,0,date("m")-2,1,date("Y")))."' LIMIT ".CRON_CLEANUP_OTHER_PORTION)) {
	usleep(CRON_CLEANUP_OTHER_DELAY);
}
PF_RET(false,'exchange bonus');

PF_CALL('actions and bonuses');
// чистка блокировок действий и бонусов
action_stat_delete(false," AND dtime>0 AND dtime<=".time_current());
while(bonus_item_stat_delete(false, sql_pholder(" AND dtime>0 AND dtime<=? LIMIT ?#CRON_CLEANUP_OTHER_PORTION",time_current()))){
	usleep(CRON_CLEANUP_OTHER_DELAY);
}	
PF_RET(false,'actions and bonuses');

PF_CALL('clans');
// удалим отклоненные просроченные заявки на кланы
clan_request_delete(array('status' => CR_STATUS_REJECTED), " AND stime<=".(time_current()-7*86400));
PF_RET(false, 'clans');

PF_CALL('area casino super games');
// Удаляем просроченные супер игры в казино
area_casino_super_game_delete(false, sql_pholder(' AND ctime<=?', time_current() - CASINO_SUPER_GAME_DURATION_MAX));
PF_RET(false, 'area casino super games');

// удаляем просроченные логи
PF_CALL('auth_restore_password');
while(auth_restore_password_delete(false, sql_pholder(' AND dtime < ? LIMIT ?#CRON_CLEANUP_OTHER_PORTION', time_current()))){
	usleep(CRON_CLEANUP_OTHER_DELAY);	
}
PF_RET(false, 'auth_restore_password');

PF_CALL('party_cleanup');
party_cleanup();
PF_RET(false, 'party_cleanup');

// удаляем инфу о старых приглашениях в социалках
PF_CALL('soc_user_invitations');
while(soc_user_invitations_delete(false, sql_pholder(' AND stime < ? LIMIT ?#CRON_CLEANUP_OTHER_PORTION', time_current() - 60*60*24*7))){
	usleep(CRON_CLEANUP_OTHER_DELAY);
}
PF_RET(false, 'soc_user_invitations');

PF_RET(false, 'cron_cleanup');