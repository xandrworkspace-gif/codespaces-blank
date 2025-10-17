<?
/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/
chdir("..");
require_once("include/common.inc");
require_once("lib/rating.lib");
require_once("lib/achievement.lib");
require_once("lib/user_stat.lib");
require_once("lib/user_metric.lib");
require_once("lib/dominance.lib");

define('CRON_RATING_ALL_USERS_PORTION' ,1000);

set_time_limit(0);
ini_set('memory_limit', '1024M');
$stime = time_current();

// Поддерживаем соединение с MySQL
global $rating_stime;
$rating_stime = time_current();
function ticks_handler() {
	global $rating_stime;

	if ((time() - $rating_stime) > 60) {
		global $db, $db_2, $db_3, $db_auth, $db_diff, $db_nodes;
		
		$rating_stime = time();

		$sql = 'SELECT 1';
		$db->execSql($sql);
		$db_2->execSql($sql);
		$db_3->execSql($sql);
		$db_diff->execSql($sql);
		$db_auth->execSql($sql);
		foreach($db_nodes as $db_node) $db_node->execSql($sql);
	}
}
register_tick_function('ticks_handler');
declare(ticks = 100000);

// Выбираем всех пользователей выше 2 уровня
// В скрипте работаем со всеми пользователями, поэтому ничего лишнего доставать и хранить не надо
$next_uid = 0;
$users = array();
do {
	$users_part = make_hash(user_list(false, sql_pholder(' and id > ? and level>2 AND !(flags & ?) AND !(flags2 & ?) LIMIT '.CRON_RATING_ALL_USERS_PORTION, $next_uid, USER_FLAG_ADMIN|USER_FLAG_PUNISH|USER_FLAG_JAIL|USER_FLAG_NOT_FINISHED_REG|USER_FLAG_CSERVER_GUEST, USER_FLAG2_NO_RATING), false, 'id,level,kind,rank,dominance'));
	if (!$users_part) break;
	$users += $users_part;
	$next_uid = max(array_keys($users_part));
} while(1);

//print_r($users);

// Уберём тех, кто не заходил 21 день
$notactive_time = $stime - 21*24*3600;
$auth_list = make_hash(auth_list(sql_pholder(' and server_id=? and time_login>?', SERVER_ID, $notactive_time), false, 'uid'), 'uid');
foreach($users as $user_id => $user) {
	unset($users[$user_id]['object_class']);
	if (!isset($auth_list[$user_id])) {
		unset($users[$user_id]);
	}
}

//print_r($users);

//echo sql_pholder(' and id > ? and level>2 AND !(flags & ?) AND !(flags2 & ?) LIMIT '.CRON_RATING_ALL_USERS_PORTION, $next_uid, USER_FLAG_ADMIN|USER_FLAG_PUNISH|USER_FLAG_JAIL|USER_FLAG_NOT_FINISHED_REG|USER_FLAG_CSERVER_GUEST, USER_FLAG2_NO_RATING);


// Дополним массив пользователей статами для рейтинга
global $rating_stats;
$rating_stats = array(
	'honor'      => array('type_id' => USER_STAT_TYPE_SKILL, 'object_id' => USER_STAT_SKILL_HONOR),
	'exp'        => array('type_id' => USER_STAT_TYPE_SKILL, 'object_id' => USER_STAT_SKILL_EXP),
	'rep_rating' => array('type_id' => USER_STAT_TYPE_SKILL, 'object_id' => USER_STAT_SKILL_REP_RATING),
    'battles'    => array('type_id' => USER_STAT_TYPE_MISC,  'object_id' => USER_STAT_OBJECT_WIN_COUNT),
    'drakoskalp' => array('type_id' => USER_STAT_TYPE_SKILL, 'object_id' => USER_STAT_SKILL_SKALP_DRAKON),
    'repgreat'   => array('type_id' => USER_STAT_TYPE_SKILL, 'object_id' => USER_STAT_SKILL_GREAT_FIGHT_REP),
);

$dominance_stat = array();

$i=0;
while(1) {
	// Будем получать скилы пачками и дописывать пользователям
    $part = array_slice($users,$i*300,300);
    if (!$part) break;
    $i++;
    $user_ids = array();
    foreach($part as $row) {
    	$user_ids[] = $row['id'];
    }

    //Получим господство!
    $dominance = make_hash(dominance_stat_list(array('user_id' => $user_ids)), 'user_id', true);
    foreach ($dominance as $user_id=>$stat){
        $dominance_stat[$user_id] = $stat;
    }

    foreach($rating_stats as $stat_key => $stat_param) {
    	foreach ($NODE_NUMS as $nn) {
			if (!NODE_SWITCH($nn)) {
				fwrite(STDERR, "Unable to switch to Node [$nn]\n");
				continue;
			}
		
			$stat_param['user_id'] = $user_ids;
			$stats = user_stat_list($stat_param);
			
			foreach($stats as $stat) {
				$users[$stat['user_id']][$stat_key] = $stat['value'];
			}
		
    	}
    }
}

