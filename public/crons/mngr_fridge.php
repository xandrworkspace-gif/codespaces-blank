<? # $Id: mngr_fridge.php,v 1.20 2010-01-15 09:50:10 p.knoblokh Exp $

exit;

chdir("..");
require_once("include/common.inc");
require_once("include/node_transfer.inc");
require_once("include/logserv.inc");
require_once("lib/money_transfer.lib");
require_once('tpl/mailer.tpl');

define('MIN_ABSENCE_TIME', 86400*7);	// минимальное время отсутствия в игре
define('AUTH_LIMIT', 1000);
define('AUTH_DELAY', 50000);

set_time_limit(0);

// Поддерживаем соединение с MySQL
global $rating_stime;
$rating_stime = time_current();
function ticks_handler() {
	global $rating_stime;

	if ((time() - $rating_stime) > 60) {
		global $db, $db_2, $db_3, $db_4, $db_auth, $db_diff, $db_nodes;
		
		$rating_stime = time();

		$sql = 'SELECT 1';
		$db->execSql($sql);
		$db_2->execSql($sql);
		$db_3->execSql($sql);
		$db_4->execSql($sql);
		$db_diff->execSql($sql);
		$db_auth->execSql($sql);
		foreach($db_nodes as $db_node) $db_node->execSql($sql);
	}
}
register_tick_function('ticks_handler');
declare(ticks = 100000);

common_define_settings();

$stime = time();
$frozen_cnt = $noticed_cnt = $deleted_cnt = 0;
$total = 0;

$next_id = 0;
while ($auth_hash = make_hash(auth_list(sql_pholder(' AND uid > ? ORDER BY uid LIMIT '.intval(AUTH_LIMIT), $next_id)), 'uid')) {
	$next_id = max(array_keys($auth_hash));
	$total += count($auth_hash);
	
	$user_ids  = get_hash($auth_hash, 'uid', 'uid');
	$user_hash = make_hash(user_list(array('id' => $user_ids)));
	
	foreach ($auth_hash as $user_id => $auth) {
		if ($auth['server_id'] != SERVER_ID ||
			$auth['time_login'] > time_current() - MIN_ABSENCE_TIME ||
			$auth['auth_flags'] & AUTH_FLAG_DELETED	) {
			$total--;
			continue;
		}
		
		$user = $user_hash[$user_id];
		if (!$user) {
			$total--;
			mngr_fridge_log(sprintf('No user record found for UID=%d', $user_id));
			continue;
		}
		
		$skip = false;
		do {
			if ($user_id <= 100) break;	// резерв пользователей
			if ($auth['auth_flags'] & AUTH_FLAG_INCOME) break;	// есть ввод реала
			if ($user['flags'] & USER_FLAG_ADMIN) break;	// админ
			if ($auth['auth_flags'] & AUTH_FLAG_SOCIAL) break;	// соц-юзер
			$level = $user['level'];
			$finished_reg = !($user['flags'] & USER_FLAG_NOT_FINISHED_REG);
			if ($level >= 4 && $finished_reg) break;
			$days_absent = intval((time_current() - $auth['time_login'])/86400);
			$days_left =  
				(!$finished_reg || $level == 1 ? 60 :
				($level == 2 ? 60 :
				($level == 3 ? 120 :
				0))) - $days_absent;
			$status = ($days_left <= 0 ? 2 : ($days_left == 3 ? 1 : 0));
			// Проверяем наличие рефералов только если собираемся удалять пользователя
			if (($status == 2) && (user_count(array('referrer_id' => $user_id)) > 0)) break;
			if ($status == 1) {	// уведомление
				mngr_fridge_user_deletion_notice($auth,$days_left);
				$noticed_cnt++;
			} elseif ($status == 2) {	// удаление
				$total--;
				$skip = true;
				user_delete($user_id, false, true);
				money_transfer_delete(array('user_id' => $user_id));
				$container = new lsContainer($user_id);
				if ($container->open()) {
					$container->truncate();
					$container->close();
				}
				$deleted_cnt++;
			}
		} while (0);
		if ($skip) continue;
		
		// проверяем возможность заморозки
		if ($auth['auth_flags'] & AUTH_FLAG_FROZEN) continue;
		if (!session_lock($user_id)) continue;
		do {
			$nn = NODE_GET($user_id);
			NODE_SWITCH($nn);
			if ($user['fight_id'] || session_get($user_id)) break;	// нельзя замораживать пользователя в данный момент!
			if ($nn != FRIDGE_NN) {
				if (!NODE_TRANSFER_USER($nn,FRIDGE_NN,$user_id)) {
					mngr_fridge_log("Can't freeze user (user_id: $user_id, nn: $nn)");
					break;
				}
				NODE_SAVE($user_id,FRIDGE_NN);
			}
			auth_save(array(
				'uid' => $user_id,
				'auth_flags' => $auth['auth_flags'] | AUTH_FLAG_FROZEN,
			));
			user_save(array(
				'id' => $user_id,
				'flags2' => $user['flags2'] | USER_FLAG2_FROZEN,
			));
			$frozen_cnt++;
		} while(0);
		session_unlock($user_id);
	}
	if ((time() - $stime) >= (3600 * 3)) break;
}

$rtime = time() - $stime;

mngr_fridge_log("User cleanup $rtime sec (processed: $total, frozen: $frozen_cnt, notified: $noticed_cnt, deleted: $deleted_cnt)");


// ---------------------------------------------------------------------------------------------------------------------------------

function mngr_fridge_log($str) {
	logfile('crons/mngr_fridge.log',getmypid().' - '.$str);
}

function mngr_fridge_user_deletion_notice(&$auth, $days_left) {
	global $PARTNERS_CFG;
	if($auth['auth_flag'] & AUTH_FLAG_NOSPAM) return false;

	$server_url = SERVER_URL;
	
	if ($PARTNERS_CFG) {
		foreach($PARTNERS_CFG as $partner) {
			if (!$partner['auth_flag']) continue;
			if (intval($auth['auth_flags']) & intval($partner['auth_flag'])) {
				if ($partner['remind_link']) $remind_link = $partner['remind_link'];
				if ($partner['server_url']) $server_url = $partner['server_url'];
				break;
			}
		}
	}
	$user = user_get($auth['uid']);

	$email_html = mailer_user_delete(array(
		'uid' => $user['uid'],
		'nick' => $auth['nick'],
		'gender' => $user['gender'],
		'email' => $auth['email'],
		'current_site_domain' => $server_url,
		'time_delete' => date('d.m.Y',$days_left*86400+time_current()),
	));

	common_send_mail($auth['email'],$auth['nick'],translate('Удаление персонажа'),$email_html, true);

}

?>