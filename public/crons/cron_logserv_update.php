<? # $Id: cron_logserv_update.php
chdir("..");
/*ini_set("display_errors", 1);*/
ini_set("memory_limit", '512M');
/*error_reporting(E_ERROR);*/

require_once("include/common.inc");
require_once("lib/common.lib");
common_define_settings();
// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) return;
require_once("lib/logserv.lib");
set_time_limit(0);

logfile(LOGSERV_FILE_LOG, "Запуск обновления буфера логсервера");
logserv_flush_buffer_v2(100000);
logfile(LOGSERV_FILE_LOG, "Остановка обновления буфера логсервера");

?>