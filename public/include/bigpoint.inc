<?php
$includePath = dirname(__FILE__);
require_once($includePath."/xmlrpc.inc");
require_once($includePath."/node_transfer.inc");

class Bigpoint extends IXR_Server {

	protected $_errors = array(
		'1001' => 'Failed to register',
		'1002' => 'User not registered',
		'1003' => 'Failed to generate login token',
		'1004' => 'Unable to freeze user',
		'1005' => 'Payment item type is not supported'
	);

	protected
		$_url,
		$_partner_id,
		$_project_id,
		$_secret,
		$_client,
		$_result;

	public function __construct($partner_id, $project_id, $secret, $mode = 'server', $url = '')
	{
		$this->_url = $url;
		$this->_partner_id = $partner_id;
		$this->_project_id = $project_id;
		$this->_secret = $secret;

		if ($mode == 'server') {
			$this->_result = array('result' => 'OK');
			$this->IXR_Server(array(
				'game.registerAndLogin' => 'this:game_register_and_login',
				'game.login' => 'this:game_login',
				'game.deleteUser' => 'this:game_delete',
				'game.getUserStats' => 'this:game_get_user_stats',

				'bookItem' => 'this:payment_book_item',
				'blockedNotify' => 'this:payment_blocked_notify',
				'getUserPaymentLanguage' => 'this:payment_get_user_payment_language'
			));
		}
	}

	public function request($method, $params = array()) {
		$params = array_merge($params, array(
			'partnerID' => intval($this->_partner_id),
			'projectID' => intval($this->_project_id),
			'authTimestamp' => floatval(time_current())
		));

		$request = new IXR_Request($method, array($params));

		$url = common_build_url($this->_url, array('authHash' => $this->getAuthHash($request->getXml()), 'devMode' => 0));
		$this->_client = null;
		$this->_client = new IXR_Client($url);
		return $this->_client->sendQueryRequest($request);
	}

	public function getAuthHash($xmlData) {
		return md5($xmlData.$this->_secret);
	}

	public function checkAuthHash($xmlData, $auth_hash) {
		return strtolower(trim($auth_hash)) == $this->getAuthHash($xmlData);
	}

	function checkAuth($data) {
		if (!isset($_GET['authHash'])) die();
		$auth_hash = $_GET['authHash'];

		return $this->checkAuthHash($data, $auth_hash);
	}


	// Server methods
	public function game_register_and_login($data) {
		$user_id = $this->game_register($data);
		if ($user_id) {
			$data['userID'] = $user_id;
			$this->_result['userID'] = $user_id;
			$this->game_login_do($data);
		} else {
			return new IXR_Error(1001, $this->_errors[1001]);
		}

		return $this->_result;
	}

	public function game_register($data) {
		$user = auth_get(array(
			'partner_user_id' => $data['bpUserID'],
		), sql_pholder(' AND auth_flags & ?#AUTH_FLAG_BIGPOINT'));

		if ($user) return $user['uid'];

		$user_id = auth_save(array(
			'email' => $data['bpUserID'].'@bigpoint',
			'passwd' => '',
			'time_registered' => time_current(),
			'nick' => $data['bpUserID'].'@bigpoint',
			'fb_id' => 0,
			'partner_user_id' => $data['bpUserID'],
			'auth_flags' => AUTH_FLAG_BIGPOINT,
			'server_id' => SERVER_ID,
			'current_server_id' => SERVER_ID
		));

		return $user_id;
	}

	public function game_login($data) {
		$user = $this->get_bp_user($data);

		if (!$user){
			// todo: if we don't have this user delete from portal ?
			return new IXR_Error(1002, $this->_errors[1002]);
		}

		$this->game_login_do($data);

		return $this->_result;
	}

	public function game_login_do($data) {
		$restore_password = array(
			'login' => $data['bpUserID'],
			'hash' => md5('login_hash_.'.$data['bpUserID'].microtime(true).uniqid()),
			'dtime' => time_current() + 5*60,
			'usage' => 'login'
		);

		auth_restore_password_delete(array('login' => $data['bpUserID'], 'usage' => 'login'));

		if (!auth_restore_password_save($restore_password)) return new IXR_Error(1003, $this->_errors[1003]);

		$this->_result['redirectURL'] = '[RAW]'.SERVER_URL."login.php?bpid=".intval($data['bpUserID']).'&hash='.$restore_password['hash'];
	}

