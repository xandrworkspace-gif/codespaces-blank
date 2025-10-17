<?php

chdir("..");
require_once("include/common.inc");
require_once("lib/session.lib");

common_init();


require_once("lib/area_post.lib");
$area_post_task_list = area_post_task_list(false, ' AND (status = 1 OR status = 2)');
foreach ($area_post_task_list as $area_post_task){
    if(!area_post_task_lock($area_post_task['id'])) continue; // Если нет лока, то иди нахуй!

    if($area_post_task['status'] == 1) {
        area_post_task_save(array(
            'id' => $area_post_task['id'],
            'status' => 2,
        ));
    } //Взяли в работу

    if(area_post_task_user_count(array('task_id' => $area_post_task['id'], 'status' => 0, 'pcnt' => 0)) == 0){
        area_post_task_save(array(
            'id' => $area_post_task['id'],
            'status' => 3,
        )); //Завершили
    }
    $area_post_task_user_list = area_post_task_user_list(array('task_id' => $area_post_task['id'], 'status' => 0, 'pcnt' => 0));
    foreach ($area_post_task_user_list as $area_post_task_user){

        //Отправка письма

        $user = user_get($area_post_task_user['user_id']);
        if(!$user){
            area_post_task_user_delete($area_post_task_user['user_id']);
        }

        $param = array(
            'to_id' => $user['id'],
            'subject' => $area_post_task['title'],
            'text' => $area_post_task['text'],
            'unread' => 1,
            'type_id' => MSG_TYPE_SYS_NORMAL,
            'area_id' => $area_post_task['area_id'],
            'stime' => time_current(),
            'rtime' => time_current() + area_post_message_ttl(MSG_TYPE_NORMAL, true),
        );
        $param['flags'] |= POST_MSG_FLAG_ADMIN;

        // Если в письме перечислены деньги
        if ($area_post_task['money']) {
            $param['money'] = $area_post_task['money'];
            $param['money_type'] = MONEY_TYPE_GAME;
        };

        // Если в письме передан артефакт
        if ($area_post_task['artikul_id']) {
            $artikul = artifact_artikul_get($area_post_task['artikul_id']);
            $amount = ($area_post_task['amount'] && ($artikul['cnt'] > 0)) ? $area_post_task['amount'] : 1;
            $artifact_id = artifact_add($area_post_task['artikul_id'],$amount,$user['id'],$area_post_task['area_id']);
            if ($artifact_id > 0) {
                $param['artifact_id'] = $artifact_id;
                $param['n'] = $amount;
            }
        }
        $status = area_post_message_save($param);

        area_post_task_user_save(array(
            'id' => $area_post_task_user['id'],
            '_set' => sql_pholder(' post_id = ?, status = ?, pcnt = pcnt + 1', $status, ($status ? 1 : 0)),
        ));
        area_post_task_save(array(
            'id' => $area_post_task_user['task_id'],
            '_set' => sql_pholder(' send_message_cnt = send_message_cnt + 1'),
        ));
    }

    if(area_post_task_user_count(array('task_id' => $area_post_task['id'], 'status' => 0, 'pcnt' => 0)) == 0){
        area_post_task_save(array(
            'id' => $area_post_task['id'],
            'status' => 3,
        )); //Завершили
    }

    area_post_task_unlock($area_post_task['id']); //Анлочим
}


////////////Ниже говно и зашквар!




require_once("lib/adv_message.lib");
require_once("lib/chat.lib");
require_once("lib/smile.lib");
require_once("tpl/chat.tpl");

mb_internal_encoding("UTF-8");

$smile_hash = make_hash(common_list($db_diff,TABLE_SMILES,false),'tag');
$smile_hash = array_map('rtag',$smile_hash);

