<? # $Id: pf.inc,v 1.4 2007-12-05 11:12:06 sen Exp $

$PF = array();

function PF_RESET() {
	global $PF;
	$PF = array();
}

function PF_CALL($key=false) {
	global $PF;
	$bt = debug_backtrace();
	$tr = $bt[1] ? $bt[1]: $bt[0];
	if (!$key) $key = $bt[1] ? ($tr['class'] ? $tr['class'].'::': '').$tr['function'] : basename($tr['file']);
	/*$t = array();
	foreach ($tr['args'] as $a) $t[] = var_export($a,true);
	$PF[$key]['last_call'] = sprintf("%s:%d %s(%s)",basename($tr['file']),$tr['line'],$tr['function'],implode(',',$t));*/
	$PF[$key]['mt'] = microtime(true);
}

function PF_RET($ret=false, $key=false) {
	global $PF;
	$bt = debug_backtrace();
	$tr = $bt[1] ? $bt[1]: $bt[0];
	if (!$key) $key = $bt[1] ? ($tr['class'] ? $tr['class'].'::': '').$tr['function'] : basename($tr['file']);
	$mt = $PF[$key]['mt'];
	if ($mt) {
		unset($PF[$key]['mt']);
		if (!isset($PF[$key]['min'])) $PF[$key]['min'] = 999999;
		if (!isset($PF[$key]['max'])) $PF[$key]['max'] = 0;
		$dt = microtime(true) - $mt;
		$PF[$key]['cnt']++;
		$PF[$key]['sum'] += $dt;
		$PF[$key]['avg'] = $PF[$key]['sum']/$PF[$key]['cnt'];
		$PF[$key]['min'] = min($PF[$key]['min'],$dt);
		$PF[$key]['max'] = max($PF[$key]['max'],$dt);
	}
	return $ret;
}

function PF_LOG($fn, $keys=false) {
	global $PF;
	if (!$fn) return;
	if (!$keys) {
		$keys = array_keys($PF);
		sort($keys);
	}
	$have_file = @file_exists($fn);
	$fout = @fopen($fn,"a");
	if (!$fout) return;
	foreach ($keys as $key) {
		$stat = $PF[$key];
		$str = sprintf("%s (%d tms, %.3f sum, %.3f avg, %.3f min, %.3f max)",$key,$stat['cnt'],$stat['sum'],$stat['avg'],$stat['min'],$stat['max']);
		$log_str = sprintf("[%s] %5d - %s\n",date("Y-m-d, H:i:s"),getmypid(),$str);
		fwrite($fout,$log_str);
	}
	fclose($fout);
	if (!$have_file) @chmod($fn,0664);
}

?>