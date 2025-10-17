<?
$profiler_inc = 0;
$profiler_stack = array();
$profiler_info = array();
$profiler_socket = array();

$profiler_default_info = array(
	'uniq_id' => time()."-".getmypid()."-".rand(),
	'server_host' =>  isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : '-',
	'script_name' =>  isset($_SERVER["SCRIPT_NAME"]) ? $_SERVER["SCRIPT_NAME"] : '-'
);

function profiler_info($uniq_request_id = null, $server_host = null, $script_name = null) {
	global $profiler_info;
	if ($uniq_request_id) $profiler_info['uniq_id'] = $uniq_request_id;
	if ($server_host) $profiler_info['server_host'] = $server_host;
	if ($script_name) $profiler_info['script_name'] = $script_name;
	return $profiler_info;
}

function profiler_start($tags) {
	if (!defined('PROFILER_ON') || !PROFILER_ON) return null;
	global $profiler_inc, $profiler_stack;
	$profiler_inc++;
	$profiler_stack[$profiler_inc] = array('tags' => $tags,  "stime" => gettimeofday());
	return $profiler_inc;
}


function profiler_stop($profiler_key) {
	if (!defined('PROFILER_ON') || !PROFILER_ON) return;
	global $profiler_stack, $profiler_info, $profiler_default_info, $profiler_socket;

	$timer = isset($profiler_stack[$profiler_key]) ? $profiler_stack[$profiler_key] : null;
	if (!$timer) {
		error_log(sprintf('No key [%s] in profiler_stack', $profiler_key));
		return;
	}	
	unset($profiler_stack[$profiler_key]);
	$profiler_stack[$profiler_key]['ftime'] = gettimeofday();

	$uniq_id = isset($profiler_info['uniq_id']) ? $profiler_info['uniq_id'] : $profiler_default_info['uniq_id'];
	$host = isset($profiler_info['server_host']) ? $profiler_info['server_host'] : $profiler_default_info['server_host'];
	$script = isset($profiler_info['script_name']) ? $profiler_info['script_name'] : $profiler_default_info['script_name'];

	$curr_time = gettimeofday();
	$delta = ($curr_time["sec"] - $timer["stime"]["sec"]) * 1000000;
	$delta += $curr_time["usec"] - $timer["stime"]["usec"];

	$packet = sprintf("%s %s %s %s %s", $uniq_id, $host, $script, $delta, serialize($timer["tags"]));
	
	if (!$profiler_socket || !is_resource($profiler_socket)) {
		$profiler_socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
	}
	if (socket_sendto($profiler_socket, $packet, strlen($packet), 0, PROFILER_UDP_HOST, intval(PROFILER_UDP_PORT)) < 0) {
		error_log(sprintf("Can send profiler packet, address %s:%s", PROFILER_UDP_HOST, PROFILER_UDP_PORT));
	}
	socket_close($profiler_socket);

	return;
}

?>
