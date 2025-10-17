<? # $Id: Exp $

// глобальная переменная, для отключения переводов в произвольный момент выполнения кода (например, при построении кеша артикулов)
global $no_translate;
$no_translate = (!defined('_TRANS_LANG') || !_TRANS_LANG);

$db_trans = new Database(DB_TRANS_HOST, DB_TRANS_NAME, DB_TRANS_USER, DB_TRANS_PASSWD, charset_code(), true);

$digits = "0123456789abcdefghijklmnpqrstuvwxyz";
$base = mb_strlen($digits);


function _trans($a, $id, $str, $b) {
	global $db_trans;
	if (function_exists('translate')) {
		return translate($str);
	}
	return $str;
//	$trans_mode = $_COOKIE['_TRANS_MODE'];
//	if (!isset($trans_mode)) $trans_mode = intval(_TRANS_MODE);
//	if (!$trans_mode) return $str;
//	$fld = "str_".@constant('_TRANS_LANG');
//	$sql = sql_pholder("SELECT str, $fld FROM phrases WHERE id=?", $id);
//	$db_trans->db_GetQueryRow($sql, $row);
//	$nstr = $row[$fld] ? $row[$fld] : ($row['str'] ? $row['str'] : $str);
//	$id = fromDecimalNumber($id);
//	return $trans_mode == 1 ? $nstr : "$id|$nstr";
}

function toDecimalNumber($baseNumber) {
	global $digits, $base;
	$baseNumber = strtolower($baseNumber);
	$p = mb_strlen($baseNumber)-1;
	$l = $p;
	$n = 0;
	for ($i=0; $i<=$l; $i=$i+1) {
		$n += pow($base, $p)*mb_strpos($digits, $baseNumber{$i});
		$p--;
	}
	return $n;
}
function fromDecimalNumber($number) {
	global $digits, $base;
	$n = "";
	$number = intval($number);
	while ($number >= $base) {
		$n = $digits{$number % $base}.$n;
		$number = floor($number / $base);
	}
	$n = $digits{$number}.$n;
	return $n;
}

global $translate_default_language_stack;
$translate_default_language_stack = array();
function translate_default_language_push($lang) {
	global $translate_default_language_stack;
	array_push($translate_default_language_stack, translate_default_language());
	
	translate_default_language($lang);
}

function translate_default_language_pop() {
	global $translate_default_language_stack;
	$lang = array_pop($translate_default_language_stack);
	
	translate_default_language($lang);
}

function translate_default_language($lang=false) {
	static $_translate_default_language = false;
	global $known_languages, $no_translate;

	if ($no_translate) return 'ru';
	
	if ($lang && $known_languages[$lang]) { // считаем, что если функцию вызвали с определённым языком, то знали, что делали
		$_translate_default_language = $lang;
		return $_translate_default_language;
	}

	if ($_translate_default_language === false) {
		if (defined('PROJECT_MAIN_LANGUAGE') && PROJECT_MAIN_LANGUAGE) {
			$_translate_default_language = PROJECT_MAIN_LANGUAGE;
		} elseif (defined('_TRANS_LANG') && _TRANS_LANG) {
			$_translate_default_language = _TRANS_LANG;
		}
	}

	return $_translate_default_language;
}

// Перводит строку
// Навороченная функия которая умеет работать с eaccelerator и memcache
// Не стал разбивать на более мелкие функции, тк пока не вижу смысла - поэтому
// здесь реализован алгоритм кэширования, подмена на английскую фразу (в случае отстуствия языка)
global $_translate_cache;
function translate($str, $lang = false) {
	return $str;
	global $_translate_cache, $no_translate;

	if ($no_translate) return $str;

	$ea_ttl = (defined('TRANS_EACCELERATOR_CACHE_TTL') && TRANS_EACCELERATOR_CACHE_TTL) ? TRANS_EACCELERATOR_CACHE_TTL : 600;
	$mc_ttl = (defined('TRANS_MEMCACHE_CACHE_TTL') && TRANS_MEMCACHE_CACHE_TTL) ? TRANS_MEMCACHE_CACHE_TTL : 1200;

	if (trim($str) == '') return '';
	if (!$lang)	$lang = translate_default_language();
	if (!$lang || ($lang == 'ru')) return $str;

	$key = md5($str);
	$cache_key = 'translate_'.$lang.'_'.$key;
	if (isset($_translate_cache[$cache_key])) {
		return $_translate_cache[$cache_key];
	}
	
	$res = common_cache_get($cache_key);
	if ($res) {
		$_translate_cache[$cache_key] = $res;
		return $res;
	}

	$cache = new Cache($cache_key);
	if ($res = $cache->get()) {
		$_translate_cache[$cache_key] = $res;
		common_cache_store($cache_key, $res, $ea_ttl);
		return $res;
	}
	// В кэше нет - берем из mysql
	$res = translate_hash($key, $lang);

	// Если нет перевода и язык не английский, пробуем выдать английский вариант
	if (!$res && ($lang != 'en')) {
		$res = translate($str, 'en');
	}
	$res = strval($res);
	// Если всё ещё не нашли перевод, то отдаём исходный вариант,
	// но его всё равно нужно закешировать, чтобы не поднимать из БД каждый раз
	if (!$res) {
		$res = $str;
	}
	
	$_translate_cache[$cache_key] = $res;
	common_cache_store($cache_key, $res, $ea_ttl);
	$cache->update($res, $mc_ttl);
	return $res;
}

