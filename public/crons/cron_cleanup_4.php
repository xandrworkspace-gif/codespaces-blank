<? # $Id: cron_cleanup.php,v 1.68 2010-03-05 13:05:02 p.knoblokh Exp $

ini_set('memory_limit', '256M');

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
require_once("include/pf.inc");
require_once("include/log.inc");

define('CRON_CLEANUP_FIGHTS_PORTION', 500);
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

PF_CALL('cron_cleanup_4');
// ---- БОИ ----

PF_CALL('old fights');
// Удаляем старые бои
//$last_fight = fight_get(false,sql_pholder(" AND ctime<=? ORDER BY id DESC",time_current()-FIGHT_TTL));
//$last_fight_id = intval($last_fight['id']);


do {
	/*
	$fight_list_portion = fight_list(null, null, null,
		sql_pholder(" AND ctime<=? LIMIT ?#CRON_CLEANUP_FIGHTS_PORTION",time_current()-FIGHT_TTL), 'id'
	);
	*/

    $fight_list_portion = common_list($db_4,TABLE_FIGHTS,false,sql_pholder(" AND ctime<=? ",time_current()-FIGHT_TTL),'id');

    echo 'pizdec '.count($fight_list_portion);

	if (!$fight_list_portion) break;
	$fight_list_portion_hash = get_hash($fight_list_portion, 'id', 'id');
	if ($fight_list_portion_hash) {
		common_delete($db_4,TABLE_FIGHTS,false,sql_pholder(" AND id IN(?@)",$fight_list_portion_hash));
		common_delete($db_4,TABLE_FIGHT_USERS,false,sql_pholder(" AND fight_id IN(?@)",$fight_list_portion_hash));
		common_delete($db_4,TABLE_FIGHT_USER_SKILLS,false,sql_pholder(" AND fight_id IN(?@)",$fight_list_portion_hash));
		common_delete($db_4,TABLE_FIGHT_USER_FLEE,false,sql_pholder(" AND fight_id IN(?@)",$fight_list_portion_hash));
#		common_delete($db,TABLE_FIGHT_LOG,false,sql_pholder(" AND fight_id IN(?@)".$fight_list_portion_hash));
		user_save(array(
			'_set' => 'fight_id=0',
			'_add' => sql_pholder(" AND fight_id IN(?@)",$fight_list_portion_hash),
		));
		bot_save(array(
			'_set' => 'fight_id=0',
			'_add' => sql_pholder(" AND fight_id IN(?@)",$fight_list_portion_hash),
		));
		common_delete($db,'id_'.TABLE_FIGHTS,false,sql_pholder(" AND id IN(?@)",$fight_list_portion_hash));
	}
	usleep(CRON_CLEANUP_FIGHTS_DELAY);
} while(1);
PF_RET(false,'old fights');

PF_CALL('temporary bots');
// ---- ВРЕМЕННЫЕ БОТЫ ----
do {
	$bot_list = bot_list(false,null,null,null,
			sql_pholder(" AND (flags & ?#BOT_FLAG_TEMP) AND fight_id = 0 AND ctime < ? LIMIT ?#CRON_CLEANUP_BOTS_PORTION ",
			time_current()-FIGHT_BOT_TTL),
			'id,artikul_skills'
	);
	if (! $bot_list) break;
	foreach ($bot_list as $bot) {
		bot_delete($bot['id']);
	}
	usleep(CRON_CLEANUP_BOTS_DELAY);
} while (1);
PF_RET(false,'temporary bots');

PF_CALL('trading');
// ---- ТОРГОВЛЯ ----
$trade_list = trade_list(false,false,false,sql_pholder(" AND stime<=?",time_current()-86400),'id');
foreach ($trade_list as $trade) {
	trade_delete($trade['id']);
}
PF_RET(false,'trading');

PF_RET(false, 'cron_cleanup_4');