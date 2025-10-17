<? # $Id: cron_import.php,v 1.6 2009-10-27 09:00:49 i.hrustalev Exp $

exit;

chdir("..");
require_once("include/common.inc");
require_once("lib/exp_imp.lib");

common_define_settings();

set_time_limit(0);

// Поиск последнего файла экспорта
$dir = opendir(SYNC_DATA_PATH);
$last_file = '';
while (($file = readdir($dir)) !== false) {
	if ((substr($file,0,6) == 'export') && (strcasecmp($file, $last_file) > 0)) {
		$last_file = $file;
	}
}

if ($last_file) {
// Импорт файла
	error_log("Last file: ".$last_file);
	$result = exp_imp_import(array('datafile' => SERVER_ROOT.SYNC_DATA_PATH.$last_file, 'deletion' => 1, 'absolute' => 1));

	if ($result[0]) error_log($result[0]);
	else {
		array_unshift($result[1],($result[1] ? 'Log:' : 'Log is empty'));
		error_log("Import successful\n\n".implode("\n",$result[1]));
	}
} else {
	error_log("No export files!");
}

?>