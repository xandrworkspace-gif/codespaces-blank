<? // $Id: stat_register_distributed.php,v 1.10 2009-08-14 08:18:44 n.alekseev Exp $ 
// =========================================================================
// Author: Nikolay Alekseev (n.alekseev@itterritory.ru)
// Task: 17721 Сбор статистики по эффективным регистрациям. (1)
// Description: собирает статистику по заданным условиям
//		по юзерам которые зарегестрировались N дней назад,
//		складывает результаты (в виде xml) в папку доступную по вебу
//		Одновременно хранится не боле 3 файлов сттистики (старые удаляются)
//		На этом функционал тулзы заканчивается, но более глобально - есть другая тулза,
//		которая качает каждый день эти файлики и собирает единую статстку для всего проекта 
// Synops: Для разработки и добавления функционала 
//		$DAY_AGO $LOOKUP_DAY - смотреть пользователей, которые зарегались в период [now - $DAY_AGO, now-$DAY_AGO+$LOOKUP_DAY]  
//		$SAVE_TO_FOLD - папка с отчетам
//		$ROTATE_MAX - максимальое количество файлов отчетов. При сохранение нового отчета, самый старый будет удален.
//		class stat_manager - список отчетов, выхов конкретых отчетов, формирование результирующей xml  		
//		Добавление статистики в отчет - 
//			$mng->add(.... ) ф-ия main
//		class save_manager - сохранение xml, удаление старых отчетов  		
//		stat_action_int - интерфейс, которым должен реализовать каждый класс конкретной статистики  
//		stat_action - абстрактный класс, содержаий в себе ф-ии помошники
//		
//		Новый вид конкретноый статистики:
//		В общем случае просто имплементируем интерфэйс stat_action_int, 
// 			но удобнее уноследоваться от stat_action, тк есть вспомогательный ф-ии, пойдем 2 путем:
//
//		class stat_foo extends stat_action {
//			protected function make_result() {
//				return $this->_stat_walk_by_kind("get_stat"); // Вызываем для каждой рассы `get_stat` со списком юзер айди 
//			}
//			protected function get_stat($uids) {
//				return count($uids); // какой-то кверик или как здесь просто подсчет кол-ва пользователей этой рассы
//			}
//			function xml_result() {
//				return compose_xml_node("foo", null, $this->_xml_kind_last_result()); 
//				получится <foo><result><kind id=1>30</kind><kind id=2>40</kind></result></foo> 
//			}
//		}
//		Здесь stat_action вызвал make_result, который в свою очередь воспользовался _stat_walk_by_kind
//			эта функция вызвала get_stat ровно столько раз, сколько рас представлено в списке пользователей и
//			сохранила резуьтат работы ф-ии в ассоцивный массив результатов ( kind=> get_stat() )
//			Затем _xml_kind_last_result сформировала выдачу <result>...</result>, т.к она имеет доступ 
//			к этому ассоциативному массиву
//		Еще было бы недурно добавлять новый класс следующим после последнего объявленого,  
//			т.е. практически в конец файла		
//		
//		Вообщем пока все, сюда можно дописывать свои полезные мысли и исправлять грамотические ошибки-) 
//		Надеюсь все несложно получилось
// =========================================================================

chdir("..");
require_once("include/common.inc");
require_once("lib/auth.lib");
require_once("lib/stat.lib");
require_once("lib/user_stat.lib");

define('STAT_REGISTER_DISTR_VER', '$Id: stat_register_distributed.php,v 1.10 2009-08-14 08:18:44 n.alekseev Exp $'); 
$DAY_AGO 		= 2;
$LOOKUP_DAY 	= 1;
$SAVE_TO_FOLD 	= SERVER_ROOT.'shared/tmp/stat_register';
$ROTATE_MAX 	= 30;

$STAT_PERIOD_START = 0;
$BENCH_START = 0;

