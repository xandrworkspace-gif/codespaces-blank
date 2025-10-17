<?# $Id: cron_stat_artifacts.php,v 1.6 2009-11-23 12:50:17 v.krutov Exp $ %TRANS_SKIP%
chdir("..");
 
require_once("include/common.inc");
require_once("lib/artifact.lib");
require_once("lib/recipe.lib");

set_time_limit(0);

// Статистика по вещам
$result = calc_stat_user_artifacts();
save_stat_user_artifacts($result);

// Статистика по уровням
$result = calc_stat_user_levels();
save_stat_user_levels($result);

// Статистика по рецептам
$result = calc_stat_user_recipies();
save_stat_user_recipies($result);


// Статистика по вещам
function calc_stat_user_artifacts() {
	global $NODE_NUMS;
	// Выборка артикулов предметов, по которым нужно вести статистику.
	// Таковыми являются артикулы с флагом ARTIFACT_FLAG_STAT_COUNT.
	$artifact_artikul_hash = make_hash(artifact_artikul_list(false, sql_pholder(' AND flags & ? ', ARTIFACT_FLAG_STAT_COUNT), 'id, title, picture'));
	if (!$artifact_artikul_hash) {
		return false;
	}
	$artikul_ids = get_hash($artifact_artikul_hash, 'id', 'id');

	// Теперь подсчет колчества предметов в игре.
	// trade_cnt - количество передаваемых предметов (флаг ARTIFACT_FLAG_STAT_COUNT) установлен.
	// non_trade_cnt - количество непередаваемых предметов.
	$fields = sql_pholder('artikul_id, user_id,
				 sum(if(cnt>0, cnt, 1)) as cnt,
				 sum(if((flags & ?) = 0, if(cnt > 0, cnt, 1), 0)) as trade_cnt,
				 sum(if((flags & ?), if(cnt > 0, cnt, 1), 0)) as non_trade_cnt',
				 ARTIFACT_FLAG_NOGIVE, 
				 ARTIFACT_FLAG_NOGIVE
				);
	$add = sql_pholder(' AND artikul_id IN (?@) ', $artikul_ids);
	$group = ' GROUP BY artikul_id ';
	foreach ($NODE_NUMS as $nn) {
		if (!NODE_SWITCH($nn)) continue;
		global $db;

		$data = common_list($db, TABLE_ARTIFACTS, false, $add.$group, $fields);
		
		$user_hash = make_hash($data, 'user_id', true);
		// получили тех юзеров из auth, которые не находятся на текущем сервере
		$auth_list = $user_hash ? auth_list(sql_pholder(' AND server_id != ? AND uid IN(?@)', SERVER_ID, array_keys($user_hash)), false, 'uid') : array();
		// удалили их из хеша
		foreach ($auth_list as $auth) {
			unset($user_hash[$auth['uid']]);
		}
		
		artifact_artikul_get_title($data, $artifact_artikul_hash);
		
		if ($data) {
			$data = make_hash($data, 'artikul_id', false);
			foreach ($data as $key => $item) {
				if (!$user_hash[$item['user_id']]) continue;
				$cnt = intval($item['cnt']) > 0 ? intval($item['cnt']) : 1;
				$trade_cnt = intval($item['trade_cnt']) > 0 ? intval($item['trade_cnt']) : 0;
				$non_trade_cnt = intval($item['non_trade_cnt']) > 0 ? intval($item['non_trade_cnt']) : 0;

				if (!$result[$key]) {
					if ($nn != FRIDGE_NN) {
						$result[$key]['artikul_id'] = $item['artikul_id'];
						
						$result[$key]['total_cnt'] =  $cnt;
						$result[$key]['trade_cnt'] = $trade_cnt;
						$result[$key]['non_trade_cnt'] = $non_trade_cnt;	
					} else {
						$result[$key]['artikul_id'] = $item['artikul_id'];
						$result[$key]['title'] = $item['title'];
						
						$result[$key]['total_cnt'] =  $cnt;
					}
				} else {
					if ($nn != FRIDGE_NN) {
						$result[$key]['total_cnt'] +=  $cnt;
						$result[$key]['trade_cnt'] += $trade_cnt;
						$result[$key]['non_trade_cnt'] += $non_trade_cnt;
					} else {
						$result[$key]['total_cnt'] +=  $cnt;
					}
				}
			}
		}
	}
	return $result;
}

