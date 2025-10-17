<? # $Id: database.inc,v 1.23 2009-11-14 16:19:15 v.krutov Exp $

if (version_compare(PHP_VERSION, '7.0.0','>=')) include 'mysql.php';

require_once(dirname(__FILE__)."/pf.inc");


class Field {
	var $name = "";
	var $size = 0;
	var $type = "";
	var $flags = "";
	var $def = "";
	var $index = "";
}

class DebugSQL {
	var $sql = "";
	var $time_start = 0;
	var $time_end = 0;
	var $error = false;
	var $result = '';
	var $red = false;
}

function getmicrotime(){
	list($usec, $sec) = explode(" ",microtime());
	return ((float)$usec + (float)$sec);
}

class Database {
	var $dbh;
	var $database_name = "";
	var $host_name = "";
	var $database_user = "";
	var $database_password = "";

	var $debug_array = array();
	var $is_debug = 0;  // 0 -  нет, 1 - запросы, 2 - запросы+результаты
	var $is_file_debug = false;
	var $die_on_error = true;
	var $file_debug;
	var $connection_error='';
	var $last_error='';
	var $charset = false;
	var $no_pconnect = false;


	function connect(){
		PF_CALL();
		if ($this->dbh) return true;
		if (!($this->dbh = ($this->no_pconnect ? mysql_connect($this->host_name, $this->database_user, $this->database_password, true) : mysql_connect($this->host_name, $this->database_user, $this->database_password)))) {
			$this->connection_error="Стандартная ошибка нет подключения к базе данных: $this->host_name и база: $this->database_name ";
			PF_RET();
			die(date('[Y-m-d H:i:s] ').$this->connection_error."\n");
//			return false;
		}
		/*
		if (!mysql_select_db($this->database_name, $this->dbh)) {
			$this->connection_error="Can't connect to database: $this->database_name";
			PF_RET();
			return false;
		}*/
		if ($this->charset) mysql_query("SET NAMES '".$this->charset."'", $this->dbh);
		PF_RET();
		return true;
	}

	function Database($host_name, $database_name, $database_user, $database_password, $charset=false, $no_pconnect=false) {
		$this->host_name = $host_name;
		$this->database_name = $database_name;
		$this->database_user = $database_user;
		$this->database_password = $database_password;
		// если кодировка - utf-8, то нужно привести к виду, приемлимому клиентом
		if (strtoupper($charset) == 'UTF-8') $charset = 'UTF8';
		$this->charset = $charset;
		$this->no_pconnect = $no_pconnect;
		if ($this->is_file_debug) {
			$this->file_debug = fopen($_SERVER['DOCUMENT_ROOT']."/database.log", "a+");
		}
		$this->init();
	}

	function close() {
		mysql_close($this->dbh);
		fclose($file_debug);
	}

	function init() {
	}

