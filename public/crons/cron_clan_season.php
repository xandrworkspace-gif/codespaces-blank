<?
chdir("..");
require_once("include/common.inc");
require_once("lib/clan.lib");
require_once("lib/clan_battle.lib");

common_define_settings();

if (!constant('CLAN_BATTLE_SEASON_SERVER_ID') || !constant('SERVER_ID')) exit;
$is_master_server = (SERVER_ID == CLAN_BATTLE_SEASON_SERVER_ID);
$full_sync = true; //(isset($_SERVER['argv'][1]) && ($_SERVER['argv'][1] == 'full'))

if ($full_sync && !$is_master_server) {
	error_log('Attempt to run cron_clan_season.php with "full" param on a slave server. Execution aborted.');
	exit;
}

if (!$full_sync && $is_master_server) {
	error_log('Attempt to run cron_clan_season.php without "full" param on the master server. Execution aborted.');
	exit;
}

if (!(constant('CLAN_BATTLE_IS_ON')) || (defined('PROJECT_STOPPED') && PROJECT_STOPPED) || !defined('CLAN_BATTLE_PREP_AREA_ID') || CLAN_BATTLE_PREP_AREA_ID <= 0) {
	exit;
}

// Дожидаемся завершения битв
if (clan_battle_is_active()) exit;

if (!clan_battle_season_lock()) exit;

do {
	if (!$full_sync) {
		$seasons = crossserver_clan_battle_seasons_export(CLAN_BATTLE_SEASON_SERVER_ID);
		
		foreach($seasons as $s) {
			$leagues = $s['leagues'];
			$checksum = $s['checksum'];
			$param = $s;
			unset($param['leagues'], $param['checksum']);
			$param['_mode'] = CSMODE_REPLACE;
			clan_battle_season_save($param);
			
			if ($leagues) foreach ($leagues as $l) {
				$param = $l;
				$param['_mode'] = CSMODE_REPLACE;
				clan_battle_league_save($param);
			}
			
			if ($s['checksum'] == clan_battle_season_get_checksum($s['id'])) break;

			$dump = crossserver_clan_battle_season_get_dump(CLAN_BATTLE_SEASON_SERVER_ID, $s['id']);
			if (!$dump) break;
			
			$cache = new Cache('CLAN_BATTLE_SEASON_CHECKSUM_'.$season['id']);
			$cache->remove();

			$dump = explode('|', trim($dump, ' |'));
			$season_id = array_shift($dump);

			clan_battle_season_clan_delete(false, sql_pholder(' AND season_id = ?', $season_id));
			foreach ($dump as $d) {
				list($clan_server_id, $clan_id, $league_id, $copy_id, $clan_skill, $clan_rating_place, $clan_rating_place_diff, $clan_battle_server_id, $clan_battle_id) = explode(',', $d);

				clan_battle_season_clan_save(array(
					'clan_id' => $clan_id,
					'server_id' => $clan_server_id,
					'season_id' => $season_id,
					'league_id' => $league_id,
					'copy_id' => $copy_id,
					'skill' => $clan_skill,
					'rating_place' => $clan_rating_place,
					'rating_place_diff' => $clan_rating_place_diff,
					'clan_battle_server_id' => $clan_battle_server_id,
					'clan_battle_id' => $clan_battle_id,
				));
			}
		}
	} else {
		$season = clan_battle_season_get_active();
		
		$cache = new Cache('CLAN_BATTLE_SEASON_CHECKSUM_'.$season['id']);
		$cache->remove();
		
		$clan_battle_season_clans = clan_battle_season_clan_list(array('season_id' => $season['id']), '', 'clan_id, server_id, league_id, copy_id, skill, rating_place, rating_place_diff, clan_battle_server_id, clan_battle_id');
		$tmp = array();
		foreach ($clan_battle_season_clans as $season_clan) {
			$tmp[$season_clan['server_id']][$season_clan['clan_id']] = $season_clan;
		}
		$clan_battle_season_clans = $tmp;
		$clan_battle_season_clans_stored_data = $tmp;
		$tmp = null;
		unset($tmp);
		
		foreach (common_get_servers('CB') as $srv) {
			$clan_battle_scores = $srv['id'] == SERVER_ID ? clan_battle_get_scores() : crossserver_clan_battle_get_scores($srv['id']);
			if ($clan_battle_scores) foreach ($clan_battle_scores as $clan_id => $score) {
				if (isset($clan_battle_season_clans[$srv['id']][$clan_id])) $clan_battle_season_clans[$srv['id']][$clan_id]['skill'] = $score;
			}
		}
		
		$tmp = array();
		$league_copies = array();
		foreach ($clan_battle_season_clans as $season_clans) {
			foreach ($season_clans as $clan_id => $season_clan) {
				$tmp[$season_clan['league_id']][$season_clan['copy_id']][] = $season_clan;
				$league_copies[$season_clan['league_id']][$season_clan['copy_id']] = $season_clan['copy_id'];
			}
		}
		$clan_battle_season_clans = $tmp;
		$tmp = null;
		unset($tmp);
		
		$sort_func = create_function('$a,$b', '
			if ($a["skill"] == $b["skill"]) {
					return 0;
			}
			return ($a["skill"] > $b["skill"]) ? -1 : 1;
		');
		
		foreach ($league_copies as $league_id => $copies) {
			foreach ($copies as $copy_id) {
				usort($clan_battle_season_clans[$league_id][$copy_id], $sort_func);
			}
		}
		
		if ($season['status'] < CLAN_BATTLE_SEASON_STATUS_RUNNING_PLAY) clan_battle_season_clan_delete(false, sql_pholder(' AND season_id = ?', $season['id']));
		
		foreach ($clan_battle_season_clans as $league_id => $copy_season_clans) {
			foreach ($copy_season_clans as $copy_id => $season_clans) {
				$place = 0;
				foreach ($season_clans as $season_clan) {
					if ($season['status'] < CLAN_BATTLE_SEASON_STATUS_RUNNING_PLAY) {
						clan_battle_season_clan_save(array(
							'clan_id' => $season_clan['clan_id'],
							'server_id' => $season_clan['server_id'],
							'season_id' => $season['id'],
							'league_id' => $league_id,
							'copy_id' => $copy_id,
							'skill' => $season_clan['skill'],
							'rating_place' => ++$place,
							'rating_place_diff' => $clan_battle_season_clans_stored_data[$season_clan['server_id']][$season_clan['clan_id']]['rating_place_diff'],
							'clan_battle_server_id' => $clan_battle_season_clans_stored_data[$season_clan['server_id']][$season_clan['clan_id']]['clan_battle_server_id'],
							'clan_battle_id' => $clan_battle_season_clans_stored_data[$season_clan['server_id']][$season_clan['clan_id']]['clan_battle_id'],
						));
					} else {
						clan_battle_season_clan_save(array(
							'skill' => $season_clan['skill'],
							'rating_place' => ++$place,
							'rating_place_diff' => $clan_battle_season_clans_stored_data[$season_clan['server_id']][$season_clan['clan_id']]['rating_place_diff'],
							'clan_battle_server_id' => $clan_battle_season_clans_stored_data[$season_clan['server_id']][$season_clan['clan_id']]['clan_battle_server_id'],
							'clan_battle_id' => $clan_battle_season_clans_stored_data[$season_clan['server_id']][$season_clan['clan_id']]['clan_battle_id'],
						), sql_pholder(' AND season_id = ? AND clan_id = ? AND server_id = ?', $season['id'], $season_clan['clan_id'], $season_clan['server_id']));
					}
				}
			}
		}
	}
} while(0);

clan_battle_season_unlock();
