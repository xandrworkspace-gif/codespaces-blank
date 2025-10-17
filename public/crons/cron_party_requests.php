<? # $Id: cron_party_requests.php,v 1.3 2010-01-27 12:40:10 m.usachev Exp $
chdir("..");
require_once("include/common.inc");

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) return;

require_once("lib/party.lib");

// удалим просроченые заявки
$add = sql_pholder(' AND expired_time <= ?', time_current());
$result = party_requests_delete(false, $add);

echo __FILE__ . ": There $result records was deleted.\n";

