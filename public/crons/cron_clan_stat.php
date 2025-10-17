<? # $Id: cron_clan_stat.php 30.04.2010 16:11:05 i.hrustalev $

ini_set("memory_limit", "256M");

chdir("..");
require_once("include/common.inc");
require_once("lib/clan.lib");
require_once("lib/clan_stat.lib");
require_once("lib/user_stat.lib");

// все кланы
$clan_ids = get_hash(clan_list(false, sql_pholder(' AND (`flags` & ?#CLAN_FLAG_DISBANDED) = 0'), 'id'), 'id', 'id');

// интересующие нас артикулы
$user_clan_stat_artikuls = clan_stat_artikul_list(false, '', 'id, type_id, object_id');
$clan_stat_artikuls = array();

foreach ($user_clan_stat_artikuls as $clan_stat) {
	if ($clan_stat['type_id'] > 0 && $clan_stat['object_id'] > 0) {
		$clan_stat_artikuls[] = $clan_stat;
	}
}

if (!$clan_ids || (!$clan_stat_artikuls && !$user_clan_stat_artikuls)) {
	// nothing to do!
	exit();
}

foreach ($clan_ids as $clan_id) {
	if ($clan_stat_artikuls) {
		// обновление total_value
		clan_stat_update($clan_id, $clan_stat_artikuls);
	}

	if ($user_clan_stat_artikuls) {
		// обновление уровней
		clan_stat_update_levels($clan_id, $user_clan_stat_artikuls);
	}
}

?>