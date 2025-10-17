<? # $Id: cron_mail.php,v 1.16 2010-01-15 09:50:10 p.knoblokh Exp $

chdir("..");
require_once("include/common.inc");

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) return;

require_once("lib/artifact.lib");
require_once("lib/area_post.lib");
require_once("lib/chat.lib");

$area_post_message_list = area_post_message_list(false, sql_pholder(' AND rtime<? LIMIT 2000', time_current() ));

foreach ($area_post_message_list as $message) {
	switch ($message['type_id']) {
	default:
	case MSG_TYPE_NORMAL:
	case MSG_TYPE_SYS_NORMAL:
	case MSG_TYPE_SYS_LONG:
	case MSG_TYPE_SYS_COD: // удалить просроченное письмо вместе со шмотками
		if ($message['artifact_id']) {
			$artifact = artifact_get_safe($message['artifact_id']);
			if ($artifact) {
				artifact_delete_safe($message['artifact_id']);
				// лог-сервис -----------------------
				logserv_log_operation(array(
					'artifact' => $artifact,
					'cnt' => -max($artifact['cnt'],1),
					'comment' => sprintf(translate('Истек срок хранения письма от %s'), strftime('%d.%m.%y %H:%M:%S', $message['stime'])),
				),$artifact['owner_id']);
				// ----------------------------------
			}
		}
		// лог-сервис -----------------------
		if (floatval($message['money'])) {
			$user_id = $message['from_id'] ? $message['from_id'] : $message['to_id'];
			if ($user_id) {
				logserv_log_operation(array(
					'money_type' => $message['money_type'],
					'amount' => -$message['money'],
					'comment' => sprintf(translate('Истек срок хранения письма от %s'), strftime('%d.%m.%y %H:%M:%S', $message['stime'])),
				),$user_id);
			}
		}
		// ----------------------------------
		area_post_message_delete($message['id']);
		break;
	case MSG_TYPE_COD: // отправить письмо с ценностями назад
		$param = array_merge($message, array(
			'from_id' => $message['to_id'],
			'to_id' => $message['from_id'],
			'subject' => concat(translate('Возврат: '),$message['subject']),
			'text' => $message['text']."\n\n".translate('*** Возврат вещи (не была куплена за отведенное время) ***'),
			'unread' => 1,
			'type_id' => MSG_TYPE_NORMAL,
			'money_type' => 0,
			'money' => 0,
			'stime' => time_current(),
			'rtime' => time_current() + area_post_message_ttl(MSG_TYPE_COD, true, true),
		));
		area_post_message_save($param);
		area_post_user_send_chat_notify($param['to_id']);
		break;
	}
}

//Очистка просроченных плащей и фонов
require_once("lib/user_cloak.lib");
require_once("lib/user_fon.lib");
user_fon_delete(false, sql_pholder(' AND dtime > 0 AND dtime < ?', time_current()));
user_cloak_delete(false, sql_pholder(' AND dtime > 0 AND dtime < ?', time_current()));
?>