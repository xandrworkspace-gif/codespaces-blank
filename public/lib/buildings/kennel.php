<?

require_once("lib/bonus.lib");

function bot_artikul_kennel_list($user = array(), $update = false){
    if(!$user) return false;
    $kennel_cache = new Cache(md5('BOT_ARTIKUL_KENNEL_'.$user['id']));
    if (!$kennel_cache->get() || $update) {// Проверим кеш

        $bot_artikul_list = make_hash(bot_artikul_list(false, sql_pholder(' AND bonus_id != 0 AND flags & ?#BOT_FLAG_KENNEL')));

        //vardump($bot_artikul_list);

        $stat_list = make_hash(user_stat_list(array(
            'user_id' => $user['id'],
            'type_id' => USER_STAT_TYPE_BOT,
        ), sql_pholder(' AND value >= ?', BUILD_KENNEL_NEED_BOT_CNT), 'value,object_id'),'object_id');

        //vardump($stat_list);

        $allow_bot_artikul = array();
        foreach ($stat_list as $stat){
            $bot_artikul = $bot_artikul_list[$stat['object_id']];
            if($bot_artikul){
                $allow_bot_artikul[$bot_artikul['id']] = $bot_artikul;
            }
        }

        $kennel_cache->update($allow_bot_artikul,60);
        return $allow_bot_artikul;
    }else{
        return $kennel_cache->get();
    }
}

