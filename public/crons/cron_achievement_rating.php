<? # $Id: cron_achievement_rating.php,v 1.4 2009-06-23 09:12:46 razor Exp $
chdir("..");
require_once("include/common.inc");
require_once("lib/rating.lib");
require_once("lib/achievement.lib");

set_time_limit(0);
define('CRON_RATING_ALL_USERS_PORTION' ,1000);

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

// Дополняем инфу информацией о достижениях
foreach($users as $user_id => $user) {
    NODE_SWITCH(null, $user_id);
    $achievement_list = user_achievement_list(array('user_id'=>$user['id']), ' group by user_id order by null', ' user_id,sum(weight) as value');
    foreach($achievement_list as $achievement) {
        $users[$user_id]['achievements'] = $achievement['value'];
        $out[$user['kind']][$user['level']][] = array(
            'user_id' => $user['id'],
            'weight' => $achievement['value'],
        );
    }
}

if ($out) {
	rating_achievement_truncate();	
	rating_achievement_extended_truncate();	
	foreach ($out as $kind => $levels) {
		foreach ($levels as $level => $weights) {
			common_fldsort($weights, true, 'weight');
			$cnt = RATING_ACHIEVEMENT_EXTENDED_CNT;
			foreach ($weights as $weight) {
				if ($cnt <= 0) break;
				if ($weight['weight'] > 0)  {
					rating_achievement_extended_save(array(
						'level' => $level,
						'user_id' => $weight['user_id'],
						'weight' => $weight['weight'],
						'kind' => $kind,
					));
				}
				$cnt--;
			}
			$winner = array_shift($weights);	
			if (!$winner) continue;
			rating_achievement_save(array(
				'level' => $level,
				'user_id' => $winner['user_id'],
				'weight' => $winner['weight'],
				'kind' => $kind,
			));
		}
	}
}

?>