//error_reporting(0);
set_time_limit(0);
function main() {
	global $DAY_AGO, $LOOKUP_DAY;
	global $STAT_PERIOD_START, $BENCH_START; 
	$BENCH_START = time();
	$STAT_PERIOD_START = mktime(0, 0, 0, date("m")  , date("d")-$DAY_AGO, date("Y"));
	stat_fill_tasks();
	$uids = stat_get_user_list($STAT_PERIOD_START, $LOOKUP_DAY * 24 * 60 * 60);
	$tasks = stat_task_list();
	$result_xml = '';
	$err = false;
	$tasks = array_reverse($tasks);
	$result_count = $last_result_count = false;
	foreach($tasks as $task) {
		$name = $task[0];
		$args = array_slice($task, 1);
		// собираем результат
		$res = array();
		foreach($uids as $kind => $ids ) {
			$tmp_res = call_user_func_array($name.'_select', array_merge(array($ids ), $args) ); 
			if (stat_last_error(false)!== false)
				break;
			$res[$kind] = $tmp_res; 
		} 
		// проверяем есть ли ошибки
		$err = stat_last_error();
		if ( $err !== false) {
			if(!$err) $err = 'Unknown error';	
			$result_xml = response_xml('fail', $err, '');
			break;	
		}
		// Это мега алгоритм
		// TODO: Описать его подробно
		$result_count = exclude_from_usr_list($uids, $res);
		$result_count = increse_result($result_count, $last_result);
		$last_result = $result_count; 
		$result_xml = call_user_func_array($name."_xml", array_merge(array($result_count), $args) )."\n".$result_xml;
	}
	if ($err === false) {
		$result_xml = response_xml('ok', '', $result_xml);
	}
	// save here	
 	xml_rotate();
 	save_xml($STAT_PERIOD_START, $result_xml);
 	if (stat_last_error(false) !== false)
 		error_log("[".basename(__FILE__)."] error: ". stat_last_error() );
}

function stat_fill_tasks() {
	stat_add_task('stat_registered');
	stat_add_task('stat_quest_point', 225, quest_point(2634), quest_point(2635) );
	stat_add_task('stat_flash_load', 1);
	stat_add_task('stat_flash_load', 2);
	stat_add_task('stat_fight', false); // Учавствовал
	stat_add_task('stat_lose_by_timeout' );
	stat_add_task('stat_have_damage' );
	stat_add_task('stat_fight', true); // Выйграл
	stat_add_task('stat_quest_point', 225, quest_point(2636), quest_point(2638) );
	stat_add_task('stat_have_artifact', artikul_item(3908), artikul_item(3911));
	stat_add_task('stat_puton_artifact', 3908, 3911);
	stat_add_task('stat_quest',225, true );
	stat_add_task('stat_quest_point', 226, quest_point(2640), quest_point(2684) );
	stat_add_task('stat_quest_point', 226, quest_point(2643), quest_point(2687) );
	stat_add_task('stat_have_artifact', artikul_item(1393, 1) );
	stat_add_task('stat_have_artifact', artikul_item(1393, 2) );
	stat_add_task('stat_have_artifact', artikul_item(1393, 3) );
	stat_add_task('stat_have_artifact', artikul_item(1393, 4) );
	stat_add_task('stat_have_artifact', artikul_item(1393, 5) );
	stat_add_task('stat_quest', 226, true );
	stat_add_task('stat_quest', 228, false );
	stat_add_task('stat_quest', 228, true );
	stat_add_task('stat_quest', 16,  false );
	stat_add_task('stat_have_artifact', artikul_item(348, 2) );
	stat_add_task('stat_have_artifact', artikul_item(348, 4) );
	stat_add_task('stat_have_artifact', artikul_item(348, 6) );
	stat_add_task('stat_have_artifact', artikul_item(348, 8) );
	stat_add_task('stat_have_artifact', artikul_item(348, 10) );
	stat_add_task('stat_quest', 16,  true );
	stat_add_task('stat_level', 2);
	stat_add_task('stat_level', 3);
	stat_add_task('stat_level', 4);
	stat_add_task('stat_level', 5);
}

function save_xml($stat_date, $xml_content) {
	global $SAVE_TO_FOLD;
	$folder = $SAVE_TO_FOLD."/";
	if (!file_exists($folder)) {
		if (!mkdir($folder) || !chmod($folder, 0777))
			return stat_error("Failed to create result foder `$folder`");	
	}
	else if ( !is_dir($folder) )
		return stat_error("Result foder `$folder` must be a folder, but it is't");
	$fname = "statdistr_".date("Y_m_d", $stat_date);
	$path = $folder.$fname;
	if (file_exists($path))
		unlink($path);
	if (file_put_contents($path, $xml_content)===false || !chmod($path, 0666) )
		return stat_error("Failed to save results to `$path`");
	return $fname; 
}

