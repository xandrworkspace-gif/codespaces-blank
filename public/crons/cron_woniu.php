<? # $Id: cron_woniu.php,v 1.1 2008-12-17 09:40:37 razor Exp $
exit;
chdir("..");
require_once("include/common.inc");

// Отправляем данные по партнерской программе
require_once('partner/woniu/template.inc');
template_partner_send();

?>