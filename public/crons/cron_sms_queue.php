<?php

exit;

chdir("..");
require_once("include/common.inc");
require_once("include/smpp.inc");
require_once("lib/phone.lib");

set_time_limit(0);

define('MAX_MESSAGES', '1000');
define('MAX_TRIES',    '2');

common_init();

$data = phone_queue_list(false, " ORDER BY stime LIMIT ".MAX_MESSAGES);

if(!$data) exit(0);

$update_list = $delete_list = array();
foreach ($data as $item) {
	if(strval($item['key']) ===  phone_queue_key($item['from_id'], $item['phone'], $item['message'], $item['stime']))
		$result = phone_send_sms($item['phone'], $item['message'], "session_admin[id]==".$item['from_id']);
	else
		$result = false;
	
	if($result || $item['error_cnt']>=MAX_TRIES)
		$delete_list[] = $item['id'];
	else
		$update_list[] = $item['id'];
}

// cleanup
if($delete_list) phone_queue_delete(array('id' => $delete_list));
if($update_list) phone_queue_save(array(
		'_cnt' => true,
		'_set' => 'error_cnt = error_cnt + 1',
		'_add' => sql_pholder(" AND id IN (?@)", $update_list),
	));
