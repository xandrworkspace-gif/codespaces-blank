<? # $Id: fsclient.inc,v 3.21 2018-02-05 11:12:12 br.world Exp $

require_once(dirname(__FILE__)."/pf.inc");

define('FS_FF_NO_BLOCK', 0x04);

define('FC_CONN_TIMEOUT', 10);
define('FC_STREAM_TIMEOUT', 10);

// param types
define('PT_INVALID', 0);
define('PT_INT',     1);
define('PT_FIXED',   2);
define('PT_STRING',  3);
define('PT_RAW',     4);
define('PT_NINT',    5);
define('PT_NFIXED',  6);
define('PT_SHORTINT', 7);
define('PT_NSHORTINT', 8);
define('PT_BIGINT',  9);
define('PT_NBIGINT', 10);

// server commands
# common
define('FS_SC_NONE',                  0);
define('FS_SC_SYNC_TIME',             1);
# ctrl
define('FS_SCCT_SRV_INFO',           11);
define('FS_SCCT_DEBUG_LEVEL',        12);
define('FS_SCCT_GET_SRV_OPTS',       13);
define('FS_SCCT_SET_SRV_OPTS',       14);
define('FS_SCCT_GET_FIGHTS',         15);
define('FS_SCCT_CREATE_FIGHT',       16);
define('FS_SCCT_SET_FIGHT_PARAMS',   17);
define('FS_SCCT_START_FIGHT',        18);
define('FS_SCCT_STOP_FIGHT',         19);
define('FS_SCCT_DELETE_FIGHT',       20);
define('FS_SCCT_CREATE_PERS',        21);
define('FS_SCCT_SET_SKILLS',         22);
define('FS_SCCT_SET_PARTS',          23);
define('FS_SCCT_ADD_EFFECT',         24);
define('FS_SCCT_ADD_COMBO',          25);
define('FS_SCCT_BIND_PERS',          26);
define('FS_SCCT_DELETE_PERS',        27);
define('FS_SCCT_GET_FIGHTSTATE',     28);
define('FS_SCCT_GET_FIGHTLOG',       29);
define('FS_SCCT_GET_FIGHTINFO',      30);
define('FS_SCCT_DELETE_FIGHTINFO',   31);
define('FS_SCCT_SET_PARAMS_LUA',     32);
define('FS_SCCT_GET_LOG_AND_EFFECTS',33);

define('FS_SCCT_BOTDMG_SKILLS', 99);

# client
define('FS_SCCL_INIT',              101);
define('FS_SCCL_STATE',             102);
define('FS_SCCL_PERS_INFO',         103);
define('FS_SCCL_PERS_PARTS',        104);
define('FS_SCCL_ATTACK',            105);
define('FS_SCCL_FIGHT_STATE',       106);

// server status
define('FS_SS_OK',                0);
define('FS_SS_WRONG_CMD',        -1);
define('FS_SS_WRONG_ARGS',       -2);
define('FS_SS_WRONG_DATA',       -3);
define('FS_SS_WRONG_STATE',      -4);
define('FS_SS_NO_AUTH',          -5);
define('FS_SS_INT_ERROR',        -6);
define('FS_SS_IO_ERROR',       -100);

define('FS_PS_CREATED',     0);
define('FS_PS_PENDING',     3);
define('FS_PS_ACTIVE',      4);
define('FS_PS_PASSIVE',     3);
define('FS_PS_DEAD',        2);

// personage events
define('FS_PE_SRVSHUTDOWN',       1);
define('FS_PE_OPPWAIT',         101);
define('FS_PE_OPPNEW',          102);
define('FS_PE_ATTACKNOW',       103);
define('FS_PE_ATTACKWAIT',      104);
define('FS_PE_ATTACK',          105);
define('FS_PE_ATTACKTIMEOUT',   106);
define('FS_PE_FIGHTLOG',        107);
define('FS_PE_FIGHTOVER',       108);
define('FS_PE_FIGHTSTATE',      109);
define('FS_PE_EFFECTUSE',       110);