function xml_rotate() {
	global $SAVE_TO_FOLD, $ROTATE_MAX;
	$folder = $SAVE_TO_FOLD."/";
	if (!is_dir($folder)) return;
	$dh = opendir($folder);
	if (!$dh) return ;
	$files = array();
	while (($f = readdir($dh)) !== false) {
		$tmp = explode("_", $f);
		if (count($tmp) != 4 || $tmp[0] != "statdistr") 
			continue;
		$files[] = array($f, $tmp);
	}
	closedir($dh);
	$delete = array();
	if (count($files) >= $ROTATE_MAX) {
		uasort($files, 'file_date_compare');
		$delete = array_slice($files, 0, -$ROTATE_MAX);	
	}
	foreach ($delete as $file) {
		$path = $folder.$file[0];
		unlink($path);
	}
}

function file_date_compare($a, $b) {
	$a = $a[1];$b = $b[1];
	$a = mktime(0, 0, 0, $a[3], $a[2], $a[1]);
	$b = mktime(0, 0, 0, $b[3], $b[2], $b[1]);
	if($a < $b) return 1;	
	if($a > $b) return -1;	
	return 0;
}
	
// Добавляет таску на новую статистику
// $func имя функции, следующие параметры - ее аргументы
function stat_add_task($func) {
	$r = &$GLOBALS["__stat_tasks"];
	if (! is_array($r) ) $r = array();
	$r[] = func_get_args();
}
//Возвращает массив тасков,  
//каждый таск это тоже массив array("func_name", arg1, arg2);
function stat_task_list() {
	return $GLOBALS["__stat_tasks"];
} 

// return user list that was registered at stat date
function stat_get_user_list($start, $period) {
	global $db_auth;
	$add = sql_pholder(" AND time_registered >= ?  AND time_registered <= ? ", $start, $start + $period);
	$uids = common_list($db_auth,TABLE_AUTH, false, $add, 'uid' );
	$uids = array_keys(make_hash($uids, "uid"));
	if (empty($uids))
		return array();
	$uids = user_list(array("id"=>$uids), '', false, 'id, kind');
	$res_list = array();
	foreach($uids as $usr) {
		$ref = &$res_list[$usr["kind"]];
		if (!$ref) $ref = array( );
		$ref[] = $usr["id"];
	}
	return $res_list;
}

// Функция регестрирует ошибку, 
// всегда возарвщвет false для упрощенного вызова return stat_error('TEST');  
function stat_error($msg = '') {
	$GLOBALS["__stat_error"] = $msg;
	return false; 
}

// Возвращает false в случае если не было ошибки или текст ошибки
function stat_last_error($unset_error = true) {
	if (!isset($GLOBALS["__stat_error"]))
		return false;
	if ( $unset_error )
		$GLOBALS["__stat_error"] = false;
	return $GLOBALS["__stat_error"];
} 

//Помогает сформировать xml результат
function stat_xml_result($result) {
	$str = "";
	foreach ($result as $kind => $count)
		$str .= compose_xml_node("kind", array("id"=>$kind), $count)."\n";
	return compose_xml_node("result", null, $str); 
}
//Формирование xml
function compose_xml_attrs($atrs) {
	$res = "";
	foreach($atrs as $name=>$val) {
		if($res) $res .= " ";
		if (is_bool($val)) $val = $val ? "true" : "false";
		
		$res .= $name."=\"".htmlspecialchars($val)."\"";
	}
	return $res;
}
//Формирование xml
function compose_xml_node($node_name, $attrs, $content) {
	$attrs = count($attrs) ? compose_xml_attrs($attrs) : "";
	$node_name = trim($node_name);
	$content = trim($content);
	// just for nice view (length enoght for new line)
	if (mb_strlen($content) > 7)
	  $content = "\n$content\n";
	// nice view too ;-)
	if (mb_strlen($content))
		return sprintf("<%s%s>%s</%s>", $node_name, $attrs ? ' '.$attrs : "", $content, $node_name);
	return sprintf("<%s %s/>", $node_name, $attrs);
}
//Формирование xml в формате ответа
function response_xml( $status_code, $err_details, $data_result ) {
	global $STAT_PERIOD_START, $BENCH_START; 
	$done = time();
	$attr = compose_xml_attrs(array(
		"project"		=> SERVER_URL,
		"statistic_date" => date("m d Y", $STAT_PERIOD_START),
		"local_start_at" => date("m d Y H:i:s", $BENCH_START),
		"gmt_start_at"	=> gmdate("m d Y H:i:s", $BENCH_START),
		"gmt_done_at"	=> gmdate("m d Y H:i:s", $done),
		"taken_sec"		=> max($done - $BENCH_START, 0),
		"version"		=> STAT_REGISTER_DISTR_VER
	));
	$status_attr = compose_xml_attrs(array("code"=>$status_code));
	$err_details = htmlspecialchars($err_details);
	return "<"."?xml version=\"1.0\" encoding=\"UTF-8\"?".">\n".
		"<statistic $attr>\n".
			"<status $status_attr>$err_details</status>\n". 
			"<data>\n$data_result</data>\n".
		"</statistic>";
} 

