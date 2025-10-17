<? # $Id: cron_logserv.php,v 1.7 2008-07-08 12:41:27 s.panferov Exp $
exit;
chdir("..");
require_once("include/common.inc");

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) return;

require_once("lib/logserv.lib");

ini_set('memory_limit', '1024M');
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

?>