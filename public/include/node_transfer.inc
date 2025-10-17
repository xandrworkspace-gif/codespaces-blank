<? # $Id: node_transfer.inc,v 1.17 2009-09-30 10:50:16 vadim_b Exp $

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
* Система узлового транспорта данных
* Sen, it-territory, 2006
* * * * * * * * * * * * * * * * * * * * * * * * * * * * */


function NODE_TRANSFER_USER($src, $dst, $user_id) {
	global $NODE_NUMS;
	if (!$NODE_NUMS[$src] || !$NODE_NUMS[$dst] || ($src == $dst) || !$user_id) {
		_node_log("[NODE_TRANSFER_USER]: Invalid arguments (transfer: $src => $dst, user_id: $user_id)");
		return false;
	}
	if (!_node_transfer($src,$dst,array('artifacts','sessions','user_achievements','user_actions','user_skills','user_spells','user_stats','pets','quest_users','quest_user_points', 'smile_users'),false,$user_id)) {
		_node_log("[NODE_TRANSFER_USER]: Can't transfer the user (transfer: $src => $dst, user_id: $user_id)");
		return false;
	}
	return true;
}

function NODE_TRANSFER_AREA($src, $dst, $area_id) {
	global $NODE_NUMS;
	if (!$NODE_NUMS[$src] || !$NODE_NUMS[$dst] || ($src == $dst) || !$area_id) {
		_node_log("[NODE_TRANSFER_AREA]: Invalid arguments (transfer: $src => $dst, area_id: $area_id)");
		return false;
	}
	if (!_node_transfer($src,$dst,array('bots'),false,$area_id)) {
		_node_log("[NODE_TRANSFER_AREA]: Can't transfer the area (transfer: $src => $dst, area_id: $area_id)");
		return false;
	}
	return true;
}

function NODE_TRANSFER_ARTIFACT($src, $dst, $artifact_id) {
	global $NODE_NUMS;
	if (!$NODE_NUMS[$src] || !$NODE_NUMS[$dst] || ($src == $dst) || !$artifact_id) {
		_node_log("[NODE_TRANSFER_ARTIFACT]: Invalid arguments (transfer: $src => $dst, artifact_id: $artifact_id)");
		return false;
	}
	if (!_node_transfer($src,$dst,array('artifacts'),$artifact_id)) {
		_node_log("[NODE_TRANSFER_ARTIFACT]: Can't transfer the artifact (transfer: $src => $dst, artifact_id: $artifact_id)");
		return false;
	}
	return true;
}

// =================================================================================================

function _node_transfer($src, $dst, $tables, $ref=false, $link=false) {
	global $db, $db_nodes, $NODE_NUMS;
	$stime = microtime(true);
	
	if (!$NODE_NUMS[$src] || !$NODE_NUMS[$dst] || ($src == $dst) || !$tables || (!$ref && !$link)) {
		_node_log("[_node_transfer]: Invalid arguments ('$src', '$dst', '$tables', '$ref', '$link')");
		return false;
	}

	// подготавливаем массив запросов
	$sql_data = array();
	foreach ($tables as $table) {
		if (!_node_prepare_sql($sql_data,$src,$dst,$table,$ref,$link)) {
			_node_log("[_node_transfer]: Can't prepare SQL set");
			return false;
		}
	}

	// выполняем перенос данных
	$same_server = $db_nodes[$src]->host_name == $db_nodes[$dst]->host_name;
	$dels = array();
	$upds = array();
	foreach ($sql_data as $sql_item) {
		list($sel,$rpl,$del,$upd) = $sql_item;
		if ($same_server) { // один запрос в рамках одного сервера
			$query = $rpl." ".$sel;
			$temp = $db_nodes[$dst]->die_on_error;
			$db_nodes[$dst]->die_on_error = false;
			$res = $db_nodes[$dst]->execSQL($query);
			$db_nodes[$dst]->die_on_error = $temp;
			if (!$res) {
				_node_log("[_node_transfer]: Can't exec SQL (rpl sel) (".$db_nodes[$dst]->last_error.") - ".$query);
				return false;
			}
		} else {	// узлы на разных серверах
			$cnt = 0;
			for ( ;; ) {
				$temp = $db_nodes[$src]->die_on_error;
				$db_nodes[$src]->die_on_error = false;
				$res = $db_nodes[$src]->db_GetQueryArray($sel." LIMIT ".$cnt.",500",$data);
				$db_nodes[$src]->die_on_error = $temp;
				if (!$res) {
					_node_log("[_node_transfer]: Can't exec SQL (sel) (".$db_nodes[$src]->last_error.") - ".$sel);
					return false;
				}
				if (!$data) break;
				$cnt += count($data);
				$query = $rpl." VALUES ";
				foreach ($data as $k=>$item) $query .= ($k ? ',' : '')."('".implode("','",array_map("addslashes",$item))."')";
				$temp = $db_nodes[$dst]->die_on_error;
				$db_nodes[$dst]->die_on_error = false;
				$res = $db_nodes[$dst]->execSQL($query);
				$db_nodes[$dst]->die_on_error = $temp;
				if (!$res) {
					_node_log("[_node_transfer]: Can't exec SQL (rpl) (".$db_nodes[$dst]->last_error.") - ".$query);
					return false;
				}
			}
			if (!$cnt) continue;
		}
		$dels[] = $del;
		if ($upd) $upds[] = $upd;
	}

	// Если перс переезжал больше половины времени лока - скорее всего мы не успеем перевезти юзера до конца лока
	// Пока ставим заглушку
	if (microtime(true) - $stime > 30) {
		_node_log("[_node_transfer]: replaces exceeded half lock time");
		return false;
	}
	
	
	// изменяем данные в cross-node таблицах
	foreach ($upds as $upd) {
		$temp = $db->die_on_error;
		$db->die_on_error = false;
		$res = $db->execSQL($upd);
		$db->die_on_error = $temp;
		if (!$res) {
			_node_log("[_node_transfer]: Can't exec SQL (upd) (".$db->last_error.") - ".$upd);
			return false;
		}
	}

	// удаляем данные в исходном узле
	foreach ($dels as $del) {
		$temp = $db_nodes[$src]->die_on_error;
		$db_nodes[$src]->die_on_error = false;
		$res = $db_nodes[$src]->execSQL($del);
		$db_nodes[$src]->die_on_error = $temp;
		if (!$res) {
			_node_log("[_node_transfer]: Can't exec SQL (del) (".$db_nodes[$src]->last_error.") - ".$del);
			return false;
		}
	}
	return true;
}

