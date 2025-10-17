<? # $Id: mngr_farm.php 15672 2010-10-23 14:34:54Z f.omelchanko $
 
chdir("..");

require_once("include/common.inc");

set_time_limit(0);
$stime1 = time();

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) {
	sleep(MNGR_FARM_INTERVAL);
	return;
}

require_once("lib/farm.lib");
require_once("lib/area.lib");
require_once("lib/instance.lib");
require_once("lib/artifact.lib");
require_once("lib/bonus.lib");
require_once("lib/activity.lib");
require_once("lib/user.lib");
require_once("lib/global_event.lib");
require_once("lib/lite_pass.lib");
require_once("lib/squest.lib");

define('MASK_GRID_SIZE', 20);

$profs = array();

foreach ($profession_info as $k=>$profession) if ($profession['type'] == PR_TYPE_FARM) $profs[] = $k;
$farm_list = farm_list();
foreach ($farm_list as &$item) {
	$item['farm_id'] = $item['id'];
}

// добавляем фальшивый фарм (поиск ботов для фарма)
foreach ($profs as $prof) {
	$k = rand(0,count($farm_list)-1);
	$fake_farm = $farm_list[$k];
	$fake_farm['skill_value'] = $fake_farm['tool_kind_id'] = 0;
	unset($fake_farm['bonus_id']);
	$fake_farm['profession'] = $prof;
	$fake_farm['id'] = -$prof;
	$farm_list[] = $fake_farm;
}

$farm_hash = make_hash($farm_list);

$online_count = 0;
foreach ($NODE_NUMS as $nn) {
	NODE_PUSH($nn);
	$online_count += session_count(null,null,true,'',true);
	NODE_POP();
}

$farm_type_hash = make_hash(farm_type_list());

