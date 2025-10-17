<?php


chdir("..");
require_once("include/common.inc");

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) return;

require_once("lib/chat.lib");

$email_item = common_get($db_diff, 'mail_send', array('send_time' => 0), sql_pholder(' ORDER BY id DESC'));
if($email_item['send_time']) exit;

$prcode = mb_strtoupper(md5($email_item['email']));

$mail = file_get_contents(SERVER_ROOT.'pub/mail/template_mail.html');
$mail = str_replace('%EMAIL_HASH%', $email_item['email'], $mail);
$mail = str_replace('%PROMO_CODE%', $prcode, $mail);
common_send_mail($email_item['email'], 'Воин', '"Elizium" - новая легенда', $mail, true);

common_save($db_diff, 'mail_send', array('id' => $email_item['id'], 'prcode' => $prcode, 'send_time' => time_current()));