// skills
define('FS_SK_LEVEL',       0);
define('FS_SK_STR',         1);
define('FS_SK_INT',         2);
define('FS_SK_DEX',         3);
define('FS_SK_ENDUR',       4);
define('FS_SK_VIT',         5);
define('FS_SK_WISDOM',      6);
define('FS_SK_INTELL',      7);
define('FS_SK_PWRMIN',      8);
define('FS_SK_PWRMAX',      9);
define('FS_SK_HP',         10);
define('FS_SK_HPMAX',      11);
define('FS_SK_MP',         12);
define('FS_SK_MPMAX',      13);
define('FS_SK_XHPMAX',     14);
define('FS_SK_XMPMAX',     15);
define('FS_SK_MAGCRIT',    16);
define('FS_SK_MAGHIT',     17);
define('FS_SK_MAGPWRMIN',  18);
define('FS_SK_MAGPWRMAX',  19);
define('FS_SK_RSTPHYSIC',  20);
define('FS_SK_RSTKIDMAG',  21);
define('FS_SK_RSTAIRFIRE', 22);
define('FS_SK_RSTWTRGRND', 23);
define('FS_SK_RSTLGHSHAD', 24);
define('FS_SK_RSTALL',     25);
define('FS_SK_MREG',       26);
define('FS_SK_CHRGDMGX',   27);
define('FS_SK_CHRGVAMP',   28);
define('FS_SK_CHRGSTUNP',  29);
define('FS_SK_CHRGSTUNT',  30);
define('FS_SK_CHRGCRIT',   31);
define('FS_SK_WILL',       32);
define('FS_SK_SHIP',	   54); //TODO: Не спрашивайте зачем так, я и сам не знаю
define('FS_SK_PENETRATION', 55); //TODO: ТОже самое
define('FS_SK_VAMPIR', 		56); //TODO: Тути вообще жопи
define('FS_SK_STOIKOST',   57); //TODO: Стойкость
define('FS_SK_HPMOD',   58); //TODO: Добавочный % к эликам жизни
define('FS_SK_INICIATIV',   59); //TODO: Инициатива (кто будет первый ходить)
define('FS_SK_UNHPMOD', 60); //TODO: Ослабление элей жизни
define('FS_SK_AURACHRGDMGX', 61);
define('FS_SK_AURACHRGVAMP', 62);
define('FS_SK_AURACHRGCRIT', 63);
define('FS_SK_USE_WISDOM', 64);
define('FS_SK_RSTPHYSICBLOCK', 65);
define('FS_SK_PCRSTAIRBLOCK', 66);
define('FS_SK_MAGCRITDEF', 67);
define('FS_SK_MAGCRITDMX', 68);
define('FS_SK_CHVAMPMINUS', 69);
define('FS_SK_CHVAMPPLUS', 70);
define('FS_SK_FIZCRITDEF', 71);
define('FS_SK_FIZCRITDMX', 72);
define('FS_SK_RAZPELENY', 73);

define('FS_SK_WILL_REPRESSION', 74);
define('FS_SK_FW_PENETRATION', 75);
define('FS_SK_LD_PENETRATION', 76);
define('FS_SK_WE_PENETRATION', 77);
define('FS_SK_INITIATIVE', 78);
define('FS_SK_ANTISTUN', 79);
define('FS_SK_CHRGSTUNC', 80);

define('FS_SK_FW_PENETR',  33);
define('FS_SK_WE_PENETR',  34);
define('FS_SK_LD_PENETR',  35);
define('FS_SK_W_REPRESS',  36);
define('FS_SK_PH_PCRESIST',37);
define('FS_SK_KM_PCRESIST',38);
define('FS_SK_FW_PCRESIST',39);
define('FS_SK_WE_PCRESIST',40);
define('FS_SK_LD_PCRESIST',41);
define('FS_SK_ENERGYMAX',  42);
define('FS_SK_ENERGY',     43);
define('FS_SK_BOWDMG',     44);
define('FS_SK_EVADEPENALTY',45);
define('FS_SK_BLOCKPENALTY',46);
define('FS_SK_CRITPENALTY', 47);
define('FS_SK_HEALONATTACK',48);
define('FS_SK_INJURYDEC',   49);
define('FS_SK_CRITAMP',			50);
define('FS_SK_MANAREGPROB', 51);
define('FS_SK_DMGMAX',      52);
define('FS_SK_HEALPENALTY', 53);

