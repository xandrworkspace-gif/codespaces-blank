<? # $Id: common.inc,v 1.62 2010-01-15 09:50:10 p.knoblokh Exp $

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(E_ERROR | E_COMPILE_ERROR | E_USER_ERROR);

umask(0);

//set_error_handler('handleError');

//Самый сильный отлов ошибок!
function error_handler($errno, $errmsg, $filename, $linenum, $vars) {
    switch ($errno) {
        case E_ERROR:
        case E_USER_ERROR:
        case E_RECOVERABLE_ERROR:
            logfile(NODE_FILE_LOG, sprintf('%s: %s', $errno, $errmsg ));
            $bt = debug_backtrace();
            unset($bt[0]);
            $out = array('BACKTRACE');
            foreach ($bt as $v) {
                $t = array();
                $out[] = sprintf("%s:%d %s(%s)\n",basename($v['file']),$v['line'],$v['function'],implode(',',$t));
            }
            if (!$bt) $out[] = '[empty]';
            foreach ($out as $t) logfile(NODE_FILE_LOG, $t);
            break;
    }
}
function shutdown_function() {
    global $request_statistic, $session_user;
    $str = $request_statistic['script'];

    $error = error_get_last();
    # Checking if last error is a fatal error
    if(($error['type'] === E_ERROR) || ($error['type'] === E_USER_ERROR)) {
        $str .= '|' . str_replace (array ("\n", "\r"), array('\n', '\r'), serialize ($_REQUEST));
        $str .= '|user_id:' . $session_user ['id'];

        foreach (array('get','list','count','save','delete','tqd','chatd','fightd','memcached','xserver') as $type) {
            if ($request_statistic[$type]) {
                foreach ($request_statistic[$type] as $table => $n) {
                    $mt_time = sprintf('%.5f',$request_statistic['time'][$type][$table]);
                    $str .= '|'.$type.'/'.$table.'/'.$n.'/'.$mt_time;
                }
            }
        }
        PF_LOG(SERVER_ROOT.PATH_LOGS.'function_time.log');
        return logfile(SERVER_ROOT.PATH_LOGS.'request_statistic.log',$str);
    }
}
//set_error_handler( 'error_handler' );

$phpself = $_SERVER["PHP_SELF"];
$includePath = dirname(__FILE__);

require_once($includePath."/config.inc");
if (!defined('EXTAUTH')) {
	define('EXTAUTH', 0);
}
if (!defined('PARTNER')) {
	define('PARTNER', '');
}

if(!extension_loaded('mbstring')) {
	error_log('[phpextension] mbstring extesion not loaded!');
	die();
}

// ===================== Charset Setup =====================
if (LC_COLLATE_VALUE) setlocale(LC_COLLATE,LC_COLLATE_VALUE);
if (LC_CTYPE_VALUE) setlocale(LC_CTYPE,LC_CTYPE_VALUE);
if (function_exists('mb_internal_encoding')) mb_internal_encoding(CHARSET_CODE);
function charset_code() {
	return CHARSET_CODE;
}
function charset_code_html() {
	return CHARSET_CODE_HTML;
}
// =========================================================


require_once($includePath."/database.inc");
require_once($includePath."/placeholder.inc");
require_once($includePath."/node.inc");
require_once($includePath."/tqc.inc");
require_once(function_exists('memcache_get') ? $includePath."/cache.memcached.inc" : $includePath."/cache.inc");
#require_once($includePath."/cache.inc"); //Времянка!
require_once($includePath."/trans.inc");
require_once($includePath."/pf.inc");
require_once($includePath."/console_log.inc");
require_once($includePath."/servers.inc");

common_init_servers();

// container definition
$db = new Database(DB_HOST, DB_NAME, DB_USER, DB_PASSWD, charset_code());
$db_2 = new Database(DB_2_HOST, DB_2_NAME, DB_2_USER, DB_2_PASSWD, charset_code());
$db_3 = new Database(DB_3_HOST, DB_3_NAME, DB_3_USER, DB_3_PASSWD, charset_code());
$db_4 = new Database(DB_4_HOST, DB_4_NAME, DB_4_USER, DB_4_PASSWD, charset_code());
$db_auth = new Database(DB_AUTH_HOST, DB_AUTH_NAME, DB_AUTH_USER, DB_AUTH_PASSWD, charset_code());
$db_diff = new Database(DB_DIFF_HOST, DB_DIFF_NAME, DB_DIFF_USER, DB_DIFF_PASSWD, charset_code());
$db_path = new Database(DB_PATH_HOST, DB_PATH_NAME, DB_PATH_USER, DB_PATH_PASSWD, charset_code());
$db_art = new Database(DB_ART_HOST, DB_ART_NAME, DB_ART_USER, DB_ART_PASSWD, charset_code());
$db_info = new Database(DB_INFO_HOST, DB_INFO_NAME, DB_INFO_USER, DB_INFO_PASSWD, charset_code());
$db_ptest = new Database(DB_PTEST_HOST, DB_PTEST_NAME, DB_PTEST_USER, DB_PTEST_PASSWD, charset_code());
//$db_trans = new Database(DB_TRANS_HOST, DB_TRANS_NAME, DB_TRANS_USER, DB_TRANS_PASSWD, 'utf-8',true);

