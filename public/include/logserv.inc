<? # $Id: logserv.inc,v 1.25 2008-08-13 11:05:00 sen Exp $

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * *
* База данных лог-сервиса
* Sen, it-territory, 2007
* * * * * * * * * * * * * * * * * * * * * * * * * * * * */

define('LOGSERV_ROOT', 'logserv/');
define('LOGSERV_STORAGE', LOGSERV_ROOT.'storage/');
define('LOGSERV_CACHE', LOGSERV_ROOT.'cache/');


// lsTable
define('LS_READBUF_MIN',     70000);	// 70Kb
define('LS_READBUF_MAX',   2000000);	// 2Mb
define('LS_INDEX_PAGE',         50);	// rows

// lsContainer
define('LS_MAX_SELECT_CNT',  50000);
define('LS_CACHE_TIME',         60);	// seconds

define('LS_RU_OLDENCODING_DATE', mktime(5, 0, 0, 7, 18, 2011));

// ====== uncomment for PHP 4 ======
if (!function_exists('array_combine')) {
	function &array_combine($keys, $values) {
		$out = array();
		foreach ($keys as $k) {
			$out[$k] = current($values);
			next($values);
		}
		return $out;
	}
}
// =================================


function lsError($str) {
	$bt = debug_backtrace();
	$bt = $bt[1];
	$str = sprintf("(%s:%d) %d [%s]: %s",basename($bt['file']),$bt['line'],getmypid(),($bt['class'] ? $bt['class'].'::' : '').$bt['function'],$str);
	logfile(PATH_LOGS.'logserv.log',$str);
//	error_log($str);
}

function &lsBitVal($x) {	// код разбиения на множители и подсчета кол-ва битов
	$out = array();
	$n = 0;
	while ($x > 0) {
		$n++;
		$out[] = $x & ~($x - 1);
		$x = $x & ($x - 1);
	}
	return $out;
}

function &lsPackInt(&$arr) {
	if (!$arr) return '';
	ksort($arr);
	$keys = array_sum(array_keys($arr));
	$values = array_values($arr);
	$cnt = count($values);
	array_unshift($values,$keys);
	array_unshift($values,'l'.($cnt+1));
	$data = call_user_func_array('pack',$values);
	return $data;
}

function &lsPackStr(&$arr) {
	if (!$arr) return '';
	ksort($arr);
	$keys = array_sum(array_keys($arr));
	$values = array_values($arr);
	$data = pack('l',$keys).implode("\x00",$values);
	return $data;
}

function &lsUnpackInt(&$data) {
	if (!$data) return false;
	$arr = array();
	$keys = unpack('l',$data);
	$keys = reset($keys);
	if ($keys) {
		$keys = lsBitVal($keys);
		$cnt = count($keys);
		$values = unpack('l'.$cnt,substr($data,4));
		$arr = array_combine($keys,$values);
	}
	return $arr;
}

function &lsUnpackStr(&$data) {
	if (!$data) return false;
	$arr = array();
	$keys = unpack('l',$data);
	$keys = reset($keys);
	if ($keys) {
		$keys = lsBitVal($keys);
		$values = explode("\x00",substr($data,4));
		$arr = array_combine($keys,$values);
	}
	return $arr;
}

function &lsFileRange($time1=false, $time2=false, $path=false) {
	$out = array();
	if ($path) {
		$files = glob($path.'*.dat.gz');
		if (!$files) {
			return $out;
		}
		sort($files);
		if ($time1) $d1 = date('Y-m',$time1);
		if ($time2) $d2 = date('Y-m',$time2);
		foreach ($files as $fn) {
			$fn = basename($fn);
			$d = reset(explode('.',$fn));
			$t = array_map('intval',explode('-',$d));
			// 1 - первый день месяца (при дне == 0, получается последний день текущего месяца)
			$t = mktime(0,0,0,$t[1],1,$t[0]);
			if (($d1 && (strcmp($d,$d1) < 0)) || ($d2 && (strcmp($d,$d2) > 0))) continue;
			$out[$t] = $fn;
		}
	} else {
		// 1 - первый день месяца (при дне == 0, получается последний день текущего месяца)
		$time2 = mktime(0,0,0,date('m',$time2)+1, 1, date('Y',$time2));
		while (($time1 > 0) && ($time1 < $time2)) {
			$d = date('Y-m',$time1);
			list($year, $month) = array_map('intval', explode('-', $d));
			$t = mktime(0,0,0,$month,1,$year);
			$out[$t] = $d.'.dat.gz';
			$time1 = mktime(0,0,0,$month+1,1,$year);
		}
	}
	return $out;
}