function stat_skill_count_select($uids, $skill) {
	global $db, $NODE_NUMS;
	$data = array();
	if (empty($uids)) 
		return $data;
	foreach ($NODE_NUMS as $nn) {
		if (!NODE_SWITCH($nn)) 
			return stat_error("Failed to switch node `$nn`");
		$add = sql_pholder(" AND skill_id IN ($skill) AND value>0 AND user_id IN (?@)",$uids);
		$data = array_merge($data, common_list($db,TABLE_USER_SKILLS,false, $add, 'DISTINCT user_id as usr'));
	} 
	return $data;	
}

function stat_user_stat_skill_count_select($uids, $stat) {
	global $db, $NODE_NUMS;
	$data = array();
	if (empty($uids))
		return $data;
	foreach ($NODE_NUMS as $nn) {
		if (!NODE_SWITCH($nn))
			return stat_error("Failed to switch node `$nn`");
		$add = sql_pholder(" AND type_id = ? AND object_id IN (?@) AND value > 0 AND user_id IN (?@)", USER_STAT_TYPE_SKILL, $stat, $uids);
		$data = array_merge($data, common_list($db,TABLE_USER_STATS,false, $add, 'DISTINCT user_id as usr'));
	}
	return $data;
}

//helper for user_new_log
function stat_userlog_count_select($uids, $ref) {
 	global $db_diff;
 	//Оказывается не на всех локализациях есть эта таблица, проверяем ее наличие  
 	if ( !defined(TABLE_STAT_USER_NEW_LOGS))
 		return array();
	if (empty($uids)) return array();
	$ref["uid"] = $uids;
	$data = common_list($db_diff, TABLE_STAT_USER_NEW_LOGS, $ref, "", "COUNT(*) as cnt");
	return $data;
}
//helper - delete users from user array
// Функция удаляет пользователей из глобального массива пользователей - так чтоб они не учавствовали в след выборке
function exclude_from_usr_list(&$common_usrs, $res) {
	$return = array();
	$ids = array();
	if (!$res || empty($res))
		return array();
	foreach($res as $kind => $data) {
		$ids = array_keys(make_hash($data, 'usr'));
		$return[$kind] = count($ids);
		$ref = &$common_usrs[$kind];
		if ($ref) {
			$ref = array_diff($ref, $ids);// Исключаем ids из сдедующий запросов 		
		}
	}
	return $return; 	
}  
// Дополняет текущий результат значенияи прошлого
function increse_result($res, $last_res) {
	if (!$last_res || empty($last_res)) 
		return $res;
	foreach($last_res as $kind=>$count) {
		if (!isset($res[$kind])) 
			$res[$kind] = $count;
		else
			$res[$kind] += $count;
	}
	return $res;
}
//------------------------------------------------------------------------------
//----------------------------Реализация статитстики----------------------------
//------------------------------------------------------------------------------
// Статистика прохождения конкретного квеста
// Дополнительно параметризиуется done_flag - квес выплнен / взят но не выполнен
function stat_quest_select($uids, $quest_id, $is_done_flag) {
	global $db_2, $NODE_NUMS;
	if (empty($uids)) return array();
	$add = sql_pholder(' AND quest_id=? AND status=? AND user_id IN (?@)',$quest_id,($is_done_flag ? 2 : 1), $uids);
	$data = array();
	foreach ($NODE_NUMS as $nn) {
		if (!NODE_SWITCH($nn)) continue;
		$data = array_merge($data, common_list($db,TABLE_QUEST_USERS, false, $add, 'user_id as usr'));
	}
	return $data;
}
function stat_quest_xml($result, $quest_id, $is_done_flag) {
	$content = compose_xml_node("item", array("id"=>$quest_id, "is_done" => $is_done_flag), "")."\n";
	$content = compose_xml_node("condition", null, $content)."\n";
	return compose_xml_node("quest", null, $content.stat_xml_result($result));
}

