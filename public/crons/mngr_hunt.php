<? # $Id: mngr_hunt.php,v 1.72 2009-12-04 15:05:59 i.hrustalev Exp $

ini_set('memory_limit', '256M');

/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/
chdir("..");
require_once("include/common.inc");

set_time_limit(0);
$stime1 = time();

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) {
	sleep(MNGR_HUNT_INTERVAL);
	return;
}

require_once("lib/bot.lib");
require_once("lib/area.lib");
require_once("lib/instance.lib");
require_once("lib/session.lib");
require_once("lib/fight.lib");
require_once("lib/restriction.lib");

/*
$defence_ids = array(
    4998 => array(40),
);
*/

define('MASK_GRID_SIZE', 20);

do {
    $info = array();
    $user_ids = $area_ids = $instance_ids = array();
    $sessions = array();
    foreach ($NODE_NUMS as $nn) {
        if ($nn == FRIDGE_NN) continue;
        NODE_PUSH($nn);
        $tmp_sessions = session_list(null, null, true, '', '*', true);
        foreach ($tmp_sessions as $tmp_session) {
            $sessions[] = $tmp_session;
        }
        NODE_POP($nn);
    }
    foreach ($sessions as $k => $session) {
        $loc_key = ($session['instance_id'] ? 0 : $session['area_id']) . '_' . $session['instance_id'];
        $info[$loc_key]['user_ids'][] = $session['uid'];
        $user_ids[$session['uid']] = 1;
        if ($session['instance_id']) $instance_ids[] = $session['instance_id'];
        elseif ($session['area_id']) $area_ids[] = $session['area_id'];
    }
    //logfile(BANK_FILE_LOG,print_r($area_ids, true));
    //logfile(BANK_FILE_LOG,print_r($instance_ids, true));
    if (!$area_ids && !$instance_ids) break;
    $user_list = count($user_ids) ? make_hash(user_list(array('id' => array_keys($user_ids)))) : array();



    /*
    if($user_ids && $defence_ids) $defence_list = make_hash(artifact_list(null, null, null, true, false, sql_pholder(' AND user_id IN (?@) AND artikul_id IN (?@)', $user_ids, array_keys($defence_ids)), false, 'artikul_id, user_id'), 'user_id', true);

    $defence_info = array();
    foreach ($defence_list as $_user_id=>$_artikul_list) {
        foreach ($_artikul_list as $_artikul) {
            if(is_array($defence_ids[$_artikul['artikul_id']])) {
                foreach ($defence_ids[$_artikul['artikul_id']] as $_bot_artikul_id) $defence_info[$_user_id][$_bot_artikul_id] = $_bot_artikul_id;
            }else{
                $defence_info[$_user_id][$defence_ids[$_artikul['artikul_id']]] = $defence_ids[$_artikul['artikul_id']];
            }
        }
    }
    */

    if ($area_ids){ #FIX
        $query_add = sql_pholder(" AND hunt=1 AND area_id IN (".implode(',',array_unique($area_ids)).")");
        no_translate_push(1);
        $bots = bot_list(null,null,null,null,$query_add);
        no_translate_pop();
    }
	if ($instance_ids) {
		$query_add = " AND hunt=1 AND instance_id IN (".implode(',',array_unique($instance_ids)).")";
		$inst_bots = bot_list(null,null,null,null,$query_add);
		foreach($inst_bots as $inst_bot) {
			$bots[] = $inst_bot;
		}
	}
    //logfile(BANK_FILE_LOG,print_r($instance_ids, true));
	$area_ids = $instance_ids = array();
	foreach ($bots as $k=>$bot) {
		$loc_key = $bot['area_id'].'_'.$bot['instance_id'];
		$info[$loc_key]['bots'][] = &$bots[$k];
		if ($bot['instance_id']) $instance_ids[] = $bot['instance_id'];
		else $area_ids[] = $bot['area_id'];
	}
	$area_hash = $area_ids ? make_hash(area_list(null,sql_pholder(" AND id IN (?@)",array_unique($area_ids)))) : array();
	$instance_hash = $instance_ids ? make_hash(instance_list(array('id' => array_unique($instance_ids)))) : array();
	$instance_root_ids = array();
	foreach($instance_hash as $instance) {
		$instance_root_ids[$instance['root_id']] = $instance['root_id'];
	}
	$instance_root_hash = $instance_root_ids ? make_hash(instance_list(array('id' => $instance_root_ids))) : array();
	if (!$info) break;

	foreach ($info as $loc_key=>$item) {
		list($area_id,$instance_id) = explode('_',$loc_key);
		if ($instance_id) $loc = &$instance_hash[$instance_id];
		else $loc = &$area_hash[$area_id];
		if (!$loc) continue;
		$mask = '';
		if ($loc['h_map']){
			$t = explode('.',$loc['h_map']);
			$mask_fn = PATH_SWF_MAPS_HNT.$t[0].'_mask.dat';
			$mask = @file_get_contents($mask_fn);
		}
		$sizeX = intval($loc['h_sizex']);
		$sizeY = intval($loc['h_sizey']);

		if (!($instance_id ? instance_lock($instance_id,1) : area_lock($area_id,1))) continue;
		$cache = new Cache('HUNT'.$loc_key);
		if (!$cache->tryLock()) {
			if ($instance_id) instance_unlock($instance_id);
			else area_unlock($area_id);
			continue;
		}
		$data = $cache->get();

		// перемещение ботов
		$bot_list = &$item['bots'];
		if (!$bot_list) $bot_list = array();
		$bot_state = array();
		foreach ($bot_list as $bot) {
			$bot_state[$bot['id']] = &$data['bot_state'][$bot['id']];
			if ($bot['fight_id']) continue;
			$bot_id = $bot['id'];
			$Xn_1 = $bot_state[$bot_id]['Xn_1'];
			$Xn_2 = $bot_state[$bot_id]['Xn_2'];
			$a = $bot_state[$bot_id]['a'];
			$x = $bot_state[$bot_id]['x'];
			$y = $bot_state[$bot_id]['y'];
			$m = $bot_state[$bot_id]['m'];
			if ($m <= 0) {
				_init_position($sizeX,$sizeY,$x,$y,$mask,$loc_key);
				$a = rand(0,359);
				$m = 20;
			}

			$J = 5; // момент инерции
			$H = 10; // момент поворота
			$Amax = 4;  // макс. угол поворота
			$S = 2; // масштаб
			$V = max(0, $bot['h_speed']); // скорость

			for ($i=0; $i<$V*MNGR_HUNT_INTERVAL; $i++) {
				$Fn = rand(0,1)*2 - 1;  // ф-ция управления
				$Xn = ($Fn + $Xn_1*(2*$J + $H) - $J*$Xn_2) / ($J + $H + 1);  // J*x'' + H*x' + x = F
				$Xn_2 = $Xn_1;
				$Xn_1 = $Xn;

				$da = $Amax * $Xn;
				$a += $da;

				$dx = $S * cos(deg2rad($a));
				$dy = $S * sin(deg2rad($a));
				if (!_check_position($sizeX,$sizeY,$x+$dx,$y+$dy,$mask)) {
					$a += 90*$Fn;
					$dx = $dy = 0;
					$m--;
				} else $m = 20;
				$x += $dx;
				$y += $dy;
			}
			$bot_state[$bot_id]['Xn_1'] = sprintf("%.4f",$Xn_1);
			$bot_state[$bot_id]['Xn_2'] = sprintf("%.4f",$Xn_2);
			$bot_state[$bot_id]['a'] = $a % 360;
			$bot_state[$bot_id]['x'] = intval($x);
			$bot_state[$bot_id]['y'] = intval($y);
			$bot_state[$bot_id]['m'] = intval($m);
		}

		// нападение
		$user_state = array();
		foreach ($item['user_ids'] as $user_id) {
			$user_state[$user_id] = &$data['user_state'][$user_id];
		}
		shuffle($item['user_ids']);
		$bot_info = array();
		foreach ($bot_list as $bot) {
			$bot_id = $bot['id'];
			if ($bot['rtime'] > time_current()) continue;
			if (!$bot['fight_id'] && ($bot['h_agrlevel'] >= rand(1,100))) {
				$bot_x = $bot_state[$bot_id]['x'];
				$bot_y = $bot_state[$bot_id]['y'];
				$attack = false;
				foreach ($item['user_ids'] as $user_id) {
					// бот не нападает на своих
					$instance_root = array();
					if ($user_list[$user_id]['instance_id']) {
						if ($instance_root_hash[$instance_hash[$user_list[$user_id]['instance_id']]['root_id']]) {
							$instance_root = $instance_root_hash[$instance_hash[$user_list[$user_id]['instance_id']]['root_id']];
						} else {
							$instance_root = instance_get_root($user_list[$user_id]['instance_id']);
						}
						if ($instance_root && $instance_root['castle_id'] && (($user_list[$user_id]['raid_id'] - $instance_root['id']) == $bot['kind'])) continue;
                        if ($instance_root && $instance_root['bg_id'] && (($user_list[$user_id]['raid_id'] - $instance_root['id']) == $bot['kind'])) continue;
					}
					if ((!$instance_root || !$instance_root['castle_id']) && ($bot['kind'] == $user_list[$user_id]['kind'])) continue;
					if ($user_list[$user_id]['flags'] & USER_FLAG_NOATTACK || $user_list[$user_id]['flags2'] & USER_FLAG2_ACTING_GUARD || $user_list[$user_id]['flags2'] & USER_FLAG2_IN_ESTATE) continue;	// на персонажа нельзя нападать

                    //Если игрок под защитой
                    //if($defence_info[$user_id] && is_array($defence_info[$user_id]) && ($defence_info[$user_id][$bot['artikul_id']] || $defence_info[$user_id][-1])) continue;

                    $user_x = $user_state[$user_id]['x'];
					$user_y = $user_state[$user_id]['y'];
					if (!$user_list[$user_id]['fight_id'] && !($user_list[$user_id]['flags'] & USER_FLAG_FARMING)) {
						$user_x = $sizeX/2;
						$user_y = $sizeY/2;
					}
					$agrdist = $bot['h_agrdist'] + max($bot['level']-$user_list[$user_id]['level'],0)*0.10;
					$d = sqrt(pow($bot_x-$user_x,2) + pow($bot_y-$user_y,2));
					do {
						if ($d > $agrdist) break;
						if ($bot['flags'] & BOT_FLAG_WITH_RESTRICTIONS) { // Для этого бота также проверяем ограничения на атаку
							$out_restriction = restriction_check(0,array(array('id' => $bot['artikul_id'], 'object_class' => OBJECT_CLASS_BOT_ARTIKUL)),array($user_list[$user_id]));
							if ($out_restriction['status'] != RESTRICTION_STATUS_ALLOW) break;
						}
						$out_attack = fight_attack($bot,$user_list[$user_id]);
						if (!$out_attack['status']) break;
						$user_x = $bot_x;
						$user_y = $bot_y;
						$attack = true;
						break 2;  // бот не затягивает в бой других пользователей рядом
					} while (0);
					$user_state[$user_id]['x'] = $user_x;
					$user_state[$user_id]['y'] = $user_y;
				}
				if ($attack) $bot = bot_get($bot_id); // обновляем запись
			}
			$bot_info[$bot['id']] = get_params($bot,'id,artikul_id,nick,level,picture,f_sk,h_agrlevel,fight_id,flags');
		}

		$cache->update(array(
			'bot_state' => &$bot_state,
			'user_state' => &$user_state,
			'bot_info' => &$bot_info,
		),HUNT_CACHE_TIME);
		$cache->freeLock();
		if ($instance_id) instance_unlock($instance_id);
		else area_unlock($area_id);
	}
} while (0);

require_once("lib/system_stat.lib");
system_stat_update('hunt');

$stime2 = time();
$rtime = $stime2-$stime1;
if ($rtime > MNGR_HUNT_INTERVAL) error_log("(mngr_hunt: ".getmypid()."): Runtime $rtime sec");
sleep(max(MNGR_HUNT_INTERVAL-$rtime,0));


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
?>