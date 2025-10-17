<? # $Id: mngr_fight_finish.php,v 1.22 2008-07-04 08:02:37 s.panferov Exp $

ini_set('memory_limit', '512M');

chdir("..");
require_once("include/common.inc");

set_time_limit(0);
$stime1 = time();

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) {
	sleep(MNGR_FIGHT_FINISH_INTERVAL);
	return;
}

require_once("include/fsclient.inc");
require_once("lib/fight.lib");

do {
	if (!$fscl->connect()) {
		mngr_fight_log('No connection with the fight server!');
		break;
	}
	// Завершаем оконченные бои
	$fs_fights = fight_fs_get_fights();
	if ($fs_fights === false) {
		mngr_fight_log('No fight status list!');
		break;
	}
	$fight_list = fight_list(null,null,FIGHT_STATUS_RUNNING);
	foreach ($fight_list as $fight) {
		$fight_id = $fight['id'];
		if ($fs_fights[$fight_id] && !in_array($fs_fights[$fight_id]['status'],array(FS_FS_OVER,FS_FS_FINISHED))) continue;
		if (!fight_lock($fight_id,0)) continue;
		fight_finish($fight_id);
		fight_unlock($fight_id);
		$ff++;
	}
} while (0);

$stime2 = time();
$rtime = $stime2-$stime1;

if ($rtime > MNGR_FIGHT_FINISH_INTERVAL) {
	error_log("(mngr_fight_finish: ".getmypid()."): Runtime $rtime sec (NN: ".$nn.")");
	mngr_fight_log(sprintf('START (runtime: %d sec, NN: %d, fights: %d, finished: %d)',$rtime,$nn,count($fight_list),$ff));
	mngr_fight_log_pf();
	mngr_fight_log('END');
}
sleep(max(MNGR_FIGHT_FINISH_INTERVAL-$rtime,0));


// ------------------------------------------------------------------

function mngr_fight_log($str) {
	logfile('crons/mngr_fight_finish.log',getmypid().' - '.$str);
//	error_log("(mngr_fight_finish: ".getmypid().")".$str);
}

function mngr_fight_log_pf($pf=false, $keys=false) {
	global $PF;
	if (!$pf) $pf = &$PF;
	if (!$keys) {
		$keys = array_keys($pf);
		sort($keys);
	}
	foreach ($keys as $key) {
		$stat = $pf[$key];
		$str = sprintf("%s (%d tms, %.3f sum, %.3f avg, %.3f min, %.3f max)",$key,$stat['cnt'],$stat['sum'],$stat['avg'],$stat['min'],$stat['max']);
		mngr_fight_log($str);
	}
}

?>