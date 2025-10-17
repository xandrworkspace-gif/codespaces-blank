<? // $Id: souz.inc 6022 2010-06-22 08:33:49Z v.babajanyan $
// =========================================================================
// Coder			: AKEB, IT-Territory
// Work started		: 16.06.2010 12:11:13
// User				: v.babajanyan
// Description		:
// =========================================================================


define('EXEC_TIMEOUT', 5); // в секундах


define('DEFAULT_EP', 0);
define('MM_SOUZ_EP', 1);
define('OK_SOUZ_EP', 2);
define('VK_SOUZ_EP', 3);
define('FB_SOUZ_EP', 4);

function souz_hash($param) {
	$str = '';
	ksort($param);
	foreach($param as $k=>$v) {
		$str .= $k.'='.$v;
	}
	$str .= SOUZ_SECRET;
	return md5($str);
}

function souz_ip() {
	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$remote_addrs = explode(',',$_SERVER['HTTP_X_FORWARDED_FOR']);
		$remote_addr = trim(end($remote_addrs));
	} else {
		$remote_addr = trim($_SERVER['REMOTE_ADDR']);
	}
	if (!$remote_addr) $remote_addr = 'undefined';
	return $remote_addr;
}

function souz_request($urls) {
	foreach ($urls as $host => $url) {
		$opts = array(
			'http'=>array(
				'method'=>"GET",
				'header' => "Host: ".$host."\r\n".
				"Connection: close\r\n",
				'timeout' => EXEC_TIMEOUT,
				'content' => '',
			)
		);

		$context = stream_context_create($opts);
		$stream = fopen($url,'r',false,$context);
		// сеть не доступна
		if (!$stream) {
			error_log('souz exec failed: looks like network failure:' . ($url));
			continue;
		}
		stream_set_timeout($stream, EXEC_TIMEOUT);

		$data = stream_get_contents($stream);
		$header = stream_get_meta_data($stream);
		fclose($stream);

		if ($header['timed_out']) {
			error_log('souz exec failed: cannot connect to "' . $url . '" connection timout');
		} else {
			// запрос успешен
			//souz_log('souz exec success "' . $url . '"', $data);
			return array('data'=>$data, 'header'=>$header);
		}
	}
	return false;
}


function souz_exec($inAttrs) {
	if (defined('GLADIATORS_SERVER') && GLADIATORS_SERVER) {
		// На гладиаторском сервере союз не нужен
		return array('status' => 'ok');
	}
	global $SOUZ_CFG;
	$ret = array("status" => "fail");
	if (!defined('SOUZ_PROJECT_ID') || !defined('SOUZ_SECRET')) {
		error_log("Check if SOUZ_PROJECT_ID and SOUZ_SECRET are defined somewhere");
		return false;
	}
	if (!isset($inAttrs["id"]) || !mb_strlen((string)($inAttrs["id"]))) {
		error_log("Missing or empty action id");
		return false;
	}
	if (!isset($SOUZ_CFG)) {
		if (!defined('SOUZ_URL')) {
			error_log("Check SOUZ_CFG or SOUZ_URL are defined somewhere");
		} else {
			$SOUZ_CFG = array( SOUZ_READ => array(1 => SOUZ_URL), SOUZ_WRITE => array(1 => SOUZ_URL));
		}
	}

	if (isset($inAttrs['__mode'])) {
		$mode = $inAttrs['__mode'];
		unset($inAttrs['__mode']);
	} else {
		$mode = SOUZ_WRITE;
	}

	if (!isset($SOUZ_CFG[$mode])) return false;
	$souz_hosts = $SOUZ_CFG[$mode];

	$method =  $inAttrs['id'];
	unset($inAttrs['id']);
	$inAttrs['sig'] = souz_hash($inAttrs);

	$urls = array();
	foreach ($souz_hosts as $souz_host) {
		$url = 'http://'.$souz_host.'/' . $method;
		$url = common_build_url($url,$inAttrs);
		$urls[$souz_host] = $url;
	}
	if (empty($urls)) return false;

	$response = souz_request($urls);
	if (!$response) return false;

	$header = (!empty($response['header'])) ? $response['header'] : null;
	$data =  $response['data'];

	$cookie = array();
	if (isset($header) &&  is_array($header)) {
		foreach ($header['wrapper_data'] as $v) {
			if (strpos($v,'Set-Cookie') !== false) {
				$cookie[] = $v;
			}
		}
	}
	if (function_exists("xml_parser_create") && function_exists("xml_parse_into_struct")) {
		$parser = xml_parser_create();
		$parsedArray = array();
		if (!xml_parse_into_struct($parser, $data, $parsedArray, $tagsIndices)) {
			error_log("Error parsing xml buffer ".$data);
		} else {
			// соберем сюда все входные точки если найдем
			$entry_points = array();
			foreach ($parsedArray as $parsedTag) {
				if (($parsedTag["tag"] == "ACTION") && isset($parsedTag["attributes"]) && sizeof($parsedTag["attributes"])) {
					foreach ($parsedTag["attributes"] as $attrName => $attrValue) {
						$ret[strtolower($attrName)] = $attrValue;
					}
				}
				if (($parsedTag["tag"] == "EP") && isset($parsedTag["attributes"]) && sizeof($parsedTag["attributes"])) {
					// точки входа обрабатываем
					$ep_id = array();
					foreach ($parsedTag["attributes"] as $attrName => $attrValue) {
						$ep_id[strtolower($attrName)] = $attrValue;
					}
				}
				if (isset($ep_id['ep_id'])) {
					$entry_points[$ep_id['ep_id']] = $ep_id;
				}
			}
			if ($cookie) {
				$ret['cookie'] = $cookie;
			}
			if (!empty($entry_points)) {
				$ret['ep'] = $entry_points;
			}

			return $ret;
		}
		xml_parser_free($parser);
	}
	return false;
}

