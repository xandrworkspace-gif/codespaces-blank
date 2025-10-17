<? # $Id: cron_start.php,v 1.1 2008-03-04 15:28:11 s.panferov Exp $
exit;
chdir("..");
require_once("include/common.inc");
require_once("lib/project.lib");

set_time_limit(0);

project_start();

error_log("cron_start processed.");

?>