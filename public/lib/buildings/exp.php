<?

switch ($action) {
    case 'conf':
        $exp_settings = get_hash(building_settings_list(array('building_type_id' => BUILDING_TYPE_EXP, 'building_level' => $building['level'])), 'name', 'value');
        $in_estate_time = $estate['last_active']-$estate['last_enter'];
        $add_exp = min($exp_settings['LIMIT'], floor($exp_settings['LIMIT']*($in_estate_time/(28800))));

        $entry_point_object = $entry_point_object + array(
                'exp' => ($estate['flags'] & ESTATE_FLAG_EXP_USE ? intval(min($exp_settings['LIMIT'], $estate['exp_value'])) : 0),
                'exp_use' => ($estate['flags'] & ESTATE_FLAG_EXP_USE ? 1 : 0),
                'exp_limit' => $exp_settings['LIMIT'],
                'exp_percent' => $exp_settings['PERCENT'],
                'exp_decr' => ($estate['flags'] & ESTATE_FLAG_EXP_DECR),
                'cost' => $exp_settings['COST'],
            );
        break;
    case 'set_direction':
        $dir = intval($_REQUEST['dir']);
        if ($dir) {
            $estate['flags'] |= ESTATE_FLAG_EXP_DECR;
            $message = translate('Вы перешли в режим уменьшения опыта за бой.');
        } else {
            $estate['flags'] &= ~ESTATE_FLAG_EXP_DECR;
            $message = translate('Вы перешли в режим увеличения опыта за бой.');
        }

        chat_msg_send_system($message, CHAT_CHF_USER, $session_user['id']);
        estate_save(array(
            'id' => $estate['id'],
            'flags' => $estate['flags'],
        ));
        $entry_point_object['status'] = 100;
        break;
    case 'store':
        estate_flush_last_enter($estate);
        estate_save(array(
            'id' => $estate['id'],
            'flags' => $estate['flags'] & ~ESTATE_FLAG_EXP_USE,
        ));
        chat_msg_send_system(translate('Вы перешли в режим накапливания опыта!'),CHAT_CHF_USER,$session_user['id']);
        $entry_point_object['status'] = 100;
        break;
    case 'use':
        do {
            $energy = intval($_REQUEST['energy']);
            $exp_settings = get_hash(building_settings_list(array('building_type_id' => BUILDING_TYPE_EXP, 'building_level' => $building['level'])), 'name', 'value');
            if ($energy) {
                $work_price = intval($exp_settings['COST'] * WORK_TO_MONEY_COURSE);
                $avail_work = $estate ? $estate['work'] : 0;
                if ($work_price > $avail_work) {
                    $error = translate('У Вас недостаточно энергии!');
                    break;
                }

                $status = (bool)estate_save(array(
                    '_cnt' => true,
                    '_set' => sql_pholder(' work = work - ? ', $work_price),
                    '_add' => sql_pholder(' AND user_id = ? AND work >= ? ', $session_user['id'], $work_price),
                ));

                if ($status) {
                    logserv_log_note(array(
                        'note' => sprintf(translate('Использование статуи опыта, изъято энергии %d'), $work_price),
                    ), $session_user);

                    chat_msg_send_special(CODE_CALL_JSFUNC, CHAT_CHF_USER, $session_user['id'], array('func' => "updateSwf({'lvl':''})"));
                    $money_str = html_work_str($work_price);
                    $entry_point_object['reload'] = 1;
                }
            } else{
                if ($session_user['money'] < $exp_settings['COST']) {
                    $error = translate('Недостаточно денег!');
                    break;
                }

                if ($estate['flags'] & ESTATE_FLAG_EXP_USE) {
                    $error = translate('Статуя опыта уже используется!');
                    break;
                }
                $operations = array(MONEY_STAT_OPERATION_LOST,MONEY_STAT_OPERATION_PURE_LOST);
                $status = user_make_payment(MONEY_TYPE_GAME,$session_user['id'], -$exp_settings['COST'], translate('Использование статуи опыта'),false, $operations);
                if ($status) {
                    logserv_log_operation(array(
                        'money_type' => MONEY_TYPE_GAME,
                        'amount' => -$exp_settings['COST'],
                        'comment' => translate('Использование статуи опыта'),
                    ), $session_user);
                    $money_str = html_money_str(MONEY_TYPE_GAME,$exp_settings['COST']);
                }
            }

            estate_flush_last_enter($estate);

            if ($status) {
                estate_save(array(
                    'id' => $estate['id'],
                    'flags' => $estate['flags']|ESTATE_FLAG_EXP_USE,
                ));

                user_stat_update($session_user['id'], USER_STAT_TYPE_ESTATE_BUILDING_USE, BUILDING_TYPE_EXP);
                chat_msg_send_system(sprintf(translate('Вы перешли в режим использования накопленного опыта! Изъято %s.'), $money_str),CHAT_CHF_USER,$session_user['id']);
                $entry_point_object['status'] = 100;
            } else {
                $error = translate('Ошибка при оплате!');
                break;
            }
        } while(0);
        break;
    default:
        $error = translate('Не задано действие');
}