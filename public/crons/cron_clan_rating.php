<? # $Id: 

chdir("..");
require_once("include/common.inc");
require_once("lib/rating.lib");
require_once("lib/user_stat.lib");
require_once("lib/clan.lib");
require_once("lib/clan_stat.lib");

common_define_settings();

// Generating clan ratings
// getting clan stats for rating
$stat_artikuls_ref_hash = array(
	'exp' => array('type_id'=>USER_STAT_TYPE_SKILL,'object_id'=>USER_STAT_SKILL_EXP),
	'honor' => array('type_id'=>USER_STAT_TYPE_SKILL,'object_id'=>USER_STAT_SKILL_HONOR),
	'rep_rating' => array('id' => 6),
);

//NODE_SWITCH(1);
//clan_stat_save_user_stats(1,2);

if (defined('CLAN_BATTLE_STAT_SHOW') && CLAN_BATTLE_STAT_SHOW) {
	$stat_artikuls_ref_hash['clan_battles'] = CLAN_BATTLE_STAT_SHOW;
}

$clan_stat_artikuls = array();
foreach ($stat_artikuls_ref_hash as $k => $ref) {
	$clan_stat_artikuls[$k] = clan_stat_artikul_get($ref);
}

// disbanded or denied clans ids
$clans = make_hash(clan_list(false, sql_pholder(' AND NOT (`flags` & ?#CLAN_FLAG_DISBANDED OR `flags` & ?#CLAN_FLAG_NO_RATING) ORDER BY id '), 'id'), 'id');

$i=0;
while(1) {
	$part = array_slice($clans,$i*300,300);
	if (!$part) break;
	$i++;
	
	$clan_ids = get_hash($clans, 'id', 'id');
	foreach($clan_stat_artikuls as $k => $clan_stat_artikul) {
		$clan_stats = clan_stat_list(array('clan_stat_artikul_id'=>$clan_stat_artikul['id'], 'clan_id' => $clan_ids));
		foreach ($clan_stats as $clan_stat) {
			if ($clan_stat['clan_stat_artikul_id'] == $clan_stat_artikuls['exp']['id']) {
				$clans[$clan_stat['clan_id']]['stat_level'] = $clan_stat['clan_stat_artikul_level'];
			} elseif ($clan_stat['clan_stat_artikul_id'] == $clan_stat_artikuls['honor']['id']) {
				$clans[$clan_stat['clan_id']]['stat_rank'] = $clan_stat['clan_stat_artikul_level'];
			} 
			$clans[$clan_stat['clan_id']][$k] = $clan_stat['total_value'];
		}
	}
}

$total_rating = array();

foreach ( $clans as $clan_id => $clan ) {
	$leader_id = clan_leader_id_get($clan_id);
    $user = user_get($leader_id);
	if ($user['kind']) {
		$clans[$clan_id]['kind'] = $user['kind'];
	} else {
		unset($clans[$clan_id]);
	}
}

// добавляем рейтинги по всем параметрам
add_clan_rating($clans, 'honor');
add_clan_rating($clans, 'exp');
add_clan_rating($clans, 'rep_rating');
add_clan_rating($clans, 'clan_battles');
// проставим общий рейтинг и общий уровневый рейтинг
foreach($clans as $clan_id => $clan) {
	$clans[$clan_id]['rating'] = get_clan_calculated_rate($clan);
}
// добавляем рейтинг по общему рейтингу :)
add_clan_rating($clans, 'rating');

foreach($clans as $clan_id => $clan) {
	$clans[$clan_id]['clan_id'] = $clan['id'];
	unset($clans[$clan_id]['id']);
}

$ratings = array(
	'rating','honor','exp','rep_rating','clan_battles',
);

// проставим изменения в рейтингах 
$i=0;
while(1) {
	$part = array_slice($clans,$i*500,500);
	if (!$part) break;
	$i++;
	$clan_ids = get_hash($part, 'clan_id', 'clan_id');
	$current_ratings = total_rating_clan_list(array('clan_id' => $clan_ids));
	foreach($current_ratings as $current_rating) {
		$uid = $current_rating['clan_id'];
		foreach ($ratings as $rating) {
			$key = 'rate_'.$rating;
			if (isset($clans[$uid])) {
				$clans[$uid][$key.'_diff'] = $current_rating[$key]-$clans[$uid][$key];
			} else {
				$clans[$uid][$key.'_diff'] = null;
			}
		}

	}
}