function save_stat_user_artifacts($result) {
	global  $db_diff;
	$current_date = time();
	if($result) {
		foreach($result as $item) {
			common_save($db_diff, 'stat_user_artifacts', array(
				'_mode'         => CSMODE_INSERT,
				'date'          => $current_date,
				'artikul_id'    => $item['artikul_id'],
				'total_cnt'     => intval($item['total_cnt']),
				'trade_cnt'	    => intval($item['trade_cnt']),
				'non_trade_cnt' => intval($item['non_trade_cnt']),
			));
		}
	}
}

// Статистика по уровням
function calc_stat_user_levels() {
	global $db_auth;
	
	$level_cnt = array();
	
	$off = 0;
	do {
		$node_info = common_list($db_auth, 'node_info', false, ' AND node_num != '.FRIDGE_NN.' ORDER BY uid LIMIT '.$off.', 5000', 'uid');
		if (!$node_info) break;
		
		$data = user_list(array('id' => get_hash($node_info, 'uid', 'uid')), ' GROUP BY level', false, 'level, COUNT(*) cnt');
		foreach ($data as $row) {
			$level_cnt[$row['level']] += $row['cnt'];
		}
		
		$off += count($node_info);		
	} while (1);
	
	return $level_cnt;
}

function save_stat_user_levels($result) {
	global  $db_diff;
	$current_date = time();
	if ($result) {
		foreach ($result as $level => $count) {
			common_save($db_diff, 'stat_user_levels', array(
				'_mode'       => CSMODE_INSERT,
				'date'        => $current_date,
				'level'       => $level,
				'users_count' => $count,
			));
		}
	}
}

// Статистика по рецептам
function calc_stat_user_recipies() {
	global $db, $db_auth, $NODE_NUMS;
	$users_get_limit = 1024;
	
	// Получение артикулов рецептов.
	$recipie_artikuls = get_hash(recipe_list(), 'id', 'id');

	$result = array();
	if ($recipie_artikuls) {
		// Всего юзеров выучивших эти рецепты.
		$user_count = recipe_user_count(false, sql_pholder(' AND artikul_id IN(?@)', $recipie_artikuls));
		// Так как количество игроков обычно стремиться к бесконечности, то 
		// лучше вытягивать их пачками по $user_max_request.
		for ($i = 0; $i < $user_count; $i += $users_get_limit) {
			$data = recipe_user_list(false, false, 
					sql_pholder(' AND artikul_id IN (?@) LIMIT '. $i.','.$users_get_limit, $recipie_artikuls));
			$recipe_user_ids = get_hash($data, 'user_id', 'user_id');
			// Узнать на каком узле находятся выбранные игроки.
			$node_users = make_hash(
						  common_list($db_auth, 'node_info', false, 
						  sql_pholder(' AND uid IN(?@) ', $recipe_user_ids)),
						  'uid', 
						  false);
			foreach ($data as $item) {
				$user_node = $node_users[$item['user_id']]['node_num'];
				if ($user_node) {
					foreach ($item as $key=>$value) {
						if ($key == 'cnt') {
							if ($user_node != FRIDGE_NN) {
								$result[$item['artikul_id']][$key] += 1;
							} else {
								$result[$item['artikul_id']]['fridge_cnt'] += 1;
							}
						} else {
							$result[$item['artikul_id']][$key] = $value;
						}
					}
				}
			}
		}
	}	
	return $result;
}

function save_stat_user_recipies($result) {
	global $db_diff;
	$current_date = time();
	
	if ($result) {
		foreach ($result as $item) {
			common_save($db_diff, 'stat_user_recipies', array(
				'_mode'        => CSMODE_INSERT,
				'date'         => $current_date,
				'artikul_id'   => intval($item['artikul_id']),
				'count'        => intval($item['cnt']),
				'fridge_count' => intval($item['fridge_cnt']),
			));
		}
	}
}
?>