// personage parts
define('FS_PPT_HD1',    0);
define('FS_PPT_HD2',    1);
define('FS_PPT_BD1',    2);
define('FS_PPT_BD2',    3);
define('FS_PPT_RH1',    4);
define('FS_PPT_RH2',    5);
define('FS_PPT_RH3',    6);
define('FS_PPT_LH1',    7);
define('FS_PPT_LH2',    8);
define('FS_PPT_LH3',    9);
define('FS_PPT_RL1',   10);
define('FS_PPT_RL2',   11);
define('FS_PPT_RL3',   12);
define('FS_PPT_LL1',   13);
define('FS_PPT_LL2',   14);
define('FS_PPT_LL3',   15);
define('FS_PPT_RW',    16);
define('FS_PPT_LW',    17);
define('FS_PPT_MW',    18);
define('FS_PPT_BN',    19);
define('FS_PPT_BOW',   20);

// fight options
define('FS_SO_MAX_FIGHT_CNT', 0);

// fight status
define('FS_FS_CREATED',    0);
define('FS_FS_RUNNING',    1);
define('FS_FS_OVER',       2);
define('FS_FS_FINISHED',   3);

// personage flags
define('FS_PF_FIGHTTEST',             0x0000001);
define('FS_PF_IMMORTAL',              0x0000002);
define('FS_PF_ART',                   0x0000020);
define('FS_PF_BOT',                   0x0000040);
define('FS_PF_CRITSIMILAR',           0x0000200);
define('FS_PF_SKGHOST',               0x0000800);
define('FS_PF_PET_ENABLE',            0x0004000);
define('FS_PF_INVISIBLE',             0x0008000);
define('FS_PF_NOBLOCK',               0x0020000);
define('FS_PF_NOEVADE',               0x0040000);
define('FS_PF_NOCRIT',                0x0080000);
define('FS_PF_PETSILENT',             0x0100000);
define('FS_PF_SILENT',                0x0200000);
define('FS_PF_DMGPENALTY',            0x0800000);
define('FS_PF_HALF_MOROC_LIGHT',	  0x0400000);
define('FS_PF_HALF_MOROC_DARK',		  0x1000000); // 16777216
define('FS_PF_NO_AURAS', 			  0x10000000); //268435456
define('FS_PF_NO_OPP_AUTO', 		  0x20000000); //536870912
define('FS_PF_SHADOW', 				  0x40000000); //1073741824

define('FS_PF_MAGIC', 0x0400); //Маг стойка

// fight log codes
define('FS_FLC_KICK',       1);
define('FS_FLC_DEATH',      2);
define('FS_FLC_INTRUSION',  3);
define('FS_FLC_EFFECTUSE',  4);
define('FS_FLC_DAMAGE',     5);

// effect codes
define('FS_PEC_ADDSKILLS',  	1);
define('FS_PEC_ADDHP',      	2);
define('FS_PEC_ADDMP',      	3);
define('FS_PEC_STUN',       	4);
define('FS_PEC_RESETCOMBO', 	5);
define('FS_PEC_RESURRECT',  	6);
define('FS_PEC_ABSORB',     	7);
define('FS_PEC_BOTHELP',    	8);
define('FS_PEC_REFLECT',    	9);
define('FS_PEC_SETFLAG',    	10);
define('FS_PEC_MODHP',      	11);
define('FS_PEC_DESTROY_AURA', 	12);

// effect flags
define('FS_PEF_GRPDENY',      0x0000001);
define('FS_PEF_GRPDROP',      0x0000002);
define('FS_PEF_WEAPONEFFECT', 0x0000004);
define('FS_PEF_SPELL',        0x0000008);
define('FS_PEF_PASSTURN',     0x0000010);
define('FS_PEF_NEEDTURN',     0x0000020);
define('FS_PEF_TARGETSELF',   0x0000040);
define('FS_PEF_TARGETOPP',    0x0000080);
define('FS_PEF_TEAMSELF',     0x0000100);
define('FS_PEF_TEAMOPP',      0x0000200);
define('FS_PEF_TARGETNOTBOT', 0x0000400);
define('FS_PEF_USEDEAD',      0x0000800);
define('FS_PEF_AUX',          0x0001000);
define('FS_PEF_CONFIRM',      0x0002000);
define('FS_PEF_CDGRPDENY',    0x0004000);
define('FS_PEF_CDGRPDROP',    0x0008000);
define('FS_PEF_CDSTART',      0x0010000);
define('FS_PEF_NOFULLRESIST', 0x0020000);
define('FS_PEF_CHARGE',       0x0040000);
define('FS_PEF_ACTIVE',       0x0080000);
define('FS_PEF_HIDDEN',       0x0100000);
define('FS_PEF_PET_ASSIST',	  0x0400000);
define('FS_PEF_PARTYSELF',	  0x0800000);
define('FS_PEF_INFINITE', 	  0x1000000); //16777216
define('FS_PEF_RESETONDIE',   0x2000000);
define('FS_PEF_CLANSELF',     0x4000000);
define('FS_PEF_ANIMINVERT',   0x8000000);
define('FS_PEF_DISABLED', 	  0x10000000); //268435456
define('FS_PEF_BOW',          0x20000000); //536870912
define('FS_PEF_TARGETNOTUSER',0x40000000);
define('FS_PEF_AURA',	      0x80000000); //2147483648