	public function game_delete($data) {
		$auth = $this->get_bp_user($data);

		if (!$auth){
			// todo: if we don't have this user delete from portal ?
			return new IXR_Error(1002, $this->_errors[1002]);
		}

		$user = user_get($auth['uid']);
		if (!$user){
			// Юзер не прошел дорегистрацию. Нет юзера - нет проблем =)
			auth_delete($auth['uid']);
			return $this->_result;
		}

		$user_id = $user['id'];
		do {
			if ($auth['auth_flags'] & AUTH_FLAG_FROZEN || !session_lock($user_id)) return new IXR_Error(1004, $this->_errors[1004]);

			$nn = NODE_GET($auth['uid']);
			NODE_SWITCH($nn);
			if ($user['fight_id']) break;	// нельзя замораживать пользователя в данный момент!

			//logout user
			if (!user_is_invisible($user)) {
				user_leaving($user);
			}

			// Удаление заявки в поле битв
			$bg_user = bg_user_get(array('user_id' => $user['id']));
			if ($bg_user && !$bg_user['dtime'] && ($bg_user['status'] != BG_USER_STATUS_LOCKED)) {
				bg_user_delete($bg_user['id']);
			}

			// Удаление заявки на бойне
			$slaughter_user = slaughter_user_get(array('user_id' => $user['id']));
			if ($slaughter_user && !$slaughter_user['dtime'] && ($slaughter_user['status'] != SLAUGHTER_USER_STATUS_LOCKED)) {
				slaughter_user_delete($slaughter_user['id']);
			}

			// Удаление заявки в замок
			$castle_user = castle_user_get(array('user_id' => $user['id']));
			if ($castle_user) castle_user_delete($castle_user['id']);

			auth_save(array(
				'uid' => $user['id'],
				'time_logout' => time_current(),
			));

			session_delete($user['id']);

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

			session_unlock($user_id);
			return $this->_result;
		} while(0);
		session_unlock($user_id);

		return new IXR_Error(1004, $this->_errors[1004]);
	}

	public function game_get_user_stats($data) {
		$auth = $this->get_bp_user($data);

		if (!$auth){
			// todo: if we don't have this user delete from portal ?
			return new IXR_Error(1002, $this->_errors[1002]);
		}

		$this->_result['virtualCurrency'] = 0;
		$this->_result['realCurrency'] = 0;
		$user = user_get($auth['uid']);
		if ($user) {
			$this->_result['virtualCurrency'] = intval($user['money']);
			$this->_result['realCurrency'] = intval($user['money_gold']);
			$this->_result['userRank'] = $user['level'];
		}

		return $this->_result;
	}

	public function get_bp_user($data) {
		return auth_get(array(
			'uid' => $data['userID'],
			'partner_user_id' => $data['bpUserID'],
		), sql_pholder(' AND auth_flags & ?#AUTH_FLAG_BIGPOINT'));
	}



	// Client methods
	/**
	 * Send user email via DevLounge portal. Mail ID's are defined in the portals backend.
	 *
	 * @param mixed $user_id
	 * @param integer $mail_id
	 * @param mixed $params Array of parameters to replace email placeholders.
	 */
	public function portal_send_user_email($user_id, $mail_id, $params = array()) {
		$res = $this->request('portal.sendUserMail', array(
			'sysMailID' => $mail_id,
			'userID' => $user_id,
			'userLang' => _TRANS_LANG,
			'params' => $params
		));

		if (!$res) return false;

		return isset($this->_client->message->params[0]['result']) && $this->_client->message->params[0]['result'] == 'OK';
	}

	/**
	 * Get tags (tracking pixels) to embed to a page.
	 *
	 * @param <type> $user_id
	 * @param <type> $page_id
	 */
	public function portal_get_page_tags($user, $page_id) {
		if (!is_array($user)) $user = user_get($user);
		$res = $this->request('portal.getPageTags', array(
			'pageID' => $page_id,
			'userID' => $user['id'],
			'bpUserID' => $user['partner_user_id'],
			'userLang' => _TRANS_LANG
		));

		if (!$res) return false;

		return isset($this->_client->message->params[0]['result']) && $this->_client->message->params[0]['result'] == 'OK';
	}

	/**
	 * Request a portal do delete user registrations.
	 *
	 * @param <type> $user_id
	 */
	public function portal_delete ($user) {
		if (!is_array($user)) $user = user_get($user);
		$res = $this->request('portal.deleteUser', array(
			'userID' => $user['id'],
			'bpUserID' => $user['partner_user_id'],
		));

		if (!$res) return false;

		return isset($this->_client->message->params[0]['result']) && $this->_client->message->params[0]['result'] == 'OK';
	}



