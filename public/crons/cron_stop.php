<? # $Id: cron_stop.php,v 1.3 2008-03-05 09:08:32 s.panferov Exp $
exit;
chdir("..");
require_once("include/common.inc");
require_once("lib/project.lib");

// Время задержки после остановки, чтобы для пользователей произошёл переход на заглушку
define('CRON_STOP_DELAY', 20);
set_time_limit(0);

project_stop();



sleep(CRON_STOP_DELAY);
error_log("cron_stop processed.");

?>