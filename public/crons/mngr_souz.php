<? // $Id: mngr_souz.php 5867 2010-06-16 15:39:42Z v.babajanyan $
// =========================================================================
// Coder			: AKEB, IT-Territory
// Work started		: 16.06.2010 18:40:06
// User				: v.babajanyan
// Description		: 
// =========================================================================

chdir("..");
require_once("include/common.inc");
require_once("include/souz.inc");

set_time_limit(0);

function csvfile($str) {
	$dir = SERVER_ROOT.PATH_LOGS.'souz/';
	$file_name = $dir.date("Y-m-d",time()).'.log';
	$have_file = @file_exists($file_name);
	$fout = @fopen($file_name,"a");
	if (!$fout) return;
	$log_str = $str."\n";
	fwrite($fout,$log_str);
	fclose($fout);
	if (!$have_file) @chmod($file_name,0664);
}

$time1 = microtime(true);
$status = souz_auth(SOUZ_STATS_PERS_LOGIN, SOUZ_STATS_PERS_PASS, '127.0.0.1', false, true);

$time2 = microtime(true);
$diff = $time2-$time1;
$str = $diff.':'.time().':'.intval($status['pers_id']);
csvfile($str);

sleep(MNGR_SOUZ_INTERVAL);

exit(0);
?>