// token queue server
$tq = new TokenQueue(defined('TQ_SERVER') ? TQ_SERVER : 'tqhost.lo:22122');

// constant init
require_once($includePath."/constant.inc");
require_once('/home/admin/web/dwar.fun/public_html/lib/common.lib');
require_once('/home/admin/web/dwar.fun/public_html/lib/user.lib');

$time_current = time();

// =================================================================================================

function time_current() {
	global $time_current;
	return $time_current;
}

function vardump($v, $error_log=false, $htmlencode=true) {
	$text = var_export($v,true);
	if ($error_log) {
		$out = preg_split('/\r?\n/',$text);
		foreach ($out as $t) error_log($t);
	} else {
		print "<pre style=\"background-color:#ffffff;color:#990000;font-size:11px\">\n";
		if ($htmlencode) $text = htmlspecialchars($text);
		print $text."\n";
		print "</pre>\n";
	}
}

function backtrace($error_log=false, $params=false) {
	$bt = debug_backtrace();
	unset($bt[0]);
	$out = array('BACKTRACE');
	foreach ($bt as $v) {
		$t = array();
		if ($params) foreach ($v['args'] as $a) $t[] = var_export($a,true);
		$out[] = sprintf("%s:%d %s(%s)\n",basename($v['file']),$v['line'],$v['function'],implode(',',$t));
	}
	if (!$bt) $out[] = '[empty]';
	if ($error_log) {
		foreach ($out as $t) error_log($t);
	} else {
		print "<pre style=\"background-color:#ffffff;color:#990000;font-size:11px\">\n";
		print implode("\n",$out)."\n";
		print "</pre>\n";
	}
}

function logfile($fn, $str, $add_date = true, $crate_have = false) {
	$have_file = @file_exists($fn);
	$fout = @fopen($fn,"a");
	if (!$fout) return;
	if(flock($fout,LOCK_EX)) {
		if ($add_date)
			$log_str = date("[Y-m-d, H:i:s]")." ".$str."\n";
		else
			$log_str = $str."\n";
		fwrite($fout,$log_str);
		fflush($fout);
		flock($fout, LOCK_UN);
	}
	fclose($fout);
	if (!$have_file) @chmod($fn,0664);
}

function logfile_get($fn){
	if(!$fn){ return false; }
	$ext = substr($fn, strrpos($fn, '.') + 1);
	if(!in_array($ext, array('log', 'txt'))) return false;
	return file_get_contents($fn, true);
}

function logfile_rm($fn){
	if(!$fn){ return false; }
    $ext = substr($fn, strrpos($fn, '.') + 1);
    if(!in_array($ext, array('log', 'txt'))) return false;
	$fp = fopen($fn,"w+");
	ftruncate($fp,-1);
	fclose($fp); //Закрытие файла
}

function get_array_value($arr,$key_value,$key_name='id',$val_name='title',$val_default=null) {
	$ret = $val_default;
	foreach ($arr as $t)
		if ($t[$key_name] == $key_value) {
			$ret = $t[$val_name];
			break;
		}
	return($ret);
}

function push_params(&$arr,$params) {
	foreach ($params as $param_key => $param_val) $arr[$param_key] = $param_val;
}

function &get_params($item, $keys, $skip_null=false, $skip_empty=false) {
	if (!is_array($keys)) $keys = preg_split("/[\s,;]+/", $keys);
	$arr = array();
	foreach ($keys as $k) {
		if ($skip_null && !isset($item[$k])) continue;
		if ($skip_empty && empty($item[$k])) continue;
		$arr[$k] = $item[$k];
	}
	return $arr;
}

function &get_symbolic_params($item, $skip_null=false, $skip_empty=false) {
	$arr = array();
	foreach ($item as $k=>$v) {
		if (is_numeric($k)) continue;
		if ($skip_null && !isset($item[$k])) continue;
		if ($skip_empty && empty($item[$k])) continue;
		$arr[$k] = $item[$k];
	}
	return $arr;
}

function &get_hash($arr, $key_name='id', $val_name='title') {
	if (!is_array($arr)) return array();
	$ret = array();
	foreach ($arr as $item) {
		$ret[$item[$key_name]] = $item[$val_name];
	}
	return $ret;
}

