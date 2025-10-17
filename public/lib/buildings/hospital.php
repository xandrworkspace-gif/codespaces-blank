<?
$hospital_settings = get_hash(building_settings_list(array('building_type_id' => BUILDING_TYPE_HOSPITAL, 'building_level' => $building['level'])), 'name', 'value');
switch ($action) {
    case 'conf':
        $artifact_list = artifact_list(false,$session_user['id'],'*',true,false," AND slot_id!='TEMP_EFFECT' AND type_id=".ARTIFACT_TYPE_ID_INJURY);
        $entry_point_object['injuries'] = array();
        $artikul_ids = array_keys(make_hash($artifact_list, 'artikul_id'));
        if ($hospital_settings['ARTIKUL_ID']) {
            $artikul_ids[] = $hospital_settings['ARTIKUL_ID'];
        }
        if ($artikul_ids) {
            $artikul_hash = make_hash(artifact_artikul_list(array('id' => $artikul_ids)));
        }
        $cure_time = floor((time_current() - $estate['last_enter']) * $hospital_settings['REG_PERCENT'] / 100);
        $entry_point_object['reg_percent'] = $hospital_settings['REG_PERCENT'];
        foreach($artifact_list as $artifact) {
            $entry_point_object['injuries'][] = array(
                'id' => $artifact['id'],
                'title' => $artikul_hash[$artifact['artikul_id']]['title'],
                'picture' => PATH_IMAGE_ARTIFACTS.$artikul_hash[$artifact['artikul_id']]['picture'],
                'expires' => intval(max($artifact['time_expire'] - time_current() - $cure_time, 0)),
            );
        }
        $entry_point_object = $entry_point_object + array(
                'buff_id' => $hospital_settings['ARTIKUL_ID'],
                'buff_picture' => PATH_IMAGE_ARTIFACTS.$artikul_hash[$hospital_settings['ARTIKUL_ID']]['picture'],
                'buff_time' => max(0,$estate['hospital_buff_time'] - time_current()),
                'buff_cooldown' => 86400,
            );
        break;
    case 'use_buff':
        do {
            if ($estate['hospital_buff_time'] >= time_current()) {
                $error = translate('Заклинание ещё не готово!');
                break;
            }
            $result = estate_save(array(
                'id' => $estate['id'],
                'hospital_buff_time' => time_current() + 86400,
            ));
            if ($result && $hospital_settings['BONUS_ID']) {
                bonus_apply($session_user, $hospital_settings['BONUS_ID']);
            }
            $entry_point_object['status'] = 100;
        } while(0);
        break;
    default:
        $error = translate('Не задано действие');
}