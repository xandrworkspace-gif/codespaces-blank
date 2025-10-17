<?
/*
 * $Id: tqc.inc,v 1.4 2007-07-16 14:32:57 sen Exp $
 *
 * Token Queue Client Interface
 * Arseny Vakhrushev (Sen), it-territory, 2007
 *
 */

final class TokenQueue {
	const TQC_CONN_TIMEOUT = 5;
	const TQC_STREAM_TIMEOUT = 25;
	const TQC_DEFAULT_TIMELOCK = 60;
	const TQC_WARN_TIMEOUT = 5;
	const TQC_ADD_SERVER_ID = SERVER_DOMAIN;
	
	private $host = false;
	private $port = false;
	private $fd = false;

	private $locks = array();
	
	public function TokenQueue($host=false, $port=false) {
		$this->host = $host ? $host: false;
		$this->port = $port ? $port: false;
		$this->fd = false;
		
		register_shutdown_function(array($this, 'on_shutdown'));
	}

	public function connect($host=false, $port=false) {
		if ($this->fd) return true;
		if (!$host) $host = $this->host;
		if (!$port) $port = $this->port;
		if (strpos($host,':') !== false) list($host,$port) = explode(':',$host);
		$this->host = $host;
		$this->port = $port;
		if (!$this->host || !$this->port) {
			error_log('(TokenQueue) No host or port given for connection');
			return false;
		}
		$fd = @fsockopen($host,$port,$errno,$errstr,self::TQC_CONN_TIMEOUT);
		if ($fd === false) {
			error_log('(TokenQueue) fsockopen() failed: '.$errstr);
			return false;
		}
		stream_set_timeout($fd,self::TQC_STREAM_TIMEOUT);
		stream_set_write_buffer($fd,1024);
		$this->fd = $fd;
		return true;
	}

	public function close() {
		if (!$this->fd) return false;
		@fclose($this->fd);
		$this->fd = false;
		return true;
	}

	private function request($req='') {
		global $request_statistic;
		if (!$req || !$this->connect()) return false;
		if (DEBUG) $st_time = microtime(true);
		$req .= "\r\n";
		if (!@fwrite($this->fd,$req)) {
			error_log('(TokenQueue) Failed to send request');
			return false;
		}
		$resp = @fread($this->fd,1024);	// since the response surely fits one TCP packet, we may read only one chunk
		if (!$resp) {
			error_log('(TokenQueue) Failed to get response');
			return false;
		}
		if (DEBUG) {
			$tmp_request_name = explode('_', $req);
			$request_name = str_replace(' ','_',substr($tmp_request_name[0],0,32));
			$request_statistic['time']['tqd'][$request_name] += microtime(true)-$st_time;
			$request_statistic['tqd'][$request_name]++;
		}
		return trim($resp);
	}

	// =======================================================================================================

	public function capture($id, $timeWait=0, $timeLock=0, $priority=0, $no_unlock=0) {
		if (!$id || ($timeWait < 0) || ($timeLock < 0)) return false;
		if (!$timeLock) $timeLock = self::TQC_DEFAULT_TIMELOCK;
		if(self::TQC_ADD_SERVER_ID) $id = self::TQC_ADD_SERVER_ID.$id;
		$bt = $this->_bt();
		if (!empty($this->locks[$id])) {
			$this->locks[$id]['ref']++;
			array_push($this->locks[$id]['no_unlocks'], $no_unlock);
			return true;
		}
		$req = sprintf('CAPTURE %s %d %d %d',$id,$timeWait,$timeLock,$priority);
		if ($this->request($req) == 'OK') {
			$this->locks[$id] = array('time' => microtime(true), 'ref' => 1, 'bt' => $bt, 'no_unlocks' => array($no_unlock));
			return true;
		}
		
		if ((defined('DEBUG') && DEBUG) || (defined('TQC_LOG') && TQC_LOG)) error_log(sprintf('(TokenQueue) Unable to lock %s %s', $id, $bt));
		return false;
	}

	public function release($id, $ignore_empty = false) {
		if (!$id) return false;
        if(self::TQC_ADD_SERVER_ID) $id = self::TQC_ADD_SERVER_ID.$id;
		$bt = $this->_bt();
		if (!$ignore_empty && empty($this->locks[$id])) {
			//error_log('(TokenQueue) Trying to release lock "'.$id.'" without capture');
			return false;
		} elseif (((defined('DEBUG') && DEBUG) || (defined('TQC_LOG') && TQC_LOG)) && (!empty($this->locks[$id]) || (empty($this->locks[$id]) && !$ignore_empty))) {
			$time = microtime(true) - $this->locks[$id]['time'];
			if ($time > self::TQC_WARN_TIMEOUT) error_log(sprintf('(TokenQueue) Long lock %s %3.4fs +++[%s] ---[%s]', $id, $time, $this->locks[$id]['bt'], $bt));
		}
		if (isset($this->locks[$id])) {
			$this->locks[$id]['ref']--;
			$no_unlock = array_pop($this->locks[$id]['no_unlocks']);
		}
		if ($ignore_empty || ($this->locks[$id]['ref'] == 0)) {
			$req = sprintf('RELEASE %s',$id);
			if ($this->request($req) == 'OK') {
				unset($this->locks[$id]);
				return true;
			}
			if (isset($this->locks[$id])) {
				$this->locks[$id]['ref'] = 1;
				$this->locks[$id]['no_unlocks'] = array($no_unlock);
			}
			return false;
		}

		return true;
	}

	protected function _bt() {
		$bt_str = '';
		$bt_list = debug_backtrace();
		for ($i=1; $i<count($bt_list); ++$i) {
			$bt = $bt_list[$i];
			$bt_str .= sprintf("%s:%s %s; " , basename($bt['file']), $bt['line'], ($bt['class'] ? $bt['class'].'::':'').$bt['function']);
		}
		return $bt_str; 
	}

	public function on_shutdown() {
		foreach ($this->locks as $id=>$lock) {
			if ((defined('DEBUG') && DEBUG) || (defined('TQC_LOG') && TQC_LOG)) {
				if (in_array(0, $lock['no_unlocks']))
					error_log(sprintf('(TokenQueue) Unrelesed lock %s +[%s]', $id, $lock['bt']));
			}
		}
	}
}

?>