// lsTable ===============================================================================================

class lsTable {
	var $name;
	var $fd;
	var $idxfd;
	var $idxRowCnt;
	var $isMemoryStream;
	var $isMemoryFlush;
	
	function lsTable($name=false, $create=false, $createIdx=false) {
		if ($name) $this->open($name,$create,$createIdx);
	}

	function open($name, $create=false, $createIdx=false) {
		if (!$name) return false;
		$this->close();
		if ($create && !file_exists($name)) {
			touch($name);
			if ($createIdx) touch($name.'.idx');
		}
		//TODO: 41705 Архивирование данных логсервиса 
		// fopen не может открывать архивные файлы в режиме `r+` (чтение запись)
		// поэтому приходиться работать с потоком в памяти, который, позже будет сохранен на диск с 
		// помощью ф-ии flushMemory
		// старый алгоритм работы с неархивными данными остался неизменным (блок else)
		// для неархивных файлов flushMemory ничего не делает 
		$this->isMemoryStream = false;
		if (pathinfo($name, PATHINFO_EXTENSION) == "gz") {
			$handle = @fopen("compress.zlib://".$name,'rb');
			if ($handle) {
				$this->fd = fopen("php://memory", "r+b"); 
				while(!feof($handle)) {
					fwrite($this->fd, fread($handle, 1024)); 
				}
				fclose($handle);
				fseek($this->fd, 0);
				$this->isMemoryStream = true;
				$this->isMemoryFlush = true;
			}
		}
		else {
			$this->fd = @fopen($name, "r+b"); 
		}
		if (!$this->fd) {
			lsError("Can't open file: $name");
			return false;
		}
		
		$this->idxRowCnt = 0;
		$this->idxfd = @fopen($name.'.idx','r+b');
		if ($this->idxfd) {
			$data = fread($this->idxfd,4);
			if (strlen($data) == 4) $this->idxRowCnt = reset(unpack('l',$data));
		}
		$this->name = $name;
		return true;
	}
	function flushMemory() {
		if (!$this->isMemoryStream || $this->isMemoryFlush || !$this->name || !$this->fd) {
			return;
		}
		$this->isMemoryFlush = true;
		$open_path = $this->name;
		if (pathinfo($open_path, PATHINFO_EXTENSION) == "gz") {
			$open_path = "compress.zlib://".$open_path;
		}
		$fstat = fstat($this->fd);
		if (!$fstat['size']) {
			return;
		} 
		fseek($this->fd, 0);
		$handle = @fopen($open_path,'wb');
		if (!$handle) {
			lsError("Can't flush to file: $name");
			return false;
		}
		while (!feof($this->fd)) {
			fwrite($handle, fread($this->fd, 1024));
		}
		fclose($handle);
	}
	
	function close() {
		if (!$this->fd) return false;
		$this->flushMemory();
		fclose($this->fd);
		unset($this->fd);
		if ($this->idxfd) {
			fclose($this->idxfd);
			unset($this->idxfd);
		}
		return true;
	}

	function truncate() {
		if (!$this->fd) return false;
		ftruncate($this->fd,0);
		if ($this->idxfd) ftruncate($this->idxfd,0);
		$this->idxRowCnt = 0;
		return true;
	}

	function setTime($t=false) {
		if (!$this->fd) return false;
		if (!$t) $t = time();
		touch($this->name,$t);
		if ($this->idxfd) touch($this->name.'.idx',$t);
		return true;
	}

	function appendRow($int=false, $str=false) {
		if (!$this->fd) return false;
		$int_data = lsPackInt($int);
		$str_data = lsPackStr($str);
		$int_size = strlen($int_data);
		$str_size = strlen($str_data);
		if ($str_size > 0xFFFF) {
			lsError("String chunk size is too large (name: $this->name, size: $str_size, data: '$str_data')");
			return false;
		}
		$data = pack('S2',$int_size,$str_size).$int_data.$str_data;
		return $this->_writeRowData($data);
	}

