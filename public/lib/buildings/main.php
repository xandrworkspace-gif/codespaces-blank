<?

$main_settings = get_hash(building_settings_list(array('building_type_id' => BUILDING_TYPE_MAIN, 'building_level' => $building['level'])), 'name', 'value');
$allowed_area_ids = explode(',',$main_settings['AREAS_'.$session_user['kind']]);
$area_hash = make_hash(area_list(array('code' => array('bank','cell'), 'id' => $allowed_area_ids), '', 'id,code,title'));
if ($area_hash) {
    global $db;
    $cell_area_links = make_hash(common_list($db, TABLE_AREA_LINKS, array('to_id' => array_keys($area_hash))), 'to_id');
    $restriction_object_list = array();
    restriction_get_dependent($session_user, $restriction_object_list);
}
$area_setting_ids = array();
foreach($area_hash as $cell_area_id => $cell_area) {
    if ($cell_area['code'] != 'cell') continue;
    if (!$cell_area_links[$cell_area_id]) {
        unset($area_hash[$cell_area_id]);
        continue;
    }
    $link = $cell_area_links[$cell_area_id];
    $link['object_class'] = OBJECT_CLASS_AREA_LINK;
    $out_restriction = restriction_check(0,array($link),$restriction_object_list);
    if ($out_restriction['status'] != RESTRICTION_STATUS_ALLOW) {
        unset($area_hash[$cell_area_id]);
        continue;
    }
    $area_setting_ids[] = $cell_area_id;
}

if ($area_setting_ids) {
    $area_settings_hash = make_hash(area_setting_list($area_setting_ids), array('area_id', 'name'), true);
}

// Добавляем ячейку в поместье
$area_hash[$estate_areas[$session_user['kind']]] = array(
    'code' => 'cell',
    'title' => translate('Ячейка в поместье'),
);
$storage_building = building_get(array('user_id' => $session_user['id'], 'type_id' => BUILDING_TYPE_STORAGE));
$estate_cell_num = ESTATE_CELL_AMOUNT;
if ($storage_building) {
    $storage_settings = get_hash(building_settings_list(array('building_type_id' => BUILDING_TYPE_STORAGE, 'building_level' => $storage_building['level'])), 'name', 'value');
    $estate_cell_num += intval($storage_settings['SLOT_CNT']);
}
$area_settings_hash[$estate_areas[$session_user['kind']]]['CELL_CAPACITY'][0]['value'] = $estate_cell_num;
$area_setting_ids[] = $estate_areas[$session_user['kind']];

$addcells = area_bank_addcell_list(array('user_id' => $session_user['id']));
$addcells_cnt = array();
foreach($addcells as $addcell) {
    $addcells_cnt[$addcell['area_id']]++;
}

