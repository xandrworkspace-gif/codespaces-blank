<?

switch ($action) {
    case 'conf':
        $mine_settings = get_hash(building_settings_list(array('building_type_id' => $type_id, 'building_level' => $building['level'])), 'name', 'value');

        if(!$mine_settings['PRODUCTION_NEED_ARTIKUL_ID']) {
            $artikul_mine = artifact_artikul_get($mine_settings['PRODUCTION_ARTIKUL_ID']);
            if (!$artikul_mine) {
                $error = translate('Здание не настроено!');
                break;
            }
        }else{
            $artikul_mine = artifact_artikul_get($mine_settings['PRODUCTION_NEED_ARTIKUL_ID']);
            if (!$artikul_mine) {
                $error = translate('Здание не настроено!');
                break;
            }
            $artikul_mine2 = artifact_artikul_get($mine_settings['PRODUCTION_ARTIKUL_ID']);
            if (!$artikul_mine2) {
                $error = translate('Здание не настроено!');
                break;
            }
        }

        $craft_requests = building_mine_user_list(array(
            'user_id' => $session_user['id'],
        ));

        if(!$mine_settings['PRODUCTION_NEED_ARTIKUL_ID']){
            $entry_point_object = $entry_point_object + array(
                    'artikul_id' => $mine_settings['PRODUCTION_ARTIKUL_ID'],
                    'artikul_quantity' => $mine_settings['PRODUCTION_CNT'],
                    'picture' => PATH_IMAGE_ARTIFACTS.$artikul_mine['picture'],
                    'title' => $artikul_mine['title'],
                );
        }else{
            $entry_point_object = $entry_point_object + array(
                    'material_artikul_quantity' => $mine_settings['PRODUCTION_NEED_CNT'],
                    'material_artikul_id' => $mine_settings['PRODUCTION_NEED_ARTIKUL_ID'],
                    'material_picture' => PATH_IMAGE_ARTIFACTS.$artikul_mine['picture'],
                    'material_title' => $artikul_mine['title'],
                    'result_artikul_quantity' => $mine_settings['PRODUCTION_CNT'],
                    'result_artikul_id' => $mine_settings['PRODUCTION_ARTIKUL_ID'],
                    'result_picture' => PATH_IMAGE_ARTIFACTS.$artikul_mine2['picture'],
                    'result_title' => $artikul_mine2['title'],
                );
        }

        $entry_point_object = $entry_point_object + array(
                'cost' => $mine_settings['PRODUCTION_COST'],
                'max_time_to_produce' => $mine_settings['PRODUCTION_TIME'],
                'max_queue_length' => BUILD_MINE_MAX_CNT,
                'material_cnt' => $mine_settings['PRODUCTION_CNT'],
            );

        $entry_point_object['craft_requests'] = array();

        foreach ($craft_requests as $k=>$craft_request) {
            $obj = array(
                'id' => $craft_request['id'],
                'time' => ($mine_settings['PRODUCTION_TIME'] - ($craft_request['dtime'] - time_current())),
            );
            $entry_point_object['craft_requests'][] = $obj;
        }
        break;
    case 'produce':
        $mine_settings = get_hash(building_settings_list(array('building_type_id' => $type_id, 'building_level' => $building['level'])), 'name', 'value');

        if($estate['work'] < $mine_settings['PRODUCTION_COST']){
            $error = 'Не хватает энергии!';
            break;
        }

        if(building_mine_user_count(array('user_id' => $session_user['id'])) >= BUILD_MINE_MAX_CNT){
            $error = 'Вы не можете производить так много! Одновременно!';
            break;
        }

        if($mine_settings['PRODUCTION_NEED_ARTIKUL_ID']) {
            $user_artifact_amount = artifact_amount($mine_settings['PRODUCTION_NEED_ARTIKUL_ID'], $session_user['id']);
            if($user_artifact_amount < $mine_settings['PRODUCTION_NEED_CNT']){
                $error = 'Вам не хватает ресурсов чтобы начать работу!';
                break;
            }
        }

        $result = estate_save(array(
            'id' => $estate['id'],
            '_set' => ' work = work - '.$mine_settings['PRODUCTION_COST'],
        ));

        if(!$result){
            $error = 'Ошибка при начале работы!';
            break;
        }

        //Изъятие нужных ресов
        if($mine_settings['PRODUCTION_NEED_ARTIKUL_ID']) {
            $artifact_info = artifact_artikul_get($mine_settings['PRODUCTION_NEED_ARTIKUL_ID']);
            artifact_remove($mine_settings['PRODUCTION_NEED_ARTIKUL_ID'], $mine_settings['PRODUCTION_NEED_CNT'], $session_user['id']);

            $msg = 'Вы использовали "'.$artifact_info['title'].'" '.$mine_settings['PRODUCTION_NEED_CNT'].' шт.';
            chat_msg_send_system($msg, CHAT_CHF_USER, $session_user['id']);
        }

        building_mine_user_save(array(
            'user_id' => $session_user['id'],
            'clan_id' => $session_user['clan_id'],
            'artikul_id' => $mine_settings['PRODUCTION_ARTIKUL_ID'],
            'cnt' => $mine_settings['PRODUCTION_CNT'],
            'dtime' => time_current() + $mine_settings['PRODUCTION_TIME'],
        ));

        $entry_point_object['status'] = 100;

        break;
    case 'pick':
        $craft_id = intval($_REQUEST['craft_id']);
        if(!$craft_id){
            $error = "Ошибка";
            break;
        }

        $craft = building_mine_user_get(array(
            'id' => $craft_id,
            'user_id' => $session_user['id']
        ));

        if(!$craft){
            $error = "Продукт не найден";
            break;
        }

        if($craft['dtime'] > time_current()){
            $error = "Время собирать еще не пришло!";
            break;
        }

        if($craft['artikul_id'] && $craft['cnt']){
            artifact_add($craft['artikul_id'], $craft['cnt'], $session_user['id']);
        }

        $artifact_artikul = artifact_artikul_get($craft['artikul_id']);
        $msg = 'Вы забрали продукт "'.$artifact_artikul['title'].'" '.$craft['cnt'].'шт.';
        chat_msg_send_system($msg, CHAT_CHF_USER, $session_user['id']);

        building_mine_user_delete($craft_id);


        $entry_point_object['status'] = 100;
        break;
    default:
        $error = translate('Не задано действие');
}