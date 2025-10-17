<?php
/*

amf3 pack/unpack interface
Author: Ignatenkov Stanislav, sen

Это переписанный интерфейс работы с амф, который лишен недостатков старой реализации, где 
интерфейс реализовывал божественный объект с мешаниной амф и есерв апи.

define('AMF_USE_OBJ_REFS',  0); // 0 - always encode complex objects by value, 1 - maintain object reference table
*/

if (function_exists('amf3_encode') && function_exists('amf3_decode')) {
	function amf_pack(&$data) {
		$res = amf3_encode($data);
		if ($res === false) error_log('amf3_encode() failed');
		return $res;	
	}
	
	function amf_unpack(&$data, &$out) {
		$count = 0;
		$out = amf3_decode($data, $count);
		if ($count < 0) {
			error_log('amf3_decode() failed: '.bin2hex($data));
			return false;
		}
		return $count;	
	}
} else {
	define('AMF_UNDEFINED',  0x00);
	define('AMF_NULL',       0x01);
	define('AMF_FALSE',      0x02);
	define('AMF_TRUE',       0x03);
	define('AMF_INT',        0x04);
	define('AMF_DOUBLE',     0x05);
	define('AMF_STRING',     0x06);
	define('AMF_XML_DOC',    0x07);
	define('AMF_DATE',       0x08);
	define('AMF_ARRAY',      0x09);
	define('AMF_OBJECT',     0x0A);
	define('AMF_XML',        0x0B);
	define('AMF_BYTE_ARRAY', 0x0C);

	final class PurePhpAmf {
		private static $strIdxs;
		private static $objIdxs;
		private static $strs;
		private static $objs;	
	
		public static function pack(&$data) {
			return self::packData($data, false);
		}

		public static function unpack(&$data, &$out) {
			if (!is_string($data) || !strlen($data)) return false;
			return self::unpackData($data, 0, strlen($data), $out, false);
		}		
		
		private static function packData(&$data, $nested) {
			switch (true) {
				case is_null($data):
					$type = AMF_NULL;
					break;
				case is_bool($data):
					$type = $data ? AMF_TRUE : AMF_FALSE;
					break;
				case is_int($data):
					$type = ($data < -268435456) || ($data > 268435455) ? AMF_DOUBLE : AMF_INT;
					break;
				case (is_float($data) || is_double($data)):
					$type = AMF_DOUBLE;
					break;
				case is_string($data):
					$type = AMF_STRING;
					break;
				case is_a($data, 'AmfDate'):
					$type = AMF_DATE;
					break;
				case is_array($data):
					$type = AMF_ARRAY;
					break;
				case is_a($data, 'AmfByteArray'):
					$type = AMF_BYTE_ARRAY;
					break;
				default:
					$type = AMF_UNDEFINED;
					break;
			}
			
			if (!$nested) self::initRefTable();
			$res = chr($type);
			switch ($type) {
				case AMF_UNDEFINED:
				case AMF_NULL:
				case AMF_FALSE:
				case AMF_TRUE:
					break;
				case AMF_INT:
					$res .= self::encodeInt($data);
					break;
				case AMF_DOUBLE:
					$res .= self::encodeDouble($data);
					break;
				case AMF_STRING:
					$res .= self::encodeStr($data);
					break;
				case AMF_DATE:
					$idx = self::getObjIdx($data);
					if ($idx !== false) { // reference index
						$res .= self::encodeInt($idx << 1);
					} else {
						$res .= self::encodeInt(1);
						$res .= self::encodeDouble($data->data());
					}
					break;
				case AMF_ARRAY:
					$idx = self::getObjIdx($data);
					if ($idx !== false) { // reference index
						$res .= self::encodeInt($idx << 1);
					} else {
						$num = 0;
						foreach ($data as $k=>$v) {
							if (!is_int($k) || ($k != $num)) break;
							++$num;
						}
						if (count($data) == $num) { // array is a sequence of values with indexes starting from zero
							$res .= self::encodeInt((count($data) << 1) | 1); // dense part size
							$res .= self::encodeStr(''); // end of associative part
							foreach ($data as $v) {
								$res .= self::packData($v,true);
							}
						} else { // associative array with mixed keys
							$res .= self::encodeInt(1); // empty dense part
							foreach ($data as $k=>$v) {
								$res .= self::encodeStr($k);
								$res .= self::packData($v,true);
							}
							$res .= self::encodeStr(''); // end of associative part
						}
					}
					break;
				case AMF_BYTE_ARRAY:
					$idx = self::getObjIdx($data);
					if ($idx !== false) { // reference index
						$res .= self::encodeInt($idx << 1);
					} else {
						$res .= self::encodeInt((strlen($data->data()) << 1) | 1);
						$res .= $data->data();
					}
					break;
				default:
					error_log("[Amf::packData] Invalid data type: $type");
					return false;
			}
			return $res;
		}
	
		private static function unpackData(&$data, $pos, $size, &$out, $nested) {
			if ($size <= 0) {
				error_log("[Amf::unpackData] Can't decode type specifier at $pos");
				return false;
			}
			$res = null; // initialzing empty object
			if (!$nested) self::initRefTable();
			$oldPos = $pos;
			$type = ord($data{$pos++});
			switch ($type) {
				case AMF_UNDEFINED:
				case AMF_NULL:
					break;
				case AMF_FALSE:
					$res = false;
					break;
				case AMF_TRUE:
					$res = true;
					break;
				case AMF_INT:
					$i = self::decodeInt($data,$pos,$size,$res);
					if ($i === false) {
						error_log("[Amf::unpackData] Can't decode integer at $pos");
						return false;
					}
					$pos += $i;
					break;
				case AMF_DOUBLE:
					$i = self::decodeDouble($data,$pos,$size,$res);
					if ($i === false) {
						error_log("[Amf::unpackData] Can't decode double at $pos");
						return false;
					}
					$pos += $i;
					break;
				case AMF_STRING:
					$i = self::decodeStr($data,$pos,$size,$res);
					if ($i === false) {
						error_log("[Amf::unpackData] Can't decode string at $pos");
						return false;
					}
					$pos += $i;
					break;
				case AMF_DATE:
					$i = self::decodeInt($data,$pos,$size,$pfx);
					if ($i === false) {
						error_log("[Amf::unpackData] Can't decode date prefix at $pos");
						return false;
					}
					$pos += $i;
					if (!($pfx & 1)) { // reference index
						$res = self::getObj($pfx >> 1);
					} else {
						self::putObj($res);
						$i = self::decodeDouble($data,$pos,$size,$res);
						if ($i === false) {
							error_log("[Amf::unpackData] Can't decode date at $pos");
							return false;
						}
						$pos += $i;
					}
					break;
				case AMF_ARRAY:
					$i = self::decodeInt($data,$pos,$size,$pfx);
					if ($i === false) {
						error_log("[Amf::unpackData] Can't decode array prefix at $pos");
						return false;
					}
					$pos += $i;
					if (!($pfx & 1)) { // reference index
						$res = self::getObj($pfx >> 1);
					} else {
						self::putObj($res);
						$pfx >>= 1;
						if (($pfx < 0) || (($pos + $pfx) > $size)) {
							error_log("[Amf::unpackData] Invalid dense portion size at ".($pos - $i));
							return false;
						}
						$res = array();
						for ( ;; ) { // associative array portion
							$i = self::decodeStr($data,$pos,$size,$key);
							if ($i === false) {
								error_log("[Amf::unpackData] Can't decode array key at $pos");
								return false;
							}
							$pos += $i;
							if (!strlen($key)) break;
							$i = self::unpackData($data,$pos,$size,$val,true);
							if ($i === false) {
								error_log("[Amf::unpackData] Can't decode array value at $pos");
								return false;
							}
							$pos += $i;
							$res[$key] = $val;
						}
						while ($pfx-- > 0) {
							$i = self::unpackData($data,$pos,$size,$val,true);
							if ($i === false) {
								error_log("[Amf::unpackData] Can't decode array value at $pos");
								return false;
							}
							$pos += $i;
							$res[] = $val;
						}
					}
					break;
				case AMF_BYTE_ARRAY:
					$i = self::decodeInt($data,$pos,$size,$pfx);
					if ($i === false) {
						error_log("[Amf::unpackData] Can't decode byte array prefix at $pos");
						return false;
					}
					$pos += $i;
					if (!($pfx & 1)) { // reference index
						$res = self::getObj($pfx >> 1);
					} else {
						self::putObj($res);
						$pfx >>= 1;
						if (($pfx < 0) || (($pos + $pfx) > $size)) {
							error_log("[Amf::unpackData] Invalid byte array size at ".($pos - $i));
							return false;
						}
						$res = new AmfByteArray(substr($data,$pos,$pfx));
						$pos += $pfx;
					}
					break;
				default:
					error_log("[Amf::unpackData] Invalid data type $type at ".($pos - 1));
					return false;
			}
			$out = $res; // copying object
			return $pos - $oldPos;
		}
	
		private static function encodeInt($data) {
			$res = '';
			$data &= 0x1FFFFFFF;
			if ($data <= 0x7F) {
				$res .= chr($data & 0x7F);
			} else
			if ($data <= 0x3FFF) {
				$res .= chr(($data >> 7) & 0x7F | 0x80);
				$res .= chr($data & 0x7F);
			} else
			if ($data <= 0x1FFFFF) {
				$res .= chr(($data >> 14) & 0x7F | 0x80);
				$res .= chr(($data >> 7) & 0x7F | 0x80);
				$res .= chr($data & 0x7F);
			} else {
				$res .= chr(($data >> 22) & 0x7F | 0x80);
				$res .= chr(($data >> 15) & 0x7F | 0x80);
				$res .= chr(($data >> 8) & 0x7F | 0x80);
				$res .= chr($data & 0xFF);
			}
			return $res;
		}
	
		private static function decodeInt(&$data, $pos, $size, &$res) {
			$res = 0;
			$ofs = 0;
			do {
				if ($pos >= $size) return false;
				$tmp = ord($data{$pos++});
				if ($ofs == 3) {
					$res <<= 8;
					$res |= $tmp & 0xFF;
				} else {
					$res <<= 7;
					$res |= $tmp & 0x7F;
				}
				$ofs++;
			} while (($ofs < 4) && ($tmp & 0x80));
			if ($res & 0x10000000) $res |= ~0x0FFFFFFF; // prolonging sign bits if negative
			return $ofs;
		}
	
		private static function encodeDouble($data) {
			return strrev(pack("d",$data));
		}
	
		private static function decodeDouble(&$data, $pos, $size, &$res) {
			$res = 0.0;
			if (($size - $pos) < 8) return false;
			// Assuming we're on Intel byte order machine
			$res = reset(unpack("d", strrev(substr($data, $pos, 8))));
			return 8;
		}
	
		private static function encodeStr($data) {
			$res = '';
			$idx = self::getStrIdx($data);
			if ($idx !== false) { // reference index
				$res .= self::encodeInt($idx << 1);
			} else {
				$res .= self::encodeInt((strlen($data) << 1) | 1);
				$res .= $data;
			}
			return $res;
		}
	
		private static function decodeStr(&$data, $pos, $size, &$res) {
			$res = '';
			$ofs = 0;
			$pfx = 0;
			$i = self::decodeInt($data,$pos,$size,$pfx);
			if ($i === false) {
				error_log("[Amf::decodeStr] Can't decode string prefix at $pos");
				return false;
			}
			$pos += $i;
			$ofs += $i;
			if (!($pfx & 1)) {	// reference index
				$res = self::getStr($pfx >> 1);
			} else {
				$pfx >>= 1;
				if (($pfx < 0) || (($pos + $pfx) > $size)) {
					error_log("[Amf::decodeStr] Invalid string length at ".($pos - $i));
					return false;
				}
				$res = substr($data,$pos,$pfx);
				$ofs += $pfx;
				if ($pfx > 0) self::putStr($res); // empty string is never sent by reference
			}
			return $ofs;
		}
	
		private static function initRefTable() {
			self::$strIdxs = array();
			self::$objIdxs = array();
			self::$strs = array();
			self::$objs = array();
		}
	
		private static function getStrIdx(&$str) {
			if (!strlen($str)) return false; // empty string is never sent by reference
			$lastIdx = count(self::$strIdxs);
			$idx = &self::$strIdxs[$str];
			if (!isset($idx)) {
				$idx = $lastIdx;
				return false;
			}
			return (int)($idx);
		}
	
		private static function getStr($idx) {
			$res = &self::$strs[$idx];
			if (!isset($res)) error_log("[Amf::getStr] String index out of range (idx: ".$idx.", size: ".count(self::$strs).")");
			return $res;
		}
	
		private static function putStr(&$str) {
			self::$strs[] = $str; // storing strings by value
		}
	
		private static function getObjIdx(&$obj) {
			if (!@constant('AMF_USE_OBJ_REFS')) return false;
			$idx = array_search($obj, self::$objIdxs);
			if ($idx === false) {
				self::$objIdxs[] = $obj;
				return false;
			}
			return (int)($idx);
		}
	
		private static function getObj($idx) {
			$res = &self::$objs[$idx];
			if (!isset($res)) error_log("[Amf::getObj] Object index out of range (idx: ".$idx.", size: ".count(self::$objs).")");
			return $res;
		}
	
		private static function putObj(&$obj) {
			self::$objs[] = &$obj; // storing objects by reference
		}
	}

	function amf_pack(&$data) {
		return PurePhpAmf::pack($data);
	}
	
	function amf_unpack(&$data, &$out) {
		return PurePhpAmf::unpack($data, $out);
	}
}
