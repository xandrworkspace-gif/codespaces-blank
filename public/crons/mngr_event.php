<? # $Id: mngr_event.php,v 1.27 2010-02-09 08:11:47 i.hrustalev Exp $
 
chdir("..");
require_once("include/common.inc");

set_time_limit(60);
$time_current = time_current();

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) {
	sleep(MNGR_EVENT_INTERVAL);
	return;
}

require_once("lib/event.lib");

$plant = false;
$event_hash = make_hash(event_list(),'artikul_id');
$event_point_hash = make_hash(event_point_list(false,' ORDER BY id'));
$event_artikul_point_hash = get_hash_grp(event_artikul_point_list(false,' ORDER BY point_num,point_id'),'artikul_id',false,'point_id');
$event_point_task_hash = get_hash_grp(event_point_task_list(),'point_id','id','max_value');
$event_area_hash = get_hash_grp(event_area_list(),'artikul_id','area_id','area_id');

$event_artikuls = event_artikul_list();
if ($event_artikuls) foreach($event_artikuls as $event_artikul) {
	$event = $event_hash[$event_artikul['id']];
	// время начала следующего события
	$event_time_start = max(
		intval($event['point_stime']) + $event_artikul['cooldown_time'],
		intval($event['stime']) + $event_artikul['period'],
		$event_artikul['stime']
	);
	if ($event && $event['point_id']) {
		// Если событие уже происходит в мире, то обсчитываем его
		if (!event_lock($event['point_id'])) {
            //echo "HUI ".$event['point_id'].PHP_EOL;
		    continue;
        }
		do {
			$event_point = $event_point_hash[$event['point_id']];
			$next_point = false;

			// Если истекла максимальная продложительность этапа, то заканчиваем событие
			if (($event['point_stime'] + $event_point['duration']) <= $time_current) {

				if ($event_point['flags'] & EVENT_POINT_FLAG_IGNORE_TASKS) { // если можно игнорировать задачи по событию, то после окончания времени начинаем следующий этап события
					$next_point = true;

				} else { // завершаем событие

					// выключаем галку "Все понимают друг друга" если нужно
					if ($event_point['flags'] & EVENT_POINT_FLAG_TALKFREE) {
						common_save_settings(array('SERVER_TALKFREE' => 0));
					}

					event_save(array(
						'id'          => $event['id'],
						'point_stime' => $time_current,
						'point_id'    => 0,
					));
					$plant = true;
					event_anouncer(
						$event_artikul['finish_cht_msg'],
						$event['artikul_id'],
						($event_artikul['flags'] & EVENT_FLAG_NOTIFY_ALL_LOCATIONS) ? array() : $event_area_hash[$event_artikul['id']],
						$event_artikul['kind'],
						true
					);
					break;
				}
			}

			if ($event_point_task_hash[$event['point_id']] && !$next_point) {
				$event_point_task_value_hash = get_hash_grp(event_point_task_user_list(array('task_id' => array_keys($event_point_task_hash[$event['point_id']])),' GROUP BY 1,2','artikul_id, task_id, SUM(value) AS value'),'artikul_id','task_id','value');

				$all_tasks_completed = true;

				// Если хоть одно из заданий не выполнено, не завершаем этап
				foreach($event_point_task_hash[$event['point_id']] as $id => $max_value)
					if (($max_value - $event_point_task_value_hash[$event['artikul_id']][$id]) > 0) {
						$all_tasks_completed = false;
						break;
					}

				$next_point = $all_tasks_completed;
			}

			if (!$next_point) { // нельзя включить следующий этап.
				break;
			}

			// Все задачи выполнены, переходим к следующему этапу
			$cur_position = array_search($event['point_id'],$event_artikul_point_hash[$event['artikul_id']]);
			if ($event_artikul_point_hash[$event_artikul['id']][$cur_position + 1]) {
				$prev_point = $event_point;
				$event_point = $event_point_hash[$event_artikul_point_hash[$event_artikul['id']][$cur_position + 1]];
				

				// если значения флага EVENT_POINT_FLAG_TALKFREE в текущем и следующем поинте НЕ равны (значит значение флага нужно изменить)
				if (($prev_point['flags'] & EVENT_POINT_FLAG_TALKFREE) != ($event_point['flags'] & EVENT_POINT_FLAG_TALKFREE)) {

					// в прошлом поинте флаг стоял, значит нужно снять его в текущем поинте
					$talkFree = intval(($event_point['flags'] & EVENT_POINT_FLAG_TALKFREE) == EVENT_POINT_FLAG_TALKFREE); 
					common_save_settings(array('SERVER_TALKFREE' => $talkFree));
				}

				
				event_save(array(
					'id'          => $event['id'],
					'point_stime' => $time_current,
					'point_id'    => $event_point['id'],
				));
				$plant = true;
				event_anouncer(
					$event_point['cht_msg'],
					$event_artikul['id'],
					($event_artikul['flags'] & EVENT_FLAG_NOTIFY_ALL_LOCATIONS) ? array() : $event_area_hash[$event_artikul['id']],
					$event_artikul['kind'],
					true
				);
				break 2;
			}
			
			// Если следующего этапа нет, то завершаем событие

			// выключаем галку "Все понимают друг друга" если нужно
			if ($event_point['flags'] & EVENT_POINT_FLAG_TALKFREE) {
				common_save_settings(array('SERVER_TALKFREE' => 0));
			}
			
			event_save(array(
				'id'          => $event['id'],
				'point_stime' => $time_current,
				'point_id'    => 0,
			));
			$plant = true;
			event_anouncer(
				$event_artikul['finish_cht_msg'],
				$event['artikul_id'],
				($event_artikul['flags'] & EVENT_FLAG_NOTIFY_ALL_LOCATIONS) ? array() : $event_area_hash[$event_artikul['id']],
				$event_artikul['kind'],
				true
			);
		} while(0);
		event_unlock($event['point_id']);
	} else if ($event && !$event['point_id'] && $event_time_start < $time_current) {
		/**
		 * Прошел кулдаун на событие, хранить его больше не нужно, удаляем.
		 * Новое событие будет создано при следующем запуске скрипта.
		 */
		event_delete($event['id']);
	} else if ($event && !$event['point_id'] && $time_current < $event_time_start) {
		/**
		 * Оповещение о предстоящем событии реализовано флагами на евенте.
		 * Флаги о следующем событии ставятся на текущее, т.к. событие создается сразу активным. 
		 */
		/**
		 *  оповещение работает так: при создании или перегенерации события флаги о спаме (EVENT_FLAG_NOTIFY_Х) выставляются в 0
		 *  а при отправке сообщения ставится флаг 1.
		 */
		/**
		 * массив соотв. флаг => кол-во минут до начала евента.
		 * @var array
		 */
		$event_notify_info = array(
			EVENT_FLAG_NOTIFY_5 => 5,
			EVENT_FLAG_NOTIFY_3 => 3,
			EVENT_FLAG_NOTIFY_1 => 1,
		);
		$time_befor_event = $event_time_start - $time_current;
		$notify_id = 0;
		$flags = intval($event['flags']);
		// смотрим, пора ли, сколько осталось, и что писать
		foreach ($event_notify_info as $id => $gap) {
			if (($gap * 60) < $time_befor_event)	
				continue;
			if ($event['flags'] & $id) {
				if ($event_notify_info[$id] < $event_notify_info[$notify_id])
					$notify_id = 0;
				continue;
			}
			if ($notify_id && intval($event_notify_info[$notify_id] < $event_notify_info[$id]))
				continue;
			$notify_id = $id;
		}
		if (!$notify_id)
			continue;

		// выбираем подписаных
		$event_track_ids = event_track_user_list(array('event_id' => $event_artikul['id']), sql_pholder(' AND flags & ?', EVENT_TRACK_USERS_FLAG_NOTIFY));
		if (!$event_track_ids) {
			// подписчиков нет, сворачиваемся
			continue;
		}
		// сохраняем флаг, чтобы показать, что уже спамили.
		$params = array(
			'artikul_id'	=> $event_artikul['id'],
			'id'			=> $event['id'],
			'_set'			=> sql_pholder(" flags = flags | ? ", $notify_id),
		);

		if (!event_lock($event_artikul['id'] . '_0'))
			continue;
			
		$result = event_save($params);
		
		event_unlock($event_artikul['id'] . '_0');
		
		if (!$result) {
			error_log(__FILE__.':'.__LINE__.": event_save error occured with result = `".var_export($result,1)."`. Spam sending was scipped.");
            continue;
		}
			
		$msg_text = sprintf(
			translate("Через %d %s начнется событие «%s»."), 
			$event_notify_info[$notify_id],
			common_decline($event_notify_info[$notify_id],translate('минуту'),translate('минуты'),translate('минут')),
			$event_artikul['title']
		);
		$msg_text="<img src=\"images/smile_event.gif\" border=0><b>".$msg_text."</b><img src=\"images/smile_event.gif\" border=0>";
		$event_track_ids = get_hash($event_track_ids, 'user_id', 'user_id');
		chat_msg_send_system($msg_text,CHAT_CHF_USER,$event_track_ids,true, array('event_id' => $event_artikul['id']));
		
	} else if (!$event && $stime < $event_time_start) {
		/**
		 * Если событие в мире ещё не началось и пора его создавать
		 * делаем это.
		 */
		if (($event_artikul['stime'] > $time_current) || !$event_artikul_point_hash[$event_artikul['id']] || ($event_artikul['flags'] & EVENT_FLAG_OFF))
			continue;
		$event_point = $event_point_hash[reset($event_artikul_point_hash[$event_artikul['id']])];
		
		if (!event_lock($event_artikul['id'] . '_0'))
			continue;
		
		// включаем галку "Все понимают друг друга"
		if ($event_point['flags'] & EVENT_POINT_FLAG_TALKFREE) {
			common_save_settings(array('SERVER_TALKFREE' => 1)); 
		}
		
		event_save(array(
			'artikul_id'  => $event_artikul['id'],
			'kind'        => $event_artikul['kind'],
			'stime'       => $time_current,
			'point_stime' => $time_current,
			'point_id'    => $event_point['id'],
		));
		$plant = true;
		event_unlock($event_artikul['id'] . '_0');
		event_anouncer(
			$event_point['cht_msg'],
			$event_artikul['id'],
			($event_artikul['flags'] & EVENT_FLAG_NOTIFY_ALL_LOCATIONS) ? array() : $event_area_hash[$event_artikul['id']],
			$event_artikul['kind']
		);
		
	}
}

if ($plant) bot_plant(); // Перерассадка ботов, если произошло событие в рамках события
if ($plant) echo 'ok';
$time_finish = time();
$rtime = $time_finish - $time_current;
if ($rtime > MNGR_EVENT_INTERVAL) error_log("(mngr_event: ".getmypid()."): Runtime $rtime sec");
sleep(max(MNGR_EVENT_INTERVAL-$rtime,0));

