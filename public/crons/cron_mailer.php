<?php
exit;
chdir('..');
require_once('include/common.inc');
require_once('lib/auth.lib');
require_once('lib/campaign.lib');
require_once('tpl/mailer.tpl');

set_time_limit(0);

function cron_mailer_log($str) {
	//logfile('crons/cron_mailer.log',getmypid().' - '.$str);
}

$cur_date = strtotime(date('Y-m-d'));

// Выборка за 7 дней
$add = sql_pholder(' AND server_id = ? AND time_login BETWEEN ? AND ?', SERVER_ID, $cur_date - 60*60*24*8, $cur_date - 60*60*24*7);
$total = 0;
do {
	$auth_list = auth_list($add . ' ORDER BY uid LIMIT '.$total.', 1000', false, 'uid,email,auth_flags');
	if (!$auth_list) break;
	$user_ids = get_hash($auth_list, 'uid', 'uid');
	$users_hash = make_hash(user_list(array('id'=>$user_ids)),'id');
	$total += count($auth_list);
	
	foreach ($auth_list as $user) {
		if ($user['auth_flags'] & AUTH_FLAG_UNSUBSCRIBE || !isset($users_hash[$user['uid']]) || $users_hash[$user['uid']]['flags'] & USER_FLAG_NOT_FINISHED_REG) continue;
		$user_nick = $users_hash[$user['uid']]['nick'];
		$user_level = $users_hash[$user['uid']]['level'];
		$user_gender = $users_hash[$user['uid']]['gender'];
		
		foreach ($CAMPAIGN_PERSONAL_CODES[0]['campaign_id']['7days'] as $group_id => $group) {
			if ($user_level >= $group['level_min'] && $user_level <= $group['level_max']) {
				$personal_code = campaign_gen_personal_code($group['campaign_id'], $user['uid']);
				if (!$personal_code) continue;
				$email_html = mailer_cron_7days(array(
					'uid' => $user['uid'],
					'nick' => $user_nick,
					'gender' => $user_gender,
					'code' => $personal_code,
					'group' => $group_id+1,
					'unsubscribe_code' => md5('uid='.$user['uid'].CAMPAIGN_MAILER_API_SECRET),
				));
				// Отправляем сообщение
				$subject = translate('Вы не заходили в игру 7 дней');
				common_send_mail($user['email'], $user_nick, $subject, $email_html, true);
				break;
			}
		}
	}
} while (1);
cron_mailer_log(sprintf("7 days: %d emails were sent",  $total));

// Выборка за 30 дней
$add = sql_pholder(' AND server_id = ? AND time_login BETWEEN ? AND ?', SERVER_ID, $cur_date - 60*60*24*31, $cur_date - 60*60*24*30);
$total = 0;
do {
	$auth_list = auth_list($add . ' ORDER BY uid LIMIT '.$total.', 1000', false, 'uid,email,auth_flags');
	if (!$auth_list) break;
	$user_ids = get_hash($auth_list, 'uid', 'uid');
	$users_hash = make_hash(user_list(array('id'=>$user_ids)),'id');
	$total += count($auth_list);
	
	foreach ($auth_list as $user) {
		if ($user['auth_flags'] & AUTH_FLAG_UNSUBSCRIBE || !isset($users_hash[$user['uid']]) || $users_hash[$user['uid']]['flags'] & USER_FLAG_NOT_FINISHED_REG) continue;
		$user_nick = $users_hash[$user['uid']]['nick'];
		$user_level = $users_hash[$user['uid']]['level'];
		$user_gender = $users_hash[$user['uid']]['gender'];
		$user_kind = ($users_hash[$user['uid']]['kind'] == KIND_HUMAN) ? 'human' : (($users_hash[$user['uid']]['kind'] == KIND_MAGMAR) ? 'magmar' : '');
		
		if (!count($CAMPAIGN_PERSONAL_CODES[0]['campaign_id']['30days'][$user_kind])) continue;
		foreach ($CAMPAIGN_PERSONAL_CODES[0]['campaign_id']['30days'][$user_kind] as $group_id => $group) {
			if ($user_level >= $group['level_min'] && $user_level <= $group['level_max']) {
				$personal_code = campaign_gen_personal_code($group['campaign_id'], $user['uid']);
				if (!$personal_code) continue;
				$email_html = mailer_cron_30days(array(
					'uid' => $user['uid'],
					'nick' => $user_nick,
					'kind' => $user_kind,
					'gender' => $user_gender,
					'code' => $personal_code,
					'group' => $group_id+1,
					'unsubscribe_code' => md5('uid='.$user['uid'].CAMPAIGN_MAILER_API_SECRET),
				));
				// Отправляем сообщение
				$subject = translate('Вы не заходили в игру 30 дней');
				common_send_mail($user['email'], $user_nick, $subject, $email_html, true);
			}
		}
	}
} while (1);
cron_mailer_log(sprintf("30 days: %d emails were sent",  $total));

