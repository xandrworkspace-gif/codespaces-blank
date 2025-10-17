<? # $Id: chat_send.php,v 1.6 2006/09/27 07:28:51 agladysh Exp $

require_once("include/config.inc");
require_once("lib/chat.lib");


  $msg_text = trim($_REQUEST['msg_text']);
  $channel = intval($_REQUEST['channel']);
  $channel_talk = intval($_REQUEST['channel_talk']);
  $msg_id = intval($_REQUEST['msg_id']);
  $area_id = intval($_REQUEST['area_id']);


  // Определяем доступные пользователю каналы
  $channel_avail = CHAT_CHF_AREA | CHAT_CHF_USER | CHAT_CHF_TRADE;
  $channel_data = array(
    'user_id' => 100,
    'area_id' => 200,
  );
  $channel |= CHAT_CHF_USER;  // всегда слушаем приват
  $channel &= $channel_avail;


  var_dump(chat_msg_send(array('msg_text' => 'ABCDEF!'), CHAT_CHF_USER, $channel_data));

?>