// Статистика прохождения поинтов в квесте
function quest_point($id) { return array('id' => $id); }
function stat_quest_point_select($uids, $quest_id, $point) {
	global $db_2, $NODE_NUMS;
	$points = array_slice(func_get_args(), 2);
	if (!count($points))
		throw stat_error("Point list is empty");
	if (empty($uids)) 
		return array();
	$points_ids = array_keys(make_hash($points, 'id'));
	$add = " AND quest_id = ? AND point_id IN (?@) AND user_id IN (?@) GROUP BY user_id HAVING COUNT(quest_id) = ?";
	$add = sql_pholder($add, $quest_id, $points_ids, $uids, count($points_ids));
	$data = array();
	foreach ($NODE_NUMS as $nn) {
		if (!NODE_SWITCH($nn)) continue;
		$data = array_merge($data, common_list($db,TABLE_QUEST_USER_POINTS, false, $add , 'user_id as usr'));
	}
	return $data;
}
function stat_quest_point_xml($result, $quest_id, $points) {
	$points = array_slice(func_get_args(), 2);
	$points_str = "";
	foreach($points as $p)
		$points_str .= compose_xml_node("point", $p, "")."\n";
	$content = compose_xml_node("quest", array("id"=>$quest_id), $points_str);
	$content = compose_xml_node("condition", null, $content)."\n";
	return compose_xml_node("quest_point", null, $content.stat_xml_result($result));
}

// Считаем сколько народу достигло определенного уровня
function stat_level_select($uids, $level) {
	global $db, $NODE_NUMS;
	if (empty($uids)) 
		return array();
	$data = array();
	$add = sql_pholder(" AND level >= ? AND id IN (?@) ", $level, $uids);
	$data = user_list(false, $add, false, ' id as usr ');
	return $data;
}
function stat_level_xml($result, $level) {
	$content = compose_xml_node("level", null, intval($level))."\n";
	$content = compose_xml_node("condition", null, $content)."\n";
	return compose_xml_node("level", null, $content.stat_xml_result($result));
}

//Считает кол-во юзеров обладающих списком артифактов (с учетом их количества)
function artikul_item($id, $count = 1) { return array('id'=>$id, 'count'=>$count); }
// Сложный кверик, для двух вещей получится что-то в этом духе
// SELECT COUNT(DISTINCT owner_id)
// FROM artifacts AS t
// WHERE 1 AND user_id IN ('5', '10' ) AND 
//			`artikul_id` IN ('1','5')
// GROUP BY owner_id 
// HAVING SUM(IF(artikul_id='1', 1, 0)) > '1' AND 
//	 	  SUM(IF(artikul_id='5', 1, 0)) > 1
// Для большего вещей кол-во будет увеличиваться HAVING
function stat_have_artifact_select($uids, $artikul_item) {
	global $db, $NODE_NUMS;
	$artikuls = array_slice(func_get_args(), 1);
	$data = array();
	if (empty($artikuls)) 
		return stat_error("Artikul list is empty");
	if (empty($uids)) 
		return $data;
	foreach ($NODE_NUMS as $nn) {
		if (!NODE_SWITCH($nn)) 
			return stat_error("Failed to switch node `$nn`");
		$having = '';
		$art_ids = array();
		foreach($artikuls as $art) { 
			$art_ids[] = $art['id'];
			if ($having) $having .= ' AND ';
			$having .= sql_pholder("SUM(IF(artikul_id=?, IF(cnt,cnt,1) , 0)) >= ?", $art['id'], $art['count']);
		}
		$select = "IF(owner_id, owner_id, user_id) as usr";
		$add = sql_pholder(" AND IF(owner_id, owner_id, user_id) IN (?@)", $uids);
		$add .= " GROUP BY usr HAVING $having";
		$data = array_merge($data, common_list($db,TABLE_ARTIFACTS, array('artikul_id' => $art_ids), $add, $select));
	}
	return $data; 	
}
function stat_have_artifact_xml($result, $artikul_item) {
	$content = "";
	$artikuls = array_slice(func_get_args(), 1);
	foreach($artikuls as $art)
		$content .= compose_xml_node("item", $art, "")."\n";
	$content = compose_xml_node("condition", null, $content)."\n";
	return compose_xml_node("have_artifact", null, $content.stat_xml_result($result));
}	