//print_r($users);

// Дополняем инфу информацией о достижениях
foreach($users as $user_id => $user) {
	NODE_SWITCH(null, $user_id);
	$achievement_list = user_achievement_list(array('user_id'=>$user['id']), ' group by user_id order by null', ' user_id,sum(weight) as value');
	foreach($achievement_list as $achievement) {
		$users[$user_id]['achievements'] = $achievement['value'];
	}
}

//Изменяем доблесть на основе господства!
foreach ($dominance_stat as $user_id=>$dominance_list){
    $users[$user_id]['honor'] += dominance_ratings_calc($dominance_list);
}

function dominance_ratings_calc($dominance_list = array()){
    $honor = 0;
    foreach ($dominance_list as $dominance){
        $honor += $dominance['honor'];
    }
    return $honor;
}

//print_r($users);

// добавляем рейтинги по всем параметрам
add_rating($users, 'honor');
add_rating($users, 'exp');
add_rating($users, 'rep_rating');
add_rating($users, 'achievements');
//новенькое
add_rating($users, 'battles');
add_rating($users, 'drakoskalp');
add_rating($users, 'repgreat');

// проставим общий рейтинг и общий уровневый рейтинг
foreach($users as $user_id => $user) {
	$users[$user_id]['rating'] = get_calculated_rate($user, '');
}
// добавляем рейтинг по общему рейтингу :)
add_rating($users, 'rating');

foreach($users as $user_id => $user) {
	$users[$user_id]['user_id'] = $user['id'];
	unset($users[$user_id]['id']);
}

$ratings = array(
	'rating','honor','exp','rep_rating','achievements','battles','drakoskalp','repgreat'
);
$rating_types = array('rate','levelrate');
$rating_types_global = array('global_rate','global_levelrate');

$rating_type_dismiss = array(
    'global_rate' => 'rate',
    'global_levelrate' => 'levelrate',
);

$honor_metric_users = array();

// проставим изменения в рейтингах 
$i=0;
while(1) {
	$part = array_slice($users,$i*300,300);
	if (!$part) break;
    $i++;
    $user_ids = array();
    foreach($part as $user) {
    	$user_ids[] = $user['user_id'];
    }
    $current_ratings = total_rating_user_list(array('user_id' => $user_ids));
    foreach($current_ratings as $current_rating) {
    	$uid = $current_rating['user_id'];
    	foreach ($rating_types as $rating_type) {
    		foreach ($ratings as $rating) {
    			$key = $rating_type.'_'.$rating;
    			if (isset($users[$uid])) {
    				$users[$uid][$key.'_diff'] = $current_rating[$key]-$users[$uid][$key];
    			} else {
				    $users[$uid][$key.'_diff'] = null;
			    }
    		}
    	}
		
		$honor_diff = $users[$uid]['honor'] - $current_rating['honor'];
		if ($honor_diff > 0) $honor_metric_users[] = array('user_id' => $uid, 'value' => $honor_diff);
    }
}

// записываем метрики
user_metric_multi_update(USER_METRIC_TYPE_HONOR, $honor_metric_users);

// очищаем таблицу и сохраняем пачками
total_rating_user_truncate();
$i=0;
while(1) {
	$part = array_slice($users,$i*300,300);
    if (!$part) break;
    $i++;
    $rows = array();
    foreach($part as $user_row) {
    	$row = array(
    		'user_id'      => $user_row['user_id'],
    		'kind'         => $user_row['kind'],
    		'level'        => $user_row['level'],
    		'honor'        => $user_row['honor'],
    		'exp'          => $user_row['exp'],
    		'rep_rating'   => $user_row['rep_rating'],
    		'achievements' => $user_row['achievements'],
    		'rating'       => $user_row['rating'],

            'battles'      => $user_row['battles'],
            'drakoskalp'   => $user_row['drakoskalp'],
            'repgreat'     => $user_row['repgreat'],
    	);
	    foreach($ratings as $rating) {
	    	foreach($rating_types as $rating_type) {
	    		$key = $rating_type.'_'.$rating;
	    		$row[$key] = $user_row[$key];
	    		$row[$key.'_diff'] = $user_row[$key.'_diff'];
	    	}
	    }
	    $rows[] = $row;	    
    }
	total_rating_user_multi_update($rows, array_keys($rows[0]));
}

