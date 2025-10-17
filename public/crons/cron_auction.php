<? # $Id: cron_auction.php,v 1.21 2010-01-15 09:50:10 p.knoblokh Exp $ 

chdir("..");
require_once("include/common.inc");

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) return;

require_once("lib/auction.lib");

$user_hash = array();
$auction_lot_list = auction_lot_list(false, sql_pholder(' AND rtime<? LIMIT 2000', time_current() ), 'id');

foreach ($auction_lot_list as $lot) {	
	if (!auction_lot_lock($lot['id'])) { // в параллели работает второй крон или выполняется операция над лотом с морды
		break;
	} 
	do {
		$lot = auction_lot_get($lot['id']);
		if (!$lot) break;
		$bid_user = ($lot['bid'] > 0 && $lot['bid_user_id']) ? cache_fetch($user_hash, $lot['bid_user_id'], 'user_get') : false;
		if ($lot['artifact_id']) {
			if ($bid_user) { // шмот был выкуплен

                $artifact_safe = artifact_get_safe($lot['artifact_id']);

				$msg = sprintf(translate('*** Вы выкупили %s, %s шт. на аукционе. ***'), $lot['title'], $lot['n']);
				auction_message_send(AUCTION_OPCODE_BUYOUT, $bid_user['id'], 0, $lot['artifact_id'], $lot['n'], array('text' => $msg, 'from_id' => $lot['user_id']), $lot);
				$msg = sprintf(translate('*** Ваш лот с предметом %s, %s шт. был выкуплен на аукционе игроком %s. ***'), $lot['title'], $lot['n'], $bid_user['nick']);
				$commission = money_floatval(auction_commission_end($lot['bid']));
				if ($commission > 0) {
					$msg .= '<br><br>'.sprintf(translate('Сумма сделки составила - %s<br>Налог за удачную сделку составил - %s<br>Сумма к получению - %s'), html_money_str(MONEY_TYPE_GAME, $lot['bid']), html_money_str(MONEY_TYPE_GAME, $commission), html_money_str(MONEY_TYPE_GAME, floatval($lot['bid']) - floatval($commission)));
					$buyout -= floatval($commission);
				}
				auction_message_send(AUCTION_OPCODE_PAYMENT, $lot['user_id'], floatval($lot['bid']) - floatval($commission), false, 0, array('text' => $msg, 'from_id' => $lot['bid_user_id']), $lot);
				auction_log($bid_user,$lot,floatval($lot['bid']) - floatval($commission));


                trade_log_add(TRADE_LOG_AUCTION, array(
                    'user_id' => $lot['user_id'],
                    'to_user_id' => $bid_user['id'],
                    'data' => array(
                        'art_transfer' => array(
                            array(
                                'id' => $lot['artifact_id'],
                                'aid' => $artifact_safe['artikul_id'],
                                'cnt' => $lot['n'],
                            ),
                        ),
                    ),
                ));
                trade_log_add(TRADE_LOG_AUCTION, array(
                    'user_id' => $bid_user['id'],
                    'to_user_id' => $lot['user_id'],
                    'data' => array(
                        'money_transfer' => array(
                            array(
                                't' => MONEY_TYPE_GAME,
                                'v' => floatval($lot['bid']) - floatval($commission),
                            ),
                        ),
                    ),
                ));

			} else { // шмот не был выкуплен
				$msg = sprintf(translate('*** Ваш лот %s, %s шт. не был выкуплен на аукционе. Аукцион возвращает Ваш лот ***'), $lot['title'], $lot['n']);
				auction_message_send(AUCTION_OPCODE_CANCEL, $lot['user_id'], 0, $lot['artifact_id'], $lot['n'], array('text' => $msg), $lot);
			}
		} else { // шмот конфискован
			if ($bid_user) {
				$msg = sprintf(translate('*** Торги за предмет %s, %s шт. отменены по техническим причинам. Аукцион возвращает Вашу ставку ***'), $lot['title'], $lot['n']);
				auction_message_send(AUCTION_OPCODE_BID_REVERT, $bid_user['id'], $lot['bid'], false, 0, array('text' => $msg), $lot);
			}
		}
		auction_lot_delete($lot['id']);
	} while (0);
	auction_lot_unlock($lot['id']);
}


