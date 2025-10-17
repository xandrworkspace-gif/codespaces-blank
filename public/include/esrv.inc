<?php

require_once 'amf.inc';

final class Esrv {
	private $host;
	private $port;
	private $fd = false;
	public $timeout = 10;

	private function connect() {
		if ($this->fd) return true;
		
		if (!$this->host || !$this->port) {
			error_log('[Esrv::connect] No host or port given for connection');
			return false;
		}
		
		$fd = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
		if ($fd === false) {
			error_log('[Esrv::connect] Failed with error '.$errstr);
			return false;
		}
		
		stream_set_timeout($fd, $this->timeout);
		$this->fd = $fd;
		return true;
	}

	private function safeRead($size) {
		$handle = $this->fd;
		if (!$handle || feof($handle)) return false;
		
		stream_set_timeout($handle, $this->timeout);

		$data = '';
		$length = $size;

		while ($length > 0) {
			$chunk = fread($handle, $length);
			$chunk_size = strlen($chunk);
			if ($chunk_size > 0) {
				$data .= $chunk;
				$length -= $chunk_size;
			} else {
				$meta_info = stream_get_meta_data($handle);
				if ($meta_info['timed_out']) {
					error_log("[Esrv::safeRead] Connection timed out while waiting for data from $this->host:$this->port (for $timeout seconds)");
					return false;
				}
				if (feof($handle)) {
					error_log("[Esrv::safeRead] Connection lost while reading data from $this->host:$this->port");
					return false;
				}
			}
		}
		
		return $data;
	}

	private function safeWrite($data) {
		if (!$this->fd) return false;
		$size = strlen($data);
		$s = $size;
		$d = $data;
		
		stream_set_timeout($this->fd, $this->timeout);
		while ($s > 0) {
			$i = fwrite($this->fd,$d);
			$fwrite_info = stream_get_meta_data($this->fd);
			if (!$i) {
				error_log("[Esrv::safeWrite] Cannot write requested $size bytes");
				return false;
			} elseif ($fwrite_info['timed_out']) {
				error_log("[Esrv::safeWrite] Connection timed out (waiting $timeout seconds for $this->host:$this->port)");
				return false;
			}
			$d = substr($d,$i);
			$s -= $i;
		}
		return true;
	}

	private function readPacket(&$in) {
		$data = $this->safeRead(4);
		if ($data === false) return false;
		
		$tmp_data = unpack("N", $data);
		$size = reset($tmp_data);
		$data = $this->safeRead($size);
		if ($data === false) return false;
		
		$in = null;
		if (amf_unpack($data, $in) === false) return false;
		
		return true;
	}

	private function writePacket(&$out) {
		$data = amf_pack($out);
		if($data === false) return false;
		
		$data = pack("N", strlen($data)).$data;
		if ($this->safeWrite($data) === false) return false;
		
		return true;
	}	

	function __construct($host, $port = 0) {
		if (strpos($host,':') !== false) list($host,$port) = explode(':',$host);
		$this->host = $host;
		$this->port = $port;
	}
	
	public function request($data) {
		if (!$this->connect()) return false;
		if (($this->writePacket($data) === false) || ($this->readPacket($data) === false)) return false;
		return $data;
	}
	

	public function cmdCreateEnv($id, $initScript, $tickRate) {
		$resp = $this->request(array(
			'rc' => 1,
			'id' => (int)($id),
			'initScript' => (string)($initScript),
			'tickRate' => (int)($tickRate),
		));
		return $this->result($resp, $resp['id'], "cmdCreateEnv($id, '$initScript', $tickRate)");
	}

	public function cmdDeleteEnv($id) {
		$resp = $this->request(array(
			'rc' => 2,
			'id' => (int)($id),
		));
		return $this->result($resp, true, "cmdDeleteEnv($id)");
	}

	public function cmdGetEnvList() {
		$resp = $this->request(array(
			'rc' => 3,
		));
		return $this->result($resp, $resp['ids'], "cmdGetEnvList()");
	}

	public function cmdGetSrvInfo() {
		$resp = $this->request(array(
			'rc' => 4,
		));
		return $this->result($resp, $resp, "cmdGetSrvInfo()");
	}

	public function cmdExecFunc($id, $func, $args = array()) {
		$resp = $this->request(array(
			'rc' => 101,
			'id' => (int)($id),
			'func' => (string)($func),
			'args' => $args,
		));
		return $this->result($resp, $resp['result'], "cmdExecFunc($id, '$func', [".implode(', ', $args)."])");
	}

	public function cmdExecCode($id, $code) {
		$resp = $this->request(array(
			'rc' => 102,
			'id' => (int)($id),
			'code' => (string)($code),
		));
		return $this->result($resp, $resp['result'], "cmdExecCode($id, [".preg_replace('/(\r?\n|\t)+/', ' ', $code)."])");
	}

	public function cmdExecFile($id, $file) {
		$resp = $this->request(array(
			'rc' => 103,
			'id' => (int)($id),
			'file' => (string)($file),
		));
		return $this->result($resp, true, "cmdExecFile($id, '$file')");
	}

	public function cmdAddClients($id, $clients) {
		$resp = $this->request(array(
			'rc' => 104,
			'id' => (int)($id),
			'clients' => $clients,
		));
		return $this->result($resp, true, "cmdAddClients($id, ...)");
	}

	public function result($resp, $res, $what) {
		if ($resp['rs'] === true) return $res;
		elseif ($what) {
			$error = $resp['error'];
			error_log('[esrv] ('.$this->host.':'.$this->port.') '.$what.' failed: '.($error ? $error : 'General error'));
		}
		return null;
	}	
}
