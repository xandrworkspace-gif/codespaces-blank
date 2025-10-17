<?php // %TRANS_SKIP%

exit;

chdir("..");
require_once("include/common.inc");
require_once("lib/artifact.lib");
common_define_settings();

$text = 'Мы любим и ценим наших игроков! Приятный сюрприз ждет вас в рюкзаке!';

$items = array(
	10368 => 10225,
	10369 => 10226,
	10370 => 10227,
	10371 => 10228,
	10372 => 10229,
	10373 => 10230,
	10374 => 10231,
	10375 => 10232,
	10376 => 10233,
	10377 => 10196
);

$rand_artikul_ids = array(
	0 => 10225,
	1 => 10226,
	2 => 10227,
	3 => 10228,
	4 => 10229
);

$delay = 3878;
$time_to_lookup = time_current() - $delay;

$chat_user_ids = array();

foreach ($NODE_NUMS as $nn) {
	if ($nn == FRIDGE_NN || !NODE_SWITCH($nn)) continue;
	
	$artifact_list = artifact_list(false, null, null, true, false, sql_pholder(' AND ctime < ? AND artikul_id IN (?@)', $time_to_lookup, array_keys($items)));
	foreach ($artifact_list as $art) {
		if (!session_lock($art['user_id'])) continue;
		NODE_PUSH(null, $art['user_id']);
		$chat_user_ids[$art['user_id']] = $art['user_id'];
		artifact_save(array('id' => $art['id'], 'artikul_id' => $items[$art['artikul_id']], '_set' => ' flags = flags &~ '.ARTIFACT_FLAG_HIDDEN));
		NODE_POP();
		session_unlock($art['user_id']);
	}
}

$rand_cnt = 0;
$online_user_ids = array();
foreach ($NODE_NUMS as $nn) {
	if ($nn == FRIDGE_NN || !NODE_SWITCH($nn)) continue;
	
	foreach (session_list(null, null, null, ' ORDER BY rand() limit 50', 'uid') as $online_user)
		if (!isset($chat_user_ids[$online_user['uid']])) $online_user_ids[$online_user['uid']] = $online_user['uid'];
}

$random_users = get_hash(user_list(array('id' => $online_user_ids), ' AND level >= 3 ORDER BY RAND() limit 5', false, 'id'), 'id', 'id');

foreach ($random_users as $rand_user) {
	if (!session_lock($rand_user)) continue;
	NODE_PUSH(null, $rand_user);
	$chat_user_ids[$rand_user] = $rand_user;
	if (artifact_create($rand_artikul_ids[rand(0, 4)], rand(1,5), $rand_user)) $rand_cnt++;
	NODE_POP();
	session_unlock($rand_user);
}

error_log('premiya_random_items cnt: '.$rand_cnt);

if ($chat_user_ids) chat_msg_send_system($text, CHAT_CHF_USER, $chat_user_ids, true);