// Биржа
do {
	$auction_request_ids = get_hash(auction_request_list(false, sql_pholder(' AND rtime < ? LIMIT 1000', time_current()), 'id'), 'id', 'id');
	if (!$auction_request_ids)
		break;
	foreach ($auction_request_ids as $request_id) {
		if (!auction_request_lock($request_id)) { // в параллели работает второй крон или выполняется операция над лотом с морды
			continue;
		}
		$request = auction_request_get($request_id);
		if (!$request) break;
		if ($request['n'] == $request['n_max']) { // ничего не было продано
			$msg = sprintf(translate('*** Вам никто не продал %s на бирже. Средства возвращены. ***'), $request['title']);
			$money = $request['n_max'] * $request['cost'];
			auction_message_send(AUCTION_REQUEST_OPCODE_DONT_SELL, $request['user_id'], $money, 0, 0, array('text' => $msg), $request);
		} elseif ($request['n']) { // была продана только часть
			$msg = sprintf(translate('*** Вам продали %s, %d шт. (из %d желаемых) на бирже. Неизрасходованная часть денег возвращена. ***'), $request['title'], $request['n_max'] - $request['n'], $request['n_max']);
			$money = $request['n'] * $request['cost'];
			auction_message_send(AUCTION_REQUEST_OPCODE_SELL_NOT_ALL, $request['user_id'], $money, $request['artifact_id'], $request['n_max'] - $request['n'], array('text' => $msg), $request);

            trade_log_add(TRADE_LOG_REQUEST, array(
                'user_id' => 0,
                'to_user_id' => $request['user_id'],
                'data' => array(
                    'art_transfer' => array(
                        array(
                            'id' => $request['artifact_id'],
                            'aid' => $request['artikul_id'],
                            'cnt' => ($request['n_max'] - $request['n']),
                        ),
                    ),
                ),
            ));

		}
		auction_request_delete($request_id);
		auction_request_log($request);
		auction_request_unlock($request_id);
	}
} while (true);

require_once("lib/user_store.lib");

$delete_list = make_hash(user_store_list(false, sql_pholder(' AND prem_time < '.time_current().' AND flags & ?#USER_STORE_FLAG_PREMIUM')));
if($delete_list){
    //Уничтожаем прем игроков
    user_store_save(array(
        '_add' => sql_pholder(' AND id IN (?@)',array_keys($delete_list)),
        '_set' => sql_pholder(' flags = flags &~ ?#USER_STORE_FLAG_PREMIUM'),
    ));
}

//Аукцион ресурсов
require_once("lib/craft_auction.lib");
require_once('lib/area_post.lib');

$craft_auction_lot_list = craft_auction_lot_list(false, sql_pholder(' AND rtime<? LIMIT 2000', time_current() ));
foreach ($craft_auction_lot_list as $lot) {
    if (!craft_auction_lot_lock($lot['id'])) { // в параллели работает второй крон или выполняется операция над лотом с морды
        break;
    }
    do {
        $lot = craft_auction_lot_get($lot['id']);
        if (!$lot) break;

        if($lot['cnt'] > 0){
            //Возвращаем ресурсы
            $user_res = building_res_user_get(array(
                'artikul_id' => $lot['artikul_id'],
                'user_id' => $lot['user_id'],
            ));
            if($user_res){
                building_res_user_save(array(
                    'id' => $user_res['id'],
                    '_set' => sql_pholder(' cnt = cnt + ?', $lot['cnt']),
                ));
            }else{
                building_res_user_save(array(
                    'artikul_id' => $lot['artikul_id'],
                    'user_id' => $lot['user_id'],
                    'cnt' => $lot['cnt'],
                ));
            }

            $param = array(
                'from_id' => 0,
                'to_id' => $lot['user_id'],
                'unread' => 1,
                'type_id' => MSG_TYPE_SYS_NORMAL,
                'stime' => time_current(),
                'rtime' => time_current() + area_post_message_ttl(MSG_TYPE_SYS_NORMAL, true),
                'flags' => POST_MSG_FLAG_ALLOW_HTML,
            );

            $param['subject'] = translate('Аукцион ресурсов: окончания времени лота');

            $res_art = building_res_artikul_get($lot['artikul_id']);
            $sell_cnt = $lot['artikul_cnt'] - $lot['cnt'];
            $res_html = '<img src="/'.PATH_IMAGE_BUILDING_RES.$res_art['picture'].'"> '.$res_art['title'];
            if($sell_cnt > 0){
                $param['text'] = sprintf(translate('**** Вы смогли продать '.$sell_cnt.' '.$res_html.' ****<br>**** Вам было возвращено '.$lot['cnt'].' '.$res_html.' ****'));
            }else{
                $param['text'] = sprintf(translate('**** К сожалению продать '.$res_html.' не удалось ****<br>**** Вам было возвращено '.$lot['cnt'].' '.$res_html.' ****'));
            }

            area_post_message_save($param);
        }
        craft_auction_lot_delete($lot['id']);
    } while (0);
    craft_auction_lot_unlock($lot['id']);
}

?>