	function error() { # get last error
		return count($this->debug_array)? $this->debug_array[count($this->debug_array)-1]->error : false;
	}

# execute sql string
# additional ~ "and visible=0 limit 100,10"
	function execSQL($sql, $additional = "") {
		if (!$this->dbh && !$this->connect()) return false;

		PF_CALL();
		if ($this->is_debug) {
			$item = new DebugSQL();
			$item->sql = $sql . " " . $additional;
			$item->time_start = getmicrotime();
		}

		if ($additional) $sql .= " " . $additional;
		$sql = '/* '.$_SERVER['PHP_SELF'].' */ '.$sql;
		if (!(@$result = mysql_db_query($this->database_name, $sql, $this->dbh))) {
			$is_error = true;
			$error = mysql_error($this->dbh);
			if (!mysql_ping($this->dbh)) { // если нет соединения попробовать пересоединиться и повторить
				if(!$this->connect(true)) die($this->connection_error);
				if (@$result = mysql_db_query($this->database_name, $sql, $this->dbh)) {
					$is_error = false;
				}
			}
			if ($is_error) {
				$this->last_error = $error;
				if ($this->is_debug) {
					$item->error = $this->last_error;
				}
				if ($this->die_on_error) {
					$debug_info = debug_backtrace();
                    $code_error = md5(time_current().'_error');
					$f = '[#'.$code_error.'] ';
					for ($i = count($debug_info)-1; $i >= 0; $i--) $f .= $debug_info[$i]['function'].'()->';
					$error_text = $f.'  '.$debug_info[0]['file'].':'.$debug_info[0]['line'].': '.$debug_info[1]['function'].'()'.sprintf("[%s] #%s %s (%s)", date('Y-m-d H:i:s'), mysql_errno(), $this->last_error, $sql);
					error_log($error_text);
					logfile(NODE_FILE_LOG, $error_text);
					die((defined('DEVS') && DEVS) ? $error_text : '<div class="debug_error_class_for_find_him" style="border: 1px #ff0000 solid;padding: 3px;margin: 3px;border-radius: 5px;background: #efecec;">Возникла ошибка [<b style="color:red;">#'.$code_error.'</b>] сообщите этот код администратору для решения проблемы!</div>');
				}
			}
		}
		if ($this->is_debug) {
			$item->time_end = getmicrotime();
					if($this->is_debug==2 && !$item->error){
									$list=array();
									while (@$row = mysql_fetch_array($result)) $list[] = $row;
									@(mysql_data_seek($result,0));
#                    $item1=new DebugSQL();
#                  $id="deb".count($this->debug_array);
#                  $item->sql.="<br><a href=# onclick='dchange(\"$id\")'>Результат:</a>";
#                  $item->sql.="<div ID=$id style=display:none><pre>".var_export($list, true)."</pre></div>";
									@($item->result=var_export($list, true));
#                  $this->debug_array[] = $item1;
					}
					$this->debug_array[] = $item;
		}

		if ($this->is_file_debug) {
			$debug_info = debug_backtrace();
			$f = '';
			for ($i = count($debug_info)-1; $i >= 0; $i--) $f .= $debug_info[$i]['function'].'()->';
			fwrite($this->file_debug,
				"\n".date("H:i:s").": (".($item->time_end-$item->time_start).') '.
				$debug_info[0]['file'].':'.$debug_info[0]['line'].': '.$debug_info[1]['function'].'() ('.$f.')'."\n".
				$sql . " " . $additional."\n"
			);
		}
		PF_RET();
		return $result;
	}

	function getTables() {
		if (!$this->dbh && !$this->connect()) return false;

		$list = array();
		$result = mysql_list_tables($this->database_name, $this->dbh);
		$n = mysql_num_rows($result);
		for ($i = 0; $i < $n; $i++) {
			$list[] = strtoupper(mysql_tablename($result, $i));
		}
		mysql_free_result($result);
		return $list;
	}

	function getFields($table_name) {
		if (!$this->dbh && !$this->connect()) return false;

		$list = array();
		$result = mysql_list_fields($this->database_name, $table_name, $this->dbh);
		$n = mysql_num_fields($result);
		$res = mysql_query("desc $table_name", $this->dbh);
		$i = 0;
		while (($row = mysql_fetch_object($res))) {
			$field = new Field();
			$obj = mysql_fetch_field($result, $i);
			$field->name = $row->Field;
			$field->size = mysql_field_len($result, $i);
			$field->type = strtoupper($row->Type);
			if ($obj->not_null) $field->flags .= " NOT NULL ";
			if ($obj->primary_key) $field->flags .= " PRIMARY KEY ";
			if ($obj->unique_key) $field->flags .= " UNIQUE KEY ";
			if ($obj->multiple_key) $field->index = $field->name;
			$inc = mysql_field_flags($result, $i);
			if (strpos($inc, "auto_increment") > 0) $field->flags .= " AUTO_INCREMENT ";
			$field->def = $obj->def;
			$list[] = $field;
			$i++;
		}
		mysql_free_result($res);
		mysql_free_result($result);
		return $list;
	}

