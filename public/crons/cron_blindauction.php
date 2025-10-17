<?

chdir("..");
require_once("include/common.inc");

common_define_settings();

// Проверка, что проект и аукцион не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) return;
if (defined('BLINDAUCTION_DISABLED') && BLINDAUCTION_DISABLED) return;

require_once("lib/blindauction.lib");

set_time_limit(0);

$lots = blindauction_lot_list(false, sql_pholder(' and status=? and end_time<?', BA_STATUS_RUNNING, time_current()));

foreach($lots as $lot) {
	$result = blindauction_process_lot($lot['id']);
	if (!$result['status']) {
		error_log(sprintf('BlindAuction Process Error: %s', $result['error']));
	}
}

$lots = make_hash(blindauction_lot_list(false, sql_pholder(' and status=? and end_time<?', BA_STATUS_FINISHED, time_current()-3*24*3600)));

if ($lots) {
	blindauction_lot_save(array(
		'_set' => sql_pholder('status=?', BA_STATUS_ARCHIEVED),
		'_add' => sql_pholder(' AND id in (?@)',array_keys($lots)),
	));
}

?>