$allowed_area_ids = array_keys($area_hash);
switch ($action) {
    case 'conf':
        $cells = array();
        $bag_artifacts = make_hash(user_get_artifact_list($session_user['id'], '', sql_pholder(' AND type_id<>?#ARTIFACT_TYPE_ID_GIFT AND NOT (flags & ?)', ARTIFACT_FLAG_NODROP | ARTIFACT_FLAG_NOWEIGHT)));
        $artikul_ids = $cell_artifacts = array();
        foreach($bag_artifacts as $artifact) {
            $artikul_ids[$artifact['artikul_id']] = $artifact['artikul_id'];
        }
        if ($allowed_area_ids) {
            $cells = area_bank_cell_list(array('area_id' => $allowed_area_ids, 'user_id' => $session_user['id']));
            foreach($area_hash as $cell_area_id => $cell_area) {
                if ($cell_area['code'] != 'cell') continue;
                $num = intval($area_settings_hash[$cell_area_id]['CELL_CAPACITY'][0]['value']);
                if ($area_settings_hash[$cell_area_id]['CELL_SKILL_CHANGE']) {
                    $skill_list = skill_object_list(OBJECT_CLASS_USER, $session_user['id'], sql_pholder(' AND skill_id = ?', $area_settings_hash[$cell_area_id]['CELL_SKILL_CHANGE'][0]['value']));
                    if ($skill_list) {
                        $num += $skill_list[0]['value'];
                    }
                }
                $cells[] = array(
                    'type' => '0',
                    'area_id' => $cell_area_id,
                    'user_id' => $session_user['id'],
                    'clan_id' => '0',
                    'num' => $num,
                );
            }
            foreach($cells as $cell) {
                $cell_artifacts[$cell['area_id']] = area_get_artifact_list($cell['area_id'], $session_user['id'], sql_pholder(' AND clan_id=0 AND time_expire<?', FAR_FAR_FUTURE));
                foreach($cell_artifacts[$cell['area_id']] as $artifact) {
                    $artikul_ids[$artifact['artikul_id']] = $artifact['artikul_id'];
                }
            }
            if ($artikul_ids) {
                $artikul_hash = make_hash(artifact_artikul_list(array('id' => $artikul_ids)));
            }
        }

        // Ставим ячейку в поместье на 1 место
        foreach($cells as $cell_id => $cell) {
            if ($cell['area_id'] == $estate_areas[$session_user['kind']]) {
                array_unshift($cells,$cell);
                break;
            }
        }
        $cells = make_hash($cells,'area_id');

        $cells_objects = array();

        $entry_point_object['cells'] = array();
        foreach($cells as $cell) {
            if (isset($addcells_cnt[$cell['area_id']])) {
                $cell['num'] += $addcells_cnt[$cell['area_id']];
            }
            $cell_object = array(
                'cell_area_id' => $cell['area_id'],
                'cell_capacity' => $cell['num'],
                'cell_area_title' => $area_hash[$cell['area_id']]['title'],
                'cell_artifacts' => array(),
            );
            foreach($cell_artifacts[$cell['area_id']] as $artifact) {
                $cell_object['cell_artifacts'][] = array(
                    'id' => $artifact['id'],
                    'picture' => PATH_IMAGE_ARTIFACTS.$artikul_hash[$artifact['artikul_id']]['picture'],
                    'title' => $artikul_hash[$artifact['artikul_id']]['title'],
                    'cnt' => $artifact['cnt'],
                );
            }
            $entry_point_object['cells'][] = $cell_object;
        }

        $entry_point_object['bag_artifacts'] = array();
        foreach ($bag_artifacts as $artifact) {
            $cell = true;
            $storage = true;
            if ($artifact['flags'] & (ARTIFACT_FLAG_NODROP | ARTIFACT_FLAG_NOWEIGHT)) {
                $cell = false;
            }
            if ($artifact['flags'] & ARTIFACT_FLAG_CANT_FREEZE) {
                $storage = false;
            }

            if (!$artifact['time_expire'] || $artifact['time_expire'] < 0) {
                $storage = false;
            }

            $obj = array(
                'id' => $artifact['id'],
                'artikul_id' => $artifact['artikul_id'],
                'cell' => ($cell ? 1 : 0),
                'storage' =>  ($storage ? 1 : 0),
                'flags' => $artifact['flags'],
                'time_expire' => ($artifact['time_expire'] ? $artifact['time_expire'] - time_current() : 0),
                'type_id' => $artifact['type_id'],
                'quality' => $artifact['quality'],
                'picture' => PATH_IMAGE_ARTIFACTS . $artikul_hash[$artifact['artikul_id']]['picture'],
                'title' => $artikul_hash[$artifact['artikul_id']]['title'],
                'cnt' => $artifact['cnt'],
                'enchant_quality' => $artifact['enchant_quality'],
                'enchant_param' => $artifact['enchant_param'],
                'enchant2' => $artifact['enchant2'],
                'enchant3' => $artifact['enchant3'],

            );

            $entry_point_object['bag_artifacts'][] = $obj;
        }

        $current_arts = area_get_artifact_list($estate_areas[$session_user['kind']], $session_user['id'],sql_pholder(' and time_expire>?', FAR_FAR_FUTURE));
        foreach($current_arts as $art) {
            if (!in_array($art['artikul_id'], $artikul_ids)) $artikul_ids[] = $art['artikul_id'];
        }
        if ($artikul_ids) {
            $artikul_hash = make_hash(artifact_artikul_list(array('id' => $artikul_ids)));
        }

        $entry_point_object['cell_storage'] = array();
        foreach($current_arts as $current_artifact) {
            $obj = array(
                'id' => $current_artifact['id'],
                'aid' => $current_artifact['artikul_id'],
                'picture' => PATH_IMAGE_ARTIFACTS.$artikul_hash[$current_artifact['artikul_id']]['picture'],
                'title' => $artikul_hash[$current_artifact['artikul_id']]['title'],
                'cnt' => $current_artifact['cnt'],
                'expires' => intval($current_artifact['time_expire']-FAR_FAR_FUTURE),
            );
            $entry_point_object['cell_storage'][] = $obj;
        }
        break;
    case 'move_art':
        $artifact_id = intval($_REQUEST['artifact_id']);
        $cnt = intval($_REQUEST['cnt']);
        $area_id_from = intval($_REQUEST['area_id_from']);
        $area_id_to = intval($_REQUEST['area_id_to']);
        do {
            if (!$artifact_id) {
                $error = translate('Предмет не найден!');
                break;
            }

            $artifact = artifact_get_safe($artifact_id);
            if (!$artifact_id ||
                (($artifact['user_id'] != $session_user['id']) && ($artifact['owner_id'] != $session_user['id'])) ||
                ($artifact['slot_id']) ||
                ($artifact['time_expire'] > FAR_FAR_FUTURE)) {
                $error = translate('Предмет не найден!');
                break;
            }

            if ($artifacts['flags'] & (ARTIFACT_FLAG_NODROP | ARTIFACT_FLAG_NOWEIGHT)) {
                $error = translate('Нельзя переместить этот предмет!');
                break;
            }

            if ($artifact['cnt'] && ($cnt > $artifact['cnt'])) {
                $error = translate('Неправильно задано количество!');
                break;
            }

            if (!$area_id_from && !$area_id_to) {
                $error = translate('Невозможное перемещение!');
                break;
            }

            $allowed_area_ids[] = 0;
            if (!in_array($area_id_to, $allowed_area_ids) || !in_array($area_id_from, $allowed_area_ids)) {
                $error = translate('Ячейчка недоступна из поместья!');
                break;
            }

            if ($area_id_from && ($artifact['area_id'] != $area_id_from)) {
                $error = translate('Предмет лежит в другом месте!');
                break;
            }

            if ($area_id_to) {
                // Перемещение в ячейку
                // Проверка веса для банка и сундуков разная
                $cell_artifact_hash = make_hash(area_get_artifact_list($area_id_to, $session_user['id'], sql_pholder(' AND clan_id=0 AND time_expire<?', FAR_FAR_FUTURE)));
                if (!in_array($area_id_to, $area_setting_ids)) {
                    $cell = area_bank_cell_get(array('area_id' => $area_id_to, 'user_id' => $session_user['id']));
                    $addcell = area_bank_addcell_list(array('area_id' => $area_id_to, 'user_id' => $session_user['id']));
                    $weight_left = ($cell['num'] + count($addcell)) - get_cell_artifact_weight($cell_artifact_hash);
                } else {
                    $num = intval($area_settings_hash[$area_id_to]['CELL_CAPACITY'][0]['value']);
                    if ($area_settings_hash[$area_id_to]['CELL_SKILL_CHANGE']) {
                        $skill_list = skill_object_list(OBJECT_CLASS_USER, $session_user['id'], sql_pholder(' AND skill_id = ?', $area_settings_hash[$area_id_to]['CELL_SKILL_CHANGE'][0]['value']));
                        if ($skill_list) {
                            $num += $skill_list[0]['value'];
                        }
                    }
                    $weight_left = $num - get_cell_artifact_weight($cell_artifact_hash);
                }
                $weight_left -= artifact_check_capacity($artifact['artikul_id'], $cnt, $session_user['id'], $area_id_to, '', 0, true);
                if ($weight_left < 0) {
                    $error = translate('У Вас нет достаточного места в ячейке!');
                    break;
                }
                $result = artifact_move($artifact_id, $cnt, $session_user['id'], $area_id_to, false, array('flags'), false, false);
            } elseif ($area_id_from) {
                // Перемещение в рюкзак
                $result = artifact_move($artifact_id, $cnt, $session_user['id'], false, false, array(), false, false);
            }
            $artifact['cnt'] -= $cnt;
            $result = intval($result);
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
    default:
        $error = translate('Не задано действие');
}