// проставим изменения в рейтингах
$i=0;
while(1) {
    $part = array_slice($users,$i*300,300);
    if (!$part) break;
    $i++;
    $user_ids = array();
    foreach($part as $user) {
        $user_ids[] = $user['user_id'];
    }
    $current_ratings = total_rating_user_all_list(array('user_id' => $user_ids));
    foreach($current_ratings as $current_rating) {
        $uid = $current_rating['user_id'];
        foreach($rating_type_dismiss as $dismiss=>$rating_type) {
            foreach ($ratings as $rating) {
                $key = $rating_type.'_'.$rating;
                $key_dismiss = $dismiss.'_'.$rating;
                if (isset($users[$uid])) {
                    $users[$uid][$key_dismiss.'_diff'] = $current_rating[$key]-$users[$uid][$key_dismiss];
                } else {
                    $users[$uid][$key_dismiss.'_diff'] = null;
                }
            }
        }
    }
}

// очищаем таблицу по общим расам! и сохраняем пачками
total_rating_user_all_truncate();
$i=0;
while(1) {
    $part = array_slice($users,$i*300,300);
    if (!$part) break;
    $i++;
    $rows = array();
    foreach($part as $user_row) {
        $row = array(
            'user_id'      => $user_row['user_id'],
            'kind'         => 0,
            'level'        => $user_row['level'],
            'honor'        => $user_row['honor'],
            'exp'          => $user_row['exp'],
            'rep_rating'   => $user_row['rep_rating'],
            'achievements' => $user_row['achievements'],
            'rating'       => $user_row['rating'],

            'battles'      => $user_row['battles'],
            'drakoskalp'   => $user_row['drakoskalp'],
            'repgreat'     => $user_row['repgreat'],
        );
        foreach($ratings as $rating) {
            foreach($rating_type_dismiss as $dismiss=>$rating_type) {
                $key = $rating_type.'_'.$rating;
                $key_dismiss = $dismiss.'_'.$rating;
                $row[$key] = $user_row[$key_dismiss];
                $row[$key.'_diff'] = $user_row[$key_dismiss.'_diff'];
            }
        }
        $rows[] = $row;

    }
    total_rating_user_all_multi_update($rows, array_keys($rows[0]));
}

// тут же будем генерить общие рейтинги
$total_ratings = array(
	'honor' => array('func'=>'cmp_honor', 'field' => 'honor'),
	'exp' => array('func'=>'cmp_exp', 'field' => 'exp'),
	'rating' => array('func'=>'cmp_rating', 'field' => 'rating'),
	'rep_rating' => array('func'=>'cmp_rep_rating', 'field' => 'rep_rating'),
	'achievement' => array('func'=>'cmp_achievements', 'field' => 'achievements'),

    'battles' => array('func'=>'cmp_battles', 'field' => 'battles'),
    'drakoskalp' => array('func'=>'cmp_drakoskalp', 'field' => 'drakoskalp'),
    'repgreat' => array('func'=>'cmp_repgreat', 'field' => 'repgreat'),
);

$total_ratings_user = array();
foreach($total_ratings as $rating_key => $total_rating) {
	uasort($users, $total_rating['func']);
	$top = array_slice($users,0,50);
	foreach($top as $user) {
		if (!isset($total_ratings_user[$user['user_id']])) {

            //Если есть господство, выбираем его!
            if($users[$user['user_id']]['dominance'] && $users[$user['user_id']]['dominance'] > $users[$user['user_id']]['rank']) $users[$user['user_id']]['rank'] = $users[$user['user_id']]['dominance'];

			$total_ratings_user[$user['user_id']] = array('user_id' => $user['user_id'], 'kind' => $user['kind'], 'rank' => intval($users[$user['user_id']]['rank']));
			foreach($total_ratings as $key => $rating) {
				$total_ratings_user[$user['user_id']][$key] = floatval($users[$user['user_id']][$rating['field']]);
			}
		}
	}
}

rating_user_truncate();
foreach($total_ratings_user as $user) {
    rating_user_save($user);
}

//////////////////////
/*Рейтинг Дуэлей*/
require_once('lib/pvp_fight.lib');
$fight_pvp_stats = array();

