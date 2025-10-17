<?php // %TRANS_SKIP%
exit;
chdir("..");
require_once("include/common.inc");
require_once("lib/artifact.lib");
common_define_settings();

$text = '<b>Поздравляем с победой в Премии Рунета 2011! Сундук с доспехами ждет вас в рюкзаке!<b/> ';

$items = array(
	10399 => 10386,
	10400 => 10388,
	10401 => 10389,
);

$chat_user_ids = array();

global $db;

$created = 0;
$deleted = 0;
foreach ($NODE_NUMS as $nn) {
	if (!NODE_SWITCH($nn)) continue;
	
	$artifact_list = artifact_list(false, null, null, true, false, sql_pholder(' AND artikul_id IN (?@) LIMIT 300', array_keys($items)));
	$user_ids = get_hash($artifact_list, 'user_id', 'user_id');
	$users = $user_ids ? make_hash(user_list(array('id' => $user_ids))) : array();
	foreach ($artifact_list as $art) {
		$user = $users[$art['user_id']];
		if (!$user || $user['flags'] & USER_FLAG_CSERVER_GUEST || !session_lock($art['user_id'])) continue;
		NODE_PUSH(null, $art['user_id']);
		$chat_user_ids[$art['user_id']] = $art['user_id'];
		if (common_delete($db,TABLE_ARTIFACTS,$art['id'])) $deleted++;
		if (artifact_create($items[$art['artikul_id']], 1, $art['user_id'])) $created++;
		NODE_POP();
		session_unlock($art['user_id']);
	}
}

error_log('[premiya_gifts] deleted: '.$deleted.' | created: '.$created);

if ($chat_user_ids) chat_msg_send_system($text, CHAT_CHF_USER, $chat_user_ids, true);