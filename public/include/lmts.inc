<?php

require_once 'esrv.inc';

final class Lmts {
	private $esrv;
	public $envId = 1;
	
	function __construct($host, $port) {
		$this->esrv = new ESrv($host, $port);
	}
	
	function create($users) {
		$inServUsers = array();
		foreach($users as $user => $key) $inServUsers[] = array(strval($user), $key);
		$res = $this->esrv->cmdAddClients($this->envId, $inServUsers);
		$this->reset(array_keys($users));
		return $res; 
	}
	
	function reset($users) {
		return $this->esrv->cmdExecFunc($this->envId, 'env.resetUsers', array($users));				
	}

	function remove($users) {
		return $this->esrv->cmdExecFunc($this->envId, 'env.deleteUsers', array(array_keys($users)));	
	}

	function subscribe($uid, $channels) {
		return $this->esrv->cmdExecFunc($this->envId, 'env.addToChannel', array($uid, $channels));	
	}

	function unsubscribe($uid, $channels) {
		return $this->esrv->cmdExecFunc($this->envId, 'env.removeFromChannel', array($uid, $channels));	
	}

	function send($channel, $msg) {
		return $this->esrv->cmdExecFunc($this->envId, 'env.send', array($channel, $msg));	
	}
	
	function isOnline($uid) {
		$ret = $this->esrv->cmdExecCode($this->envId, 'return chmngr.chusers[' . intval($uid) .'];');
		return $ret[0] == 1;
	}
	
	function test() {
		$ret = $this->esrv->cmdExecCode($this->envId, 'return 1');
		return $ret[0] == 1;
	}
}
