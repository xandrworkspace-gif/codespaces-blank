<? /* $Id: cache.memcached.inc,v 1.22 2009-12-25 13:47:14 i.hrustalev Exp $ */

/*
* Locking cache maintenance
* Sen, it-territory, 2006
*/

define("LOCK_TTL", 60);
define("TTL_MULTIPLIER", 2);

global $mcObjects, $globalLockId; // process-wide cache link and locker id
$mcObjects = array();
$globalLockId = rand();

if (!$MEMCACHE_CFG) {
	$MEMCACHE_CFG = array(
		0 => array(
			'host' => MEMCACHE_HOST,
			'port' => MEMCACHE_PORT,
		),
	);
}
foreach($MEMCACHE_CFG as $memcache_key => $memcache_srv) {
	$mcObject = new Memcache;
	if (!@$mcObject->pconnect($memcache_srv['host'], $memcache_srv['port'])) {
		error_log("Connection to ".$memcache_srv['host'].":".$memcache_srv['port']." failed");
		$mcObject = false;
	} else {
		$mcObject->setCompressThreshold(256*1024*1024);
		
	}
	$mcObjects[$memcache_key] = $mcObject;
}

class Cache {
	var $id = false;
	var $lId = 0;
	var $pId = 0;
	var $mcObject = false;
	function Cache($cacheId, $path='') {
		global $globalLockId, $mcObjects;
		$this->path = $path ? $path: PATH_CACHE;
		$this->cacheId($cacheId.cache_const_get());
		$this->lId = $globalLockId;
		$this->pId = getmypid();
		$memcache_key = sprintf('%u',(crc32($this->id))) % count($mcObjects);
		$this->mcObject = $mcObjects[$memcache_key];
	}

	function cacheId($cacheId = false) {
		if ($cacheId) $this->id = $cacheId;
		return $this->id;
	}

	function lockId() {
		return $this->lId;
	}

	function pid() {
		return $this->pId;
	}

	function &get() {
		global $request_statistic;
		if (!$this->mcObject || !$this->cacheId()) return false;
		if (DEBUG) $st_time = microtime(true);
		$data = $this->mcObject->get($this->cacheId());
		if (DEBUG) {
			$request_statistic['time']['memcached']['get'] += microtime(true)-$st_time;
			$request_statistic['memcached']['get']++;
		}
		return $data;
	}

	function isAvail() {
		if (!$this->mcObject || !$this->cacheId()) return false;
		$ttlInfo = $this->mcObject->get($this->cacheId().".ttl");
		if ($ttlInfo) {
			$data = $this->mcObject->get($this->cacheId());
		};
		return ($ttlInfo !== false) && ($data !== false);
	}

	function isValid() {
		if (!$this->mcObject || !$this->cacheId()) return false;
		$ttlInfo = $this->mcObject->get($this->cacheId().".ttl");
		return $ttlInfo !== false ? ((int)$ttlInfo["createTime"] + (int)$ttlInfo["ttl"]) > time() : false;
	}

	function haveLock() {
		if (!$this->mcObject || !$this->cacheId() || !$this->lockId()) return false;
		$lockData = $this->mcObject->get($this->cacheId().".lock");
		$ret = $lockData != false ? ($lockData[0] == $this->pid()) && ($lockData[1] == $this->lockId()) : false;
		return $ret;
	}

	function tryLock() {
		if (!$this->mcObject || !$this->cacheId()) return false;
		if ($this->haveLock()) return true;
		$lockData = array($this->pid(), $this->lockId());
		$this->mcObject->add($this->cacheId().".lock", $lockData, 0, LOCK_TTL);
		return $this->haveLock();
	}

	function freeLock() {
		if (!$this->mcObject) return false;
		return $this->mcObject->delete($this->cacheId().".lock",0);
	}

	function update($data, $ttl) {
		global $request_statistic;
		if (!$this->mcObject || ($ttl < 0)) return false;
		if (!$this->haveLock() && !$this->tryLock()) return false;
		if ($ttl == 0) $ttl = 60;
		$ttlInfo = array("createTime"=>time(), "ttl" => $ttl);
		if (DEBUG) $st_time = microtime(true);
		$status = $this->mcObject->set($this->cacheId(), $data, 0, $ttl * TTL_MULTIPLIER);
		if ($status) $this->mcObject->set($this->cacheId().".ttl", $ttlInfo, 0, $ttl * TTL_MULTIPLIER);
		$this->freeLock();
		if (DEBUG) {
			$request_statistic['time']['memcached']['update'] += microtime(true)-$st_time;
			$request_statistic['memcached']['update']++;
		}
		return $status;
	}
	
	function getTTL() {
		if (!$this->mcObject || !$this->cacheId()) return false;
		$ttlInfo = $this->mcObject->get($this->cacheId().".ttl");
		return $ttlInfo !== false ? max(0, time() - (int)$ttlInfo["ttl"]) : false;
	}

	function setTTL($ttl) {
		error_log(__FUNCTION__);
	}

	function remove() {
		if (!$this->mcObject || !$this->cacheId()) return false;
		$ret = $this->mcObject->delete($this->cacheId());
		$ret &= $this->mcObject->delete($this->cacheId().".ttl");
		return $ret;
	}

	// =====================================================================
	function _fname() {
		if (!$this->cacheId()) return false;
		return $this->path.'cache_'.$this->cacheId().'.dat';
	}
}


?>