function souz_sync_user($id) {
	$souz_info = souz_user_info(false,$id);
	if (!$souz_info || !$souz_info['login']) return false;
	$auth = auth_get($id);
	if (!$auth) return false;
	$status = true;
	if ($auth['email'] != $souz_info['login']) {
		$status = auth_save(array(
			'uid' => $auth['uid'],
			'email' => $souz_info['login'],
		));
	}
	return $status;
}

function souz_register($login, $password,$pers_id=null) {
	$request = array(
		'id' => 'register',
		'login' => $login,
		'project_id' => SOUZ_PROJECT_ID,
		'ip' => souz_ip(),
		'__mode' => SOUZ_WRITE,
	);
	if ($password) {
		if (defined('SOUZ_EURO') && SOUZ_EURO) {
			$request['password'] = md5($password);
			$request['phash'] = 1;
		} else {
			$request['password'] = $password;
		}
		$mailru = 0;
	} else {
		$mailru = 1;
	}

	if ($pers_id) $request['pers_id'] = $pers_id;

	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	setcookie('mailru_user',intval($mailru),time()+(30*24*60*60));
	return $ret["status"] == "ok";
}

function souz_auth($login,$password,$ip=false,$return_status=false,$souz_only=false) {
	if (is_int($login)) {
		return _souz_auth(false,$login,false, $password,$ip,$return_status,$souz_only);
	} else {
		$res = _souz_auth($login,false,false, $password,$ip,$return_status,$souz_only);
		// Костыль для пользователей с хешами паролями от cp1251 строки
		if (_TRANS_LANG == 'ru' && strtoupper(charset_code()) == 'UTF-8' && (!$res || $res['status'] != 'ok')) {
			$res = _souz_auth($login,false,false, iconv('UTF-8','cp1251',$password),$ip,$return_status,$souz_only);
		}
		return $res;
	}
}

function souz_check_auth($cookie,$ip=false,$return_status=false) {
	return _souz_auth(false, false, $cookie, false, $ip, $return_status);
}

