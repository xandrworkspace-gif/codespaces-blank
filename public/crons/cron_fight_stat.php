<?

ini_set("memory_limit", "1024M");

chdir("..");
require_once("include/common.inc");
require_once("lib/fight_stat.lib");

// Поддерживаем соединение с MySQL
global $rating_stime;
$rating_stime = time_current();
function ticks_handler() {
	global $rating_stime;

	if ((time() - $rating_stime) > 60) {
		global $db, $db_2, $db_3, $db_auth, $db_diff, $db_nodes;
		
		$rating_stime = time();

		$sql = 'SELECT 1';
		$db->execSql($sql);
		$db_2->execSql($sql);
		$db_3->execSql($sql);
		$db_diff->execSql($sql);
		$db_auth->execSql($sql);
		foreach($db_nodes as $db_node) $db_node->execSql($sql);
	}
}
register_tick_function('ticks_handler');
declare(ticks = 100000);


$current_day = strtotime(date('Y-m-d',time())) - 86400;


fight_stat_cost_delete(array('date' => $current_day));

fight_stat_cost_collect($current_day);

?>
