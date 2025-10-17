<? # $Id: cron_loan.php,v 1.11 2010-01-15 09:50:10 p.knoblokh Exp $

ini_set("memory_limit", "256M");

chdir("..");
require_once("include/common.inc");

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) return;

require_once("lib/area_bank.lib");
require_once("lib/area_post.lib");

define('DAY_SECONDS', 60*60*24); 
define('CREDIT_TIME', 60*60*24*10); // 10 дней на возврат
define('FIRST_WARNING_TIME', 60*60*24*9);

$deadline = time_current() + CREDIT_TIME;
$loan_list = user_credit_list(false, sql_pholder(' AND loan_size > 0 AND warn_num < 2 AND return_date > 0 AND return_date <= ? ', $deadline-8*DAY_SECONDS));
if (!is_array($loan_list)) {
	$loan_list = array();
}

$msg_tpl = translate('Здравствуйте!<br>');
$msg_tpl .= translate(' Напоминаю, что Вы должны банкиру <b class="redd">%s</b>, которые необходимо отдать до <b class="redd">%s</b>.');
$msg_tpl .= translate(' Если Вы не отдадите долг, то на Вас наложат проклятие нищеты до возврата долга!');

foreach ($loan_list as $item) {
	$warn_num = 0;
	if (($item['warn_num'] < 2) && ($item['return_date'] < $deadline-9*DAY_SECONDS)) { // второе уведомление
		$warn_num = 2;
	} elseif (($item['warn_num'] < 1) && ($item['return_date'] < $deadline-8*DAY_SECONDS)) { // первое уведомление
		$warn_num = 1;
	}
	if ($warn_num) {
		$amount_taked = round($item['loan_size'],2);
		$msg = sprintf($msg_tpl, html_money_str(MONEY_TYPE_GOLD, $amount_taked), strftime('%d.%m.%Y&nbsp;%H:%M:%S', $item['return_date']));
		area_bank_message_send($item['user_id'], array('text' => $msg, 'subject' => translate('Банк: напоминание')));
		user_credit_save(array(
			'user_id' => $item['user_id'],
			'warn_num' => $warn_num,
		));
	}
}


if(defined('MAIL_ACCEPT_TABLE_SHOW') && MAIL_ACCEPT_TABLE_SHOW){
    require_once("lib/session.lib");
    require_once("lib/auth.lib");
    require_once("lib/chat.lib");

    NODE_SWITCH(1);

    global $db;
    $session_list = common_list($db,TABLE_SESSIONS,false, sql_pholder(" AND stime>=?",(time_current()-300)),'uid');

    $auth_list = array();
    if($session_list) $auth_list = auth_list(sql_pholder(' AND !(auth_flags & ?#AUTH_FLAG_ACCEPT) AND accept_time < ?', (time_current() - 600)), array('uid' => array_keys(make_hash($session_list,'uid'))));
    foreach ($auth_list as $auth){
        chat_msg_send_special(CODE_CALL_JSFUNC, CHAT_CHF_USER, $auth['uid'], array('func' => "showMsg2('/auth_accept.php?mode=1');"));
    }
}

?>