function _souz_auth($login,$pers_id,$cookie, $password,$ip=false,$return_status=false,$souz_only=false) {
	if (!$ip) $ip = souz_ip();
	$request = array(
		'id' => 'auth',
		'project_id' => SOUZ_PROJECT_ID,
		'ip'=>$ip,
		'__mode' => SOUZ_READ,
	);
	if ($cookie) $request['mpop_cookie'] = $cookie;
	else {
		if (defined('SOUZ_EURO') && SOUZ_EURO) {
			$request['password'] = md5($password);
			$request['phash'] = 1;
		} else {
			$request['password'] = $password;
		}
		if (defined('MAILRU') && MAILRU && !$souz_only) $request['mail_domain']=1;
		if ($login) $request['login'] = $login;
		elseif ($pers_id) $request['pers_id'] = $pers_id;
	}
	$ret = souz_exec($request);
	//vardump($ret);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	if ($ret["status"] != "ok") return $return_status ? false : $ret;
	//Поставить куку
	setcookie('mailru_user',intval($ret['mailru']),time()+(30*24*60*60));

	if (!$cookie && $ret['cookie']) {
		foreach($ret['cookie'] as $v) {
			header($v,false);
		}
	}

	if ($return_status) return true;

	return $ret;
}

function souz_auth_get($login, $password, $pers_id = null, $nick = null, $ip=false) {
	if (!$ip) $ip = souz_ip();
	$request = array(
		'id' => 'auth',
		'project_id' => SOUZ_PROJECT_ID,
		'ip'=>$ip,
	);

	if (defined('SOUZ_EURO') && SOUZ_EURO) {
		$request['password'] = md5($password);
		$request['phash'] = 1;
	} else {
		$request['password'] = $password;
	}
	$request['login'] = $login;


	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}

	if($ret["status"] != "ok") return false;

	if (!$ret['pers_id'] || (null !== $pers_id && $res['pers_id'] != $pers_id)) return false;

	$ref = array(
		'email' => $login,
		'uid' => $ret['pers_id'],
	);
	if (null !== $nick) $ref['nick'] = $nick;
	if (SERVER_ID) $ref['server_id'] = SERVER_ID;

	$auth = auth_get($ref);
	if (!$auth) return false;

	return $ret;
}

function souz_delete($login='',$pers_id='') {
	if (!$login && !$pers_id) return false;
	$request = array(
		'id' => 'delete_user',
		'project_id' => SOUZ_PROJECT_ID,
	);
	if ($login) $request['login'] = $login;
	elseif ($pers_id) $request['pers_id'] = $pers_id;
	
	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	return $ret["status"] == "ok";
}

function souz_change_pw($login='',$pers_id='',$newPassword='') {
	
	if (!$login && !$pers_id) return false;
	if (!$newPassword) return false;
	$request = array(
		'id' => 'change_pw',
		'project_id' => SOUZ_PROJECT_ID,
	);
	if (defined('SOUZ_EURO') && SOUZ_EURO) {
		$request['new_password'] = md5($newPassword);
		$request['phash'] = 1;
	} else {
		$request['new_password'] = $newPassword;
	}
	if ($login) $request['login'] = $login;
	else $request['pers_id'] = $pers_id;
	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	return $ret["status"] == "ok";
}

function souz_project_list($login) {
	$request = array(
		'id' => 'project_list',
		'login' => $login,
		'project_id' => SOUZ_PROJECT_ID,
	);
	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	if ($ret["status"] != "ok") return false;
	$data = explode(',',$ret["projects"]);
	$new_data = array();
	foreach ($data as $item) {
		$t = explode(':',$item);
		$new_data[] = array(
			'project_id' => intval($t[0]),
			'title' => strval($t[1]),
			'register_time' => intval($t[2]),
		);
	}
	return $new_data;
}

function souz_get_flags($login='',$pers_id='') {
	if (!$login && !$pers_id) return false;
	$request = array(
		'id' => 'get_flags',
		'project_id' => SOUZ_PROJECT_ID,
		'__mode' => SOUZ_READ,
	);
	if ($login) $request['login'] = $login;
	elseif ($pers_id) $request['pers_id'] = $pers_id;
	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	if ($ret["status"] != "ok") return false;
	return intval($ret["flags"]);
}

function souz_set_flags($login='',$pers_id='',$flags=0) {
	if (!$login && !$pers_id) return false;
	$request = array(
		'id' => 'set_flags',
		'project_id' => SOUZ_PROJECT_ID,
		'new_flags' => $flags,
	);
	if ($login) $request['login'] = $login;
	elseif ($pers_id) $request['pers_id'] = $pers_id;
	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	return $ret["status"] == "ok";
}

