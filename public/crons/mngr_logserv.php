<?
chdir("..");
require_once("include/common.inc");
require_once("include/logserv.inc");

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) {
	sleep(MNGR_LOGSERV_INTERVAL);
	return;
}

if (!is_dir(LOGSERV_STORAGE)) {
	sleep(MNGR_LOGSERV_INTERVAL);
	exit("mngr_logserv has no storage!!!");
}

$stime1 = time();
require_once("lib/logserv.lib");


set_time_limit(0);

logserv_flush_buffer();

// очищаем кеш
$cache_list = glob(LOGSERV_CACHE.'*');
if ($cache_list) {
	foreach ($cache_list as $name) {
		if (is_dir($name)) continue;
		if (@filemtime($name) > (time_current() - 60)) continue;
		@unlink($name);
	}
}
$stime2 = time();
$rtime = $stime2-$stime1;
if ($rtime > MNGR_LOGSERV_INTERVAL) error_log("(mngr_logserv: ".getmypid()."): Runtime $rtime sec");
sleep(max(MNGR_LOGSERV_INTERVAL-$rtime,0));
?>