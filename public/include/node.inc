<? # $Id: node.inc,v 1.35 2009-09-30 11:51:38 vadim_b Exp $

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
* Система управления контейнерами узловых баз
* Sen, it-territory, 2006
* * * * * * * * * * * * * * * * * * * * * * * * * * * * */


// структура узловой базы
$_node_struct = array(        // ref,  cross-node,  link,               dependent tables,   fields
	'artifacts'           => array('id',      1,      'user_id',          'artifact_actions,artifact_notes,artifact_skills', '*'),
	'artifact_actions'    => array('id',      0,      'artifact_id',      '',                 'artifact_id,action_id,n,dispose,per_unit,per_value'),
	'artifact_notes'      => array('id',      0,      'artifact_id',      '',                 'artifact_id,note,type'),
	'artifact_skills'     => array('id',      0,      'artifact_id',      '',                 'artifact_id,skill_id,value,value2'),
	'sessions'            => array('uid',     0,      'uid',              '',                 '*'),
	'user_achievements'   => array(NULL,      0,      'user_id',          '',                 'user_id,achievement_id,status,weight,kind,stime'),
	'user_actions'        => array('id',      0,      'user_id',          '',                 'user_id,action_id,n,per_unit,per_value'),
	'user_skills'         => array(NULL,      0,      'user_id',          '',                 'user_id,skill_id,value'),
	'user_spells'         => array('id',      0,      'user_id',          '',                 'user_id,artikul_id,slot,class'),
	'user_stats'          => array(NULL,      0,      'user_id',          '',                 'user_id,type_id,object_id,value,next_value,status,clan_id,before_clan_value,do_clan_value'),
	'pets'                => array('id',      1,      'user_id',          '',                 '*'),
	'smile_users'         => array(NULL,      0,      'user_id',          '',                 '*'),
	'quest_users'         => array(NULL,      0,      'user_id',          '',                 'user_id,quest_id,status,point_num,flags,dtime,stime'),
	'quest_user_points'   => array(NULL,      0,      'user_id',          '',                 'user_id,quest_id,point_id,subpoint_id,status'),
);

$_node_count_no_fridge = false;
$_node_count = false;

$NODE_NUMS = array();

$db_nodes = array();	// массив контейнеров

foreach ($DB_NODE_CFG as $num=>$cfg) {
	$db_nodes[$num] = new Database($cfg['host'], $cfg['name'], $cfg['user'], $cfg['passwd'], charset_code());
	$NODE_NUMS[$num] = $num;
}

$node_num = 0;	// текущий номер узла (по умолчанию не определен)

$_node_stack = array();
$_node_cache = array();
$_node_table_cache = array();


// =================================================================================================

// находится ли таблица в узле
function NODE_TABLE($table_name) {
	global $_node_struct;
	return isset($_node_struct[$table_name]);
}

// переключить узел по номеру или по uid
// возвращает номер узла
function NODE_SWITCH($num=null, $uid=null) {
	global $NODE_NUMS, $node_num;
	if (!isset($num) && !isset($uid)) return $node_num;
	if ($uid) $num = NODE_GET($uid);
	if (!$NODE_NUMS[$num]) return false;
	$node_num = $num;
	return $node_num;
}

function NODE_PUSH($num=null, $uid=null) {
	global $_node_stack;
	array_push($_node_stack,NODE_SWITCH());
	return NODE_SWITCH($num,$uid);
}

function NODE_POP() {
	global $_node_stack;
	if (!$_node_stack) return false;
	return NODE_SWITCH(array_pop($_node_stack));
}

// взять номер узла пользователя
function NODE_GET($uid) {
	global $db_auth, $_node_cache;
	
	if (!$_node_cache[$uid]) {
		$db_auth->db_GetQueryVal("SELECT node_num FROM node_info WHERE uid='".intval($uid)."'",$num);
		$_node_cache[$uid] = intval($num);
	}
	
/*
 * Раскоментим, когда откажемся от dragons_auth.
	if (!$_node_cache[$uid]) {
    require_once("lib/user.lib");
    $user = user_get(array('id' => $uid));
    $res = ($user['flags'] & USER_FLAG_FROZEN) ? FRIDGE_NN : NODE_UID_TO_NUM($uid);
    
		$_node_cache[$uid] = intval($res);
	}*/
	
	
	return $_node_cache[$uid];
}

