<?
require_once('lib/pet.lib');

$petshop_settings = get_hash(building_settings_list(array('building_type_id' => BUILDING_TYPE_PETSHOP, 'building_level' => $building['level'])), 'name', 'value');
$pet_training_types = array();
$i = 0;
foreach($petshop_settings as $training) {
    $training_parts = explode(';', $training);
    if (count($training_parts) != 3) continue;
    $i++;
    $pet_training_types[$i] = array(
        'id' => $i,
        'duration' => $training_parts[0],
        'feed_cost' => $training_parts[1],
        'exp' => $training_parts[2],
    );
}

switch ($action) {
    case 'conf':

        NODE_SWITCH(false, $session_user['id']);

        $pet_list = pet_list(false, $session_user['id'], sql_pholder(' and (not flags & (?#PET_FLAG_PUTTED_ON))'));
        // Посмотрим не надо ли завершить кому-нибудь тренировку
        $need_reload = false;
        foreach($pet_list as $pet) {
            if ($pet['training_time'] && ($pet['training_time']<time_current())) {
                if ($pet_training_types[$pet['training_type']]) {
                    pet_exp($pet, $pet_training_types[$pet['training_type']]['exp']);
                }
                pet_save(array(
                    'id' => $pet['id'],
                    'training_type' => 0,
                    'training_time' => 0,
                    'flags' => $pet['flags'] & ~PET_FLAG_TRAINING,
                ));
                $need_reload = true;
            }
        }
        if ($need_reload) {
            $pet_list = pet_list(false, $session_user['id'], sql_pholder(''));
        }

        $artikul_ids = array_keys(make_hash($pet_list, 'artikul_id'));
        if ($artikul_ids) {
            $pet_artikuls = make_hash(pet_artikul_list(array('id' => $artikul_ids)));
        }

        $entry_point_object['pets'] = array();

        foreach($pet_list as $pet) {
            if ($pet_artikuls[$pet['artikul_id']]['level'] >= $session_user['level']) continue;
            $obj = array(
                'id' => $pet['id'],
                'title' => $pet['title'],
                'picture' => PATH_IMAGE_PETS.$pet_artikuls[$pet['artikul_id']]['fight_picture'],
                'picture2' => PATH_IMAGE_PETS.$pet_artikuls[$pet['artikul_id']]['picture2'],
                'level' => $pet_artikuls[$pet['artikul_id']]['level'],
                'exp' => $pet['exp'],
                'exp_max' => $pet['exp_max'],
                'time_left' => ($pet['training_time'] ? intval($pet['training_time']-time_current()) : 0),
                'time_total' => intval($pet_training_types[$pet['training_type']]['duration']),
                'error' => '',
                'quality' => $pet_artikuls[$pet['artikul_id']]['quality']
            );
            $entry_point_object['pets'][] = $obj;
        }
        $entry_point_object['training_types'] = array();
        foreach($pet_training_types as $training_type) {
            $obj = array(
                'id' => $training_type['id'],
                'duration' => floor($training_type['duration']/3600),
                'feed_cost' => $training_type['feed_cost'],
                'exp' => $training_type['exp'],
            );
            $entry_point_object['training_types'][] = $obj;
        }
        break;
    case 'train':
        $pet_id = intval($_REQUEST['pet_id']);
        $training_type = intval($_REQUEST['training_type']);
        do {
            if (!$pet_training_types[$training_type]) {
                $error = translate('Неправильный тип тренировки!');
                break;
            }
            $pet = pet_get($pet_id);
            if (!$pet || ($pet['user_id'] != $session_user['id'])) {
                $error = translate('Питомец не найден!');
                break;
            }

            if ($pet['flags'] & (PET_FLAG_PUTTED_ON)) {
                $error = translate('Питомец не готов к тренировкам!');
                break;
            }

            if ($pet['flags'] & PET_FLAG_TRAINING) {
                $error = translate('Питомец уже тренируется!');
                break;
            }

            if (pet_get(array('user_id' => $session_user['id']), sql_pholder(' and training_time>?', time_current()))) {
                $error = translate('Можно тренировать только одного питомца!');
                break;
            }

            $pet_artikul = pet_artikul_get($pet['artikul_id']);

            if ($pet_artikul['level'] >= $session_user['level']) {
                $error = translate('Нельзя тренировать питомца этого уровня!');
                break;
            }

            $food_artifact = artifact_get(array('artikul_id' => $pet_artikul['food_artikul_id'], 'user_id' => $session_user['id']));
            if ($food_artifact['cnt'] < $pet_training_types[$training_type]['feed_cost']) {
                $error = translate('Недостаточно корма!');
                break;
            }

            if ($pet_training_types[$training_type]['feed_cost'] < $food_artifact['cnt']) {
                artifact_save(array(
                    'id' => $food_artifact['id'],
                    'cnt' => $food_artifact['cnt'] - $pet_training_types[$training_type]['feed_cost'],
                ));
            } else {
                artifact_delete($food_artifact);
            }

            user_stat_update($session_user['id'], USER_STAT_TYPE_ESTATE_BUILDING_USE, BUILDING_TYPE_PETSHOP);

            $ls_comment = sprintf(translate('Тренировка питомца &laquo;%s&raquo; с опытом %d на %d часов на %d опыта'),
                '<a href="'.common_build_url('pet_info.php', array('pet_id' => $pet_id)).'" target="_blank">'.$pet['title'].'</a>',
                $pet['exp'],
                $pet_training_types[$training_type]['duration'] / 3600,
                $pet_training_types[$training_type]['exp']);

            logserv_log_operation(array(
                'artikul' => $pet_artikul['food_artikul_id'],
                'cnt' => -$pet_training_types[$training_type]['feed_cost'],
                'comment' => $ls_comment,
            ),$session_user);

            pet_save(array(
                'id' => $pet['id'],
                'training_type' => $training_type,
                'training_time' => time_current()+$pet_training_types[$training_type]['duration'],
                'flags' => $pet['flags'] | PET_FLAG_TRAINING,
            ));
            $entry_point_object['reload'] = 1;
            $food_artikul = artifact_artikul_get($pet_artikul['food_artikul_id']);
            $food_str = sprintf(translate('<a href="#" onClick="showArtifactInfo(false,%d);return false;">%s</a> <b>%d шт</b>'),$pet_artikul['food_artikul_id'],$food_artikul['title'],$pet_training_types[$training_type]['feed_cost']);
            chat_msg_send_system(sprintf(translate('Ваш питомец &laquo;%s&raquo; отправлен на тренировку на %s ! Изъято %s.'), $pet['title'], html_period_str($pet_training_types[$training_type]['duration']), $food_str),CHAT_CHF_USER,$session_user['id']);
        } while(0);
        break;
    case 'train_cancel':
        $pet_id = intval($_REQUEST['pet_id']);
        do {
            $pet = pet_get($pet_id);
            if (!$pet || ($pet['user_id'] != $session_user['id'])) {
                $error = translate('Питомец не найден!');
                break;
            }

            pet_save(array(
                'id' => $pet['id'],
                'training_type' => 0,
                'training_time' => 0,
                'flags' => $pet['flags'] & ~PET_FLAG_TRAINING,
            ));
            $entry_point_object['reload'] = 1;
        } while(0);
        break;
    default:
        $error = translate('Не задано действие');
}