<? # $Id: cron_export.php,v 1.11 2009-10-27 08:33:30 i.hrustalev Exp $

chdir("..");
require_once("include/common.inc");
require_once("lib/exp_imp.lib");

common_init();
set_time_limit(0);

// 13 - injures 
$result = exp_imp_export(
	SERVER_ROOT.SYNC_DATA_PATH.'export-'.preg_replace('/\./','_',$current_site_domain).'-'.date('Y-m-d-His',time_current()).'.dat',
	array('entities' => array (
							'areas', 'artifact_artikuls','bot_artikuls','instance_artikuls','avatar_artikuls','nps','pets','lua_scripts',
							'actions','bgs','bonuses','castles','farm','punishments','quests','recipes',
							'restrictions','skills','slaughters','slots','events','achievements',
							'clan_stat_artikuls','admin_profiles','flood','strongholds','tutorials','conversions'
						))
);

if ($result[0]) error_log("cron_export: ".$result[0]);
else {
	array_unshift($result[1],($result[1] ? 'Log:' : 'Log is empty'));
	error_log("cron_export: Export successful\n\n".implode("\n",$result[1]));
}