function souz_not_delete($login='',$pers_id='') {
	if (!$login) {
		$souz_stat = souz_user_info(null,$pers_id);
		if ($souz_stat['login']) $login = $souz_stat['login'];
	}
	if (!$login) return false;
	$url = 'http://swa.mail.ru/cgi-bin/donotdelit?email='.$login;
	$ret = file_get_contents($url);
	if ($ret == 'Ok') return true;
	return false;
}

function souz_bind($login,$pers_id) {
	$request = array(
		'id' => 'bind',
		'login' => $login,
		'pers_id' => $pers_id,
		'project_id' => SOUZ_PROJECT_ID,
		'ip' => souz_ip(),
	);
	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	return $ret["status"] == "ok";
}

function souz_user_info($login='',$pers_id='') {
	//return array('pers_id'=>1);
	if (!$login && !$pers_id) return false;
	$request = array(
		'id' => 'uinfo',
		'project_id' => SOUZ_PROJECT_ID,
		'__mode' => SOUZ_READ,
	);
	if ($login) $request['login'] = $login;
	elseif ($pers_id) $request['pers_id'] = $pers_id;
	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	if ($ret["status"] != "ok") return false;
	return $ret;
}

function souz_change_login($login='',$pers_id='',$newLogin='',$newPassword='') {
	if (!$login && !$pers_id) return false;
	if (!$newLogin) return false;
	$request = array(
		'id' => 'change_login',
		'project_id' => SOUZ_PROJECT_ID,
		'new_login' => $newLogin,
	);
	if ($newPassword) $request['new_password'] = $newPassword;
	if ($login) $request['login'] = $login;
	else $request['pers_id'] = $pers_id;
	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	return $ret["status"] == "ok";
}


// Возврат старого пароля! Пароль можно вернуть если его изменили менее 24 часов назад
function souz_rollback_password($login='',$pers_id='') {
	//return array('pers_id'=>1);
	if (!$login && !$pers_id) return false;
	$request = array(
		'id' => 'rollback_password',
		'project_id' => SOUZ_PROJECT_ID,
	);
	if ($login) $request['login'] = $login;
	elseif ($pers_id) $request['pers_id'] = $pers_id;
	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	if ($ret["status"] != "ok") return false;
	return $ret;
}

function souz_get_ticket($login='',$project_id=SOUZ_PROJECT_ID) {
	if (!$login && !$project_id) return false;
	$request = array(
		'id' => 'key_gen',
		'login' => $login,
		'ext_project_id' => $project_id,
		'project_id' => SOUZ_PROJECT_ID,
	);
	
	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	if ($ret["status"] != "ok") return false;
	return strval($ret["key"]);
}

function souz_check_ticket($login='',$pers_id='',$key='') {
	if ((!$login && !$pers_id) || !$key) return false;
	$request = array(
		'id' => 'key_check',
		'key' => $key,
		'project_id' => SOUZ_PROJECT_ID,
	);
	if ($login) $request['login'] = $login;
	elseif ($pers_id) $request['pers_id'] = $pers_id;
	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	if ($ret["status"] != "ok") return false;
	return true;
}

function souz_login($pers_id) {
	if (!$pers_id) return false;
	$request = array(
		'id' => 'login',
		'project_id' => SOUZ_PROJECT_ID,
		'pers_id' => $pers_id,
		'uts' => time_current(),
	);

	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}

	if ($ret["status"] != "ok") {
		souz_log('souz_exec bad status', $ret, $request);
		return false;
	}
	
	return true;
}


/*
 * Новое апи от союза
 *
 */


/**
 * Функция регистрации привязок пользователя
 * @param <type> $login - либо email (для ММ), либо soc_uid (для всего остального)
 * @param <type> $ep_id - номер соц сети в собюзе
 * @param <type> $password - пароль (для обычной)
 * @param <type> $pers_id - ид перса
 * @param <type> $act_ts - время (для отложенные рег)
 * @return boolean тру /фолсе
 */
function souz_ep_register($login, $ep_id = 0, $password = null, $pers_id=null, $act_ts = null) {
	if (!isset($login) || !isset($ep_id)) return false;
	$request = array(
		'id' => 'register',
		'login' => $login,
		'ep_id' => $ep_id,
		'project_id' => SOUZ_PROJECT_ID,
		'__mode' => SOUZ_WRITE,
	);
	if ($password) $request['password'] = $password;
	if ($pers_id) $request['pers_id'] = $pers_id;
	if ($act_ts) $request['act_ts'] = $act_ts;

	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	return $ret["status"] == "ok";
}