	function fetchRow(&$int, &$str) {
		if (!$this->fd) return false;
		$int = $str = false;
		$data = fread($this->fd,4);
		if (strlen($data) != 4) return 0;	// eof
		list(,$int_size,$str_size) = unpack('S2',$data);
		$size = $int_size + $str_size;
		if (!$size) return 4;
		$data = fread($this->fd,$size);
		if ($int_size) $int = lsUnpackInt(substr($data,0,$int_size));
		if ($str_size) $str = lsUnpackStr(substr($data,$int_size,$str_size));
		return $size + 4;
	}

	function selectRows(&$result, &$table, $condInt=false, $condStr=false, $limit=0) {
		if (!$this->fd) return false;
		$out_result = isset($result);
		$out_table = isset($table);
		if (!$out_result && !$out_table) return false;	// no output selected
		if (!is_array($condInt)) $condInt = array();
		if (!is_array($condStr)) $condStr = array();
		$cond_int_keys = array_sum(array_keys($condInt));
		$cond_str_keys = array_sum(array_keys($condStr));
		$limit = intval($limit);

		$buf = '';
		$buf_pos = $buf_size = 0;
		$eof = false;
		$row_cnt = 0;
		$row_buf = '';
		do {
			// Reading in advance
			$buf_left = $buf_size - $buf_pos;
			if (($buf_left < LS_READBUF_MIN) && !$eof) {
				$data = fread($this->fd,LS_READBUF_MAX);
				if ($data) {
					$buf = substr($buf,$buf_pos,$buf_left).$data;
					$buf_size = strlen($buf);
					$buf_pos = 0;
					$br++;
				} else {
					$eof = true;
				}
			}
			if ($buf_pos >= $buf_size) break;

			// Extracting row data
			list(,$int_size,$str_size) = unpack('S2',substr($buf,$buf_pos,4));
			$size = $int_size + $str_size;
			if ($out_table) $row_buf = substr($buf,$buf_pos,$size+4);
			$buf_pos += 4;
			$data = substr($buf,$buf_pos,$size);
			$buf_pos += $size;

			// Examining the row
			$int = $str = false;
			if ($cond_int_keys) {	// checking int key conditions
				if (!$int_size) continue;
				$int_keys = reset(unpack('l',substr($data,0,4)));
				if (($cond_int_keys & $int_keys) != $cond_int_keys) continue;
			}
			if ($cond_str_keys) {	// checking str key conditions
				if (!$str_size) continue;
				$str_keys = reset(unpack('l',substr($data,$int_size,4)));
				if (($cond_str_keys & $str_keys) != $cond_str_keys) continue;
			}
			if ($out_result || $cond_int_keys) {
				if ($int_size) $int = lsUnpackInt(substr($data,0,$int_size));
				foreach ($condInt as $k=>$v) {	// checking int value conditions
					if (!isset($v)) continue;
					if (is_array($v)) {
						if (isset($v['in'])) $f = in_array($int[$k],$v['in']);	// 'in' - in set
						elseif (isset($v['ne'])) $f = ($int[$k] != $v['ne']);		// 'ne' - not equal
						elseif (isset($v['le'])) $f = ($int[$k] <= $v['le']);		// 'le' - less or equal
						elseif (isset($v['ge'])) $f = ($int[$k] >= $v['ge']);		// 'ge' - greater or equal
						elseif (isset($v['range'])) $f = ($int[$k] >= $v['range'][0]) && ($int[$k] <= $v['range'][1]);	// 'range' - in range
					} else $f = ($int[$k] == $v);	// 'eq' - equal
					if (!$f) continue 2;
				}
			}
			if ($out_result || $cond_str_keys) {
				if ($str_size) $str = lsUnpackStr(substr($data,$int_size,$str_size));
				// Костыль для записей логсервиса россии,
				// которые старше перехода на utf8
				if ($str_size && _TRANS_LANG == 'ru' && $int[1] < LS_RU_OLDENCODING_DATE) {
					foreach ($str as $k => $v) $str[$k] = iconv('cp1251','UTF-8',$v);
				}
				foreach ($condStr as $k=>$v) {	// checking str value conditions
					if (!isset($v)) continue;
					if (is_array($v)) {
						if (isset($v['in'])) $f = in_array($str[$k],$v['in']);	// 'in' - in set
						elseif (isset($v['ne'])) $f = ($str[$k] != $v['ne']);		// 'ne' - not equal
					} else $f = ($str[$k] == $v);	// 'eq' - equal
					if (!$f) continue 2;
				}
			}
			if ($out_result) $result[] = array($int,$str);
			if ($out_table) $table->_writeRowData($row_buf);
			$row_cnt++;
			if ($limit && ($row_cnt >= $limit)) break;
		} while (1);
		return $row_cnt;
	}

