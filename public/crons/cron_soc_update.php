<?php

exit;

chdir("..");
require_once("include/common.inc");

common_define_settings();

// Проверка, что проект и аукцион не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) return;

require_once("lib/mailru_api.lib");

set_time_limit(0);
$stime1 = time();

$reload_requests = make_hash(soc_reload_request_list(false, ' limit 1000'),'uid');
$soc_systems_ids = array_keys(make_hash($reload_requests, 'soc_system_id'));

$soc_users = $reload_requests ? soc_user_list(array('uid' => array_keys($reload_requests), 'soc_system_id' => $soc_systems_ids)) : array();

$uids_processed = array();
foreach($soc_users as $soc_user) {
	$soc_system = SocialSystem::getSystem($soc_systems[$soc_user['soc_system_id']]['class_name']);

	if ($soc_user['soc_system_id'] == SOC_SYSTEM_FB || $soc_user['soc_system_id'] == SOC_SYSTEM_OK) {
		$soc_system->set_access_token($reload_requests[$soc_user['uid']]['access_token']);
	}

	$result = $soc_system->sync_user_friends($soc_user, true);

	if ($result) $uids_processed[] = $soc_user['uid'];
	if (time() - $stime1 > 500) break;
}

if ($reload_requests) {
	soc_reload_request_delete(array('uid'=>array_keys($reload_requests)));
}