$flood_users_hash = make_hash(user_list(array('flood' => 1)));
if(!$flood_users_hash) exit; //Считаем что отработали xD
echo sql_pholder(' AND user_id IN (?@) AND flags & ?#ADV_MESSAGE_FLAG_ACTIVE', array_keys($flood_users_hash)).'<br>';
$adv_messages = adv_message_list(false, sql_pholder(' AND user_id IN (?@) AND flags & ?#ADV_MESSAGE_FLAG_ACTIVE', array_keys($flood_users_hash)));
$adv_messages_hash = make_hash($adv_messages, 'user_id', true);
foreach ($adv_messages_hash as $user_id=>$adv_messages){

    $cur_user = $flood_users_hash[$user_id];
    if(($cur_user['flags'] & USER_FLAG_PUNISH) ||
        ($cur_user['flags'] & USER_FLAG_JAIL) || ($cur_user['gag_time'] > time_current())){
        adv_message_save(array(
            '_mode' => CSMODE_UPDATE,
            '_set' => sql_pholder('flags = flags ^ ?#ADV_MESSAGE_FLAG_ACTIVE'),
            '_add' => sql_pholder(' AND user_id = ?',$user_id),
        )); //Деактивируем флудилку
        user_save(array('id' => $user_id, 'flood' => 0, 'flood_id' => 0)); //Деактивируем ему все
    }

if(!user_is_online($user_id)){
adv_message_save(array(
    '_mode' => CSMODE_UPDATE,
    '_set' => sql_pholder('flags = flags ^ ?#ADV_MESSAGE_FLAG_ACTIVE'),
    '_add' => sql_pholder(' AND user_id = ?',$user_id),
)); //Деактивируем флудилку
user_save(array('id' => $user_id, 'flood' => 0, 'flood_id' => 0)); //Деактивируем ему все
}else{
common_fldsort($adv_messages, false, 'weight'); //Сортируем
$adv_messages = make_hash($adv_messages, 'id');
//SEND MESSAGE
$user_storage = array();
//$flood_id = get_next_flood_id($flood_users_hash[$user_id]['flood_id'],$adv_messages);
//$flood_users_hash[$user_id]['flood_id'] = $flood_id;
//echo $flood_id;
//e($adv_messages);
$flood_id = get_next_flood_id($flood_users_hash[$user_id]['flood_id'],$adv_messages);
foreach ($adv_messages as $message){
    $flood_users_hash[$user_id]['flood_id'] = $flood_id;
    //echo $flood_id.'<br>';
    //e($message);
    //echo $message['id'].' == '.$flood_id;
    if($message['id'] != $flood_id) continue;
    send_message($flood_id,$message,$user_storage);
    break;
}

}
}

function send_message($flood_id,$message,&$user_storage){
global $adv_message_period,$flood_users_hash, $smile_hash;
$user_id = $message['user_id'];
if(!$user_id) return false;

//echo "Отправим ".$flood_id.'<br>';

//echo html_date_str(time_current() + $adv_message_period[$message['period']]['period']).'<br>';
//echo html_date_str(time_current()).'<br>';

if($message['last_time'] + $adv_message_period[$message['period']]['period'] < time_current()){
//echo "Отправили ".$flood_id.'<br>';
user_save(array('id' => $user_id, 'flood_id' => $flood_id)); //Установим флуд ид
adv_message_save(array(
'_mode' => CSMODE_UPDATE,
'_set' => sql_pholder('last_time = ?',time_current()),
'_add' => sql_pholder(' AND user_id = ?',$user_id),
)); //Деактивируем флудилку

    $message['message'] = str_replace(array_keys($smile_hash),$smile_hash,$message['message']);
    //$smile_count = preg_match_all('/<img[^<>]+?\>/',$message['message'],$matches);
    //if ($smile_count > 3) $message['message'] = preg_replace('/<img[^<>]+?\>/','',$message['message'],$smile_count-3);

    $message['message'] = tpl_chat_artifacts($message['message']);

    $msg = array(
        'user_id' => $user_id,
        'user_nick' => $flood_users_hash[$user_id]['nick'],
        'user_kind' => $flood_users_hash[$user_id]['kind'],
        'msg_text' => $message['message'],
        'msg_color' => $flood_users_hash[$user_id]['msg_color'] ? $flood_users_hash[$user_id]['msg_color']: 'black',
    );

    $channel_data = false;
    if($message['channel'] == CHAT_CHF_TRADE){
        $channel_data['trade_id'] = CHAT_TRADE_ID;
    }

    chat_msg_send($msg,$message['channel'],$channel_data);
//$message['send_time'] = time_current() + $adv_message_period[$message['period']];
$user_storage[$message['id']] = $message;
}
}

function get_next_flood_id($flood_id,$adv_messages){
$adv_messages_restore = $adv_messages;
if(!$flood_id){foreach ($adv_messages as $message){return $message['id'];}} //Если нет флуд ID укажем первый в элементе массива.

//Необходимо отрезать все предыдущие части
if($flood_id){
$c=0;
foreach ($adv_messages as $k=>$v){
//echo $v['id'].'=='.$flood_id.'<br>';
if($v['id'] != $flood_id){$c++;}
else{break;}
}
//echo $c;
array_splice($adv_messages, 0, $c);
}

$keys = array_keys($adv_messages);
e($keys);
foreach ($keys as $index=>$key) {
//Текущий элемент
$item = $adv_messages[$key];
//e($item);
if($item['id'] == $flood_id && count($adv_messages) != 1) continue; //Текущий элемент успешно пропускаем, он нас больше не интересует
//Следующий элемент для сверки и проверки на существование
$k_v = array_keys($adv_messages);
$next = $adv_messages[$k_v[$index + 1]];
//e($next);
if($next && $next['id'] != $flood_id) return $item['id'];
if(!$next && $item['id'] != $flood_id) return $item['id'];
if(!$next) return get_next_flood_id(0, $adv_messages_restore); //Если не нашли следующий элемент, то возвращаемся к истоку.
}
}

function e($v){echo'<pre>';print_r($v);echo'</pre>';}