	// Payment methods
	public function payment_book_item($data) {
		$user = user_get($data['userID']);
		if (!$user) {
			return new IXR_Error(1002, $this->_errors[1002]);
		}

		$type = $data['type'];
		$amount = $data['amount'];
		$unique_id = $data['uniqueID'];
		$money_type = false;

		$comment = sprintf(translate('Оплата через Bigpoint. ID=%s, Sum=%s, timestamp=%s'), $unique_id, $amount, $data['authTimestamp']);

		switch($type) {
			case 'realCurrency':
				require_once("lib/money_transfer.lib");
				require_once("lib/artifact.lib");
				
				$money_type = MONEY_TYPE_GOLD;
				
				$status = user_make_payment(MONEY_TYPE_GOLD, $user["id"], $amount, $comment, false);
				if (!$status) return new IXR_Error(1002, $this->_errors[1002]);

				auth_save(array(
					'uid' => $user["id"],
					'_set' => 'auth_flags = auth_flags | '.(int)AUTH_FLAG_INCOME,
				));
				
				// Сохраняем поступившую сумму в общую сумму вложений пользователя
				$user_credit = user_credit_get(array('user_id' => $user['id']));
				if (!$user_credit) {
					$user_credit = array(
						'_mode' => CSMODE_INSERT,
						'user_id' => $user['id'],
						'payments_total' => 0,
					);
				}
				$user_credit['payments_total'] += floatval($amount);
				user_credit_save($user_credit);

				money_transfer_save(array(
					'user_id' => $user["id"],
					'money_type' => MONEY_TYPE_GOLD,
					'amount' => $amount,
					'stime' => time_current(),
					'comment' => $comment,
					'curr' => 'BIGPOINT',
				));

				// лог-сервис -----------------------
				logserv_log_operation(array(
					'money_type' => MONEY_TYPE_GOLD,
					'amount' => $amount,
					'comment' => translate('Оплата через Bigpoint (брюли)'),
				),$user);

				chat_msg_send_system(sprintf(translate('На Ваш счет успешно зачислено %s бриллиантов.'),html_money_str(MONEY_TYPE_GOLD,$amount)),CHAT_CHF_USER,$user["id"]);
				break;
			case 'virtualCurrency':
				require_once("lib/money_transfer.lib");
				
				$money_type = MONEY_TYPE_SILVER;
				
				$status = user_make_payment(MONEY_TYPE_SILVER, $user["id"], $amount, $comment, false);
				if (!$status) return new IXR_Error(1002, $this->_errors[1002]);

				$success = true;
				auth_save(array(
					'uid' => $user_id,
					'_set' => 'auth_flags = auth_flags | '.(int)AUTH_FLAG_INCOME,
				));

				money_transfer_save(array(
					'user_id' => $user["id"],
					'money_type' => MONEY_TYPE_SILVER,
					'amount' => $amount,
					'stime' => time_current(),
					'comment' => $comment,
					'curr' => 'BIGPOINT',
				));

				// лог-сервис -----------------------
				logserv_log_operation(array(
					'money_type' => MONEY_TYPE_SILVER,
					'amount' => $amount,
					'comment' => sprintf(translate('Оплата через Bigpoint (рубины)'),$phone),
				),$user);

				chat_msg_send_system(sprintf(translate('На Ваш счет успешно зачислено %s рубинов.'),html_money_str(MONEY_TYPE_SILVER,$amount)),CHAT_CHF_USER,$user["id"]);
				
				break;
			default:
				return new IXR_Error(1005, $this->_errors[1005]);
		}
		
		global $lucky_star_multipliers, $db_2;
		// Акция ЗВЕЗДА УДАЧИ
		if ($money_type && defined('LUCKY_STAR_ACTION') && LUCKY_STAR_ACTION
				&& LUCKY_STAR_ARTIKUL_ID && $lucky_star_multipliers
				&& is_array($lucky_star_multipliers)
			) {
			$lucky_star_user = common_get($db_2, TABLE_LUCKY_STAR_USERS, array('user_id' => $user['id']));
			$lucky_star_artikul_ids = array_keys($lucky_star_multipliers);
			$lucky_star_baff = false;
			if ($lucky_star_artikul_ids && NODE_SWITCH(null, $user['id'])) {
				$query_add = sql_pholder(" AND (time_expire=0 OR time_expire>?) AND artikul_id IN (?@)", time_current(), $lucky_star_artikul_ids);
				$lucky_star_baff = artifact_get(array('user_id' => $user['id'], 'slot_id' => 'TEMP_EFFECT'), $query_add);
			}

			$multiplier = $lucky_star_baff ? $lucky_star_multipliers[$lucky_star_baff['artikul_id']] : false;
			$max_payment = max(0, LUCKY_STAR_MAX_PAYMENT - intval($lucky_star_user['gold_total_payment']));
			if ($multiplier && ($multiplier > 0) && $lucky_star_user && $max_payment) {
				// Делаем начисление бонусных денег по акции
				// При оплате меньше или равной LUCKY_STAR_MAX_PAYMENT, бонус на всю сумму начисляется
				$actual_payment = min($max_payment, $amount);
				$bonus_sum = round($actual_payment * $multiplier, 2);
				$comment = sprintf(translate('Акция "Звезда Удачи". Бонусное начисление к оплате через Bigpoint. Sum=%s, date=%s'), $bonus_sum, date('Y-m-d H:i:s'));
				$operations = ($money_type == MONEY_TYPE_GAME) ? array(MONEY_STAT_OPERATION_RECEIVE,MONEY_STAT_OPERATION_PURE_RECEIVE) : false;
				$status = user_make_payment($money_type, $user['id'], $bonus_sum, $comment, false, $operations);
				$status = $status && common_save($db_2, TABLE_LUCKY_STAR_USERS, array(
					'_set' => sql_pholder(' gold_total_payment = gold_total_payment + ?', $actual_payment),
					'_add' => sql_pholder(' AND gold_total_payment + ? <= ? AND user_id = ?', $actual_payment, LUCKY_STAR_MAX_PAYMENT, $user['id']),
					'_cnt' => 1,
				));

				if ($status) {
					artifact_artikul_get_title($lucky_star_baff);
					chat_msg_send_system(sprintf(translate('Вы получили бонус от эффекта <b class="redd">%s</b> - %s %s.'), $lucky_star_baff['title'], html_money_str($money_type, $bonus_sum), $money_type == MONEY_TYPE_GOLD ? translate('бриллиантов') : translate('рубинов')), CHAT_CHF_USER, $user['id']);

					if ($lucky_star_user['gold_total_payment'] + $actual_payment >= LUCKY_STAR_MAX_PAYMENT) {
						// отбираем баф после оплаты с бонусным начислением
						// 9664 Бонус акции - платеж сделан
						require_once("lib/bonus.lib");
						if (NODE_SWITCH(null, $user['id'])) bonus_apply($user, LUCKY_START_BONUS_RESET);
					}

					// лог-сервис -----------------------
					logserv_log_operation(array(
						'money_type' => $money_type,
						'amount' => $bonus_sum,
						'comment' => $comment,
					),$user);

					if ($auth['server_id'] != $auth['current_server_id']) {
						$money_arr = array(
							$money_type => $bonus_sum,
						);
						crossserver_change_money($auth['current_server_id'], $user['id'], $money_arr);
					}
				}
			}
		}

		return $this->_result;
	}