// damage type
define('FS_PDT_PHYSICAL',     0x0001);
define('FS_PDT_KIDMAGIC',     0x0002);
define('FS_PDT_AIRFIRE',      0x0004);
define('FS_PDT_WATERGROUND',  0x0008);
define('FS_PDT_LIGHTSHADOW',  0x0010);


class fsClient {
	var $host = false, $port = false, $fd = false;

	function fsClient($host=false, $port=false, $_conn=false) {
		$this->host = $host ? $host: false;
		$this->port = $port ? $port: false;
		$this->fd = false;
		if($_conn){
			$this->connect($host, $port);
		}
	}

	function connect($host=false, $port=false) {
		if ($this->fd) return true;
		if (!$host) $host = $this->host;
		if (!$port) $port = $this->port;
		if (strpos($host,':') !== false) list($host,$port) = explode(':',$host);
		$this->host = $host;
		$this->port = $port;
		if (!$this->host || !$this->port) {
			error_log('(fsclient) [connect]: No host or port given for connection');
			return false;
		}
		PF_CALL();
		$fd = @fsockopen($host,$port,$errno,$errstr,FC_CONN_TIMEOUT);
		if ($fd === false) {
			error_log('(fsclient) [connect]: fsockopen() failed: '.$errstr);
			return PF_RET(false);
		}
		stream_set_timeout($fd,FC_STREAM_TIMEOUT);
		$this->fd = $fd;
		return PF_RET(true);
	}

	function close() {
		if (!$this->fd) return false;
		@fclose($this->fd);
		$this->fd = false;
		return true;
	}

	function sendCommand(/* ... */) {
		global $request_statistic;
		if (DEBUG) $st_time = microtime(true);
		if (!$this->connect()) return false;
		PF_CALL();
		$argc = func_num_args();
		$argv = func_get_args();
		$params = array();
		for ($i=0; $i < $argc; $i++) $this->pushParams($params,$argv[$i]);
		if (DEBUG) {
			$request_statistic['time']['fightd']['send'] += microtime(true)-$st_time;
			$request_statistic['fightd']['send']++;
		}
		return PF_RET($this->writePacket($params));
	}

	function sendCommandAdv(/* ... */) {
		global $request_statistic;
		if (DEBUG) $st_time = microtime(true);
		if (!$this->connect()) return false;
		PF_CALL();
		$argc = func_num_args();
		$argv = func_get_args();
		$params = array();
		if(is_array(($argv[0]))){
			$params = $argv[1]; //ARGS
		}else {
			if (!is_array($argv[1])) {
				for ($i = 0; $i < $argc; $i++) $this->pushParams($params, $argv[$i]);
			} else {
				$this->pushParams($params, $argv[0]); //COMMAND
				$params = array_merge($params, $argv[1]); //ARGS
			}
		}
		if (DEBUG) {
			$request_statistic['time']['fightd']['send'] += microtime(true)-$st_time;
			$request_statistic['fightd']['send']++;
		}
		return PF_RET($this->writePacket($params));
	}

	function getAnswer(&$params) {
		global $request_statistic;
		PF_CALL();
		if (DEBUG) $st_time = microtime(true);
		$params = array();
		if (!$this->readPacket($params)) return PF_RET(FS_SS_IO_ERROR);
		$cmd = $params[0]['val'];
		$status = $params[1]['val'];
		if (DEBUG) {
			$request_statistic['time']['fightd']['answer'] += microtime(true)-$st_time;
			$request_statistic['fightd']['answer']++;
		}
		return PF_RET($status);
	}