	function idxRowCnt() {
		if (!$this->fd || !$this->idxfd) return false;
		return $this->idxRowCnt;
	}

	function idxJump($rowNum) {
		if (!$this->fd || !$this->idxfd) return false;
		if ($rowNum < 0) return false;
		if ($rowNum >= $this->idxRowCnt) {
			fseek($this->fd,0,SEEK_END);
			return true;
		}
		$offset = intval($rowNum / LS_INDEX_PAGE)*4 + 4;
		fseek($this->idxfd,$offset);
		$data = fread($this->idxfd,4);
		if (strlen($data) != 4) return false;
		$offset = reset(unpack('l',$data));
		fseek($this->fd,$offset);
		$n = $rowNum % LS_INDEX_PAGE;
		while (($n-- > 0) && $this->_skipRow());
		return true;
	}

	// ------------------------------------------------------------------------------

	function _skipRow() {
		$data = fread($this->fd,4);
		if (strlen($data) != 4) return false;
		list(,$int_size,$str_size) = unpack('S2',$data);
		$size = $int_size + $str_size;
		fseek($this->fd,$size,SEEK_CUR);
		return true;
	}

	function _writeRowData(&$data) {
		fseek($this->fd,0,SEEK_END);
		if ($this->idxfd) {
			if (!$this->idxRowCnt) {	// initializing the index file
				rewind($this->idxfd);
				fwrite($this->idxfd,pack('l',0));
			}
			if (!($this->idxRowCnt % LS_INDEX_PAGE)) {
				fseek($this->idxfd,0,SEEK_END);
				fwrite($this->idxfd,pack('l',ftell($this->fd)));
			}
			$this->idxRowCnt++;
			rewind($this->idxfd);
			fwrite($this->idxfd,pack('l',$this->idxRowCnt));
		}
		$this->isMemoryFlush = false;
		return fwrite($this->fd,$data);
	}

}


// lsContainer ===========================================================================================

class lsContainer {
	var $uid;
	var $path;
	var $pathGrp;
	var $useOpenCache = false;
	var $openTableList; // Используется для кэширование указателей на открытые таблицы
	function lsContainer($uid=false) {
		if ($uid) $this->uid = $uid;
		$this->openTableList = array();
	}

	function open($uid=false) {
		if ($uid) $this->uid = $uid;
		if (!$this->uid) return false;
		$this->close();
		$grp = intval($this->uid/10000);
		$this->path = LOGSERV_STORAGE.sprintf('%04d/u%08d/',$grp,$this->uid);
		$this->pathGrp = LOGSERV_STORAGE.sprintf('%04d/',$grp);
		return true;
	}

	function close() {
		foreach($this->openTableList as $table => $data) {
			if ($data['handle']) {
				$data['handle']->close();
			}
		}
		$this->openTableList = array();
		if (!$this->path) return false;
		unset($this->path);
		return true;
	}

	function truncate() {
		if (!$this->path) return false;
		if (file_exists($this->pathGrp)) {
			if (file_exists($this->path)) {
				foreach (lsFileRange(false,false,$this->path) as $name) {
					if (!unlink($this->path.$name)) {
						lsError("Can't delete file: ".$this->path.$name);
						return false;
					}
				}
				if (!rmdir($this->path)) {
					lsError("Can't delete directory: $this->path");;
					return false;
				}
			}
			if (!count(glob($this->pathGrp.'*')) && !rmdir($this->pathGrp)) {
				lsError("Can't delete directory: $this->pathGrp");;
				return false;
			}
		}
		return true;
	}

	function saveRecord($int=false, $str=false, $ctime=false) {
		if (!$this->path) return false;
		if (!file_exists($this->pathGrp)) {
			if (!mkdir($this->pathGrp,0775)) {
				lsError("Can't make directory: $this->pathGrp");
				return false;
			}
		}
		if (!file_exists($this->path)) {
			if (!mkdir($this->path,0775)) {
				lsError("Can't make directory: $this->path");
				return false;
			}
		}
		if (!$ctime) $ctime = time();
		$name = $this->path.reset(lsFileRange($ctime,$ctime));
		$table = $this->openTable($name,true);
		if (!$table) {
			lsError("Can't open table: $name");
			return false;
		}
		if (!$table->appendRow($int,$str)) {
			lsError("Can't append row: $name");
			return false;
		}
 		$this->closeTable($table);
		return true;
	}