switch ($action) {
    case 'conf':

        $bot_artikul_list = bot_artikul_kennel_list($session_user);

        //vardump($bot_artikul_list);

        if(!$bot_artikul_list){
            $error = translate('Вы не можете начать охоту, т.к не заслужили доверие лидера Псарни!');
            break;
        }

        $kennel_user = building_kennel_user_get(array(
            'user_id' => $session_user['id'],
        ));
        if(!$kennel_user){
            building_kennel_user_save(array(
                'user_id' => $session_user['id'],
                'can_kill' => BUILD_KENNEL_FREE_CNT,
                'kill_cnt' => BUILD_KENNEL_DEFAULT_CNT,
                'dtime' => mktime(23,59,59) + 1,
            ));
            $kennel_user = building_kennel_user_get(array(
                'user_id' => $session_user['id'],
            ));
        }

        if(!$kennel_user){
            $error = translate('Не найдены настройки!');
            break;
        }

        $entry_point_object['bot_list'] = array();
        foreach ($bot_artikul_list as $bot){
            $obj = array(
                'id' => $bot['id'],
                'nick' => $bot['nick'],
                'picture' => $bot['picture'],
                'money_min' => $bot['money_min'],
                'money_max' => $bot['money_max'],
                'price' => $bot['k_price'],
                'exp' => $bot['k_exp'],
            );
            $entry_point_object['bot_list'][] = $obj;
        }


        $entry_point_object['user_times'] = array(
            'KENNEL_TIME' => $kennel_user['last_use'],
            'KENNEL_BIG_TIME' => $kennel_user['last_use_big'],
        );
        $entry_point_object['settings'] = array(
            'KILL10_PRICE_EXT' => BUILD_KENNEL_KILL10_PRICE,
            'KILL10_TIMER' => BUILD_KENNEL_KILL10_TIMER,
            'KILL1_TIMER' => BUILD_KENNEL_KILL1_TIMER,
        );

        $entry_point_object['can_kill'] = $kennel_user['can_kill'];
        $entry_point_object['kill_cnt'] = $kennel_user['kill_cnt'];
        break;
    //https://elements.dwar.xyz/estate_conf.php?type%5Fid=20&mode=building&action=use&kennel%5Fbot%5Fid=14&big%5Fhunt=0
    case 'use':
        $kennel_bot_id = intval($_REQUEST['kennel_bot_id']);
        $big_hunt = intval($_REQUEST['big_hunt']);
        do {
            $avialible_bots = bot_artikul_kennel_list($session_user);
            $bot_artikul = $avialible_bots[$kennel_bot_id];
            if(!$bot_artikul){
                $error = 'Вы не можете заказать убийство этого монстра!';
                break;
            }

            if(!$bot_artikul['k_price'] || !$bot_artikul['k_exp']){
                $error = 'Извините, данный монстр еще исследуюется лидерами Псарни!';
                break;
            }

            $kennel_user = building_kennel_user_get(array(
                'user_id' => $session_user['id'],
            ));

            if(!$kennel_user){
                $error = 'Неизвестная ошибка!';
                break;
            }

            if((!$big_hunt && $kennel_user['can_kill'] <= 0) || ($big_hunt && $kennel_user['kill_cnt'] < 10) || (!$big_hunt && $kennel_user['kill_cnt'] <= 0)){
                $error = 'На сегодня вы уже использовали все попытки!';
                break;
            }

            $kennel_user = array(
                'id' => $kennel_user['id'],
            );

            //Проверяем деньги
            if($big_hunt){
                if(user_get_money_amount(MONEY_TYPE_GAME, $session_user['id']) < ($bot_artikul['k_price'] * 10)){
                    $error = 'Недостаточно денег!';
                    break;
                }
                if(user_get_money_amount(MONEY_TYPE_GOLD, $session_user['id']) < BUILD_KENNEL_KILL10_PRICE){
                    $error = 'Недостаточно денег!';
                    break;
                }
            }else{
                if(user_get_money_amount(MONEY_TYPE_GAME, $session_user['id']) < $bot_artikul['k_price']){
                    $error = 'Недостаточно денег!';
                    break;
                }
            }

            //Отнимаем деньги
            if($big_hunt){
                $status = user_make_payment(MONEY_TYPE_GAME, $session_user['id'], -($bot_artikul['k_price'] * 10));
                $status2 = user_make_payment(MONEY_TYPE_GOLD, $session_user['id'], -BUILD_KENNEL_KILL10_PRICE);
                if(!$status || !$status2){
                    $error = 'Ошибка при оплате!';
                    break;
                }
            }else{
                $status = user_make_payment(MONEY_TYPE_GAME, $session_user['id'], -$bot_artikul['k_price']);
                if(!$status){
                    $error = 'Ошибка при оплате!';
                    break;
                }
            }

            //Отнимаем счетчики
            if($big_hunt){
                $kennel_user['_set'] = sql_pholder(' kill_cnt = kill_cnt - 10');
            }else{
                $kennel_user['_set'] = sql_pholder(' kill_cnt = kill_cnt - 1, can_kill = can_kill - 1');
            }

            //Начало операций
            $start_kennel = microtime(true);

            //Операции там какие-то

            $estate = estate_get(array(
                'user_id' => $session_user['id'],
            ));

            //Добавление опыта
            user_stat_update($session_user['id'], USER_STAT_TYPE_SKILL, USER_STAT_SKILL_EXP, $bot_artikul['k_exp'], USER_STAT_OP_INC, array('exit_nostat' => 1));

            //Учет убийств монстров
            user_stat_update($session_user['id'], USER_STAT_TYPE_BOT, $bot_artikul['id'], ($big_hunt ? 10 : 1));

            //Добавление энергии
            if ($work_update = estate_user_work_update($session_user, true, $estate, ($big_hunt ? WORK_FROM_BOT * 10 : WORK_FROM_BOT))) {
                $estate['work'] += $work_update;
                chat_msg_send_system('Вы получили '.($big_hunt ? WORK_FROM_BOT * 10 : WORK_FROM_BOT).' энергии.', CHAT_CHF_USER, $session_user['id']);
            }

            //Добавление хуйни
            //Добавляем убийство душ... че за хуйня чел блять?
            if($session_user['id'] == DEV_ACCOUNT_ID){
                building_user_res_add($session_user['id'], BUILDING_RES_SPIRIT_BOT, ($big_hunt ? 10 : 1));
            }

            //Выдача бонусов
            bonus_apply_many($session_user, $bot_artikul['bonus_id'], false, ($big_hunt ? 10 : 1));

            //Выдача денег
            $money = ($big_hunt ? (rand($bot_artikul['money_min']*100,$bot_artikul['money_max']*100)/100) * 10 : rand($bot_artikul['money_min']*100,$bot_artikul['money_max']*100)/100);
            user_make_payment(MONEY_TYPE_GAME, $session_user['id'], $money);
            chat_msg_send_system('Вы получили '.html_money_str(MONEY_TYPE_GAME, $money).'.', CHAT_CHF_USER, $session_user['id']);

            //Окончание операций
            $stop_kennel = round(microtime(true) - $start_kennel,4);
            //Запишем разницу
            if($big_hunt){
                $kennel_user['last_use_big'] = time_current() - $stop_kennel; //Игрок не должен ждать больше 10 или 30 секунд.. зачем?
            }else{
                $kennel_user['last_use'] = time_current() - $stop_kennel; //Игрок не должен ждать больше 10 или 30 секунд.. зачем?
            }

            building_kennel_user_save($kennel_user);

            $entry_point_object['status'] = 100;
        } while(0);
        break;
    default:
        $error = translate('Не задано действие');
}