do {
	$info = array();
	$user_ids = $area_ids = $instance_ids = array();
	$sessions = array();
	foreach($NODE_NUMS as $nn) {
		if ($nn == FRIDGE_NN) continue;
		NODE_PUSH($nn);
		$tmp_sessions = session_list(null,null,true,'','*',true);
		foreach($tmp_sessions as $tmp_session) {
			$sessions[] = $tmp_session;
		}
		NODE_POP($nn);
	}
	foreach ($sessions as $k=>$session) {
		if ($session['instance_id']) $instance_ids[$session['instance_id']]++;
		elseif ($session['area_id']) $area_ids[$session['area_id']]++;
	}
	$area_amounts = $instance_amounts = array();
	$event_active_point_ids = get_hash_grp(event_list(false,' AND point_id > 0','artikul_id,point_id'),'artikul_id','point_id','point_id');
	foreach (farm_area_list(false, '') as $farm_area) {
		// Если указан этап события, то нужно проверить, что именно это событие сейчас идёт
		
		if ($farm_area['area_id'] && $area_ids[$farm_area['area_id']]) {
			$cnt = ($farm_area['event_point_id'] && !$event_active_point_ids[$farm_area['event_artikul_id']][$farm_area['event_point_id']]) ? 0 : round(min($farm_area['cnt_min'] + (intval($farm_area['cnt_ratio'] * $online_count * 0.0001)),$farm_area['cnt_max']));
			$area_amounts[$farm_area['area_id']][$farm_area['farm_id']] += $cnt;
			$info[$farm_area['area_id'].'_0'][$farm_area['farm_id']] += $cnt;
		}
		if ($farm_area['inst_artikul_id']) {
			$cnt = max($farm_area['cnt_min'],$farm_area['cnt_max']);
			$instance_amounts[$farm_area['inst_artikul_id']][$farm_area['farm_id']] += $cnt;
		}
	}

    /* RANDOM AREA FARMS 2019 rev1 */
    $farm_list_random = farm_list(false, sql_pholder(' AND flags & ?#FARM_FLAG_RANDOM'));
    foreach ($farm_list_random as $random_farm){

        if($random_farm['stime'] && $random_farm['dtime']) {
            if($random_farm['stime'] > time_current()) continue;
            if($random_farm['dtime'] < time_current()) continue;
        }

        $_rarea_ids = explode(',', $random_farm['area_ids']);

        $_cc = 0;
        if($random_farm['flags'] & FARM_FLAG_RANDOM_AREA){
            $cache = new Cache(md5('FARM_RANDOM_SHUFFLE_HASHER_'.$random_farm['id'].'_'.md5($random_farm['area_ids'])));
            $shuffle_rarea_ids = array();
            if($cache->isAvail()){
                $shuffle_rarea_ids = $cache->get();
            }else{
                shuffle($_rarea_ids); //Рандомно перемешаем локи, чтобы появлялись в разных локациях, но сделаем это раз в 1 час, ибо будет неловко.
                $shuffle_rarea_ids = $_rarea_ids;
                $cache->update($_rarea_ids, 3600);
            }

            if(!$shuffle_rarea_ids) continue;

            $cnt_max = max($random_farm['cnt_max']);
            $cnt = max(floor($random_farm['cnt_max'] / ($random_farm['cnt_area'] ? $random_farm['cnt_area'] : count($_rarea_ids))), 1); //Кол-во на одну локу
            foreach ($shuffle_rarea_ids as $area_id){
                if($random_farm['cnt_area'] && $_cc >= $random_farm['cnt_area']) break;
                $area_amounts[$area_id][$random_farm['id']] += $cnt;
                $info[$area_id.'_0'][$random_farm['id']] += $cnt;
                $_cc++;
            }
        }else{
            if($random_farm['cnt_max'] > 0) {
                $cnt = $random_farm['cnt_max'];
                foreach ($_rarea_ids as $area_id) {
                    $area_amounts[$area_id][$random_farm['id']] += $cnt;
                    $info[$area_id . '_0'][$random_farm['id']] += $cnt;
                }
            }
        }
    }
    /* RANDOM AREA FARMS 2019 rev1 */

	$area_hash = $area_amounts ? make_hash(area_list(null,sql_pholder(" AND id IN (?@)",array_keys($area_amounts)))) : array();
	$instance_hash = array();
	if ($instance_amounts) {
		foreach (instance_list(array('artikul_id' => array_keys($instance_amounts))) as $instance) {
			if ($instance_ids[$instance['id']]) {
				$instance_hash[$instance['id']] = $instance;
				$info['0_'.$instance['id']] = $instance_amounts[$instance['artikul_id']];
			}
		}
	}
	if (!$info) break;
	foreach ($info as $loc_key=>$farm_amounts) {
		list($area_id,$instance_id) = explode('_',$loc_key);
		if ($instance_id) $loc = &$instance_hash[$instance_id];
		else $loc = &$area_hash[$area_id];
		if (!$loc['h_map']) continue;
		$t = explode('.',$loc['h_map']);
		$masks = array();
		foreach ($profs as $prof) {
			$mask_fn = PATH_SWF_MAPS_HNT.$t[0].sprintf('_mask_farm%02d.dat',$prof);
			$masks[$prof] = @file_get_contents($mask_fn);
		}
		$sizeX = intval($loc['h_sizex']);
		$sizeY = intval($loc['h_sizey']);
		
		if (!farm_lock($loc_key)) continue;
		$data = farms_loc_data_get($area_id, $instance_id);
		
		
		// обновим фарм, если стоит соотв. флаг.
		if ($area_id && ($loc['flags'] & AREA_FLAG_FARM_FLORA_REFRESH)) {
			if ($data['id'])
				$data = array('id' => $data['id']);
			else
				$data = array();
		}
		
		$farm_info = &$data['farm_info'];
		$farm_state = &$data['farm_state'];
		if (!$farm_info) $farm_info = array();
		
		// устанавливаем кол-во фальшивого фарма
		foreach ($profs as $prof) $farm_amounts[-$prof] = 5;

		// генерируем фарм
		$max_respawn_time = 0;
		$cnts = array();
		$num = 0;
		$max_num = 0;
		foreach ($farm_info as $num=>$farm) {	// существующий фарм
			$farm_id = $farm['id'];
			$cnts[$farm_id]++;
			
			$max_num = max($num, $max_num);
			
			if (!$farm_amounts[$farm_id]) {	// этих объектов быть не должно
				unset($farm_info[$num]);
				continue;
			}
			if ($farm['active']) {
				continue;
			}
			if ($cnts[$farm_id] > $farm_amounts[$farm_id]) {	// слишком много таких объектов, чистим
				unset($farm_info[$num]);
				continue;
			}
			$max_respawn_time = max($max_respawn_time, $farm['rtime'] - time_current());
			if ($farm['rtime'] > time_current()) continue;
			if (($farm['respawn_prob'] > 0) && !rand_roll($farm['respawn_prob']/100)) {
				$rand_k = 1000000;
				$farm_info[$num]['rtime'] = $farm_info[$num]['respawn_time'] + time_current() + $farm_info[$num]['respawn_time'] * rand(-$farm_info[$num]['respawn_dispersion']/100/2*$rand_k, $farm_info[$num]['respawn_dispersion']/100/2*$rand_k) / $rand_k;
				continue;
			}
			_init_position($sizeX,$sizeY,$x,$y,$masks[$farm['profession']],$loc_key.'-'.$farm['profession']);
			
			// обновляем запись
			$farm_info[$num] = $farm_hash[$farm_id];
			$farm_info[$num]['x'] = $x;
			$farm_info[$num]['y'] = $y;
			$farm_info[$num]['active'] = true;
			
			// фальшивый фарм сдвигаем за пределы карты
			if ($farm['id'] < 0) {
				$farm_info[$num]['x'] += $sizeX + 1;
				$farm_info[$num]['y'] += $sizeY + 1;
			}
		}
				
		// добавление нового фарма
		// если стоит флаг не обновлять фарм, то пропускаем.
		$holes = _find_holes_in_array(array_keys($farm_info));
		if (!($area_id && ($loc['flags'] & AREA_FLAG_FARM_NO_REGENERATION && !($loc['flags'] & AREA_FLAG_FARM_FLORA_REFRESH)))) {
			foreach ($farm_amounts as $farm_id=>$amount) {
				$d = $amount - $cnts[$farm_id];
				if ($d <= 0) continue;
				for ($i = 0; $i < $d; $i++) {
					$farm_info[_next_id($holes, $max_num)] = $farm_hash[$farm_id];
				}
			}
		}

		$users_hash = $farm_state ? make_hash(user_list(array('id' => array_keys($farm_state)))) : array();
		
		// производим проверку фарма
		$farm_finished = array();
		if ($farm_state) {
			foreach ($farm_state as $user_id=>$state) {
				if ($state['ftime'] <= (time_current() - 600)) {	// чистим массив состояний
					unset($farm_state[$user_id]);
					continue;
				}
				$num = $state['num'];
				if (($state['ftime'] > time_current()) || $state['farm'] != 0) continue;
                $skill_info = user_get_skill_info($user_id,array('FAILRES_CHANCE'));
                $fail_res_chance = intval($skill_info['skills']['FAILRES_CHANCE']['value']);
				$fail = !$farm_info[$num]['active'] || !rand_roll(($farm_info[$num]['farm_prob'] + $fail_res_chance)/100) || ($farm_info[$num]['id'] < 0) || !$users_hash[$user_id] || !($users_hash[$user_id]['flags'] & USER_FLAG_FARMING);
				if (!$fail) {
					$rand_k = 1000000;
					$farm_info[$num]['rtime'] = $farm_info[$num]['respawn_time'] + time_current() + $farm_info[$num]['respawn_time'] * rand(-$farm_info[$num]['respawn_dispersion']/100/2*$rand_k, $farm_info[$num]['respawn_dispersion']/100/2*$rand_k) / $rand_k;
					$farm_info[$num]['active'] = false;
				}
				$farm_state[$user_id]['farm'] = !$fail ? 1 : -1;
				$farm_finished[$user_id] = !$fail ? $num : false;
			}
		}

		farms_loc_data_save($area_id, $instance_id, array(
			'id' => $data['id'],
			'farm_info' => $farm_info,
			'farm_state' => $farm_state,
			'max_respawn_time' => $max_respawn_time,
		));
		
		farm_unlock($loc_key);

		// обновили фарм, удалим флаг с арии.
		if ($area_id && ($loc['flags'] & AREA_FLAG_FARM_FLORA_REFRESH)) {
			do {
				if (!area_lock($area_id)) {
					error_log(__FILE__.':'.__LINE__.": There 'area_lock' function no succed for area_id = '$yard_area_id'.");
					break;
				}
				$params = array(
					'_mode'	=> CSMODE_UPDATE,
					'_set'	=> sql_pholder(" flags = flags & ~ ?#AREA_FLAG_FARM_FLORA_REFRESH "),
					'id'	=> $area_id,
				);
				$result = area_save($params);
				if (!$result) {
					error_log(__FILE__.':'.__LINE__."There 'area_save' function no succed for area_id = '$yard_area_id'.",1);
				}
				area_unlock($area_id);
			} while (false);
		}
		
		// раздаем мастерство
		// подготавливаем хэш пользователей
		$farm_user_ids = array();
		$farm_user_hash = array();
		if (is_array($farm_finished)) $farm_user_ids = array_keys($farm_finished);
		if ($farm_user_ids) $farm_user_hash = make_hash(user_list(array('id' => $farm_user_ids)));

		foreach ($farm_finished as $user_id => $num) {
			user_set_flag($user_id,(USER_FLAG_NOACTION | USER_FLAG_FARMING),false);	// снимаем флаги с пользователя
			if (!$num) continue;
			$farm = &$farm_info[$num];
			
			if ($farm['artifact_artikul_id']) {
				$user = cache_fetch($farm_user_hash, $user_id, 'user_get');
				$artikul = artifact_artikul_get($farm['artifact_artikul_id']);
				NODE_PUSH(null, $user_id);
				$into_profbag = user_check_prof_bag($artikul, $user, 1);
				$storage = ($into_profbag) ? ARTIFACT_STORAGE_TYPE_PR_BAG : ARTIFACT_STORAGE_TYPE_USER;
				
				$cnt = 1;
                //Активность по фарму
                activity_user_check(ACTIVITY_STAT_FARM,$artikul['id'],$user, false);

				if (!($farm['flags'] & FARM_FLAG_NOLUCKYCHANCE)) {
					$skill_info = user_get_skill_info($user_id,array('LUCKY_CHANCE'));
					$lucky_chance = intval($skill_info['skills']['LUCKY_CHANCE']['value']);
					if (rand(0, 100) <= $lucky_chance) {
						$lucky_cnt = 2;
						$lucky_msg = translate($farm_type_hash[$farm['type_id']]['lucky_msg']);
						$msg = str_replace('#LIST#', '%d <a href="#" onClick="showArtifactInfo(false,%d);return false;">%s</a>', $lucky_msg);
						$cnt += 2;
						//artifact_add($artikul['id'],$lucky_cnt,$user_id);
						//chat_msg_send_system(sprintf($msg,$lucky_cnt,$artikul['id'],$artikul['title']),CHAT_CHF_USER,$user_id);
					}
				}
				
				$artifact_id = artifact_add($artikul['id'],$cnt,$user_id, false, false, array(), $storage);
				if($artifact_id) {  try{ artifact_energy_slot_charge($user_id, 1, ENERGY_TYPE_FARM); }catch (Exception $energy_ex){} }
                $artifact_user_cnt = artifact_amount($artikul['id'], $user_id, null, null, true);
                artifact_bag_send_diff($user_id, $artifact_id);

                try{
                    lite_pass_user_action($user, LITE_PASS_ACTION_RESOURCE_GET, 1);
                }catch(Exception $ex){}

                try {
                    squest_trigger($user, SQUEST_ACTION_RESOURCE_GET, $farm['id']);
                }catch (Exception $ex){}
				
				// собираем статистику
				require_once('lib/user_stat.lib');
				user_stat_update($user_id, USER_STAT_TYPE_FARM, $farm['artifact_artikul_id']);
				NODE_POP();
			}

			// Бросаем вероятность на бонус и выдаём, если успешно
			if ($farm['bonus_id'] && $farm['bonus_prob'] && rand_roll($farm['bonus_prob']/100)) {
				$user = cache_fetch($farm_user_hash, $user_id, 'user_get');
				NODE_PUSH(null, $user_id);
				bonus_apply($user,$farm['bonus_id']);
				NODE_POP();
			}

            if ($farm['artifact_artikul_id'] && $artifact_id) {
                chat_msg_send_system(sprintf(translate('Вы получили вещь '.tpl_artikul_info($artikul).' %d шт. (в рюкзаке %d шт.)'), $cnt, $artifact_user_cnt), CHAT_CHF_USER, $user_id);
            }

			if ($farm['flags'] & FARM_FLAG_NOPROFESSION) continue;
			$farm_user = farm_user_get(array('farm_id' => $farm['id'], 'user_id' => $user_id));
			farm_user_save(array(
				'_mode' => CSMODE_REPLACE,
				'farm_id' => $farm['id'],
				'user_id' => $user_id,
				'cnt' => intval($farm_user['cnt']) + 1,
			));

			_global_event_trigger(GLOBAL_EVENT_TYPE_RES_GIVE, 1, $user_id);

            if($farm['artifact_artikul_id'] && $artifact_id) area_event_trigger($user, AREA_EVENT_TYPE_FARM, $farm['artifact_artikul_id']);
			
			$skill_id = $profession_info[$farm['profession']]['skill_id'];
			if ($skill_id) {
				NODE_PUSH(null, $user_id);
				$skill_info = user_get_skill_info($user_id,array($skill_id),false,false);
				$user = cache_fetch($farm_user_hash, $user_id, 'user_get');
				$user_skill_value = intval($skill_info['skills'][$skill_id]['value']);
				$user_level_value = $user['level'];
				if ($farm['artifact_artikul_id']) {
					metric_group_add(METRIC_TYPES_FARM, array('artikul_id' => $farm['artifact_artikul_id'],'prof_id' => $farm['profession'], 'skill_cnt' => $user_skill_value), array('farm_cnt' => 1 + $lucky_cnt));
				}
				$exp = farm_get_exp($farm['skill_value'],$user_skill_value, $user_level_value);
				if ($exp > 0) {
					if (skill_object_set_value(OBJECT_CLASS_USER,$user_id,$skill_id,$exp,array('relative' => true))) {
						chat_msg_send_system(sprintf(translate('Ваше мастерство увеличилось на +%d.'),$exp),CHAT_CHF_USER,$user_id);
					}
				}
				NODE_POP();
			}
		}
	}
} while (0);