function translate_hash($hash_key, $lang = false) {
	global $db_trans;
	
	if (!$lang) $lang = translate_default_language();

	$lang_field = 'str_'.$lang;
	$row = false;
	$db_trans->db_GetQueryRow('SELECT `'.$lang_field.'` FROM `multilang_phrases` WHERE strid = "'.$hash_key.'"', $row);
	$res = $row ? $row[$lang_field] : '';
	if ($res) return $res;
	
	return false;
}

// это функция для переводов неких составных сущностей на лету
function translate_entity(&$entity, $entity_class=false, $lang=false) {
	global $entity_translate_fields;
	if (!$entity) return false;

	$entity_class = $entity_class ? $entity_class : $entity['object_class'];
	if (!$entity_class) return false;

	$fields = $entity_translate_fields[$entity_class];
	if ($fields && is_array($fields)) {
		foreach ($fields as $field) {
			if (is_array($entity[$field])) {
				// звездолёт из-за сложных структур, пока мало данных для обобщения алгоритма
				if ($entity_class == OBJECT_CLASS_ARTIFACT_ARTIKUL && $field == 'description') {
					$entity[$field]['text'] = translate($entity[$field]['text'], $lang);
				}
				continue;
			}
			$entity[$field] = translate($entity[$field], $lang);
		}
	}
	return true;
}

function is_translate_table($table_name) {
	// ничего не делаем для русской версии
	global $no_translate;
	if ($no_translate) return false;

	global $table_translate_fields;
	return isset($table_translate_fields[$table_name]);
}

// это функции специально для перевода записей из БД, сразу после их получения
function translate_data_row(&$data_row, $table_name, $lang=false) {
	// ничего не делаем для русской версии
	global $no_translate;
	if ($no_translate) return false;
	if (!$lang)	$lang = translate_default_language();
	if (!$lang || ($lang == 'ru')) return true;

	global $table_translate_fields;
	if (!$table_name) return false;
	if (!$data_row || !is_array($data_row)) return false;

	$fields = $table_translate_fields[$table_name];
	if ($fields && is_array($fields)) {
		foreach ($fields as $field) {
			if (!isset($data_row[$field]) || is_array($data_row[$field])) continue;
			$data_row[$field] = translate($data_row[$field], $lang);
		}
	}
	return true;
}

function translate_data_all_rows(&$data, $table_name, $lang=false) {
	// ничего не делаем для русской версии
	global $no_translate;
	if ($no_translate) return false;
	if (!$lang)	$lang = translate_default_language();
	if (!$lang || ($lang == 'ru')) return true;

	if (!$data || !$table_name) return false;
	foreach ($data as &$row) {
		translate_data_row($row, $table_name, $lang);
	}
	return true;
}

$no_translate_stack = array();
function no_translate_push($state) {
	global $no_translate, $no_translate_stack;
	
	array_push($no_translate_stack, $no_translate ? true : false);
	$no_translate = $state ? true : false;
}
function no_translate_pop() {
	global $no_translate, $no_translate_stack;
	
	$state = array_pop($no_translate_stack);
	$no_translate = $state ? true : false;
}

function no_translate_switch($state=null) {
	global $no_translate;
	
	if (!defined('_TRANS_LANG') || !_TRANS_LANG) {
		$no_translate = true;
		return;
	}

	if (isset($state)) {
		// если явно указано
		$no_translate = (bool)$state;
	} else {
		// иначе просто переключаем
		$no_translate = !$no_translate;
	}
}

?>