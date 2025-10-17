<?php

chdir("..");
require_once("include/common.inc");
require_once("lib/session.lib");
require_once("lib/stat_user_online.lib");

require_once("lib/user.lib");
require_once("lib/pet.lib");

pets_cron();

while (stat_user_online_delete(false, sql_pholder(" AND time < ? LIMIT 100", time_current() - STAT_USER_ONLINE_TTL)) > 0)
	sleep(1);

foreach ($NODE_NUMS as $nn) {
	// пропускаем морозилку, там не может быть активных пользователей
	if ($nn == FRIDGE_NN) {
		continue;
	}
	if (!NODE_PUSH($nn)) {
		error_log(sprintf('cron_stat_online.php: Unable to push node %d', $nn));
		continue;
	}
	$add = sql_pholder(' AND stime > ?', time_current() - $SESSION_TTL);
	$online_users = session_list(null, null, null, $add, 'uid');
	NODE_POP();

	$t = time_current();
	if ($online_users) foreach ($online_users as $user) {
		stat_user_online_save(array('uid' => $user['uid'], 'time' => $t));
	}
}

//END