// очищаем таблицу
rating_duel_truncate();
$i=0;
while(1) {
    // Будем получать рейтинг Дуэлей
    $part = array_slice($users,$i*300,300);
    if (!$part) break;
    $i++;
    $user_ids = array();
    foreach($part as $row) {
        $user_ids[] = $row['user_id'];
    }
    if($user_ids) $duels = pvp_fight_stat_list(array('user_id' => $user_ids));
    //print_r($user_ids);
    $fight_pvp_stats = array_merge($fight_pvp_stats,$duels);
}

//print_r($fight_pvp_stats);

$fight_pvp_rating = array();
foreach ($fight_pvp_stats as $fight_pvp_stat){
    $fight_pvp_rating[] = array(
        'user_id' => $fight_pvp_stat['user_id'],
        'win' => $fight_pvp_stat['win'],
        'fail' => $fight_pvp_stat['fail'],
        'score' => pvp_stat_up($fight_pvp_stat),
    );
}
usort($fight_pvp_rating, function($a, $b) {
    return $b['score'] > $a['score'];
});

$i = 1;
foreach ($fight_pvp_rating as $rating){
    $rating['id'] = $i;
    $rating['_mode'] = CSMODE_INSERT;
    rating_duel_save($rating);
    $i++;
}

function get_calculated_rate($user, $field_add = '') {
	global $rating_stats;
	$power = 0.3;
	$rating_sum = 0;
	foreach($rating_stats as $stat_key => $stat) {
		$rating_sum += pow($user[$field_add.'rate_'.$stat_key], $power);
	}
	$rating_sum += pow($user[$field_add.'rate_achievements'], $power);
	return ($rating_sum>0) ? 4000/$rating_sum : 0;
}

// функция добавления расового/общего рейтинга в массив пользователей
function add_rating(&$users, $field, $ignore_kind = false) {
	uasort($users, 'cmp_'.$field);
	$field_add = ($ignore_kind) ? 'global' : '';
	$kind_counters = array();
	$counter = 0;
	foreach($users as $user_id => $user) {
		$users[$user_id]['rate_'.$field] = ++$kind_counters[$user['kind']];
		$users[$user_id]['global_rate_'.$field] = ++$counter;
	}
	// Добавляем ещё уровневые рейтинги

	for($level=3;$level<20;$level++) {
		$level_users = array();
		foreach($users as $user_id => $user) {
			if ($user['level'] == $level) $level_users[$user_id] = $user;
		}
		uasort($level_users, 'cmp_'.$field);
		$kind_counters = array();
        $counter = 0;
		foreach($level_users as $user_id => $user) {
			$users[$user_id][$field_add.'levelrate_'.$field] = ++$kind_counters[$user['kind']];
            $users[$user_id]['global_levelrate_'.$field] = ++$counter;
		}
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
        return $a['rate_honor'] > $b['rate_honor'];
    }
    return ($a['exp'] < $b['exp']) ? 1 : -1;
}

function cmp_rep_rating($a, $b) {
    if ($a['rep_rating'] == $b['rep_rating']) {
        return $a['rate_honor'] > $b['rate_honor'];
    }
    return ($a['rep_rating'] < $b['rep_rating']) ? 1 : -1;
}

function cmp_achievements($a, $b) {
    if ($a['achievements'] == $b['achievements']) {
        return $a['rate_honor'] > $b['rate_honor'];
    }
    return ($a['achievements'] < $b['achievements']) ? 1 : -1;
}

function cmp_rating($a, $b) {
    if ($a['rating'] == $b['rating']) {
        return $a['rate_honor'] > $b['rate_honor'];
    }
    return ($a['rating'] < $b['rating']) ? 1 : -1;
}

function cmp_globalrating($a, $b) {
    if ($a['globalrating'] == $b['globalrating']) {
        return $a['rate_honor'] > $b['rate_honor'];
    }
    return ($a['globalrating'] < $b['globalrating']) ? 1 : -1;
}

//Новоое
function cmp_battles($a, $b) {
    if ($a['battles'] == $b['battles']) {
        return 0;
    }
    return ($a['battles'] < $b['battles']) ? 1 : -1;
}

function cmp_drakoskalp($a, $b) {
    if ($a['drakoskalp'] == $b['drakoskalp']) {
        return 0;
    }
    return ($a['drakoskalp'] < $b['drakoskalp']) ? 1 : -1;
}

function cmp_repgreat($a, $b) {
    if ($a['repgreat'] == $b['repgreat']) {
        return 0;
    }
    return ($a['repgreat'] < $b['repgreat']) ? 1 : -1;
}