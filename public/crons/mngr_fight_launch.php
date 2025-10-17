<? # $Id: mngr_fight_launch.php,v 1.19 2008-07-04 08:02:37 s.panferov Exp $

chdir("..");
require_once("include/common.inc");

set_time_limit(0);
$stime1 = time();

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) {
	sleep(MNGR_FIGHT_LAUNCH_INTERVAL);
	return;
}

require_once("include/fsclient.inc");
require_once("lib/fight.lib");

do {
	if (!$fscl->connect()) {
		mngr_fight_log('No connection with the fight server!');
		break;
	}
	// Запускаем бои по заявкам
	$request_list = fight_request_list(null,null,sql_pholder(" AND start_time>0 AND start_time<=?",time_current()));
	foreach ($request_list as $request) {
		$request_id = $request['id'];
		if (!fight_request_lock($request_id,0)) continue;
		fight_launch($request_id);
		fight_request_delete($request_id);
		fight_request_unlock($request_id);
		$rs++;
	}
	fight_cleanup();
} while (0);

$stime2 = time();
$rtime = $stime2-$stime1;
if ($rtime > MNGR_FIGHT_LAUNCH_INTERVAL) {
	error_log("(mngr_fight_launch: ".getmypid()."): Runtime $rtime sec (NN: ".$nn.")");
	mngr_fight_log(sprintf('START (runtime: %d sec, NN: %d, reqs: %d, started: %d)',$rtime,$nn,count($request_list),$rs));
	mngr_fight_log_pf();
	mngr_fight_log('END');
}
sleep(max(MNGR_FIGHT_LAUNCH_INTERVAL-$rtime,0));


// ------------------------------------------------------------------

function mngr_fight_log($str) {
	logfile('crons/mngr_fight_launch.log',getmypid().' - '.$str);
//	error_log("(mngr_fight_launch: ".getmypid().")".$str);
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