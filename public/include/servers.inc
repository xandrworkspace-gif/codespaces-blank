<?

global $common_server_info;
$SERVERS = array(
    SERVER_ID => array(
        'id' => SERVER_ID,
        'name' => 'Элизиум',
        'image' => '/images/icon-rating-clan.png',
        'secret' => 'sg1234saft341',
    ),
);

$common_server_info = array(
    'CB' => array(
        SERVER_ID => array(
            'id' => SERVER_ID,
            'name' => 'Элизиум',
            'image' => '/images/icon-rating-clan.png',
        ),
    ),
    'GP' => array(
        SERVER_ID => array(
            'id' => SERVER_ID,
            'name' => 'Элизиум',
            'image' => '/images/icon-rating-clan.png',
        ),
    ),
    'INST' => array(
        SERVER_ID => [
            'id' => SERVER_ID,
            'name' => 'Элизиум',
            'image' => '/images/icon-rating-clan.png',
        ],
    ),
);
function common_init_servers() {
	global $common_server_info, $SERVERS;

	$custom_servers_arrays = array(
		'CB',		// Clan battles
		'GP',		// Gold pool
		'ADM',	// Admin accesses
		'INST'	// Inst list
	);

	foreach ($custom_servers_arrays as $s) {
		$cfg_name = $s.'_SERVERS';
		global $$cfg_name;
		
		if (!$cfg_name || empty($cfg_name)) {
			$common_server_info[$s] = array();
			continue;
		}
		
		/*foreach ($$cfg_name as $srv_id) {
			if (isset($SERVERS[$srv_id])) 
				$common_server_info[$s][$srv_id] = $SERVERS[$srv_id];
			else
				error_log(sprintf('Trying to init undefined server [id: %d]', $srv_id));
		}*/
	}
}

function common_get_servers($mode = false) {
	global $common_server_info, $SERVERS;
	
	if (!$mode) $res = $SERVERS;
	else $res = isset($common_server_info[$mode]) ? $common_server_info[$mode] : array();

	return $res;
}