/**
 *
 * @param <type> $login
 * @param <type> $ep_id
 * @param <type> $password
 * @param <type> $pers_id
 * @param <type> $act_ts
 * @return <type>
 */

function souz_ep_bind($login, $ep_id = 0, $password = null, $pers_id=null, $act_ts = null) {
	$request = array(
		'id' => 'bind',
		'login' => $login,
		'ep_id' => $ep_id,
		'project_id' => SOUZ_PROJECT_ID,
		'__mode' => SOUZ_WRITE,
	);
	if ($password) $request['password'] = $password;
	if ($pers_id) $request['pers_id'] = $pers_id;
	if ($act_ts) $request['act_ts'] = $act_ts;

	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	return $ret["status"] == "ok";
}

/**
 * Функция авторизации привязок
 * @param <type> $login
 * @param <type> $ep_id
 * @param <type> $pers_id
 * @param <type> $act_ts
 * @return <type>
 */
function souz_ep_login($login, $ep_id = 0, $pers_id=null, $act_ts = null) {
	$request = array(
		'id' => 'login',
		'project_id' => SOUZ_PROJECT_ID,
		'__mode' => SOUZ_WRITE,
	);
	if ($login && $ep_id) {
		$request['login'] = $login;
		$request['ep_id'] = $ep_id;
	} else if ($pers_id) {
		$request['pers_id'] = $pers_id;
	} else {
		return false;
	}
	if ($act_ts) $request['act_ts'] = $act_ts;
	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	return $ret["status"] == "ok";
}

/**
 *
 * @param <type> $login
 * @param <type> $ep_id
 * @param <type> $pers_id
 * @return <type> 
 */
function souz_ep_userinfo($login, $ep_id = 0, $pers_id=null) {
	$request = array(
		'id' => 'uinfo',
		'project_id' => SOUZ_PROJECT_ID,
		'__mode' => SOUZ_READ,
	);
	if ($login && $ep_id) {
		$request['login'] = $login;
		$request['ep_id'] = $ep_id;
	} else if ($pers_id) {
		$request['pers_id'] = $pers_id;
	} else {
		return false;
	}

	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	return $ret;
}
/**
 *
 * @param <type> $pers_id
 * @param <type> $ep_id
 * @return <type>
 */
function souz_ep_delete($login, $ep_id) {
	if (!$login || !$ep_id) return false;
	$request = array(
		'id' => 'ep_del',
		'login' => $login,
		'ep_id' => $ep_id,
		'project_id' => SOUZ_PROJECT_ID,
		'__mode' => SOUZ_WRITE,
	);
	$ret = souz_exec($request);
	if (!$ret || !is_array($ret) || !isset($ret["status"])) {
		souz_log('souz_exec() failed', $ret, $request);
		return false;
	}
	return $ret["status"] == "ok";
}
/**
 *
 * @param <type> $soc_sys_id
 * @return <type>
 */
function get_souz_ep_id($soc_sys_id) {
	switch ($soc_sys_id) {
		case SOC_SYSTEM_MM:
			return MM_SOUZ_EP;
		case SOC_SYSTEM_OK:
			return OK_SOUZ_EP;
		case SOC_SYSTEM_VK:
			return VK_SOUZ_EP;
		case SOC_SYSTEM_FB:
			return FB_SOUZ_EP;
		default:
			return DEFAULT_EP;
	}
}

// Функция для перенаправления союзных ошибок в отдельный файл
function souz_log($msg, $result = false, $request = false) {
	$file = SERVER_ROOT.PATH_LOGS.'souz/exec_'.date('Y-m-d', time_current()).'.log';
	$msg = date('[H:i:s]: ', time_current()).$msg;
	if ($request)
		$msg .= ' . Request: '.var_export($request, true);
	if ($result)
		$msg .= ' . Result: '.var_export($result, true);
	//if (defined('DEVS') && DEVS || defined('DEBUG') && DEBUG)
	//	$msg .= ' . Trace: '.var_export(debug_backtrace(), true);
	logfile($file, $msg);
}