// deleting old rating data
if (!total_rating_clan_truncate()) exit("Error while truncating old rating");

$i=0;
while(1) {
	$part = array_slice($clans,$i*500,500);
	if (!$part) break;
	$i++;
	$rows = array();
	foreach($part as $clan_row) {
		$row = array(
			'clan_id'      => $clan_row['clan_id'],
			'stat_level'   => $clan_row['stat_level'],
			'stat_rank'    => $clan_row['stat_rank'],
			'kind'         => $clan_row['kind'],
			'honor'        => $clan_row['honor'],
			'exp'          => $clan_row['exp'],
			'rep_rating'   => $clan_row['rep_rating'],
			'clan_battles' => $clan_row['clan_battles'],
			'rating'       => $clan_row['rating'],
		);
		foreach($ratings as $rating) {
			$key = 'rate_'.$rating;
			$row[$key] = $clan_row[$key];
			$row[$key.'_diff'] = $clan_row[$key.'_diff'] ? $clan_row[$key.'_diff'] : 0;
		}
		$rows[] = $row;	    
	}
	total_rating_clan_multi_update($rows, array_keys($rows[0]));
}

// тут же будем генерить общие рейтинги
$total_ratings = array(
	'honor' => array('func'=>'cmp_honor', 'field' => 'honor'),
	'exp' => array('func'=>'cmp_exp', 'field' => 'exp'),
	'rating' => array('func'=>'cmp_rating', 'field' => 'rating'),
	'rep_rating' => array('func'=>'cmp_rep_rating', 'field' => 'rep_rating'),
	'clan_battles' => array('func'=>'cmp_clan_battles', 'field' => 'clan_battles'),
);

$total_ratings_clan = array();
foreach($total_ratings as $rating_key => $total_rating) {
	uasort($clans, $total_rating['func']);
	$top = array_slice($clans,0,50);
	foreach($top as $clan) {
		if (!isset($total_ratings_clan[$clan['clan_id']])) {
			$total_ratings_clan[$clan['clan_id']] = array('clan_id' => $clan['clan_id'], 'kind' => $clan['kind'], 'stat_rank' => $clan['stat_rank'], 'stat_level' => $clan['stat_level']);
			foreach($total_ratings as $key => $rating) {
				$total_ratings_clan[$clan['clan_id']][$key] = floatval($clans[$clan['clan_id']][$rating['field']]);
			}
		}
	}
}

rating_clan_truncate();
foreach($total_ratings_clan as $clan) {
	rating_clan_save($clan);
}

function get_clan_calculated_rate($clan) {
	global $clan_stat_artikuls;
	$power = 0.3;
	$rating_sum = 0;
	foreach($clan_stat_artikuls as $stat_key => $stat) {
		if ($stat_key == 'clan_battles') continue;
		$rating_sum += pow($clan['rate_'.$stat_key], $power);
	}
	return ($rating_sum>0) ? 3000/$rating_sum : 0;
}

function add_clan_rating(&$clans, $field) {
	uasort($clans, 'cmp_'.$field);
	$counter = 0;
	foreach($clans as $clan_id => $clan) {
		$clans[$clan_id]['rate_'.$field] = ++$counter;
	}

	return true;
}

function cmp_honor($a, $b) {
    if ($a['honor'] == $b['honor']) {
        return 0;
    }
    return ($a['honor'] < $b['honor']) ? 1 : -1;
}

function cmp_exp($a, $b) {
    if ($a['exp'] == $b['exp']) {
        return $a['rate_honor'] > $b['rate_honor'] ? 1 : -1;
    }
    return ($a['exp'] < $b['exp']) ? 1 : -1;
}

function cmp_rep_rating($a, $b) {
    if ($a['rep_rating'] == $b['rep_rating']) {
        return $a['rate_honor'] > $b['rate_honor'] ? 1 : -1;
    }
    return ($a['rep_rating'] < $b['rep_rating']) ? 1 : -1;
}

function cmp_clan_battles($a, $b) {
    if ($a['clan_battles'] == $b['clan_battles']) {
        return $a['rate_honor'] > $b['rate_honor'] ? 1 : -1;
    }
    return ($a['clan_battles'] < $b['clan_battles']) ? 1 : -1;
}

function cmp_rating($a, $b) {
    if ($a['rating'] == $b['rating']) {
        return $a['rate_honor'] > $b['rate_honor'] ? 1 : -1;
    }
    return ($a['rating'] < $b['rating']) ? 1 : -1;
}
