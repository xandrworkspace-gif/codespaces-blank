<? 

chdir("..");
require_once("include/common.inc");
require_once("lib/area_casino.lib");
require_once("lib/chat.lib");

common_define_settings();

set_time_limit(0);
// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) return;


if (defined("SETTINGS_CASINO_SCHEDULE") && SETTINGS_CASINO_SCHEDULE) {
	$add=sql_placeholder(' AND (date_start < ? OR date_finish < ? ) AND (!(flags & ?#AREA_CASINO_FLAG_TASK_START_INFORMED) OR !(flags & ?#AREA_CASINO_FLAG_TASK_FINISH_INFORMED))',intval(time_current()),intval(time_current()));
	$tasks = area_casino_schedule_list(false,$add);
	if ($tasks) {
	    foreach($tasks as $task){
	    	// Если эвент завершился
	    	if (($task["date_finish"] < time_current()) && !($task["flags"] & AREA_CASINO_FLAG_TASK_FINISH_INFORMED)) {
	    		$out=array();
	    		$out["casino_closed"] = 1;
	    		$task["flags"] |= AREA_CASINO_FLAG_TASK_FINISH_INFORMED;
	    		$msg_text = translate('"Однорукий бандит" закрыт!');
	    		chat_msg_send_broadcast($msg_text, LOUDSPEAKER_ID);
	    	}
			//Если эвент начался, а мы о нём не предупредили
			if (($task["date_start"] < time_current()) && !($task["flags"] & AREA_CASINO_FLAG_TASK_START_INFORMED)) {
			    // если эвент ещё не закончился, то уведомляем
			    if ($task["date_finish"] > time_current()) {
				$out=array();
				$out["casino_closed"] = 0;
				$out["casino_time_close"] = $task["date_finish"];
				$msg_text = translate('"Однорукий бандит" работает!').' '.translate('Испытай удачу!');
				chat_msg_send_broadcast($msg_text, LOUDSPEAKER_ID);
				//chat_msg_send($msg_text, CHAT_CHF_AREA);
			    }
			    $task["flags"] |= AREA_CASINO_FLAG_TASK_START_INFORMED;
			}
		
			area_casino_schedule_save($task);
		
	    }
	}
 }

//удалим просроченные задачи
//area_casino_schedule_delete(false, sql_placeholder(' AND date_finish < ?',intval(time_current())));
//сгенерим новые
area_casino_schedule_generate();

sleep(MNGR_CASINO_INTERVAL-(time()-time_current()));
