<?php

exit;
chdir("..");
require_once("include/common.inc");
require_once("lib/auth.lib");
require_once 'tpl/mailer.tpl';

$cur_date = strtotime(date('Y-m-d'));

$time_scope = $cur_date - 86400;
$not_finished_auths = auth_list(sql_pholder(' AND auth_flags & ?#AUTH_FLAG_NOTIFICATION AND !(auth_flags & ?#AUTH_FLAG_UNSUBSCRIBE) AND time_registered < ?', $time_scope), false, 'uid, email');
if (!count($not_finished_auths)) die;

$sent = 0;
$not_finished_auths_hash = get_hash($not_finished_auths, 'uid', 'email');
foreach ($not_finished_auths_hash as $uid => $email) {
	if (!common_is_email_valid($email)) continue;
	// Отправляем сообщение
	$subj = translate('"Незавершенная регистрация в проекте "'.MAIN_TITLE.'."');
	if (common_send_mail($email, '', $subj, mailer_not_finished_register(), true)) $sent++;
	auth_save(array(
		'_set' => sql_pholder('auth_flags = auth_flags & ~?#AUTH_FLAG_NOTIFICATION'),
		'_add' => sql_pholder(' AND uid = ?', $uid),
	));
}

//logfile('crons/cron_mailer.log',getmypid().' - '.sprintf("Not finished reg: %d emails were sent",  $sent));