require_once("lib/system_stat.lib");
system_stat_update('farm');

$stime2 = time();
$rtime = $stime2-$stime1;
if ($rtime > MNGR_FARM_INTERVAL) error_log("(mngr_farm: ".getmypid()."): Runtime $rtime sec");
sleep(max(MNGR_FARM_INTERVAL-$rtime,0));


// ===============================================================================================

function _check_position($sizeX, $sizeY, $x, $y, &$mask) {
	if (($x < 0) || ($y < 0) || ($x >= $sizeX) || ($y >= $sizeY)) return false;
	if ($mask) {
		$i = (int)($sizeX/MASK_GRID_SIZE)*(int)($y/MASK_GRID_SIZE) + (int)($x/MASK_GRID_SIZE);
		$b = ord($mask{(int)($i/8)});
		$bt = ($b >> ($i % 8)) & 1;
		if ($bt) return false;
	}
	return true;
}

function _init_position($sizeX, $sizeY, &$x, &$y, &$mask, $key='') {
	static $r, $rc;
	if (!isset($r[$key])) {
		$r[$key] = array();
		for ($j=0; $j<$sizeY; $j+=MASK_GRID_SIZE) {
			for ($i=0; $i<$sizeX; $i+=MASK_GRID_SIZE) {
				if (!_check_position($sizeX,$sizeY,$i,$j,$mask)) continue;
				$r[$key][] = array($i,$j);
			}
		}
		$rc[$key] = count($r[$key]);
	}
	if ($rc[$key] <= 0) return false;
	$i = rand(0,$rc[$key]-1);
	$x = $r[$key][$i][0];
	$y = $r[$key][$i][1];
	$x = rand($x,$x+MASK_GRID_SIZE-1);
	$y = rand($y,$y+MASK_GRID_SIZE-1);
}

function _find_holes_in_array($array) {
	if (!$array) return array();
	sort($array);
	$old_num = 0;
	$holes = array();
	foreach ($array as $num) {
		if ($num - $old_num > 1) {
			for ($i = $old_num + 1; $i < $num; $i++) {
				$holes[] = $i;
			}
		}
		$old_num = $num;
	}
	return $holes;
}

function _next_id(&$array, &$max) {
	if (!$array) return ++$max;
	return array_pop($array);
}

?>