function &get_hash_grp($arr, $grp_key_name='type_id', $key_name='id', $val_name='title') {
	if (!is_array($arr)) return array();
	$ret = array();
	foreach ($arr as $item) {
		if ($key_name)
			$ret[$item[$grp_key_name]][$item[$key_name]] = $item[$val_name];
		else
			$ret[$item[$grp_key_name]][] = $item[$val_name];
	}
	return $ret;
}

function &make_hash($arr, $key_name='id', $group=false) {
	if (!is_array($arr)) return array();
	$key = is_array($key_name) ? array_shift($key_name) : $key_name;
	$ret = array();
	foreach ($arr as $item) {
		if ($group) $ret[$item[$key]][] = $item;
		else $ret[$item[$key]] = $item;
	}
	if (is_array($key_name) && count($key_name) > 0 && $group) {
		foreach ($ret as $idx => $ret_line) {
			$ret[$idx] = make_hash($ret[$idx], $key_name, true);
		}
	}
	return $ret;
}

function &make_hash_ex($arr, $key_name=array('id'), $group=false) {
    if (!is_array($arr)) return array();
    $key = $key_name[0];
    $ret = array();
    foreach ($arr as $item) {
        if(count($key_name) == 2){
            $ret[$item[$key_name[0]]][$item[$key_name[1]]] = $item;
        }elseif(count($key_name) == 3){
            $ret[$item[$key_name[0]]][$item[$key_name[1]]][$item[$key_name[2]]] = $item;
        }else $ret[$item[$key]] = $item;
    }
    return $ret;
}

function file_url($src) { // Борьба с кешированием
	$t = @filemtime($src);
	if ($t > 0) $src .= '?ux='.$t;
	return $src;
}

function secure_url($key='', $url=null) {	// Контрольная сумма в ссылках
	$query = '';
	if (!isset($url)) {	// check query
		$t = explode('&',urldecode($_SERVER['QUERY_STRING']));
		$crc = array_pop($t);
		$query = implode('&',$t);
	} else {	// get secure url
		$t = explode('?',$url);
		if ($t[1]) $query = $t[1];
	}
	$_crc = md5('SECURE-URL-'.$key.$query);
	return !isset($url) ? $_crc == $crc : $url.($query ? '&'.$_crc : '?'.$_crc);
}

// Получаем ключи, не находящиеся в кеше
function cache_unprocessed_ids(&$cache, $keys) {
	if (!$cache) $cache = array();
	$res = array_diff($keys, array_keys($cache));

	return $res;
}

function cache_fetch(&$cache, $key, $fetch_func) {
	if (!isset($cache[$key])) $cache[$key] = $fetch_func($key);
	return $cache[$key];
}

function utf8($text) {
	$charset = charset_code();
	if ($charset == 'UTF-8') return $text;
	return iconv($charset,'UTF-8',$text);
}

function from_utf8($text) {
	$charset = charset_code();
	if ($charset == 'UTF-8') return $text;
	return iconv('UTF-8',$charset,$text);
}

function date_bkey($unit=0, $value=0, $descr=false) {
	$info = array(
		 1 => array(date('Y-m-d.H:i'),                             60, translate('в минуту')),
		 4 => array(date('Y-m-d.H:5\M').(int)(date('i')/5),       300, translate('в 5 минут')),
		 7 => array(date('Y-m-d.H:10\M').(int)(date('i')/10),     600, translate('в 10 минут')),
		10 => array(date('Y-m-d.H:15\M').(int)(date('i')/15),     900, translate('в 15 минут')),
		13 => array(date('Y-m-d.H:30\M').(int)(date('i')/30),     900, translate('в 30 минут')),
		20 => array(date('Y-m-d.H'),                             3600, translate('в час')),
		22 => array(date('Y-m-d.3\H').(int)(date('H')/3),       10800, translate('в 3 часа')),
		24 => array(date('Y-m-d.6\H').(int)(date('H')/6),       21600, translate('в 6 часов')),
		26 => array(date('Y-m-d.12\H').(int)(date('H')/12),     43200, translate('в 12 часов')),
		30 => array(date('Y-m-d'),                              86400, translate('в день')),
		40 => array(date('Y-\WW'),                             604800, translate('в неделю')),
		50 => array(date('Y-\Mm'),                            2592000, translate('в месяц')),
		60 => array(date('\YY'),                             31536000, translate('в год')),
		100 => array(date('\~'),                                    0, translate('в вечность')),
		110 => array(sprintf('I%d',$value),                        -1, translate('интервал (в сек.)')),
	);
	if ($descr) {
		if ($unit == 110) return sprintf(translate('1 раз в %d сек.'),$value);
		if ($info[$unit]) return $value.' '.translate('раз').' '.$info[$unit][2];
		$out = array();
		foreach ($info as $k=>$v) $out[$k] = $v[2];
		return $out;
	}
	return $info[$unit];
}