	// =======================================================================================================

	function safeRead($size) {
		if (!$this->fd) return false;
		$data = '';
		$s = $size;
		while ($s > 0) {
			$d = @fread($this->fd,$s);
			if (!$d) {
				error_log('(fsclient) [safeRead]: Cannot read requested '.$size.' bytes');
				return false;
			}
			$data .= $d;
			$s -= strlen($d);
		}
		return $data;
	}

	function safeWrite($data) {
		if (!$this->fd) return false;
		$size = strlen($data);
		$s = $size;
		$d = $data;
		while ($s > 0) {
			$i = @fwrite($this->fd,$d);
			if (!$i) {
				error_log('(fsclient) [safeWrite]: Cannot write requested '.$size.' bytes');
				return false;
			}
			$d = substr($d,$i);
			$s -= $i;
		}
		return true;
	}

	function readPacket(&$params) {
		$data = $this->safeRead(4);
		if ($data === false) return false;
		$size = intval($data,16);
		$data = $this->safeRead($size+1);	// Plus extra \0 at the end since we read C-strings
		if ($data === false) return false;
		$data = substr($data,0,$size);
		$params = array();
		if (!$this->unpackParams($params,$data)) return false;
		return true;
	}

	function writePacket($params) {
		$data = '';
		if (!$this->packParams($data,$params)) return false;
		$data = sprintf("%04x",strlen($data)).$data."\000";	// Plus extra \0 at the end since we write C-strings
		if ($this->safeWrite($data) === false) return false;
		return true;
	}

	function packParams(&$buf, $params) {
		if (!is_array($params)) return false;
		$buf = '';
		foreach ($params as $param) {
			if (($param['type'] == PT_INT) && ($param['val'] < 0)) $param['type'] = PT_NINT;
			if (($param['type'] == PT_FIXED) && ($param['val'] < 0)) $param['type'] = PT_NFIXED;
			$buf .= sprintf("%04x",($param['id'] * 0x100) + ($param['type'] & 0xFF));
			switch ($param['type']) {
				case PT_SHORTINT:
				case PT_NSHORTINT:
					$buf .= sprintf("%04x",($param['type'] == PT_NSHORTINT ? -$param['val']: $param['val']));
					break;
				case PT_INT:
				case PT_NINT:
					$buf .= sprintf("%08x",($param['type'] == PT_NINT ? -$param['val']: $param['val']));
					break;
				case 11:
					$buf .= sprintf("%09x",($param['type'] == PT_NINT ? -$param['val']: $param['val']));
					break;
				case PT_BIGINT:
				case PT_NBIGINT:
					$buf .= sprintf("%16x",($param['type'] == PT_NBIGINT ? -$param['val']: $param['val']));
					break;
				case PT_FIXED:
				case PT_NFIXED:
					$buf .= sprintf("%08x",($param['type'] == PT_NFIXED ? -$param['val']: $param['val']) * 10000);
					break;
				case PT_STRING:
				case PT_RAW:
					//$buf .= sprintf("%04x",mb_strlen($param['val'], 'UTF-8')).$param['val'];
					$buf .= sprintf("%04x",strlen($param['val'])).$param['val'];
					break;
				default:
					error_log('(fsclient) [packParams]: Invalid param type: '.$param['type']);
					return false;
			}
		}
		return true;
	}