// Выборка за 90 дней
$add = sql_pholder(' AND server_id = ? AND time_login BETWEEN ? AND ?', SERVER_ID, $cur_date - 60*60*24*91, $cur_date - 60*60*24*90);
$total = 0;
do {
	$auth_list = auth_list($add . ' ORDER BY uid LIMIT '.$total.', 1000', false, 'uid,email,auth_flags');
	if (!$auth_list) break;
	$user_ids = get_hash($auth_list, 'uid', 'uid');
	$users_hash = make_hash(user_list(array('id'=>$user_ids)),'id');
	$users_count = count($auth_list);
	if (!$users_count) continue;
	$total += $users_count;
	
	// Получаем коды купонов с других проектов
	$jugger_codes = unserialize(@file_get_contents(sprintf(
		translate('http://%s/pub/personal_codes.php?mode=gen_personal_codes&campaign_id=%d&codes_num=%d&sign=%s'), 
		$CAMPAIGN_PERSONAL_CODES[1]['project_url'],
		$CAMPAIGN_PERSONAL_CODES[1]['campaign_id'],
		$users_count, 
		md5('mode=gen_personal_codes&campaign_id='.$CAMPAIGN_PERSONAL_CODES[1]['campaign_id'].'&codes_num='.$users_count.CAMPAIGN_MAILER_API_SECRET))
	));
	$tks_codes = unserialize(@file_get_contents(sprintf(
		translate('http://%s/pub/personal_codes.php?mode=gen_personal_codes&campaign_id=%d&codes_num=%d&sign=%s'), 
		$CAMPAIGN_PERSONAL_CODES[3]['project_url'],
		$CAMPAIGN_PERSONAL_CODES[3]['campaign_id'],
		$users_count, 
		md5('mode=gen_personal_codes&campaign_id='.$CAMPAIGN_PERSONAL_CODES[3]['campaign_id'].'&codes_num='.$users_count.CAMPAIGN_MAILER_API_SECRET))
	));
	unset($jugger_codes['status']);
	unset($tks_codes['status']);
	
	if (!count($jugger_codes) || !count($tks_codes)) continue;
	
	foreach ($auth_list as $user) {
		if ($user['auth_flags'] & AUTH_FLAG_UNSUBSCRIBE || !isset($users_hash[$user['uid']]) || $users_hash[$user['uid']]['flags'] & USER_FLAG_NOT_FINISHED_REG) continue;
		$user_nick = $users_hash[$user['uid']]['nick'];
		$user_gender = $users_hash[$user['uid']]['gender'];
		
		$personal_code = campaign_gen_personal_code($CAMPAIGN_PERSONAL_CODES[0]['campaign_id']['90days']['campaign_id'], $user['uid']);
		$jugger_code = array_pop($jugger_codes);
		$tks_code = array_pop($tks_codes);
		
		if (!$personal_code || !$jugger_code || !$tks_code) continue;
		
		$email_html = mailer_cron_90days(array(
			'dwar' => array(
				'uid' => $user['uid'],
				'nick' => $user_nick,
				'gender' => $user_gender,
				'code' => $personal_code,
				'unsubscribe_code' => md5('uid='.$user['uid'].CAMPAIGN_MAILER_API_SECRET),
			),
			'jugger' => array(
				'url' => sprintf(translate('http://%s/'), $CAMPAIGN_PERSONAL_CODES[1]['project_url']),
				'code' => $jugger_code,
			),
			'tks' => array(
				'url' => sprintf(translate('http://%s/'), $CAMPAIGN_PERSONAL_CODES[3]['project_url']),
				'code' => $tks_code,
			),
		));
		// Отправляем сообщение
		$subject = translate('Вы не заходили в игру 90 дней');
		common_send_mail($user['email'], $user_nick, $subject, $email_html, true);

	}
} while (1);
cron_mailer_log(sprintf("90 days: %d emails were sent",  $total));

// Выборка за 5 дней
$add = sql_pholder(' AND server_id = ? AND time_login < ? AND time_registered > ? AND time_login > ?', SERVER_ID, $cur_date - 60*60*24*2, $cur_date - 60*60*24*7, $cur_date - 60*60*24*3);
$total = 0;
do {
	$auth_list = auth_list($add . ' ORDER BY uid LIMIT '.$total.', 1000', false, 'uid,email,auth_flags');
	if (!$auth_list) break;
	$user_ids = get_hash($auth_list, 'uid', 'uid');
	$users_hash = make_hash(user_list(array('id'=>$user_ids)),'id');
	$total += count($auth_list);
	foreach ($auth_list as $user) {
		if ($user['auth_flags'] & AUTH_FLAG_UNSUBSCRIBE || !isset($users_hash[$user['uid']]) || $users_hash[$user['uid']]['flags'] & USER_FLAG_NOT_FINISHED_REG) continue;
		$user_nick = $users_hash[$user['uid']]['nick'];
		$user_level = $users_hash[$user['uid']]['level'];
		$user_gender = $users_hash[$user['uid']]['gender'];

		$email_html = mailer_cron_5days(array(
			'uid' => $user['uid'],
			'nick' => $user_nick,
			'gender' => $user_gender,
		));
		// Отправляем сообщение
		$subject = translate('Благословение Новичка в '.MAIN_TITLE.'');
		common_send_mail($user['email'], $user_nick, $subject, $email_html, true);
	}
} while (1);
cron_mailer_log(sprintf("5 days: %d emails were sent",  $total));