	public function payment_blocked_notify($data) {
		$user = user_get($data['userID']);
		if (!$user) {
			return new IXR_Error(1002, $this->_errors[1002]);
		}

		$params = array('id' => $user['id'], 'blocked' => $data['blocked'] ? '1' : '0');
		user_save($params);

		return $this->_result;
	}

	public function payment_get_user_payment_language($data) {
		$user = user_get($data['userID']);
		if (!$user) {
			return new IXR_Error(1002, $this->_errors[1002]);
		}

		$this->_result['userLang'] = strtoupper(_TRANS_LANG);

		return $this->_result;
	}

	public function generate_payment_url($user, $billing_url, $sandbox = 0, $iframe = 1)
	{
		if (!$user) return false;

		if (!is_array($user)) $user = user_get($user);

		$reqOriginal = array(
			'projectID' => $this->_project_id,
			'lang'      => _TRANS_LANG,
			'username'  => $user['nick'],
			'userID'    => $user['id'],
			'time'      => time_current(),
			'sandbox'   => $sandbox,
			'iframe'    => $iframe ? 1 : 0
		);

		$req = base64_encode(common_json_encode($reqOriginal) );
		$hash = md5($req.$this->_secret);
		$url = common_build_url($billing_url, array('authreq' => $req, 'hash' => urlencode($hash)), false);

		return $url;
	}
	
}