/* LEGACY */
// сохранить номер узла пользователя
function NODE_SAVE($uid, $num) {
	global $db_auth, $_node_cache;
	$db_auth->execSQL("REPLACE INTO node_info (uid,node_num) VALUES ('".intval($uid)."','".intval($num)."')");
	$_node_cache[$uid] = $num;
	return true;
}

// удалить номер узла пользователя
function NODE_REMOVE($uid) {
	global $db_auth;
	$db_auth->execSQL("DELETE FROM node_info WHERE uid='".intval($uid)."'");
	return true;
}

// получить статистику по узлам
function NODE_STAT() {
  return -1;
	global $db_auth;
	$db_auth->db_getQueryHash("SELECT node_num,count(*) FROM node_info GROUP BY node_num",$out);
	return $out;
}
/*\ LEGACY \*/


// являются ли ID в таблице кросс-узловыми
function NODE_IS_CROSSNODE($table_name) {
	global $db, $_node_struct;
	if (!isset($_node_struct[$table_name])) {
		return false;
	}
	return $_node_struct[$table_name][1];
}

// взять номер узла по таблице id_<table_name>
function NODE_GET_BY_REF($table_name, $ref) {
	global $db, $_node_table_cache;
	if (!NODE_IS_CROSSNODE($table_name)) return false;

	if (!$_node_table_cache[$table_name][$ref]) {
		$db->db_GetQueryVal("SELECT node_num FROM id_".$table_name." WHERE id='".intval($ref)."'",$num);
		$_node_table_cache[$table_name][$ref] = intval($num);
	}
	
	return $_node_table_cache[$table_name][$ref];
}

// выделить ID в таблице id_<table_name>
function NODE_GET_CROSSNODE_REF($table_name, $num, $ref=false) {
	global $db, $NODE_NUMS;
	if (!NODE_IS_CROSSNODE($table_name) || !$NODE_NUMS[$num]) return false;
	if (!$ref) {
		$res = $db->execSQL("INSERT INTO id_".$table_name." (node_num) VALUES (".intval($num).")");
	} else {
		$res = $db->execSQL("REPLACE INTO id_".$table_name." (id, node_num) VALUES (".intval($ref).", ".intval($num).")");
	}
	return $res && ($db->affected_rows() > 0) ? $db->insert_id() : 0;
}

// удалить ID в таблице id_<table_name>
function NODE_DELETE_CROSSNODE_REF($table_name, $ref) {
	global $db;
	if (!NODE_IS_CROSSNODE($table_name)) return false;
	return $db->execSQL("DELETE FROM id_".$table_name." WHERE id='".intval($ref)."'");
}

function NODE_MULTI_DELETE_CROSSNODE_REF($table_name, $ref) {
	global $db;
	if (!is_array($ref) || empty($ref) || !NODE_IS_CROSSNODE($table_name)) return false;
	$ids_str = '';
	foreach ($ref as $ref_item) {
		$ref_item = intval($ref_item);
		if ($ref_item > 0) {
			$ids_str .= (strlen($ids_str) > 0 ? ',' : '').$ref_item;
		}
	}
	
	if (strlen($ids_str) <= 0) return false;
	
	return $db->execSQL("DELETE FROM id_".$table_name." WHERE id IN (".$ids_str.")");
}

// "цвет" узла
function NODE_COLOR($num) {
	$c1 = sprintf('%2x',max(160-(int)($num/8)*32,0x40));
	$c2 = sprintf('%2x',max(255-(int)($num/8)*32,0x9F));
	$s = substr(strrev(str_pad(decbin($num),3,'0',STR_PAD_LEFT)),0,3);
	$s = str_replace('0',$c1,$s);
	$s = str_replace('1',$c2,$s);
	return '#'.$s;
}

function NODE_COUNT() {
	global $_node_count, $NODE_NUMS;
	
	if ($_node_count === false) {
		$_node_count = count($NODE_NUMS);
	}
	return $_node_count;
}

function NODE_COUNT_NO_FRIDGE() {
	global $_node_count_no_fridge, $NODE_NUMS;
	
	if ($_node_count_no_fridge === false) {
		$_node_count_no_fridge = NODE_COUNT();
		if (defined("FRIDGE_NN") && isset($NODE_NUMS[FRIDGE_NN])) {
			--$_node_count_no_fridge;
		}
	}
	return $_node_count_no_fridge;
}

function NODE_UID_TO_NUM($uid) {
	return 1; 
}

// =================================================================================================

function _node_log($str) {
	logfile(PATH_LOGS.'node.log',getmypid().' - '.$str);
//	error_log("(node.inc: ".getmypid().") ".$str);
}

?>