	function unpackParams(&$params, $data) {
		if (!is_string($data)) return false;
		$params = array();
		$size = strlen($data);
		$pos = 0;
		while ($size > 0) {
			if ($size < 4) {
				error_log('(fsclient) [unpackParams]: Insufficient data for reading type (left: '.$size.', offset: '.$pos.', data: '.$data.')');
				return false;
			}
			$i = intval(substr($data,$pos,4),16);
			$id = $i >> 8;
			$type = $i & 0xFF;
			$pos += 4;
			$size -= 4;
			switch ($type) {
				case PT_INT:
				case PT_NINT:
					if ($size < 8) {
						error_log('(fsclient) [unpackParams]: Insufficient data for reading value (left: '.$size.', offset: '.$pos.', data: '.$data.')');
						return false;
					}
					$val = intval(substr($data,$pos,8),16);
					if ($type == PT_NINT) $val = -$val;
					$pos += 8;
					$size -= 8;
					break;
				case PT_FIXED:
				case PT_NFIXED:
					if ($size < 8) {
						error_log('(fsclient) [unpackParams]: Insufficient data for reading value (left: '.$size.', offset: '.$pos.', data: '.$data.')');
						return false;
					}
					$val = intval(substr($data,$pos,8),16) / 10000;
					if ($type == PT_NFIXED) $val = -$val;
					$pos += 8;
					$size -= 8;
					break;
				case PT_STRING:
				case PT_RAW:
					$len = intval(substr($data,$pos,4),16);
					$pos += 4;
					$size -= 4;
					if ($len > $size) {
						error_log('(fsclient) [unpackParams]: Invalid length: '.$len.' (offset: '.($pos-4).', data: '.$data.')');
						return false;
					}
					$val = substr($data,$pos,$len);
					$pos += $len;
					$size -= $len;
					break;
				default:
					error_log('(fsclient) [unpackParams]: Invalid param type: '.$type.' (offset: '.($pos-4).', data: '.$data.')');
					return false;
			}
			$params[] = array(
				'id' => $id,
				'type' => $type,
				'val' => $val,
			);
		}
		return true;
	}

	function pushParams(&$params,$p) {
		if (is_array($p)) {
			if (array_key_exists('type',$p) && array_key_exists('val',$p)) {
				$params[] = $p;
			} else {
				foreach ($p as $v) $this->pushParams($params,$v);
			}
			return;
		}
		if (is_int($p)) {
            $type = PT_INT;
		}
		elseif (is_float($p)) $type = PT_FIXED;
		else $type = PT_STRING;
		$params[] = array('type' => $type, 'val' => $p);
	}

	// ===============================================================================================================

	function debugParams($params, $prefix='', $shortForm=false) {
		$n = 0;
		$text = '';
		$text .= "<pre>";
		if ($shortForm) {
			if (!$params) $params = array();
			$text .= $prefix.'['.count($params).']';
			$t = array();
			foreach ($params as $param) {
				switch ($param['type']) {
					case PT_INT:
					case PT_NINT:
						$t[] = sprintf('%d',$param['val']);
						break;
					case PT_FIXED:
					case PT_NFIXED:
						$t[] = sprintf('%.4f',$param['val']);
						break;
					case PT_STRING:
						$t[] = sprintf('"%s"',$param['val']);
						break;
					case PT_RAW:
						$t[] = sprintf('RAW(%d bytes)',strlen($param['val']));
						break;
					default:
						$t[] = sprintf('UNKNOWN TYPE(%d)',$param['type']);
						break;
				}
			}
			if ($t) $text .= ' '.implode(', ',$t);
		} else {
			$text .= $prefix."--------------------------------".$prefix."\n";
			if ($params) {
				$text .= sprintf("SIZE (%d param(s))\n",count($params));
				foreach ($params as $param) {
					switch ($param['type']) {
						case PT_INT:
							$text .= sprintf("#%-3d id=%-3d INT (%d)\n",$n,$param['id'],$param['val']);
							break;
						case PT_NINT:
							$text .= sprintf("#%-3d id=%-3d NINT (%d)\n",$n,$param['id'],$param['val']);
							break;
						case PT_FIXED:
							$text .= sprintf("#%-3d id=%-3d FIXED (%.4f)\n",$n,$param['id'],$param['val']);
							break;
						case PT_NFIXED:
							$text .= sprintf("#%-3d id=%-3d NFIXED (%.4f)\n",$n,$param['id'],$param['val']);
							break;
						case PT_STRING:
							$text .= sprintf("#%-3d id=%-3d STRING (\"%s\")\n",$n,$param['id'],$param['val']);
							break;
						case PT_RAW:
							$text .= sprintf("#%-3d id=%-3d RAW (%d bytes)\n",$n,$param['id'],strlen($param['val']));
							break;
						default:
							$text .= sprintf("#%-3d id=%-3d UNKNOWN TYPE (%d)\n",$n,$param['id'],$param['type']);
							break;
					}
					$n++;
				}
			} else $text .= "empty\n";
			$text .= "--------------------------------\n";
		}
		$text .= "</pre>\n";
		return $text;
	}
}

?>
