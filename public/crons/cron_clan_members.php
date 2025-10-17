<? # $Id: cron_clan_stat.php 30.04.2010 16:11:05 i.hrustalev $

ini_set("memory_limit", "256M");

chdir("..");
require_once("include/common.inc");
require_once("lib/clan.lib");

// Удалить старые неподтвержденные приглашения в клан
$ref = array(
	'status' => CM_STATUS_INVITED,
);
$add = ' AND stime <= UNIX_TIMESTAMP(NOW() - INTERVAL 1 HOUR)';
clan_member_delete($ref, false, $add);