	function TableExists($table_name) {
		if (!($this->execSQL("SELECT COUNT(*) FROM $table_name"))) {
			return false;
		}
		return true;
	}

	function FieldExists($table_name, $field_name) {
		if (!($this->execSQL("SELECT COUNT($field_name) FROM $table_name"))) {
			return false;
		}
		return true;
	}


	function printDebug() {
		print $this->getDebug();
	}

	function addDebug($str='--------') {
		$item=new DebugSQL();
		$item->sql=$str;
		$item->red=true;
		$this->debug_array[] = $item;
	}

	function getDebug() {
		$result = '';
		if (!$this->is_debug) return;
		$sum = 0;

		$result .= '<SCRIPT>function dchange(id){gebi(id).style.display= (gebi(id).style.display=="none"?"block":"none");return false}function gebi(id){return document.getElementById(id)}</SCRIPT><pre><ol>';
		for ($i = 0; $i < count($this->debug_array); $i++) {
			$item = $this->debug_array[$i];
			$result .= "<li".($item->red?' style=color:red':'').">".htmlspecialchars(stripslashes($item->sql)).": time=".($item->time_end-$item->time_start)."\n";
			if(($item->result)){
									$id="deb$i";
									$result .= "<a href=# onclick='return dchange(\"$id\")'>Result:</a>";
									$result .= "<div ID=$id style=display:none>".htmlspecialchars(stripslashes($item->result))."</div>\n\n";
			}
			$sum += ($item->time_end-$item->time_start);
		}
		$result .= "<li>total: time=".$sum."\n";
		$result .= "</ol></pre>\n";

		return $result;
	}

	function clearDebug() {
		$this->debug_array = array();
	}

# ******************************************************************************

	function db_GetQueryArray($sql, &$result)  {

        register_shutdown_function('shutdown_function');

		$result = array();
		if (!($db_result = $this->execSQL($sql))) return false;
		while (is_array($row = mysql_fetch_array($db_result, MYSQL_ASSOC))) $result[] = $row;
		mysql_free_result($db_result);
		return true;
	}

	function db_GetQueryRow($sql, &$result)  {
		$result = array();
		if (!($db_result = $this->execSQL($sql))) return false;
		if (is_array($row = mysql_fetch_array($db_result, MYSQL_ASSOC))) $result = $row;
		mysql_free_result($db_result);
		return true;
	}

	function db_GetQueryCol($sql, &$result)  {
		$result = array();
		if (!($db_result = $this->execSQL($sql))) return false;
		while (is_array($row = mysql_fetch_array($db_result))) $result[] = $row[0];
		mysql_free_result($db_result);
		return true;
	}

	function db_GetQueryHash($sql, &$result)  {
		$result = array();
		if (!($db_result = $this->execSQL($sql))) return false;
		while (is_array($row = mysql_fetch_array($db_result))) $result[$row[0]] = $row[1];
		mysql_free_result($db_result);
		return true;
	}

	function db_GetQueryVal($sql, &$result, $default='')  {
		$result = $default;
		if (!($db_result = $this->execSQL($sql))) return false;
		$row = mysql_fetch_array($db_result);
		mysql_free_result($db_result);
		if (!$row) return false;
		$result = $row[0];
		return true;
	}

	function db_ExecQuery($sql)  {
		if (!($db_result = $this->execSQL($sql))) return false;
		$res = mysql_affected_rows($this->dbh);
//	  mysql_free_result($db_result);
		if (!$res) $res=-1;
		return $res;
	}

	function insert_id() {
		return mysql_insert_id($this->dbh);
	}

	function affected_rows() {
		return mysql_affected_rows($this->dbh);
	}
	
	function db_NextRow(&$result) {
		return mysql_fetch_array($result, MYSQL_ASSOC);
	}
}

?>