function money_floatval($val) {
	$val = floatval($val);
	if (!is_numeric($val)) return 0;
	$val = str_replace(',','.',$val);
	$i = mb_strpos($val,'.');
	if ($i !== false) $val = mb_substr($val,0,$i+3);
	return $val;
}

function gauss_rand($m=0, $d=1) {	// нормально распределенная СВ
	$r = 0;
	for ($i=0; $i<12; $i++) $r += mt_rand(0,1000000)/1000000;
	$r += -6;
	return  $m + $r*$d;
}

function bit_cnt($x) {	// код подсчета кол-ва битов
	$x = ($x & 0x55555555) + (($x >>  1) & 0x55555555);
	$x = ($x & 0x33333333) + (($x >>  2) & 0x33333333);
	$x = ($x & 0x0F0F0F0F) + (($x >>  4) & 0x0F0F0F0F);
	$x = ($x & 0x00FF00FF) + (($x >>  8) & 0x00FF00FF);
	$x = ($x & 0x0000FFFF) + (($x >> 16) & 0x0000FFFF);
	return $x;
}


function rand_roll($p=0) {
	return $p*1000000 >= rand(1,1000000);
}

function concat() {
	$args = func_get_args();
	return implode('',$args);
}

// получние дня недели
function date_week_day($time = null) {
	if ($time === null) $time = time_current();
	$week_day = idate('w', $time);
	return ($week_day == 0) ? 7 : $week_day;
}

function common_json_encode($a) {
	if(!is_array($a)) {
		return sprintf('"%s"', common_json_enc_quote($a));
	} else {
		$pairs = array();
		$i = 0;
		$is_obj=false;
		foreach($a as $k=>$v) {
			if (preg_match('/\D/', $k) || $k!=$i++) $is_obj=true;
			array_push($pairs, array($k, $v));
		}
		if ($is_obj) {
			return sprintf('{%s}', join(', ', array_map(common_json_enc_obj, $pairs)));
		} else {
			return sprintf('[%s]', join(', ', array_map(common_json_enc_arr, $pairs)));
		}
	}
}
function common_json_enc_quote($v) {
	return preg_replace('/([\\\\"])/','\\\\$1', $v);
}
function common_json_enc_arr($v) {
	return sprintf('%s', common_json_encode($v[1]));
}
function common_json_enc_obj($v) {
	return sprintf('"%s":%s', common_json_enc_quote($v[0]), common_json_encode($v[1]));
}

function common_cache_get($cache_key) {
	if (function_exists('apc_fetch')) {
		return apc_fetch($cache_key);
	}
	if (function_exists('eaccelerator_get')) {
		return eaccelerator_get($cache_key);
	}
	return false;
}

function common_cache_store($cache_key, $value, $local_cache_ttl = LOCAL_CACHE_TTL) {
	if (function_exists('apc_store')) apc_store($cache_key, $value, $local_cache_ttl);
	if (function_exists('eaccelerator_put')) eaccelerator_put($cache_key, $value, $local_cache_ttl);
	return false;
}

function language_info($filtered=true) {
	global $language_info, $SUPPORTED_LANGUAGE_LIST;
	$res = array();
	if ($filtered && is_array($SUPPORTED_LANGUAGE_LIST)) {
		foreach ($SUPPORTED_LANGUAGE_LIST as $lang) {
			if ($language_info[$lang]) $res[$lang] = $language_info[$lang];
		}
	}
	return $filtered ? $res : $language_info;
}

function mb_substr_replace($output, $replace, $posOpen, $len) {
	return mb_substr($output, 0, $posOpen).$replace.mb_substr($output, $posOpen + $len);
}

//Разбить по мелким значениям
function common_array_molot($arr, $cnt = 100, $is_hash = false){
    $array = array();
    $m_cnt = count($arr); //Сколько всего записей?
    $p_vh = intval($m_cnt / $cnt); //Порог прохождения
    if($m_cnt <= $cnt) return false;
    if(!$p_vh) return false;
    $i = 0; //Счетчик обработки сбрасывать будет сам себя
    $m = 0; //Индекс нового массива куда записывать данные
    foreach($arr as $k=>$v){
        if($i == $cnt){ //Порог вхождения активирован
            $m++; $i=0; //Уберем нахуй $i
        }
        if($is_hash) $array[$m][$k] = $v;
        else  $array[$m][] = $v;
        $i++;
    }
    return $array;
}

function curl_get_contents($url){
    if(!$url) return '';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}