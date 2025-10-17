<?
require_once('lib/pet.lib');

$workshop_settings = get_hash(building_settings_list(array('building_type_id' => BUILDING_TYPE_WORKSHOP, 'building_level' => $building['level'])), 'name', 'value');
$user_recipes = make_hash(recipe_user_list($session_user['id']), 'artikul_id');
$recipe_artikuls = $artikul_ids = $result_artikul_ids = $bag_artifacts = $craft_requests = array();
if ($user_recipes) {
    $recipe_artikuls = make_hash(recipe_list(false, sql_pholder(' and id in (?@) and skill_value<=? order by skill_value desc', array_keys($user_recipes), intval($workshop_settings['MAX_SKILL']))));
    foreach($recipe_artikuls as $recipe_artikul) {
        $result_artikul_ids[] = $recipe_artikul['create_artikul_id'];
        for($i=1;$i<8;$i++) {
            if ($recipe_artikul['res'.$i.'_id']) {
                $artikul_ids[] = $recipe_artikul['res'.$i.'_id'];
            }
        }
    }

    if ($artikul_ids) {
        $bag_artifacts = user_get_artifact_list($session_user['id'], '', sql_pholder(' and artikul_id in (?@)', $artikul_ids));
        if (!$bag_artifacts) $bag_artifacts = array();
    }

    $artikul_hash_ids = $artikul_ids;
    foreach($result_artikul_ids as $result_artikul_id) {
        $artikul_hash_ids[] = $result_artikul_id;
    }

    if ($artikul_hash_ids) {
        $artikul_hash = make_hash(artifact_artikul_list(array('id' => $artikul_hash_ids), '', 'id,title,picture,price,price_type'));
    }

    $craft_requests = make_hash(estate_craft_request_list(array('user_id' => $session_user['id'])));
    ksort($craft_requests);
    $current_duration = $workshop_settings['CRAFT_TIME']*60;
    foreach($craft_requests as $craft_request) {
        $current_duration -= $recipe_artikuls[$craft_request['recipe_id']]['duration'] * $craft_request['amount'];
    }
    $current_duration = max($current_duration,0);

}

