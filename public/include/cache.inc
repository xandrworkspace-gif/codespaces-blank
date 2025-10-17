<? # $Id: cache.inc,v 1.24 2009-12-25 13:47:14 i.hrustalev Exp $

/*
* Locking cache maintenance
* Sen, it-territory, 2006
*/

define('PATH_CACHE', 'shared/cache/');


class Cache {
	var $id = false;
	var $tmpfd = false;
	var $path = '';

	function Cache($cacheId, $path='') {
		$this->path = $path ? $path: PATH_CACHE;
		$this->cacheId($cacheId.cache_const_get());
	}

	function cacheId($cacheId=false) {
		if ($cacheId) $this->id = $cacheId;
		return $this->id;
	}

	function &get() {
		if (!$this->cacheId()) return false;
		$data = false;
		do {	// some kind of race
			if (!file_exists($this->_fname())) break;
			$data = @file_get_contents($this->_fname());
		} while (!$data && !$i++);
		return $data ? unserialize($data) : false;
	}

	function isAvail() {
		if (!$this->cacheId()) return false;
		return @file_exists($this->_fname());
	}

	function isValid() {
		if (!$this->cacheId()) return false;
		return @filemtime($this->_fname()) > time();
	}

	function haveLock() {
		return !empty($this->tmpfd);
	}

	function tryLock() {
		if (!$this->cacheId()) return false;
		if ($this->haveLock()) return true;
		$this->tmpfd = @fopen($this->_fname().'.tmp',"x");
		return $this->haveLock();
	}

	function freeLock() {
		if ($this->haveLock()) @fclose($this->tmpfd);
		$this->tmpfd = false;
		return @unlink($this->_fname().'.tmp');
	}

	function update($data, $ttl) {
		if ($ttl < 0) return false;
		if (!$this->haveLock() && !$this->tryLock()) return false;
		fwrite($this->tmpfd,serialize($data));
		fclose($this->tmpfd);
		$this->tmpfd = false;
		$fn = $this->_fname();
		$tmpfn = $this->_fname().'.tmp';
		@touch($tmpfn,time()+$ttl);
		@chmod($tmpfn,0666);
		$status = @rename($tmpfn,$fn) || (@copy($tmpfn,$fn) && @touch($fn,time()+$ttl));
		$this->freeLock();
		return $status;
	}
	
	function getTTL() {
		$fn = $this->_fname();
		if (!$fn) return false;
		return max(0, time() - @filemtime($fn));
	}

	function setTTL($ttl) {
		$fn = $this->_fname();
		if (!$fn) return false;
		return @touch($fn,time()+$ttl);
	}

	function remove() {
		if (!$this->cacheId()) return false;
		return @unlink($this->_fname());
	}

	// =====================================================================
	function _fname() {
		if (!$this->cacheId()) return false;
		return $this->path.'cache_'.$this->cacheId().'.dat';
	}
}


?>