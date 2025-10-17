<?
switch ($action) {
    case 'conf':
        $storage_settings = get_hash(building_settings_list(array('building_type_id' => BUILDING_TYPE_STORAGE, 'building_level' => $building['level'])), 'name', 'value');
        $tmp_artifacts = user_get_artifact_list($session_user['id'], '', sql_pholder(' AND type_id<>?#ARTIFACT_TYPE_ID_GIFT and time_expire>0'));
        $current_arts = area_get_artifact_list($estate_areas[$session_user['kind']], $session_user['id'],sql_pholder(' and time_expire>?', FAR_FAR_FUTURE));
        $artikul_ids = array_keys(make_hash($tmp_artifacts, 'artikul_id'));
        foreach($current_arts as $art) {
            if (!in_array($art['artikul_id'], $artikul_ids)) $artikul_ids[] = $art['artikul_id'];
        }
        if ($artikul_ids) {
            $artikul_hash = make_hash(artifact_artikul_list(array('id' => $artikul_ids)));
        }
        $entry_point_object['fridge_cnt'] = $storage_settings['FRIDGE_CNT'];
        $entry_point_object['tmp_artifacts'] = array();
        foreach($tmp_artifacts as $tmp_artifact) {
            $obj = array(
                'id' => $tmp_artifact['id'],
                'aid' => $tmp_artifact['artikul_id'],
                'picture' => PATH_IMAGE_ARTIFACTS.$artikul_hash[$tmp_artifact['artikul_id']]['picture'],
                'title' => $artikul_hash[$tmp_artifact['artikul_id']]['title'],
                'cnt' => $tmp_artifact['cnt'],
                'expires' => intval($tmp_artifact['time_expire']-time_current()),
            );
            $entry_point_object['tmp_artifacts'][] = $obj;
        }

        $entry_point_object['current_artifacts'] = array();
        foreach($current_arts as $current_artifact) {
            $obj = array(
                'id' => $current_artifact['id'],
                'aid' => $current_artifact['artikul_id'],
                'picture' => PATH_IMAGE_ARTIFACTS.$artikul_hash[$current_artifact['artikul_id']]['picture'],
                'title' => $artikul_hash[$current_artifact['artikul_id']]['title'],
                'cnt' => $current_artifact['cnt'],
                'expires' => intval($current_artifact['time_expire']-FAR_FAR_FUTURE),
            );
            $entry_point_object['current_artifacts'][] = $obj;
        }
        break;
    case 'put_art':
        $artifact_id = intval($_REQUEST['artifact_id']);
        $cnt = intval($_REQUEST['cnt']);
        do {
            if (!$artifact_id) {
                $error = translate('Предмет не найден!');
                break;
            }

            $artifact = artifact_get($artifact_id);
            if (!$artifact_id || ($artifact['user_id'] != $session_user['id']) || ($artifact['slot_id'])) {
                $error = translate('Предмет не найден!');
                break;
            }

            if ($artifact['cnt'] && ($cnt > $artifact['cnt'])) {
                $error = translate('Неправильно задано количество!');
                break;
            }

            if ($artifact['flags'] & ARTIFACT_FLAG_CANT_FREEZE) {
                $error = translate('Этот предмет нельзя положить в хранилище!');
                break;
            }

            if (!$artifact['time_expire'] || $artifact['time_expire'] < 0) {
                $error = translate('Этот предмет нельзя положить в хранилище!');
                break;
            }

            $area = area_get($estate_areas[$session_user['kind']]);

            $storage_settings = get_hash(building_settings_list(array('building_type_id' => BUILDING_TYPE_STORAGE, 'building_level' => $building['level'])), 'name', 'value');

            NODE_PUSH($area['node_num']);
            $current_arts = artifact_count(false, null, null, false, false, sql_pholder(' and area_id=? and owner_id=? and time_expire>?', $estate_areas[$session_user['kind']], $session_user['id'], FAR_FAR_FUTURE));
            NODE_POP();

            if ($storage_settings['FRIDGE_CNT'] <= $current_arts) {
                $error = translate('Хранилище переполнено!');
                break;
            }

            $new_expire = FAR_FAR_FUTURE + ($artifact['time_expire']-time_current());

            if (NODE_PUSH($area['node_num'])) {
                $storage_art = artifact_get(false, sql_pholder(' AND artikul_id = ? AND area_id = ? AND owner_id = ? AND time_expire > ?#FAR_FAR_FUTURE ', $artifact['artikul_id'], $area['id'], $session_user['id']));
                NODE_POP();

                if ($storage_art && $storage_art['time_expire'] && ($storage_art['cnt'] > 0 && $artifact['cnt'] > 0)) $new_expire = max($new_expire, $storage_art['time_expire']);
            }

            $result = artifact_move($artifact_id, $cnt, $session_user['id'], $estate_areas[$session_user['kind']], false, array('flags'), false,false);
            if ($result === true && !$artifact['cnt']) $result = $artifact_id;
            NODE_PUSH($area['node_num']);
            artifact_save(array(
                'id' => $result,
                'owner_id' => $session_user['id'],
                'time_expire' => $new_expire,
            ));
            NODE_POP();
            $artifact['cnt'] -= $cnt;

            if ($result) {
                $artifact_new = artifact_get_safe($result);
                if ($artifact_new) {
                    ob_start();
                    $artifact_hash = array($result => $artifact_new, $artifact_id => $artifact);
                    tpl_artifact_alt_prepare($artifact_hash, OBJECT_CLASS_ARTIFACT);
                    foreach($artifact_hash as $new_artifact) {
                        tpl_artifact_alt($new_artifact);
                    }
                    $js_str = strip_tags(ob_get_clean(),'<img><span>');
                    $js_str = str_replace("\\\"",'\'',$js_str);
                    $entry_point_object['js_code'] = '<![CDATA['.$js_str.']]>';
                }
            }
        } while (0);
        break;
    case 'give_art':
        $artifact_id = intval($_REQUEST['artifact_id']);
        $cnt = intval($_REQUEST['cnt']);
        do {
            if (!$artifact_id) {
                $error = translate('Предмет не найден!');
                break;
            }

            $artifact = artifact_get_safe($artifact_id);
            if (!$artifact_id || ($artifact['owner_id'] != $session_user['id']) || ($artifact['area_id']!=$estate_areas[$session_user['kind']])) {
                $error = translate('Предмет не найден!');
                break;
            }

            if ($artifact['cnt'] && ($cnt > $artifact['cnt'])) {
                $error = translate('Неправильно задано количество!');
                break;
            }

            $old_expire = ($artifact['time_expire'] - FAR_FAR_FUTURE) + time_current();

            if ($old_expire < 0) {
                $error = translate('Предмет не найден!');
                break;
            }

            $backpack_art = artifact_get(false, sql_pholder(' AND artikul_id = ? AND user_id = ? AND slot_id = "" AND storage_type = ?#ARTIFACT_STORAGE_TYPE_USER AND time_expire > ? ', $artifact['artikul_id'], $session_user['id'], time_current()));
            if ($backpack_art && $backpack_art['time_expire'] && ($backpack_art['cnt'] > 0 && $artifact['cnt'] > 0)) $old_expire = max($old_expire, $backpack_art['time_expire']);

            $result = artifact_move($artifact_id, $cnt, $session_user['id'], false, false, array(), false, false);
            if ($result === true && !$artifact['cnt']) $result = $artifact_id;
            artifact_save(array(
                'id' => $result,
                'owner_id' => 0,
                'time_expire' => $old_expire,
            ));
            $artifact['cnt'] += $cnt;

            if ($result) {
                $artifact_new = artifact_get_safe($result);
                if ($artifact_new) {
                    ob_start();
                    $artifact_hash = array($result => $artifact_new,$artifact_id => $artifact);
                    tpl_artifact_alt_prepare($artifact_hash, OBJECT_CLASS_ARTIFACT);
                    foreach($artifact_hash as $new_artifact) {
                        tpl_artifact_alt($new_artifact);
                    }
                    $js_str = strip_tags(ob_get_clean(),'<img><span>');
                    $js_str = str_replace("\\\"",'\'',$js_str);
                    $entry_point_object['js_code'] = '<![CDATA['.$js_str.']]>';
                }
            }
        } while (0);
        break;
    default:
        $error = translate('Не задано действие');
}