switch ($action) {
    case 'conf':
        $entry_point_object = $entry_point_object + array(
                'duration_left' => $current_duration,
                'reduce_duration' => 0,
            );
        $entry_point_object['recipes'] = array();
        foreach($recipe_artikuls as $recipe_artikul) {
            $obj = array(
                'recipe_id' => $recipe_artikul['id'],
                'artikul_id' => $recipe_artikul['create_artikul_id'],
                'title' => $artikul_hash[$recipe_artikul['create_artikul_id']]['title'],
                'cnt' => intval($recipe_artikul['workshop_artikul_num']),
                'picture' => PATH_IMAGE_ARTIFACTS.$artikul_hash[$recipe_artikul['create_artikul_id']]['picture'],
                'duration' => $recipe_artikul['duration'],
                'parts' => array(),
            );
            for($i=1;$i<8;$i++) {
                if ($recipe_artikul['res'.$i.'_id']) {
                    $obj['parts'][] = array(
                        'artikul_id' => $recipe_artikul['res'.$i.'_id'],
                        'title' => $artikul_hash[$recipe_artikul['res'.$i.'_id']]['title'],
                        'picture' => PATH_IMAGE_ARTIFACTS.$artikul_hash[$recipe_artikul['res'.$i.'_id']]['picture'],
                        'cnt' => $recipe_artikul['res'.$i.'_num'],
                    );
                }
            }
            $entry_point_object['recipes'][] = $obj;
        }

        $entry_point_object['bag_artifacts'] = array();
        foreach($bag_artifacts as $bag_artifact) {
            $obj = array(
                'artikul_id' => $bag_artifact['artikul_id'],
                'picture' => PATH_IMAGE_ARTIFACTS.$artikul_hash[$bag_artifact['artikul_id']]['picture'],
                'title' => $artikul_hash[$bag_artifact['artikul_id']]['title'],
                'cnt' => $bag_artifact['cnt'],
            );
            $entry_point_object['bag_artifacts'][] = $obj;
        }

        $entry_point_object['craft_requests'] = array();
        foreach($craft_requests as $craft_request) {
            $total_duration = $craft_request['amount'] * $recipe_artikuls[$craft_request['recipe_id']]['duration'];
            $obj = array(
                'id' => $craft_request['id'],
                'recipe_id' => $craft_request['recipe_id'],
                'amount' => $craft_request['amount'],
                'duration' => $total_duration,
                'time' => max(0, intval(time_current()-$craft_request['start_time'])),
            );
            $entry_point_object['craft_requests'][] = $obj;
        }
        break;
    case 'craft':
        $recipe_id = intval($_REQUEST['recipe_id']);
        $amount = intval($_REQUEST['amount']);
        do{
            if (!$recipe_id || !$recipe_artikuls[$recipe_id]) {
                $error = translate('Рецепт не найден!');
                break;
            }

            $recipe = $recipe_artikuls[$recipe_id];

            $profession_id = $recipe['profession_id'];
            $skill_id = $profession_info[$profession_id]['skill_id'];
            $user_skill = user_get_skill_info($session_user['id'], $skill_id);
            $user_skill = intval($user_skill['skills'][$skill_id]['value']);
            if ($user_skill < $recipe['skill_value']) {
                $error = translate('Вы не можете использовать этот рецепт, у Вас недостаточно мастерства!');
                break;
            }

            if ($amount <= 0) {
                $error = translate('Неправильное количество!');
                break;
            }

            if ($amount * $recipe['duration'] > $current_duration) {
                $error = translate('Недостаточно времени для изготовления такого количества!');
                break;
            }

            $artikul_cost = array();
            for($i=1;$i<8;$i++) {
                if ($recipe['res'.$i.'_id']) {
                    $artikul_cost[$recipe['res'.$i.'_id']] = $recipe['res'.$i.'_num']*$amount;
                }
            }

            $check_artikul_cost = $artikul_cost;

            foreach($bag_artifacts as $bag_artifact) {
                if (isset($check_artikul_cost[$bag_artifact['artikul_id']])) {
                    $check_artikul_cost[$bag_artifact['artikul_id']] -= min($check_artikul_cost[$bag_artifact['artikul_id']], $bag_artifact['cnt']);
                }
            }

            if (array_sum($check_artikul_cost)) {
                $error = translate('Недостаточно ингредиентов!');
                break;
            }

            foreach($artikul_cost as $artikul_id => $artikul_amount) {
                $cnt = artifact_remove($artikul_id, $artikul_amount, $session_user['id']);
                if ($cnt != $artikul_amount) {
                    $error = translate('Ошибка при создании рецепта!');
                    break;
                }
            }

            if ($craft_requests){
                $craft_requests_hash = make_hash($craft_requests,'start_time');
                krsort($craft_requests_hash);
                $last_request = reset($craft_requests_hash);
                $last_request_total_duration = $last_request['amount'] * $recipe_artikuls[$last_request['recipe_id']]['duration'];
                $last_request_end_time = $last_request['start_time'] + $last_request_total_duration;
            }
            $start_time = max(time_current(),intval($last_request_end_time));
            estate_craft_request_save(array(
                'user_id' => $session_user['id'],
                'ctime' => time_current(),
                'recipe_id' => $recipe_id,
                'amount' => $amount,
                'start_time' => $start_time,
            ));

            foreach($artikul_cost as $artikul_id => $artikul_amount) {
                logserv_log_operation(array(
                    'artikul' => $artikul_id,
                    'cnt' => -$artikul_amount,
                    'comment' => sprintf(translate('Заказ %d &laquo;%s&raquo; в мастерской'), $amount, $recipe['title']),
                ),$session_user);

                $t[] = sprintf(translate('<a href="#" onClick="showArtifactInfo(false,%d);return false;">%s</a> <b>%d шт</b>'),$artikul_id, $artikul_hash[$artikul_id]['title'], $artikul_amount);
            }
            $t_c = sprintf(translate('<a href="#" onClick="showArtifactInfo(false,%d);return false;">%s</a> <b>%d шт</b>'),$recipe['create_artikul_id'], $artikul_hash[$recipe['create_artikul_id']]['title'], intval($recipe['workshop_artikul_num']) * $amount);
            chat_msg_send_system(sprintf(translate('В мастерской началось производство %s. Изъято: %s.'), $t_c, implode(',', $t)),CHAT_CHF_USER,$session_user['id']);
            $entry_point_object['status'] = 100;
        } while(0);
        break;
    case 'cancel':
        $craft_request_id = intval($_REQUEST['craft_request_id']);
        do {
            if (!$craft_request_id) {
                $error = translate('Неправильный параметр!');
                break;
            }
            $craft_request = estate_craft_request_get($craft_request_id);
            if (!$craft_request || ($craft_request['user_id'] != $session_user['id'])) {
                $error = translate('Заявка не найдена!');
                break;
            }

            $recipe = $recipe_artikuls[$craft_request['recipe_id']];
            if (!$recipe) {
                $error = translate('Рецепт не найден!');
                break;
            }

            $total_duration = $craft_request['amount'] * $recipe['duration'];

            if ($total_duration < time_current()-$craft_request['start_time']) {
                $error = translate('Нельзя удалить готовый продукт!');
                break;
            }
            $time_offset = ($craft_request['start_time'] < time_current()) ? ($craft_request['start_time'] + $total_duration - time_current()) : $total_duration;
            estate_craft_request_save(array(
                '_add' => sql_pholder(' AND user_id=? AND start_time > ?', $session_user['id'], $craft_request['start_time']),
                '_set' => sql_pholder('start_time = start_time - ?', $time_offset),
            ));

            if (!estate_craft_request_delete($craft_request_id)) {
                $error = translate('Произошла ошибка, попробуйте позднее!');
                break;
            }

            for($i=1;$i<8;$i++) {
                if ($recipe['res'.$i.'_id']) {
                    artifact_add($recipe['res'.$i.'_id'], $recipe['res'.$i.'_num'] * $craft_request['amount'], $session_user['id']);

                    logserv_log_operation(array(
                        'artikul' => $recipe['res'.$i.'_id'],
                        'cnt' => $recipe['res'.$i.'_num'] * $craft_request['amount'],
                        'comment' => sprintf(translate('Отмена %d &laquo;%s&raquo; в мастерской'), $craft_request['amount'], $recipe['title']),
                    ),$session_user);
                    $t[] = sprintf(translate('<a href="#" onClick="showArtifactInfo(false,%d);return false;">%s</a> <b>%d шт</b>'),$recipe['res'.$i.'_id'], $artikul_hash[$recipe['res'.$i.'_id']]['title'], $recipe['res'.$i.'_num'] * $craft_request['amount']);
                }
            }

            $t_c = sprintf(translate('<a href="#" onClick="showArtifactInfo(false,%d);return false;">%s</a> <b>%d шт</b>'),$recipe['create_artikul_id'], $artikul_hash[$recipe['create_artikul_id']]['title'], intval($recipe['workshop_artikul_num']) * $craft_request['amount']);
            chat_msg_send_system(sprintf(translate('В мастерской прекращено производство %s.  Получено: %s.'), $t_c, implode(',', $t)),CHAT_CHF_USER,$session_user['id']);
            $entry_point_object['status'] = 100;
        } while(0);
        break;
    case 'pick':
        $craft_request_id = intval($_REQUEST['craft_request_id']);
        do {
            if (!$craft_request_id) {
                $error = translate('Неправильный параметр!');
                break;
            }
            $craft_request = estate_craft_request_get($craft_request_id);
            if (!$craft_request || ($craft_request['user_id'] != $session_user['id'])) {
                $error = translate('Заявка не найдена!');
                break;
            }

            $recipe = $recipe_artikuls[$craft_request['recipe_id']];
            if (!$recipe) {
                $error = translate('Рецепт не найден!');
                break;
            }

            $total_duration = $craft_request['amount'] * $recipe['duration'];

            if ($total_duration > time_current()-$craft_request['start_time']) {
                $error = translate('Ещё не готово!');
                break;
            }

            if (!estate_craft_request_delete($craft_request_id)) {
                $error = translate('Произошла ошибка, попробуйте позднее!');
                break;
            }

            artifact_add($recipe['create_artikul_id'], $recipe['workshop_artikul_num'] * $craft_request['amount'], $session_user['id'], false, null, array('flags' => intval($recipe['force_flags'])));

            user_stat_update($session_user['id'], USER_STAT_TYPE_ESTATE_BUILDING_USE, BUILDING_TYPE_WORKSHOP);

            logserv_log_operation(array(
                'artikul' => $recipe['create_artikul_id'],
                'cnt' => $recipe['workshop_artikul_num'] * $craft_request['amount'],
                'comment' => sprintf(translate('Получение результата %d &laquo;%s&raquo; в мастерской'), $craft_request['amount'], $recipe['title']),
            ),$session_user);
            $entry_point_object['status'] = 100;
        } while(0);
        $craft_request_id = intval($_REQUEST['craft_request_id']);
        break;
    default:
        $error = translate('Не задано действие');
}