	function selectRecords(&$data, $timeStart=false, $timeEnd=false, $condInt=false, $condStr=false) {
		if (!$this->path || !is_array($data)) return false;
		$row_cnt = 0;
		$files = lsFileRange($timeStart,$timeEnd,$this->path);
		foreach ($files as $fn) {
			$name = $this->path.$fn;
			$table = $this->openTable($name);
			if (!$table) {
				lsError("Can't open table: $name");
				break;
			}
			$result = array();
			$cnt = $table->selectRows($result,$null,$condInt,$condStr,(LS_MAX_SELECT_CNT-$row_cnt));
			$this->closeTable($table);
			if (!$cnt) continue;
			$data = array_merge($data,$result);
			$row_cnt += $cnt;
			if ($row_cnt >= LS_MAX_SELECT_CNT) break;
		}
		return $row_cnt;
	}

	// ------------------------------------------------------------------------------

	function cacheTable($cacheKey) {
		if (!$cacheKey) return false;
		return LOGSERV_CACHE.$cacheKey.'.dat';
	}

	function cacheTryLock($cacheKey) {
		if (!$cacheKey) return false;
		$f = @fopen($this->cacheTable($cacheKey).'.tmp',"x");
		return !empty($f);
	}

	function cacheFreeLock($cacheKey) {
		if (!$cacheKey) return false;
		return @unlink($this->cacheTable($cacheKey).'.tmp');
	}

	function cacheIsAvail($cacheKey) {
		if (!$cacheKey) return false;
		return @file_exists($this->cacheTable($cacheKey));
	}

	function cacheIsValid($cacheKey) {
		if (!$cacheKey) return false;
		return @filemtime($this->cacheTable($cacheKey)) > (time() - LS_CACHE_TIME);
	}

	function cacheReq($cacheKey, $timeStart=false, $timeEnd=false, $condInt=false, $condStr=false) {
		if (!$this->path || !$cacheKey) return false;
		$cache_name = $this->cacheTable($cacheKey);
		$cache_table = $this->openTable($cache_name,true,true);
		if (!$cache_table) {
			lsError("Can't open cache table: $cache_name");
			return false;
		}
		$cache_table->setTime(time() + LS_CACHE_TIME);
		$cache_table->truncate();
		$row_cnt = 0;
		$files = lsFileRange($timeStart,$timeEnd,$this->path);
		foreach ($files as $fn) {
			$name = $this->path.$fn;
			$table = $this->openTable($name);
			if (!$table) {
				lsError("Can't open table: $name");
				break;
			}
			$cnt = $table->selectRows($null,$cache_table,$condInt,$condStr,(LS_MAX_SELECT_CNT-$row_cnt));
			$this->closeTable($table);
			if (!$cnt) continue;
			$row_cnt += $cnt;
			if ($row_cnt >= LS_MAX_SELECT_CNT) break;
		}
		$this->closeTable($cache_table);
		return $row_cnt;
	}
	
	protected function closeTable($table) {
		if (!$this->useOpenCache) {
			return $table->close();
		}
		return true;
	}
	
	protected function openTable($name, $create=false, $createIdx=false) {
		if (!$this->useOpenCache) {
			$table = new lsTable();
			if (!$table->open($name, $create, $createIdx)) {
				return false;
			}
			return $table;
		}
		$cache = $this->openTableList[$name];
		// Если кэша нет
		if (!$cache) {
			$table = new lsTable();
			if (!$table->open($name, $create, $createIdx)) {
				return false;
			}
			$this->openTableList[$name] = array(
				'create' => $create,
				'createIdx' => $createIdx,
				'handle' => &$table
			);
			return $table;
		}
		// Если кэш не подходит
		if (($create && !$cache['create']) || ($createIdx && !$cache['createIdx'])) {
			// надо переоткрыть таблицу, тк она открыта с меньшими привелегиями
			$cache['handle']->close();
			unset($this->openTableList[$name]);
			return $this->openTable($name, $create, $createIdx);
		} 
		if (!$cache['handle']->fd) {
			unset($this->openTableList[$name]);
			return $this->openTable($name, $create, $createIdx);
		}
		rewind($cache['handle']->fd);
		
		return $cache['handle'];
	}

}

?>
