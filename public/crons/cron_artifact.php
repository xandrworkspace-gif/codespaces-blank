<? # $Id: cron_artifact.php,v 1.24 2010-01-15 09:50:10 p.knoblokh Exp $

ini_set("memory_limit", "256M");

chdir("..");
require_once("include/common.inc");

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) return;

require_once("lib/artifact.lib");
require_once("lib/area_store.lib");

set_time_limit(0);

$end_time = time() + CRON_ARTIFACT_SCRIPT_TIME_LIMIT;
// флаг "обработанности" ноды, если стоит, то в ней все удалили
$nodes_cleaned = array();

// цикл ограничиваем по времени и по "обработанности" всех нод.
while (time() < $end_time && (count($NODE_NUMS) != count($nodes_cleaned))) {
	
	foreach ($NODE_NUMS as $nn) {
		// если нода уже обработана пропускаем
		if(isset($nodes_cleaned[$nn]) && $nodes_cleaned[$nn]){
			continue;
		}
		// TODO тотал по идее вообще не нужен
		$total = 0;
		
		if(!NODE_SWITCH($nn)){
			continue;
		}
		
		$artifact_list = artifact_list(false,null,null,false,false," AND time_expire>0 AND time_expire<=".time_current()." LIMIT $total,10000");
		
		if ( !(is_array($artifact_list))){
			break;
		} elseif ( count($artifact_list) <= 0 ) {
			// помечаем ноду как обработанную
			$nodes_cleaned[$nn] = true;
			break;
		}
		
		$total += count($artifact_list);
		foreach ($artifact_list as $artifact) {
			if( artifact_delete($artifact) ){
				--$total;
			}

			/*
			if($artifact['slot_id'] == 'BELT'){ //TODO:Тот самый случай настал!
                artifact_delete_on_belt($artifact['user_id']);
            }*/ //Пока чТо не актуально!

			// лог-сервис -----------------------
			$user_id = $artifact['owner_id'] ? $artifact['owner_id'] : $artifact['user_id'];
			if (($artifact['type_id'] != ARTIFACT_TYPE_ID_INJURY) && $user_id) {
				logserv_log_operation(array(
					'artifact' => $artifact,
					'cnt' => -max($artifact['cnt'],1),
					'comment' => translate('Истек срок годности'),
				),$user_id);
			}
			// ----------------------------------
		}
	}
}

artifact_sell_delete(false, ' AND sell_time + '.RANSOM_TIME.' < '.time_current());

function artifact_delete_on_belt($user_id){
    if(!$user_id) return false;
    $slot_cnt_now = user_get_slot_num_max($user_id, 'EFFECT');
    $effect_list = user_get_artifact_list($user_id, 'EFFECT');
    foreach($effect_list as $effect) {
        if ($effect['slot_num'] > $slot_cnt_now) {
            $effect_in_backpack = artifact_get(array('artikul_id' => $effect['artikul_id'], 'user_id' => $user_id, 'slot_id' => ''));
            if (!$effect_in_backpack) {
                // перекладываем
                artifact_save(array(
                    'id' => $effect['id'],
                    'slot_id' => '',
                ));
            } else {
                // изменяем количество
                artifact_change_cnt($effect_in_backpack['id'], $effect['cnt'], '', array(
                    'time_expire' => $effect['time_expire'],
                ));
                // удаляем старый
                artifact_delete($effect);
            }
        }
    }
    return true;
}

?>