function _node_prepare_sql(&$sql_data, $src, $dst, $table_name, $refs=false, $links=false) {
	global $db_nodes, $_node_struct, $NODE_NUMS;
	if (!$NODE_NUMS[$src] || !$NODE_NUMS[$dst] || ($src == $dst) || !$table_name || (!$refs && !$links)) {
		_node_log("[_node_prepare_sql]: Invalid arguments ('$src', '$dst', '$table_name', '$refs', '$links')");
		return false;
	}
	$src_dbname = $db_nodes[$src]->database_name;
	$dst_dbname = $db_nodes[$dst]->database_name;
	list($ref_name,$cross_node,$link_name,$tables,$fields) = $_node_struct[$table_name];
	if (!$link_name || !$fields) {
		_node_log("[_node_prepare_sql]: Invalid table struct (table: $table_name, struct: ['$ref_name', '$link_name', '$tables', '$fields'])");
		return false;
	}
	$where = "WHERE 1";
	if ($refs) $where .= is_array($refs) ? " AND ".$ref_name." IN ('".implode("','",$refs)."')" : " AND ".$ref_name."='".$refs."'";
	if ($links) $where .= is_array($links) ? " AND ".$link_name." IN ('".implode("','",$links)."')" : " AND ".$link_name."='".$links."'";
	$sel = "SELECT ".$fields." FROM ".$src_dbname.".".$table_name." ".$where.($ref_name ? " ORDER BY ".$ref_name : "");
	$rpl = "REPLACE INTO ".$dst_dbname.".".$table_name.($fields != '*' ? " (".$fields.")" : "");
//	$rpl = "INSERT INTO ".$dst_dbname.".".$table_name.($fields != '*' ? " (".$fields.")" : "");	// для теста надежности!
	$del = "DELETE FROM ".$src_dbname.".".$table_name." ".$where;
	$upd = "";
	if ($cross_node || $tables) {
		$refs = array();
		$db_nodes[$src]->db_GetQueryCol("SELECT ".$ref_name." FROM ".$table_name." ".$where,$refs);
		if ($refs) {
			if ($cross_node) $upd = "UPDATE id_".$table_name." SET node_num='".$dst."' WHERE id IN ('".implode("','",$refs)."')";
			if ($tables) {
				$tables = preg_split("/[\s,;]+/",$tables);
				foreach ($tables as $table) {
					if (!_node_prepare_sql($sql_data,$src,$dst,$table,false,$refs)) return false;
				}
			}
		}
	}
	$sql_data[] = array($sel,$rpl,$del,$upd);
	return true;
}

?>