// Количество зареганых
function stat_registered_select($uids) {
	$res = array();
	foreach ($uids as $id)
		$res[] = array('usr' => $id );
	return $res; 
}
function stat_registered_xml($result) { 
	return compose_xml_node("registered", null, stat_xml_result($result));
}

//// Кол-во юзеров которые успели подраться
//function stat_fight_select($uids_kind, $only_win_fight) {
//	$skill = $only_win_fight ? "'FIGHT_WIN'" : "'FIGHT_WIN', 'FIGHT_CNT', 'FIGHT_LOSS'";
//	return stat_skill_count_select($uids_kind, $skill);
//}
// Кол-во юзеров которые успели подраться полученное через статистику
function stat_fight_select($uids_kind, $only_win_fight) {
	$stat = $only_win_fight ? array(USER_STAT_OBJECT_WIN_COUNT) : array(USER_STAT_OBJECT_WIN_COUNT, USER_STAT_OBJECT_LOSS_COUNT);
	return stat_user_stat_skill_count_select($uids_kind, $stat);
}

function stat_fight_xml($result, $only_win_fight) {
	$content = compose_xml_node("only_win", array("value" =>$only_win_fight), "");
	$content = compose_xml_node("condition", null, $content)."\n";
	return compose_xml_node("fight", null, $content.stat_xml_result($result));
}

// Считает сколько народа надело артефакт(ы) 
function stat_puton_artifact_select($uids, $id) {
	global $db, $NODE_NUMS;
	$artikuls = array_slice(func_get_args(), 1);
	if (empty($artikuls)) 
		return stat_error("Artikul list is empty");
	if (empty($uids)) 
		return array();
	$data = array();
	foreach ($NODE_NUMS as $nn) {
		if (!NODE_SWITCH($nn)) 
			return stat_error("Failed to switch node `$nn`");
		$add = sql_pholder(" AND slot_id<>'' AND artikul_id IN (?@) AND  IF(owner_id, owner_id, user_id) IN (?@)", $artikuls, $uids);
		$add .= sql_pholder(" GROUP BY usr ");
		$select = "IF(owner_id, owner_id, user_id) as usr";
		$data = array_merge($data, common_list($db,TABLE_ARTIFACTS, false, $add, $select));
	}
	return $data;
}
function stat_puton_artifact_xml($result, $id) {
	$artikuls = array_slice(func_get_args(), 1);
	$content = "";
	foreach($artikuls as $art)
		$content .= compose_xml_node("item", array("id"=>$art), "")."\n";
	$content = compose_xml_node("condition", null, $content)."\n";
	return compose_xml_node("puton_artifact", null, $content.stat_xml_result($result));
}

// считает сколько нарду прошло этап загрузки флышки
function stat_flash_load_select($uids_kind, $status) {
	return stat_userlog_count_select($uids_kind, array('status'=>$status));
}
function stat_flash_load_xml($result, $status) {
	$content = compose_xml_node("status", null, $status)."\n";
	$content = compose_xml_node("condition", null, $content)."\n";
	return compose_xml_node("flash_load", null, $content.stat_xml_result($result));
}

// считает людей которые получили хотя бы одно очко повреждения
function stat_have_damage_select($uids_kind) {
	return stat_userlog_count_select($uids_kind, array('dmg'=>1));
}
function stat_have_damage_xml($result) {
	return compose_xml_node("have_damage", null, stat_xml_result($result));
}

// Считает количество народу, которые проиграли бой из-за тайм-аута
function stat_lose_by_timeout_select($uids_kind) {
	return stat_userlog_count_select($uids_kind, array('timeout'=>1));
}
function stat_lose_by_timeout_xml($result) {
	return compose_xml_node("lose_by_timeout", null, stat_xml_result($result));
}

main();

?>