<? # $Id: artifact.tpl,v 1.92 2010-03-16 15:40:05 v.krutov Exp $

require_once("/home/admin/web/dwar.fun/public_html/tpl/common.tpl");

function tpl_artifact_alt_prepare(&$artifacts, $object_class=false, $skip_fields=array(), $skip_other = array()) {
	global $enchant_skill_hash, $quality_info, $socket_pics, $energy_type_artikul_hash;
	
	if (!$artifacts) return false;
	
	// заполняем тайтлы и картинку чтобы использовать их в tpl_artifact_alt
	if ($object_class == OBJECT_CLASS_ARTIFACT) {
		artifact_artikul_get_title($artifacts);
	}
	
	$artifact_note_id = array();
	$artifact_artikul_ids = array();
	$artifact_kind_ids = array();
	$artifact_set_ids = array();
	$artifact_pack_ids = array();
    $energy_artifact_ids = array();
	foreach ($artifacts as $artifact) {
		if ($artifact['artikul_id']) {
		    if($object_class == OBJECT_CLASS_ARTIFACT) $artifact_note_ids[] = $artifact['id'];
			$artifact_artikul_ids[$artifact['artikul_id']] = 1;
		} else {
			if ($artifact['set_id']) $artifact_set_ids[$artifact['set_id']] = 1;
		}
		if ($artifact['kind_id']) $artifact_kind_ids[$artifact['kind_id']] = 1;
		if ($artifact['enchant_id']) $artifact_artikul_ids[$artifact['enchant_id']] = 1;
		if ($artifact['enchant2_id']) $artifact_artikul_ids[$artifact['enchant2_id']] = 1;
        if ($artifact['oprava_id']) $artifact_artikul_ids[$artifact['oprava_id']] = 1;
        if ($artifact['char_id']) $artifact_artikul_ids[$artifact['char_id']] = 1;
        if ($artifact['packet_id']) $artifact_pack_ids[$artifact['packet_id']] = $artifact['packet_id'];
	}

    $artifact_notes = count($artifact_note_ids) ? make_hash(artifact_note_list($artifact_note_ids, sql_pholder(' AND type=?', ARTIFACT_NOTE_TYPE_GIFT)), 'artifact_id') : array();
    $artifact_engraves = count($artifact_note_ids) ? make_hash(artifact_note_list($artifact_note_ids, sql_pholder(' AND type=?', ARTIFACT_NOTE_TYPE_ENGRAVE)), 'artifact_id') : array();
    $artifact_kinds = count($artifact_kind_ids) ? get_hash(artifact_kind_list(array('id' => array_keys($artifact_kind_ids)))) : array();
    $artifact_artikuls = count($artifact_artikul_ids) ? make_hash(artifact_artikul_list(array('id' => array_keys($artifact_artikul_ids)))) : array();
    $artifact_mount_packet_hash = count($artifact_pack_ids) ? make_hash(artifact_mount_pack_list(array('pack_id' => $artifact_pack_ids)), 'pack_id') : array();
    if (!$artifact_set_ids && $artifact_artikuls) {
        $artifact_set_ids = get_hash($artifact_artikuls, 'set_id', 'set_id');
    }
    $artifact_restriction_ids = array();
    foreach ($artifact_artikuls as $_artikul){
        if($_artikul['restriction_id']) $artifact_restriction_ids[$_artikul['restriction_id']] = $_artikul['restriction_id'];
    }
    if($artifact_restriction_ids) $artifact_restrictions = make_hash(artifact_restrictions_title_list(array('id' => $artifact_restriction_ids)));
    foreach ($artifact_artikuls as $k=>$_v) {
        if($_v['restriction_id']) artifact_description_put($artifact_artikuls[$k], $artifact_restrictions[$_v['restriction_id']]);
    }

    $artifact_sets = count($artifact_set_ids) ? get_hash(artifact_set_list(array('id' => array_keys($artifact_set_ids)))) : array();
    if ($object_class) foreach ($artifacts as $k => $artifact) $artifacts[$k]['object_class'] = $object_class;
    if(!$skip_other['skills']) {
        skill_objects_list($artifacts, '', true);
    }

    //Энергия
    foreach ($artifacts as $k => $artifact) {
        if ($artifact['artikul_id']) {
            $artikul = $artifact_artikuls[$artifact['artikul_id']];
            foreach ($energy_type_artikul_hash as $_energy_type => $_energy_field) {
                if ($artikul[$_energy_field]) {
                    $energy_artifact_ids[$artifact['id']] = $artifact['id'];
                    $artifacts[$k]['energy_list'][$_energy_type]['m'] = intval($artikul[$_energy_field]);
                    $artifacts[$k]['energy_list'][$_energy_type]['c'] = 0;
                }
            }
        }
    }
    $energy_artifacts_hash = array();
    if($energy_artifact_ids) {
        $energy_artifacts = artifact_energy_list(array('artifact_id' => $energy_artifact_ids));
        foreach ($energy_artifacts as $energy_artifact) {
            $energy_artifacts_hash[$energy_artifact['artifact_id']][$energy_artifact['type']] = $energy_artifact['energy'];
        }
    }

	foreach ($artifacts as $k => $artifact) {
		$artifacts[$k]['artifact_kind_title'] = $artifact_kinds[$artifact['kind_id']];
        $artifacts[$k]['artifact_socket'] = artifact_socket_get($artifact);

        if($artifacts[$k]['artifact_socket']['cnt']){
            $artifacts[$k]['artifact_socket_artikuls'] = artifact_socket_artikuls($artifact);
        }

		if ($artifact['artikul_id']) {
			$artikul = $artifact_artikuls[$artifact['artikul_id']];

            if(!($artifact['flags2'] & ARTIFACT_FLAG2_TITLE_ON_A)){
                $artifact[$k]['title'] = $artikul['title'];
            }

			$merge_fields = array('description', 'price', 'price_type', 'quality', 'level_min', 'level_max', 'class', 'set_id', 'slot_id', 'f_cfg', 'trend');

            $skip_fields_cur = $skip_fields;
            if(($artifact['flags2'] & ARTIFACT_FLAG2_RUNEWORD) && !in_array('quality', $skip_fields_cur)) {
                $skip_fields_cur[] = 'quality';
            }
			foreach ($merge_fields as $_k => $field) {
				if (in_array($field, $skip_fields_cur)) {
                    unset($merge_fields[$_k]);
                }
			}
			$artifacts[$k] = array_merge($artifacts[$k], get_params($artikul, $merge_fields));
			if ($artifact_notes[$artifact['id']]) {
				$artifacts[$k]['artifact_note'] = common_split_words(mb_substr(common_lf_to_br(htmlspecialchars($artifact_notes[$artifact['id']]['note'])),0,1024));
			}
			if ($artifact_engraves[$artifact['id']]) {
				$artifacts[$k]['artifact_engrave'] = common_lf_to_br(htmlspecialchars($artifact_engraves[$artifact['id']]['note']));
			}
			
			if ($artikul['set_id']) {
				$artifacts[$k]['artifact_set_title'] = $artifact_sets[$artikul['set_id']];
			}

			//Энергия
            if($energy_artifacts_hash[$artifact['id']]) {
                foreach ($energy_type_artikul_hash as $_energy_type => $_energy_field) {
                    if ($energy_artifacts_hash[$artifact['id']][$_energy_type]) {
                        $artifacts[$k]['energy_list'][$_energy_type]['c'] = intval($energy_artifacts_hash[$artifact['id']][$_energy_type]);
                    }
                }
            }

		} else { // если нет artikul_id - значит передали уже сразу массив артикулов в $artifacts
			if ($artifact['set_id']) {
				$artifacts[$k]['artifact_set_title'] = $artifact_sets[$artifact['set_id']];
			}
		}

        $artifacts[$k]['socket_object_skill_hash'] = socket_skills_get($artifact);

		$artifacts[$k]['enchant_object_skill_hash'] = array();
		$artifacts[$k]['enchant'] = array();
		if ($artifact['enchant_id']) {
			$artifacts[$k]['enchant'] = $artifact_artikuls[$artifact['enchant_id']];
			if (!isset($enchant_skill_hash[$artifacts[$k]['enchant']['id']])) {
				$enchant_skill_hash[$artifacts[$k]['enchant']['id']] = make_hash(skill_object_list(OBJECT_CLASS_ARTIFACT_ARTIKUL, $artifacts[$k]['enchant'], false, true),'skill_id');
			}
			$artifacts[$k]['enchant_object_skill_hash'] = $enchant_skill_hash[$artifacts[$k]['enchant']['id']];
			$artifacts[$k]['enchant_color'] = $quality_info[$artifacts[$k]['enchant']['quality']]['color'];
		}

		$artifacts[$k]['enchant2'] = array();
		if ($artifact['enchant2_id']) {
			$artifacts[$k]['enchant2'] = $artifact_artikuls[$artifact['enchant2_id']];
			$artifacts[$k]['enchant2_color'] = $quality_info[$artifacts[$k]['enchant2']['quality']]['color'];
		}

		if($artifact['packet_id'] && $artifact_mount_packet_hash[$artifact['packet_id']]) {
		    $artifacts[$k]['mount_packet'] = $artifact_mount_packet_hash[$artifact['packet_id']]['title'];
        }

        if ($artifact['oprava_id']) {
            $artifacts[$k]['oprava'] = $artifact_artikuls[$artifact['oprava_id']];
            if (!isset($oprava_skill_hash[$artifacts[$k]['oprava']['id']])) {
                $oprava_skill_hash[$artifacts[$k]['oprava']['id']] = make_hash(skill_object_list(OBJECT_CLASS_ARTIFACT_ARTIKUL, $artifacts[$k]['oprava'], false, true),'skill_id');
            }
            $artifacts[$k]['oprava_object_skill_hash'] = $oprava_skill_hash[$artifacts[$k]['oprava']['id']];
            $artifacts[$k]['oprava_color'] = $quality_info[$artifacts[$k]['oprava']['quality']]['color'];
        }

		if ($artifact['flags'] & ARTIFACT_FLAG_DURAB30) {	// 30% прочности
			$artifacts[$k]['durability_max'] = intval($artifact['durability_max'] * 0.30);
			$artifacts[$k]['durability'] = $artifacts[$k]['durability_max'];
		}

		foreach ($artifacts[$k]['enchant_object_skill_hash'] as $k2 => $v) {
			if ($artifacts[$k]['artifact_skills'][$k2]) {
				$artifacts[$k]['artifact_skills'][$k2]['value'] += $v['value'];
			}
		}

        //Заточечки
        if($artifact['toch'] && !($artifact['flags2'] & ARTIFACT_FLAG2_ZTOCH_TITLE)){
            $artifacts[$k]['title'] = $artifact['title'].' +'.$artifact['toch'];
        }

        $sl_id = ($artifact['artikul_id'] ? $artikul['slot_id'] : $artifact['slot_id']);
        $skills_toch = ztoch_skills($artifact, $sl_id);
        if ($skills_toch) foreach ($skills_toch as $_skid => $_val) {
            if(!$artifacts[$k]['artifact_skills'][$_skid]){
                $sk_needeed_embed[$_skid] = $_skid;
            }
            $artifacts[$k]['artifact_skills'][$_skid]['skill_id'] = $_val;
            $artifacts[$k]['artifact_skills'][$_skid]['value'] += $_val;
        }

        //Чары
        if ($artifact['char_id']) {
            $artifacts[$k]['char'] = $artifact_artikuls[$artifact['char_id']];
            if (!isset($char_skill_hash[$artifacts[$k]['char']['id']])) {
                $char_skill_hash[$artifacts[$k]['char']['id']] = make_hash(skill_object_list(OBJECT_CLASS_ARTIFACT_ARTIKUL, $artifacts[$k]['char'], false, true),'skill_id');
            }
            $artifacts[$k]['char'] = $artifact_artikuls[$artifact['char_id']];
            $artifacts[$k]['char_color'] = $quality_info[$artifacts[$k]['char']['quality']]['color'];
            foreach ($char_skill_hash[$artifacts[$k]['char']['id']] as $k2 => $v) {
                if(!$artifacts[$k]['artifact_skills'][$k2]){
                    $sk_needeed_embed[$k2] = $k2;
                }
                if ($artifacts[$k]['artifact_skills'][$k2]) {
                    $artifacts[$k]['artifact_skills'][$k2]['value'] += $v['value'];
                }
            }
        }

        if($sk_needeed_embed){
            $skill_embed = skill_list(array('id' => $sk_needeed_embed));
            foreach ($skill_embed as $skill_emb){
                $artifacts[$k]['artifact_skills'][$skill_emb['id']]['title'] = $skill_emb['title'];
                $artifacts[$k]['artifact_skills'][$skill_emb['id']]['flags'] = $skill_emb['flags'];
            }
        }

		user_calc_power_skills($artifacts[$k]['artifact_skills']);
		user_calc_power_skills($artifacts[$k]['enchant_object_skill_hash']);
        user_calc_power_skills($artifacts[$k]['socket_object_skill_hash']);
        user_calc_power_skills($artifacts[$k]['oprava_object_skill_hash']);
	}
}

// Перед отрисовкой необходимо выполнить функцию tpl_artifact_alt_prepare 
function tpl_artifact_alt(&$artifact, $param=false, $artifact_id=false) {
	global $trend_info, $quality_info, $class_info, $enchant_pics, $symbol_info, $symbol_rym_numbers, $energy_type_artikul_title;
	if (!$artifact) return false;

	$data = array();

	if (!$artifact_id)
		$artifact_id = $param['div_prefix'].'AA_'.$artifact['id'];

	$artifact_socket = $artifact['artifact_socket'];

    $artifact_socket_artikuls = $artifact['artifact_socket_artikuls'];

	$data['id'] = $artifact['id'];
	$data['title'] = $artifact['title'];
	$data['color'] = $quality_info[$artifact['quality']]['color'];
	$data['image'] = PATH_IMAGE_ARTIFACTS . $artifact['picture'];
	$data['slot_num'] = $artifact['slot_num'];
	if ($artifact['cnt'] > 1) {
		$data['cnt'] = $artifact['cnt'];
	}
	if ($artifact['enchant_id'] || $artifact['enchant']) {
		$enchant_level = $artifact['enchant']['aparam1'];
		if(!$enchant_level) {
            $enchant_level = $artifact['enchant']['param1'];
		}
        /*$data['enchant']['level'] = $enchant_level;
        $data['enchant']['quality'] = $artifact['enchant']['quality'];*/
		if($artifact['flags'] & ARTIFACT_FLAG_ARMOR_STYLE && $artifact['enchant']){
            //$data['enchant_icon'] = '<img src="' . PATH_IMAGE_ENCHANTS . 'enchant4_'.$artifact['enchant']['aparam1'].'.png' . '" alt="" class="enchant_png">';
            $data['enchant_icon'] = '<img src="' . PATH_IMAGE_ENCHANTS . 'enchant4_0.png' . '" alt="" class="enchant_png">';
        }else{
            if (isset($enchant_pics[$artifact['enchant']['quality']][$enchant_level])) {
                $data['enchant_icon'] = '<img src="' . PATH_IMAGE_ENCHANTS . $enchant_pics[$artifact['enchant']['quality']][$enchant_level] . '" alt="" class="enchant_png">';
            }
		}
	}
	$data['kind'] = $artifact['artifact_kind_title'];
	$data['kind_id'] = $artifact['kind_id'];
	if ($artifact["price"] > 0) {
		$data['price'] = html_money_str($artifact['price_type'], $artifact['price']);
	}
	if ($artifact['commission'] > 0) {
		$data['com']['title'] = 'Налог:';
		$data['com']['value'] = html_money_str(MONEY_TYPE_GAME,$artifact['commission']);
	}
	if ($artifact['durability_max']) {
		$data['dur'] = $artifact['durability'];
		$data['dur_max'] = $artifact['durability_max'];
	}
    if ($artifact['flags2'] & ARTIFACT_FLAG2_NO_BREAK) {
        unset($data['dur']);
        $data['dur2'] = 'Неломаемый предмет';
    }
	if ($artifact['owner'] > 0) {
		$data['owner']['title'] = 'Владелец:';
		$data['owner']['value'] = $artifact['owner']['nick'];
	}
	if ($artifact['level_min'] && $artifact['type_id'] != ARTIFACT_TYPE_ID_SPELL) {
		$data['lev']['title'] = ' Уровень ';
		$data['lev']['value'] = ($artifact['level_min'] > 0 ? $artifact['level_min'] : 1).($artifact['level_max'] >= $artifact['level_min'] ? '-'.$artifact['level_max']: '');
	}
	if ($artifact['class']) {
		$data['cls'] = array();
		foreach ($class_info as $class) {
			if ($artifact['class'] & $class['id']) {
				$data['cls'][] = '<img src="images/elements/'. $class_info[$class['id']]['picture'].'" width="11" height="10" align="absmiddle">&nbsp;'.$class_info[$class['id']]['title'];
			}
		}
	}
	if($artifact['energy_list']) {
	    $data['energy_list'] = $artifact['energy_list'];
	    foreach ($data['energy_list'] as $energy_type=>$energy) {
            $data['energy_list'][$energy_type]['w'] = round(265-($energy['c']*265/$energy['m']));
	        $data['energy_list'][$energy_type]['title'] = '<div class="w100" align="center"><b>'.($energy_type_artikul_title[$energy_type] ? $energy_type_artikul_title[$energy_type] : 'Энергия').'</b></div>';
        }
    }
	if ($artifact['trend']) {
		$data['trend'] = $trend_info[$artifact['trend']]['title2'];
	}
	// skills
	if ($artifact['artifact_skills']) {
		$data['skills'] = array();
		foreach ($artifact['artifact_skills'] as $skill) {
			if (!($skill['flags'] & SKILL_FLAG_VISIBLE) || !$skill['value'])
				continue;
			$enchant_skill = $artifact['enchant_object_skill_hash'][$skill['skill_id']];
            $socket_skill = $artifact['socket_object_skill_hash'][$skill['skill_id']];
            $oprava_skill = $artifact['oprava_object_skill_hash'][$skill['skill_id']];
			$data['skills'][] = array(
				'title' => $skill['title'],
				'value' => '<b>'.user_print_skill_value($skill['skill_id'], $skill['value']).'</b>'.(
					$enchant_skill ?
					'<b style="color:'.$artifact['enchant_color'].'"> ('.str_replace('+', '', user_print_skill_value($enchant_skill['skill_id'], $enchant_skill['value'])) . ')' :
					'').(
                    $oprava_skill ?
                        '<b style="color:'.$artifact['oprava_color'].'"> ('.str_replace('+', '', user_print_skill_value($oprava_skill['skill_id'], $oprava_skill['value'])) . ')' :
                        '').(
                    $socket_skill ?
                        '<b style="color:#955c4a;"> ('.str_replace('+', '', user_print_skill_value($socket_skill['skill_id'], $socket_skill['value'])) . ')' :
                        '').'</b>'
			);
		}
	}
	if (!is_array($artifact['enchant_object_skill_hash']))
		$artifact['enchant_object_skill_hash'] = array();
	$data['skills_e'] = array();
	foreach($artifact['enchant_object_skill_hash'] as $skill) { // оставшиеся скилы с энчанта
		if (!($skill['flags'] & SKILL_FLAG_VISIBLE) || !$skill['value'] || $artifact['artifact_skills'][$skill['skill_id']])
			continue;
		$value = user_print_skill_value($skill['skill_id'], $skill['value']);
        $socket_skill = $artifact['socket_object_skill_hash'][$skill['skill_id']];
        $oprava_skill = $artifact['oprava_object_skill_hash'][$skill['skill_id']];
		$data['skills_e'][] = array(
			'title' => $skill['title'],
			'value' => '<b>'.$value.'</b><b style="color:'.$artifact['enchant_color'].'"> ('.str_replace('+', '', $value).(
                $oprava_skill ?
                    '<b style="color:'.$artifact['oprava_color'].'"> ('.str_replace('+', '', user_print_skill_value($oprava_skill['skill_id'], $oprava_skill['value'])) . ')' :
                    '').(
                $socket_skill ?
                    '<b style="color:#955c4a;"> ('.str_replace('+', '', user_print_skill_value($socket_skill['skill_id'], $socket_skill['value'])) . ')' :
                    '').')</b>',
		);
	}
    if (!is_array($artifact['socket_object_skill_hash']))
        $artifact['socket_object_skill_hash'] = array();
    foreach($artifact['socket_object_skill_hash'] as $skill) { // оставшиеся скилы с сокетов
        if (!$skill['value'] || $artifact['artifact_skills'][$skill['skill_id']] || $artifact['enchant_object_skill_hash'][$skill['skill_id']])
            continue;
        $oprava_skill = $artifact['oprava_object_skill_hash'][$skill['skill_id']];
        $data['skills'][] = array(
            'title' => $skill['title'],
            'value' => '<b>'.user_print_skill_value($skill['skill_id'], $skill['value']).(
                $oprava_skill ?
                    '<b style="color:'.$artifact['oprava_color'].'"> ('.str_replace('+', '', user_print_skill_value($oprava_skill['skill_id'], $oprava_skill['value'])) . ')' :
                    '').'</b>'
        );
    }

    if (!is_array($artifact['oprava_object_skill_hash']))
        $artifact['oprava_object_skill_hash'] = array();
    foreach($artifact['oprava_object_skill_hash'] as $skill) { // оставшиеся скилы с оправы
        if (!$skill['value'] || $artifact['artifact_skills'][$skill['skill_id']] || $artifact['enchant_object_skill_hash'][$skill['skill_id']] || $artifact['socket_object_skill_hash'][$skill['skill_id']])
            continue;
        $data['skills'][] = array(
            'title' => $skill['title'],
            'value' => '<b>'.user_print_skill_value($skill['skill_id'], $skill['value']).'</b>'
        );
    }
if (!($artifact['flags'] & ARTIFACT_FLAG_ARMOR_STYLE)) {
	if ($artifact['enchant']) {
		$data['enchant']['title'] = 'Руна';
		$data['enchant']['value'] = '<b style="color:'.$artifact['enchant_color'].'">'.$artifact['enchant']['title'].(
		$artifact['param2'] ? ' (&#9650;'.(100*$artifact['param2'] / ARTIFACT_RUNE_UPGRADE_AMOUNT).'%)' : '').'</b>';
} }else{
	if ($artifact['enchant']) {
		$data['enchant']['title'] = 'Лак';
		$data['enchant']['value'] = '<b style="color:'.$artifact['enchant_color'].'">'.$artifact['enchant']['title'].(
		$artifact['param2'] ? ' (&#9650;'.(100*$artifact['param2'] / ARTIFACT_RUNE_UPGRADE_AMOUNT).'%)' : '').'</b>';
	}
}
    if($artifact['mount_packet']) $data['mount_packet'] = $artifact['mount_packet'];

	if ($artifact['enchant2']) {
		$enchant2_action = reset(action_object_list($artifact['object_class'], $artifact['id']));
		$data['enchant_mod']['title'] = 'Встроено';
		$data['enchant_mod']['value'] = '<b style="color:'.$artifact['enchant2_color'].'">'.$artifact['enchant2']['title'].(
			$enchant2_action['n'] ? ' ('.$enchant2_action['n'].')':'') . '</b>';
	}
    if ($artifact['oprava']) {
        $data['oprava']['title'] = 'Оправа';
        $data['oprava']['value'] = '<b style="color:'.$artifact['oprava_color'].'">'.$artifact['oprava']['title'].'</b>';
    }
    if ($artifact['char']) {
        $data['chars']['title'] = 'Начертано';
        $data['chars']['value'] = '<b style="color:'.$artifact['char_color'].'">'.$artifact['char']['title'].'</b>';
    }
    if($artifact['flags2'] & ARTIFACT_FLAG2_RUNIC_FRAME){
        $data['symbol']['title'] = 'Символ';
        if($artifact['rf_enchant_id']){
            //$data['symbol']['value'] = '<img src="/'.PATH_IMAGE_SYMBOL.$artifact['rf_enchant_id'].'_'.$artifact['rf_enchant_level'].'.png">';
            $data['symbol']['value'] = '<b style="color:'.$quality_info[$artifact['rf_enchant_level'] - 1]['color'].'">'.$symbol_info[$artifact['rf_enchant_id']]['title'].' '.$symbol_rym_numbers[$artifact['rf_enchant_level']].'</b>';
        }else{
            $data['symbol']['value'] = 'Отсутствует';
        }
    }

    if ($artifact_socket_artikuls) {
        $socket_cnt = 1;
        foreach ($artifact_socket_artikuls as $artikul){
            $data['socket_a_'.$socket_cnt]['title'] = 'Сокет #'.$socket_cnt;
            $data['socket_a_'.$socket_cnt]['value'] = '<b style="color:'.$quality_info[$artikul['quality']]['color'].'">'.$artikul['title'].'</b>';
            $socket_cnt++;
        }
    }
	if ($artifact['set_id']) {
		$data['set']['title'] = 'Комплект';
		$data['set']['value'] = '<b style="color:'.$quality_info[$artifact['quality']]['color'].'">'.$artifact['artifact_set_title'].'</b>';
	}
	if ($artifact['time_expire'] || $artifact['validity']) {
		$time_start = (defined('FAR_FAR_FUTURE') && ($artifact['time_expire'] > FAR_FAR_FUTURE)) ? FAR_FAR_FUTURE : time_current();
		$data['exp']['title'] = 'Время жизни';
		$data['exp']['value'] = html_period_str($artifact['time_expire'] ? $artifact['time_expire'] - $time_start: $artifact['validity']);
	}
	if(!$param['no_flags']){
        if (($artifact['flags'] & ARTIFACT_FLAG_CHANGE) && !$artifact['artifact_note']) {
            $data['change'] = 'Предмет можно обменять!';
        }
        if (($artifact['flags'] & ARTIFACT_FLAG_NOGIVE) && !$artifact['artifact_note'] && !($artifact['flags'] & ARTIFACT_FLAG_CHANGE)) {
            $data['nogive'] = 'Предмет нельзя передать!';
        }
        if (($artifact['flags'] & ARTIFACT_FLAG_CLAN_THING) && !$artifact['artifact_note'] && !($artifact['flags'] & ARTIFACT_FLAG_CHANGE)) {
            $data['clan_thing'] = 'Клановый предмет';
        }
        if (($artifact['flags'] & ARTIFACT_FLAG_BOE) && !($artifact['flags'] & ARTIFACT_FLAG_NOGIVE) && !($artifact['flags'] & ARTIFACT_FLAG_CHANGE)) {
            $data['boe'] = 'Предмет станет непередаваемым после надевания!';
        }
        if (($artifact['flags'] & ARTIFACT_FLAG_NOWEIGHT)) {
            $data['noweight'] = 'Предмет не занимает места в рюкзаке';
        }
        if (($artifact['flags'] & ARTIFACT_FLAG_NOSELL)) {
            $data['nosell'] = 'Предмет нельзя сдать в скупку';
        }
        if (($artifact['flags'] & ARTIFACT_FLAG_CANT_FREEZE)) {
            $data['nofreeze'] = 'Предмет нельзя положить в хранилище';
        }
        if(($artifact['flags2'] & ARTIFACT_FLAG2_CAN_SMOL)){
            $data['cansmol'] = 'Предмет можно укрепить смолой';
        }
        if(($artifact['flags2'] & ARTIFACT_FLAG2_NO_BREAK)){
            $data['nobreaks'] = 'Неломаемый предмет';
        }
        if(($artifact['flags'] & ARTIFACT_FLAG_CANT_BROKEN)){
            $data['cant_broken'] = 'Нельзя сломать';
        }
        if(($artifact['flags'] & ARTIFACT_FLAG_CANT_CRUSHED)){
            $data['cant_crushed'] = 'Нельзя раздробить';
        }
    }

	if ($artifact['description']) {
		$data['desc'] = tpl_common_tags($artifact['description']);

        $comm_desc = preg_split('/<br[^>]*>/i', $data['desc']);
        if(count($comm_desc) > 10){
            $data['desc'] = '';
            for($i = 0; $i < 10; $i++){
                if(!$comm_desc[$i]) continue;
                $data['desc'] .= $comm_desc[$i].'<br>';
            }
            $data['desc'] .= '...';
        }
	}
	if ($artifact['artifact_note']) {
		$from_txt = mb_substr($artifact['artifact_note'], 0, mb_strpos($artifact['artifact_note'], '.'));
		$msg_txt = trim(mb_substr($artifact['artifact_note'], mb_strlen($from_txt)+1));
		$data['note'] = '<span class="redd b">'.$from_txt.':</span> '.$msg_txt;
	}
	if ($artifact['artifact_engrave']) {
		$data['engrave'] = '<span class="redd b">'.'Гравировка'.':</span> '.$artifact['artifact_engrave'];
	}
	if ($artifact['discount']) {
		$data['discount'] = $artifact['discount'];
	}
	if ($artifact['price_old']) {
		$data['price_old'] = array(
			'title' => 'Цена:',
			'value' => addslashes(html_money_str($artifact['price_type'], $artifact['price_old'])),
		);
	}
	if ($artifact['store_flags'] & AREA_STORE_ARTIKUL_FLAG_BEST) {
		$data['store_flag_best'] = true;
	}
	if ($artifact['store_flags'] & AREA_STORE_ARTIKUL_FLAG_NEW) {
		$data['store_flag_new'] = true;
	}

	$data['_act1'] = $artifact['slot_id'] ? 2 : ($artifact['flags'] & ARTIFACT_FLAG_USE ? 1 : 0);
	$data['_act2'] = $artifact['flags'] & ARTIFACT_FLAG_NODROP ? 0 : 3;
	$data['_act3'] = ($artifact['slot_id'] && ($artifact['flags'] & ARTIFACT_FLAG_USE)) ? 1 : 0;
	$data['_puton'] = $artifact['flags'] & ARTIFACT_FLAG_BOE ? 1 : 0;

	$data['slot_id'] = $artifact['slot_id'];
	if($artifact['f_cfg'] & ARTIFACT_PPT_MW){ $data['dh'] = 1; }

	//Сокеты
    for($i = 1; $i <= $artifact_socket['cnt']; $i++){
        $data['socket_'.$i] = PATH_IMAGE_GEMS.$artifact_socket['socket_pic'][$i];
    }

	if ($param['mode'] == 'raw') {
		return $data;
	} else {
		if ($param['mode'] == 'return') {
			ob_start();
		}
		echo '<script type="text/javascript">'.$param['location'].'art_alt["'.$artifact_id.'"] = '.json_encode($data).';</script>';
		if ($param['mode'] == 'return') {
			$str = ob_get_contents();
			ob_end_flush();
			return $str;
		}
	}
}

function tpl_artifact_packop_adv(){
    ?>
    <script>
        var ask_amount_title = null;
        function ask_amount(func, mx, title, id, action) {
            ask_amount_title = ask_amount_title || $('#ask_amount_title');
            var div = gebi('cart_amount_div');
            if(!func) {
                div.style.display = 'none';
                frame_content_hider.hide();
                return;
            }

            try{
                if(no_spros_drop_artifacts_adv !== undefined && no_spros_drop_artifacts_adv === true) { //Кидать максимум не спрашивать хули
                    func(mx||1,id);
                    return false;
                }
            }catch(e){}

            gebi('cart_amount').value = 1;
            var cart_amount_form = gebi('cart_amount_form');
            var onsubmit = function() {
                onsubmit = function() {};
                cart_amount_form.onsubmit = function() {return false;};
                func(gebi('cart_amount').value, id);
                return false;
            };

            var input = $('#cart_amount');
            var max_val = parseInt(mx);
            if(max_val){
                input.data('min-value', 1);
                input.data('max-value', max_val);
            } else {
                $('#cart_amount_sell_cnt').text('');
                input.data('max-value', 1);
                input.data('min-value', 1);
            }
            input[0].onkeyup = counter_controller.keypress;
            counter_controller.change(input);

            $(document).unbind('keyup');
            $(document).on('keyup', function(e) {
                if (e.keyCode == 13) { // Enter
                    $(this).unbind('keyup');
                    onsubmit();
                } else if (e.keyCode == 27) { // Esc
                    $(this).unbind('keyup');
                    ask_amount();
                }
            });

            cart_amount_form.onsubmit = onsubmit;
            gebi('cart_amount_all').onclick = function() {
                this.onclick = function() {};
                gebi('cart_amount').value = mx;
                counter_controller.change(input);
            };

            div.style.display = 'block';
            div.style.top = document.body.scrollTop + (document.body.clientHeight - div.offsetHeight)/2;
            div.style.left = document.body.scrollLeft + (document.body.clientWidth - div.offsetWidth)/2;

            gebi('cart_amount').focus();

            if (id) {
                var artifact_alt = get_art_alt('AA_' + id);
                var title = artifact_alt.title || '';
                var artifact_color = artifact_alt.color;
                console.log(artifact_alt);

                var art = gebi('art_amount');
                art.innerHTML = '<img src="' + (artifact_alt.picture != undefined ? artifact_alt.picture : artifact_alt.image) + '">';

                if (artifact_alt) $('#ask_confirm_type_amount').text(artifact_alt.kind);
                (artifact_alt.lev) ? $('#ask_confirm_level_amount').text(artifact_alt.lev.value) : $('#ask_confirm_level_amount').text(0);
            }

            $('#cart_amount_cnt').text(mx);


            $('#ask_confirm_title_amount').html(title).css('color', artifact_color);
            frame_content_hider.show();

            if (action) {
                $('#action_title_amount').html(action);
                if (action == 'Перемещение предмета') {
                    $('#ask_confirm_ok_container').html('<b class="butt1 pointer"><b><input value="Переместить" type="submit" onClick="ask_amount();" style="width: 100px;" class="redd" ></b></b>');
                } else if (action == 'Вернуть предмет в казну') {
                    $('#ask_amount_title').html('Вы уверены, что хотите вернуть предмет в казну?');
                    $('#ask_confirm_ok_container').html('<b class="butt1 pointer"><b><input value="Ok" type="submit" onClick="ask_amount();" style="width: 100px;" class="redd" ></b></b>');
                } else if (action == 'Переместить предмет') {
                    $('#ask_amount_title').html('Вы уверены, что хотите переместить предмет?');
                    $('#ask_confirm_ok_container').html('<b class="butt1 pointer"><b><input value="Ok" type="submit" onClick="ask_amount();" style="width: 100px;" class="redd" ></b></b>');
                } else {
                    $('#ask_confirm_ok_container').html('<b class="butt1 pointer"><b><input value="Добавить" type="submit" onClick="ask_amount();" style="width: 100px;" class="redd" ></b></b>');
                }
            } else {
                $('#action_title_amount').html('Удаление предмета');
                $('#ask_amount_title').html('Вы уверены, что хотите ВЫБРОСИТЬ?');
                $('#ask_confirm_ok_container').html('<b class="butt1 pointer"><b><input value="Выбросить" type="submit" onClick="ask_amount();" style="width: 100px;" class="redd" ></b></b>');
            }
        }
    </script>
    <form id="cart_amount_form">
        <div id="cart_amount_div" style="display: none; position: absolute; z-index: 9999;">
            <div class="popup_global_container">
                <div class="popup-top-left">
                    <div class="popup-top-right">
                        <div class="popup-top-center">

                            <div class="popup_global_title" id="action_title_amount"></div>

                        </div>
                    </div>
                    <div class="popup_global_close_btn" onclick="ask_amount();"></div>
                </div>

                <div class="popup-left-center">
                    <div class="popup-right-center">

                        <div class="popup_global_content" style="padding: 20px;">
                            <div id="ask_amount_title" class="redd" style="text-align: center;"></div>
                            <span id="action_description_amount"></span>
                            <div class="popup-artifact">
                                <div class="popup-artifact__dsc">
                                    <span id="art_amount" class="popup-artifact__img"></span>
                                    <div id="ask_confirm_title_amount" class="popup-artifact__title popup-artifact__left"></div>
                                    <div><img src="images/tbl-shp_item-icon.gif" width="11" height="10" class="tbl-shp_item-ico" alt=""> <span id="ask_confirm_type_amount"></span></div>
                                    <div><img src="images/tbl-shp_level-icon.gif" width="11" height="10" class="tbl-shp_item-ico" alt=""> Уровень <span id="ask_confirm_level_amount"></span></div>
                                    <div id="price_container_amount" style="display: none">Цена: <span id="price_amount"></span></div>
                                </div>
                            </div>

                            <div style="padding: 0 11px;">
                                <div class="cart-amount-sell-price" style="text-align: left;">
                                    <div>
                                        <b>Количество:</b>
                                        <span class="cart-amount-input-cont">
											<span class="b-input">
												<span class="b-input__inner">
													<span class="arrow left left-disabled" onclick="counter_controller.left(this);" title="Уменьшить кол-во"></span>
													<span class="arrow right" onclick="counter_controller.right(this);" title="Увеличить кол-во"></span>
														<input id="cart_amount" type="text" value="1" class="cart_amount_sell_input"
                                                               onchange="counter_controller.change(this, event);"
                                                               autocomplete="off"
                                                               onkeypress="counter_controller.keypress(this, event);">
												</span>
											</span>
										</span>
                                        из <span id="cart_amount_cnt"></span>
                                        <b class="butt1 pointer"><b><input value="Все" type="button" style="width: 100px;" class="redd" id="cart_amount_all"></b></b>									</div>
                                </div>

                                <div style="clear: both; margin-top: 10px; text-align: center;">
                                    <span id="ask_confirm_ok_container"></span>
                                    <b class="butt1 pointer"><b><input value="Отмена" type="button" onClick="ask_amount();" style="width: 100px;" class="redd"  ></b></b>								</div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="popup-left-bottom">
                    <div class="popup-right-bottom">
                        <div class="popup-bottom-center"></div>
                    </div>
                </div>

            </div>
        </div>
    </form>
    <div id="frame_content_hider">&nbsp;</div>
    <script type="text/javascript">
        var frame_content_hider = $('#frame_content_hider');
    </script>
    <script>
        var counter_controller = {
            left: function(e){
                var left = $(e);
                if(left.hasClass('left-disabled')) return false;
                var input = left.parent().find('input');
                var value = input.val();
                value--;
                if(value >= 1) input.val(value);
                counter_controller.change(input);
                return false;
            },
            right: function(e){
                var right = $(e);
                if(right.hasClass('right-disabled')) return false;
                var input = right.parent().find('input');
                var value = input.val();
                value++;
                if(value <= input.data('max-value')) input.val(value);
                counter_controller.change(input);
                return false;
            },
            keypress: function(e){
                var key = e.keyCode || e.which,
                    el = $(this);
                if (key == 38) { // up
                    counter_controller.right(el.parent().parent().find('.arrow.right'));
                } else if (key == 40) { // down
                    counter_controller.left(el.parent().parent().find('.arrow.left'));
                } else {
                    counter_controller.change(el);
                }
                return false;
            },
            change : function(e){
                var input = $(e).parent().find('input'),
                    value = input.val(),
                    min = input.data('min-value') || 0;
                max = input.data('max-value') || 1;
                if(!parseInt(value)) value = 1;
                if(value >= max) {
                    value = max;
                    $(e).parent().parent().find('.arrow.right').addClass('right-disabled')
                }
                if(value <= min) {
                    value = min;
                    $(e).parent().parent().find('.arrow.left').addClass('left-disabled')
                }
                if(value > min){
                    $(e).parent().parent().find('.arrow.left').removeClass('left-disabled')
                }
                if(value < max){
                    $(e).parent().parent().find('.arrow.right').removeClass('right-disabled')
                }
                input.val(value);
                input.trigger('update_value', input);
                return false;
            }
        }
    </script>
    <?
}

function tpl_artifact_packop(){
?>
<script>
function ask_amount(func, mx){
	var div = gebi('cart_amount_div');
	if(!func) {
		div.style.display = 'none';
		return;
	}
	gebi('cart_amount_form').onsubmit = function() {
		func(gebi('cart_amount').value);
		return false
	}
	gebi('cart_amount_all').onclick = function() {
		gebi('cart_amount_div').style.display = 'none';
		func(mx||1);
	}
	div.style.display = 'block';
	div.style.top = document.body.scrollTop + (document.body.clientHeight - div.offsetHeight)/2-100;
	div.style.left = document.body.scrollLeft + (document.body.clientWidth - div.offsetWidth)/2;
	gebi('cart_amount').focus();
}
</script>
<form id="cart_amount_form">
	<table class="p2v brd2-all bg_l" id="cart_amount_div" style="z-index: 1000;position: absolute; display: none;border-color: #660000" cellpadding="3" cellspacing="0" border="0">
		<tr>
			<td colspan="2" class="redd" align="center"><b><?=common_java_escape('Введите количество:');?></b></td>
			<td><div style="float: right;"><?=html_button('X','butt2','button','',array('add' => 'style="width:17px" onclick="ask_amount()"'));?></div></td>
		</tr>
		<tr>
			<td align="right"><input id="cart_amount" type="text" value="1" style="width:34px; padding-left: 8px" class="tbl-shp_item-input-price"></td>
			<td align="right" ><?=html_button('OK','butt2','submit','',array('add' => 'id="button_ok" style="width:40px" onclick="ask_amount();"'));?></td>
			<td align="left" ><?=html_button(common_java_escape('ВСЕ'),'butt2','button','',array('add' => 'style="width:40px" id="cart_amount_all" onclick="ask_amount();"'));?></td>
		</tr>
	</table>
</form>

<script>
function ask_confirm(func){
	var div = gebi('cart_confirm_div')
	if(!func) {
		div.style.display = 'none'
		return
	}
	gebi('cart_confirm_ok').onclick = function() {
		func();
	}
	
	div.style.display = 'block'
	div.style.top = document.body.scrollTop + (document.body.clientHeight - div.offsetHeight)/2-100
	div.style.left = document.body.scrollLeft + (document.body.clientWidth - div.offsetWidth)/2
    gebi('cart_confirm_ok').focus();
}
</script>
<table class="p2v brd2-all bg_l" ID='cart_confirm_div' style="z-index: 1000;position: absolute; display: none; border-color: #660000" cellpadding="3" cellspacing="0" border="0">
<tr>
<td colspan="2" id="ask_confirm_ms" width="300" align="center"><b><?=common_java_escape('Вы уверены что хотите выбросить предмет?');?></b> </td>
<tr>
<tr>
<td align="right" width="50%"><?=html_button('OK','butt2','submit','',array('add' => ' style="width:50px" ID="cart_confirm_ok"'));?></td>
<td align="left" width="50%"><?=html_button(common_java_escape('ОТМЕНА'),'butt2','button','',array('add' => 'style="width:60px" onclick="ask_confirm()"'));?></td>
</tr>
</table>

<?
}

function tpl_artifact_sell_packop_adv(){
    ?>
    <script>

        var ask_amount_sell_title = null;

        function ask_amount_sell(func, mx, params) {
            ask_amount_sell_title = ask_amount_sell_title || $('#ask_amount_sell_title');
            var div = gebi('cart_amount_sell_div');
            var cart_amount_sell = gebi('cart_amount_sell');
            var cart_amount_sell_form = gebi('cart_amount_sell_form');
            var action_block = gebi('action_title_sell');

            if(!func) {
                cart_amount_sell_form.style.display = 'none';
                frame_content_hider.hide();
                return;
            }

            var onsubmit = function() {
                onsubmit = function() {};
                cart_amount_sell_form.onsubmit = function() {return false;};
                func(gebi('cart_amount_sell').value);
                return false;
            };

            var amount_block = gebi('cart_amount_sell_price');
            var e = $(amount_block);
            var err = e.find('.error');
            var cur_price = e.find('.current_price');

            var action_price = gebi('action_price_sell');

            var action_description = gebi('action_description_sell');

            if (params) {

                var action_title = params.action || {};
                var prices = params.prices || {};
                var artifact_id = params.artifact_id || null;
                var description = params.description || null;
                //if (artifact_id) var artifact_color = artifact_get_color(artifact_id);

                if (artifact_id) {
                    var artifact_alt = get_art_alt('AA_' + artifact_id);
                    var title = artifact_alt.title || '';
                    var artifact_color = artifact_alt.color;
                }

                var action = action_title ? ask_confirm_title_actions[action_title] : {},
                    price = artifact_alt.price ? artifact_alt.price : 0;

                if (description) {
                    action_description.innerHTML = description;
                    action_description.style.display = '';
                }

                var art = gebi('art_sell');
                var action_ask_confirm = gebi('action_ask_confirm');

                var action_titl = '';
                try{ action_titl = action.title; }catch (e) {}

                art.innerHTML = '<img src="' + (artifact_alt.picture != undefined ? artifact_alt.picture : artifact_alt.image) + '">';
                action_ask_confirm.innerHTML = 'Вы уверены, что хотите ' + action_titl+ '?';
                action_block.innerHTML = ask_confirm_title_actions[params.action]['title_popup'] ? ask_confirm_title_actions[params.action]['title_popup'] : action_titl;
            }

            var money = 0, money_silver = 0, money_gold = 0;
            if (prices) {
                money        = prices.money        ? prices.money        : money;
                money_silver = prices.money_silver ? prices.money_silver : money_silver;
                money_gold   = prices.money_gold   ? prices.money_gold   : money_gold;
            }

            var onkeyup = function () {
                err.hide();
                updateCurPriceHtml(parseInt(this.value));
            };

            var updateCurPriceHtml = function(count) {
                if (count > 0) {
                    if (money > 0) {
                        cur_price.html(html_money_str(count, money, money_silver, money_gold)).show();
                    } else {
                        //if item without price it sell for 0,01 for any quantity in stack
                        cur_price.html(html_money_str(1, 0.01, money_silver, money_gold)).show();
                    }
                } else {
                    var err_msg = $('<b/>').addClass('redd').text('Укажите корректное количество');
                    err.html(err_msg).show();
                }
            };

            cart_amount_sell.value = 1;
            cart_amount_sell.onkeyup = counter_controller.keypress;
            $(cart_amount_sell).off('update_value').on('update_value', onkeyup);

            $(document).unbind('keyup');
            $(document).on('keyup', function(e) {
                if (e.keyCode == 13) { // Enter
                    $(this).unbind('keyup');
                    onsubmit();
                } else if (e.keyCode == 27) { // Esc
                    $(this).unbind('keyup');
                    ask_amount_sell();
                }
            });
            cart_amount_sell_form.onsubmit = onsubmit;
            gebi('cart_amount_sell_all').onclick = function() {
                this.onclick = function() {};
                cart_amount_sell.value = artifact_alt.cnt;
                updateCurPriceHtml(parseInt(artifact_alt.cnt));
                counter_controller.change(cart_amount_sell);
            };

            var cart_amount_sell_all_money = $('.cart_amount_sell_all_money');

            if (prices) {
                updateCurPriceHtml(1);
                e.show();err.hide();
                var price_at_all = $('<span/>').attr({id: "area_cart_all_items_price", 'class': "area-cart-all-items-price-money"});

                if (money > 0) {
                    price_at_all.html(html_money_str(mx, money, money_silver, money_gold));
                } else {
                    //if item without price it sell for 0,01 for any quantity in stack
                    price_at_all.html(html_money_str(1, 0.01, money_silver, money_gold));
                }


            } else {
                e.hide();
            }

            cart_amount_sell_form.style.display = 'block';
            cart_amount_sell_form.style.top = document.body.scrollTop + (document.body.clientHeight - div.offsetHeight)/2;
            cart_amount_sell_form.style.left = document.body.scrollLeft + (document.body.clientWidth - div.offsetWidth)/2;

            if (title) $('#ask_confirm_title_sell').text(title).css('color', artifact_color);
            if (artifact_alt) $('#ask_confirm_type_sell').text(artifact_alt.kind);
            (artifact_alt.lev) ? $('#ask_confirm_level_sell').text(artifact_alt.lev.value) : $('#ask_confirm_level_sell').text(0);

            var input = $('#cart_amount_sell');

            if(artifact_alt.cnt){
                $('#cart_amount_sell_cnt').text(artifact_alt.cnt);
                input.data('min-value', 1);
                input.data('max-value', artifact_alt.cnt);
            } else {
                $('#cart_amount_sell_cnt').text('');
                input.data('max-value', 1);
                input.data('min-value', 1);
            }

            counter_controller.change(input);

            if (price) {
                //$('#price_container_sell').show();
                $('#price_sell').html(artifact_alt.price);
            }

            gebi('cart_amount').focus();
            title = title || '';
            ask_amount_sell_title.html(title);
            frame_content_hider.show();
        }

        var counter_controller = {
            left: function(e){
                var left = $(e);
                if(left.hasClass('left-disabled')) return false;
                var input = left.parent().find('input');
                var value = input.val();
                value--;
                if(value >= 1) input.val(value);
                counter_controller.change(input);
                return false;
            },
            right: function(e){
                var right = $(e);
                if(right.hasClass('right-disabled')) return false;
                var input = right.parent().find('input');
                var value = input.val();
                value++;
                if(value <= input.data('max-value')) input.val(value);
                counter_controller.change(input);
                return false;
            },
            keypress: function(e){
                var key = e.keyCode || e.which,
                    el = $(this);
                if (key == 38) { // up
                    counter_controller.right(el.parent().parent().find('.arrow.right'));
                } else if (key == 40) { // down
                    counter_controller.left(el.parent().parent().find('.arrow.left'));
                } else {
                    counter_controller.change(el);
                }
                return false;
            },
            change : function(e){
                var input = $(e).parent().find('input'),
                    value = input.val(),
                    min = input.data('min-value') || 0;
                max = input.data('max-value') || 1;
                if(!parseInt(value)) value = 1;
                if(value >= max) {
                    value = max;
                    $(e).parent().parent().find('.arrow.right').addClass('right-disabled')
                }
                if(value <= min) {
                    value = min;
                    $(e).parent().parent().find('.arrow.left').addClass('left-disabled')
                }
                if(value > min){
                    $(e).parent().parent().find('.arrow.left').removeClass('left-disabled')
                }
                if(value < max){
                    $(e).parent().parent().find('.arrow.right').removeClass('right-disabled')
                }
                input.val(value);
                input.trigger('update_value', input);
                return false;
            }
        }
    </script>
    <form style="display: none; position: absolute; z-index: 9999;" id="cart_amount_sell_form">
        <div id="cart_amount_sell_div">
            <div class="popup_global_container">
                <div class="popup-top-left">
                    <div class="popup-top-right">
                        <div class="popup-top-center">

                            <div class="popup_global_title" id="action_title_sell">Продажа предмета</div>

                        </div>
                    </div>
                    <div class="popup_global_close_btn" onclick="ask_amount_sell();"></div>
                </div>

                <div class="popup-left-center">
                    <div class="popup-right-center">

                        <div class="popup_global_content" style="padding: 20px;">
                            <div id="action_ask_confirm_sell" class="redd" style="text-align: center;"></div>
                            <span id="action_description_sell"></span>
                            <div class="popup-artifact">
                                <div class="popup-artifact__dsc">
                                    <span id="art_sell" class="popup-artifact__img"></span>
                                    <div id="ask_confirm_title_sell" class="popup-artifact__title popup-artifact__left" style="color: rgb(255, 0, 0);"></div>
                                    <div><img src="images/tbl-shp_item-icon.gif" width="11" height="10" class="tbl-shp_item-ico" alt=""> <span id="ask_confirm_type_sell"></span></div>
                                    <div><img src="images/tbl-shp_level-icon.gif" width="11" height="10" class="tbl-shp_item-ico" alt=""> Уровень <span id="ask_confirm_level_sell">0</span></div>
                                    <div id="price_container_sell" style="display: none">Цена: <span id="price_sell"></span></div>
                                </div>
                            </div>

                            <div style="padding: 0 11px;">
                                <div id="cart_amount_sell_price" class="cart-amount-sell-price" style="text-align: left;">
                                    <div>
                                        <b>Количество:</b>
                                        <span class="cart-amount-input-cont">
											<span class="b-input">
												<span class="b-input__inner">
													<span class="arrow left left-disabled" onclick="counter_controller.left(this);" title="Уменьшить кол-во"></span>
													<span class="arrow right" onclick="counter_controller.right(this);" title="Увеличить кол-во"></span>
														<input id="cart_amount_sell" type="text" value="1" class="cart_amount_sell_input" onchange="counter_controller.change(this, event);" autocomplete="off" onkeypress="counter_controller.keypress(this, event);">
												</span>
											</span>
										</span>
                                        из <span id="cart_amount_sell_cnt">3</span>
                                        <b class="butt1 pointer"><b><input value="Все" type="button" style="width: 100px;" class="redd" id="cart_amount_sell_all"></b></b>									</div>

                                    <div>
                                        <b>Цена:</b> <span class="current_price"></span><span class="error" style="display: none;"></span>
                                    </div>
                                </div>

                                <div style="clear: both; margin-top: 10px; text-align: left;">
                                    <b class="butt1 pointer"><b><input value="Продать" type="submit" onclick="ask_amount_sell();" style="width: 100px;" class="redd"></b></b>									<b class="butt1 pointer"><b><input value="Отмена" type="button" onclick="ask_amount_sell();" style="width: 100px;" class="redd"></b></b>								</div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="popup-left-bottom">
                    <div class="popup-right-bottom">
                        <div class="popup-bottom-center"></div>
                    </div>
                </div>

            </div>
        </div>
    </form>
    <script>
        /**
         * this function is full copy ask_amount_sell, but then remove all code related with price
         * @param func        callback function
         * @param maxQuantity max quantity
         * @param params      additional params
         *
         * @return void
         */
        function ask_amount_version2(func, maxQuantity, params) {
            var div = gebi('cart_amount_version2_div');
            if(!func) {
                div.style.display = 'none';
                frame_content_hider.hide();
                return;
            }

            var cart_amount = gebi('cart_amount_version2');
            var cart_amount_form = gebi('cart_amount_version2_form');
            var onsubmit = function() {
                onsubmit = function() {};
                cart_amount_form.onsubmit = function() {return false;};
                func(gebi('cart_amount_version2').value);
                return false;
            };

            var amount_block = gebi('cart_amount_version2_price');
            var e = $(amount_block);
            var err = e.find('.error');
            var t = e.find('.lang_desc');

            var action = '';
            var artifact_color = '';
            if (params) {
                var title = params.title;
                action = params.action;
                var artifact_id = params.artifact_id;
                if (artifact_id) {
                    artifact_color = artifact_get_color(artifact_id);
                }
            }

            var onkeyup = function () {
                e.find('span').hide();
                checkQuantity(parseInt(this.value));
            };

            var checkQuantity = function(count) {
                if (count <= 0 || (maxQuantity && maxQuantity < count)) {
                    var err_msg = $('<b/>').addClass('redd').text('Укажите корректное количество');
                    err.html(err_msg).show();
                }
                t.show();
            };

            cart_amount.value = 1;
            cart_amount.onkeyup = counter_controller.keypress;
            $(cart_amount).off('update_value').on('update_value', onkeyup);
            counter_controller.change(cart_amount);

            $(document).unbind('keyup');
            $(document).on('keyup', function(e) {
                if (e.keyCode == 13) { // Enter
                    $(this).unbind('keyup');
                    onsubmit();
                } else if (e.keyCode == 27) { // Esc
                    $(this).unbind('keyup');
                    ask_amount();
                }
            });

            cart_amount_form.onsubmit = onsubmit;
            gebi('cart_amount_version2_all').onclick = function() {
                this.onclick = function() {};
                func(maxQuantity || 1);
                ask_amount_version2();
            };

            var all_button = $('#cart_amount_version2_all');
            all_button.removeClass('no-marg-right');

            div.style.display = 'block';
            div.style.top = document.body.scrollTop + (document.body.clientHeight - div.offsetHeight)/2-100;
            div.style.left = document.body.scrollLeft + (document.body.clientWidth - div.offsetWidth)/2;
            title = '<span style="'+(artifact_color ? 'color:' + artifact_color + ';' : '') + '">' + (title || '') + '</span>';
            if (ask_confirm_title_actions[action]) {
                title = ask_confirm_title_actions[action]['title'] + '<br />' + title;
            }

            $('#ask_amount_version2_title').html(title);
            frame_content_hider.show();
        }
    </script>
    <form id="cart_amount_version2_form">
        <div class="p2v brd2-all bg_l cart-amount-sell-div" id="cart_amount_version2_div" style="padding: 10px 15px;">
            <div class="cart-amount-sell-name"><b id="ask_amount_version2_title"></b></div>
            <div id="cart_amount_version2_price" class="cart-amount-sell-price">
                Введите количество:				<input id="cart_amount_version2" type="text" value="1" class="tbl-shp_item-input-price">
                <span class="lang_desc"></span>
                <span class="error"></span>
            </div>
            <div style="text-align: center;">
                <b class="butt2 pointer"><b><input value="Ok" type="submit" onclick="ask_amount_sell();" style="width:40px"></b></b>				<b class="butt2 all_items pointer"><b><input value="Все" type="button" id="cart_amount_version2_all"></b></b>				<b class="butt2 cart-amount-sell_cancel pointer"><b><input value="Отмена" type="button" onclick="ask_amount_version2();"></b></b>			</div>
        </div>
    </form>

    <script>

        var ask_confirm_title = null;
        var ask_confirm_title_actions = {
            "3":{
                "title":"ВЫБРОСИТЬ",
                "title_popup":"Удаление предмета"
            },
            "5":{
                "title":"ПРОДАТЬ",
                "price_block":"Цена продажи",
                "title_popup":"Продажа предмета"
            },
            "repair":{
                "title":"ПОЧИНИТЬ",
                "price_block":"Цена ремонта",
                "title_popup":"Починка предмета"
            },
            "perp":{
                "title":"ОСТАВИТЬ у себя НАВСЕГДА",
                "price_block":"Стоимость"
            },
            "perp_clan":{
                "title":"ОСТАВИТЬ у клана <a title=\"\" href=\"#\" onClick=\"showClanInfo(''); return false;\"><img src=\"/images/data/clans/\" border=0 width=13 height=13 align=\"absmiddle\"></a> <b></b> НАВСЕГДА",
                "price_block":"Стоимость"
            }
        };
        function ask_confirm(func, params) {
            ask_confirm_title = ask_confirm_title || $('#ask_confirm_title');

            var div = gebi('cart_confirm_div'), action_block = gebi('action_title');
            if(!func) {
                div.style.display = 'none';
                frame_content_hider.hide();
                return;
            }

            $(document).unbind('keyup');
            $(document).on('keyup', function(e) {
                if (e.keyCode == 13) { // Enter
                    $(this).unbind('keyup');
                    $('#cart_confirm_ok').trigger('click');
                } else if (e.keyCode == 27) { // Esc
                    $(this).unbind('keyup');
                    ask_confirm();
                }
            });

            var btn_name = '';

            if(params.action == 'drop') {
                $('#ask_confirm_ok_container_btn').html('<b class="butt1 pointer"><b><input value="Выбросить" type="submit" onClick="if(document._submit)return false;document._submit=true;" class="redd" ID="cart_confirm_ok" style="width: 100px;"></b></b>');
            } else if (params.action == 'sell') {
                btn_name = 'Продать';
                $('#ask_confirm_ok_container_btn').html('<b class="butt1 pointer"><b><input value="Продать" type="submit" onClick="if(document._submit)return false;document._submit=true;" class="redd" ID="cart_confirm_ok" style="width: 100px;"></b></b>');
            } else {
                $('#ask_confirm_ok_container_btn').html('<b class="butt1 pointer"><b><input value="Ok" type="submit" onClick="if(document._submit)return false;document._submit=true;" class="redd" ID="cart_confirm_ok" style="width: 100px;"></b></b>');
            }

            var cart_confirm_ok = gebi('cart_confirm_ok');
            cart_confirm_ok.onclick = function() {
                this.onclick = function() {};
                func();
            };

            var action_price = gebi('action_price');
            action_price.style.display = 'none';

            var action_description = gebi('action_description');
            action_description.style.display = 'none';

            if (params) {
                var title = params.title || '';
                var action_title = params.action || {};
                var prices = params.prices || {};
                var artifact_id = params.artifact_id || null;
                var description = params.description || null;

                if (artifact_id) {
                    var artifact_alt = get_art_alt('AA_' + artifact_id);
                    var artifact_color = artifact_alt.color;
                }

                var action = action_title ? ask_confirm_title_actions[action_title] : {};

                if (params.action != 'rent' && params.action != 'harvest_cancel') {
                    var price = artifact_alt.price ? artifact_alt.price : 0;
                }


                if (prices && action!=undefined && action.price_block) {
                    var money = prices.money ? prices.money : 0,
                        money_silver = prices.money_silver ? prices.money_silver : 0,
                        money_gold = prices.money_gold ? prices.money_gold : 0;

                    action_price.children[0].innerHTML = action.price_block;
                    action_price.children[1].innerHTML = html_money_str(1, money, money_silver, money_gold);
                    action_price.style.display = '';
                }

                if (description) {
                    action_description.innerHTML = description;
                    action_description.style.display = '';
                }

                var art = gebi('art');
                var action_ask_confirm = gebi('action_ask_confirm');

                if (params.action != 'rent' && params.action != 'harvest_cancel') {
                    if (artifact_alt.enchant_icon) {
                        art.innerHTML = '<img src="' + (artifact_alt.picture != undefined ? artifact_alt.picture : artifact_alt.image) + '">' + artifact_alt.enchant_icon;
                    } else {
                        art.innerHTML = '<img src="' + (artifact_alt.picture != undefined ? artifact_alt.picture : artifact_alt.image) + '">';
                    }
                } else {
                    gebi('popup_artifact').style.display = 'none';
                }

                var action_titl = '';
                try{ action_titl = action.title; }catch (e) {}
                action_ask_confirm.innerHTML = (params.question) ? params.question : ('Вы уверены, что хотите ' + action_titl + '?');
                action_block.innerHTML = ask_confirm_title_actions[params.action]['title_popup'] ? ask_confirm_title_actions[params.action]['title_popup'] : action_titl;
            }

            div.style.display = 'block';
            div.style.top = document.body.scrollTop + (document.body.clientHeight - div.offsetHeight)/2;
            div.style.left = document.body.scrollLeft + (document.body.clientWidth - div.offsetWidth)/2;

            if (title) ask_confirm_title.text(title);
            if (artifact_alt) $('#ask_confirm_type').text(artifact_alt.kind);
            if (artifact_alt && artifact_alt.lev) $('#ask_confirm_level').text(artifact_alt.lev.value);
            if (artifact_alt && artifact_alt.dur) {
                if (artifact_alt.flags2 && artifact_alt.flags2.crashproof) {
                    $('#ask_confirm_durability_amount').html('<span class="red">' + artifact_alt.flags2.crashproof + '</span>');
                } else {
                    $('#ask_confirm_durability_amount').html('<span class="red">' + artifact_alt.dur + '</span>/' + artifact_alt.dur_max);
                }
                $('#sellAskDurability').show();
            } else {
                $('#sellAskDurability').hide();
            }
            if (price) {
                //$('#price_container').show();
                $('#price').html(artifact_alt.price);
            }
            if (params.message) $('#ask_confirm_ms').html(params.message);
            if (artifact_color) ask_confirm_title.get(0).style.color = artifact_color;
            frame_content_hider.show();
            cart_confirm_ok.focus();
        }
    </script>
    <div style="display: none" id="cart_confirm_div">
        <div class="popup_global_container">
            <div class="popup-top-left">
                <div class="popup-top-right">
                    <div class="popup-top-center">

                        <div class="popup_global_title" id="action_title"></div>

                    </div>
                </div>
                <div class="popup_global_close_btn" onclick="ask_confirm();"></div>
            </div>

            <div class="popup-left-center">
                <div class="popup-right-center">

                    <div class="popup_global_content" style="padding: 20px;">
                        <div id="action_ask_confirm" class="redd" style="text-align: center;">Вы уверены, что хотите ПРОДАТЬ?</div>
                        <span id="action_description"></span>
                        <div class="popup-artifact" id="popup_artifact">
                            <div class="popup-artifact__dsc">
                                <span id="art" class="popup-artifact__img"></span>
                                <div id="ask_confirm_title" class="popup-artifact__title popup-artifact__left"></div>
                                <div><img src="images/tbl-shp_item-icon.gif" width="11" height="10" class="tbl-shp_item-ico" alt=""> <span id="ask_confirm_type"></span></div>
                                <div><img src="images/tbl-shp_level-icon.gif" width="11" height="10" class="tbl-shp_item-ico" alt=""> Уровень <span id="ask_confirm_level"></span></div>
                                <div id="sellAskDurability" style=""><img src="images/tbl-shp_item-iznos.gif" width="11" height="10" class="tbl-shp_item-ico" alt=""> Прочность <span id="ask_confirm_durability_amount"></span></div>
                                <div id="price_container" style="display: none">Цена: <span id="price"></span></div>
                            </div>
                        </div>
                        <div id="action_price" class="popup-artifact__action-price"><b></b> <span></span></div>
                        <div style="clear: both; margin-top: 10px; text-align: center;">
                            <span id="ask_confirm_ok_container_btn"></span>
                            <b class="butt1 pointer"><b><input value="Отмена" type="button" onclick="ask_confirm();" class="redd" style="width: 100px;"></b></b>						</div>
                    </div>

                </div>
            </div>

            <div class="popup-left-bottom">
                <div class="popup-right-bottom">
                    <div class="popup-bottom-center"></div>
                </div>
            </div>

        </div>
    </div>
    <?
}

function tpl_artifact_sell_packop(){
    global $money_type_info;
    ?>
    <script>
        var curr_pix2 = {
            <? $type = $money_type_info[MONEY_TYPE_GAME];?>
            '<?=MONEY_TYPE_GAME?>': {pic1: '<?=$type['picture1']?>', pic2: '<?=$type['picture2']?>', pic3: '<?=$type['picture3']?>', url: '<?=isset($type['url']) ? $type['url'] : '';?>',alt:''},
            '':''
        };
        function js_money_str_sell(money_type, amount) {
            var money_info = curr_pix2[money_type];
            var str = ' ';
            if (!amount)
                return str;
            var t = [];
            amount = Math.floor(amount * 100);
            for (i = 0; i < 2; i++) {
                t[i] = (amount % 100);
                amount = (amount - t[i]) / 100;
            }
            t[2] = amount;
            for (i = 2; i >= 0; i--) {
                if (t[i].toFixed(0) <= 0) continue;
                str += '<img src="images/'+money_info['pic'+(i+1)]+'">'+'&nbsp;'+t[i].toFixed(0)+'&nbsp;';
            }
            return str;
        }

        function ask_amount_sell(func, mx){
            var div = gebi('cart_amount_sell_div');
            if(!func) {
                div.style.display = 'none';
                return;
            }
            gebi('cart_amount_sell_form').onsubmit = function() {
                func(gebi('cart_amount_sell').value);
                return false
            }
            gebi('cart_amount_sell_all').onclick = function() {
                ask_amount_sell_all();
            }
            div.style.display = 'block';
            div.style.top = document.body.scrollTop + (document.body.clientHeight - div.offsetHeight)/2-100;
            div.style.left = document.body.scrollLeft + (document.body.clientWidth - div.offsetWidth)/2;
            gebi('cart_amount_sell').focus();
        }
        function recalculate_sell_price() {
            var sell_cost = parseFloat(gebi('sell_cost').value);
            var cnt = parseInt(gebi('cart_amount_sell').value);
            var sell_cnt = parseInt(gebi('sell_cnt').value);
            if(cnt > sell_cnt){
                cnt = sell_cnt;
            }
            if(cnt <= 0){
                cnt = 1;
            }
            gebi('cart_amount_sell').value = cnt;
            gebi('cart_amount_price').innerHTML = js_money_str_sell(<?=MONEY_TYPE_GAME?>, (sell_cost * cnt));
        }
        function ask_amount_sell_all(){
            var sell_cnt = parseInt(gebi('sell_cnt').value);
            gebi('cart_amount_sell').value = sell_cnt;
            recalculate_sell_price();
        }
    </script>
    <input type="hidden" id="sell_cost" value="30">
    <input type="hidden" id="sell_cnt" value="1">
    <form id="cart_amount_sell_form">
        <table class="p2v brd2-all bg_l" id="cart_amount_sell_div" style="z-index: 1000;position: absolute; display: none;border-color: #660000" cellpadding="3" cellspacing="0" border="0">
            <tr>
                <td colspan="2" class="redd" align="center"><b><?=common_java_escape('Введите количество:');?></b></td>
                <td><div style="float: right;"><?=html_button('X','butt2','button','',array('add' => 'style="width:17px" onclick="ask_amount_sell()"'));?></div></td>
            </tr>
            <tr>
                <td align="right"><input id="cart_amount_sell" type="text" oninput="recalculate_sell_price();" onchange="recalculate_sell_price();" value="1" style="width:34px; padding-left: 8px" class="tbl-shp_item-input-price"></td>
                <td align="right" ><?=html_button('OK','butt2','submit','',array('add' => 'id="button_ok" style="width:40px" onclick="ask_amount_sell();"'));?></td>
                <td align="left" ><?=html_button(common_java_escape('ВСЕ'),'butt2','button','',array('add' => 'style="width:40px" id="cart_amount_sell_all"'));?></td>
            </tr>
            <tr>
                <td colspan="3" class="redd" align="left"><b><?=common_java_escape('Стоимость:');?></b> <span id="cart_amount_price"></span></td>
            </tr>
        </table>
    </form>

    <script>
        function ask_confirm_sell(func){
            var div = gebi('cart_confirm_sell_div')
            if(!func) {
                div.style.display = 'none'
                return
            }
            gebi('cart_confirm_sell_ok').onclick = function() {
                func();
            }

            div.style.display = 'block'
            div.style.top = document.body.scrollTop + (document.body.clientHeight - div.offsetHeight)/2-100
            div.style.left = document.body.scrollLeft + (document.body.clientWidth - div.offsetWidth)/2
            gebi('cart_confirm_sell_ok').focus();
        }
    </script>
    <table class="p2v brd2-all bg_l" ID='cart_confirm_sell_div' style="z-index: 1000;position: absolute; display: none; border-color: #660000" cellpadding="3" cellspacing="0" border="0">
        <tr>
            <td colspan="2" id="ask_confirm_sell_ms" width="300" align="center"><b><?=common_java_escape('Вы уверены что хотите выбросить предмет?');?></b> </td>
        <tr>
        <tr>
            <td align="right" width="50%"><?=html_button('OK','butt2','submit','',array('add' => ' style="width:50px" ID="cart_confirm_sell_ok"'));?></td>
            <td align="left" width="50%"><?=html_button(common_java_escape('ОТМЕНА'),'butt2','button','',array('add' => 'style="width:60px" onclick="ask_confirm_sell()"'));?></td>
        </tr>
    </table>

    <?
}

function tpl_ask_money_form(){
?>
<script>
function ask_money_amount(func, mx){
	var div = gebi('money_amount_div')
	if(!func) {
		div.style.display = 'none'
		return
	}
	gebi('money_amount_form').onsubmit = function() {
		value = js_money_input_assemble('money_amount');
		func(value);
		return false;
	}
	js_money_input_fill('money_amount',mx);
	div.style.display = 'block'
	status = document.body.scrollHeight;
	div.style.top = document.body.scrollTop + (document.body.clientHeight - div.offsetHeight)/2
	div.style.left = document.body.scrollLeft + (document.body.clientWidth - div.offsetWidth)/2
	gebi('money_amount3').focus()
}
</script>
<form ID="money_amount_form">
<table class="p2v brd2-all bg_l" ID='money_amount_div' style="z-index: 1000;position: absolute; display: none;border-color: #660000" cellpadding="3" cellspacing="0" border="0">
<tr><td colspan="3" class="redd" align="left"><b><?='Введите цену:';?></b><img width="85" height=1 src="images/d.gif">&nbsp;&nbsp;&nbsp;<?=html_button('X','butt2','button','',array('add' => 'style="width:7px" onclick="ask_money_amount()"'));?></td></tr>
<tr>
<td><?=html_money_input_print(1,'money_amount',array('add_class' => 'tbl-shp_item-input-price', 'add_style3'=>'text-align:center;width:31px;', 'add_style'=>'text-align:center;width:31px;'))?></td>
<td align="center"><?=html_button('OK','butt2','submit','',array('add' => 'style="width:40px"'));?></td>
</tr>
</table>
</form>
<?
}

function tpl_ask_money_form_v2($title_btn = 'Ок'){
    ?>
    <script>
        function ask_money_amount(func, mx, money_type){
            if(!money_type || money_type == undefined) money_type = 1;
            $('.ask_money_h').hide();
            $('#ask_money_' + money_type).show();
            if(money_type == 1){
                var div = gebi('money_amount_div');
                if(!func) {
                    div.style.display = 'none';
                    return
                }
                gebi('money_amount_form').onsubmit = function() {
                    value = js_money_input_assemble('money_amount');
                    func(value);
                    return false;
                };
                js_money_input_fill('money_amount',mx);
                div.style.display = 'block';
                status = document.body.scrollHeight;
                div.style.top = document.body.scrollTop + (document.body.clientHeight - div.offsetHeight)/2;
                div.style.left = document.body.scrollLeft + (document.body.clientWidth - div.offsetWidth)/2;
                gebi('money_amount3').focus()
            }else{
                var div = gebi('money_amount_div');
                if(!func) {
                    div.style.display = 'none';
                    return
                }
                gebi('money_amount_form').onsubmit = function() {
                    value = gebi('money_amount').value;
                    func(value);
                    return false;
                };
                gebi('money_amount').value = mx;
                div.style.display = 'block';
                status = document.body.scrollHeight;
                div.style.top = document.body.scrollTop + (document.body.clientHeight - div.offsetHeight)/2;
                div.style.left = document.body.scrollLeft + (document.body.clientWidth - div.offsetWidth)/2;
                gebi('money_amount').focus()
            }
        }
    </script>
    <form id="money_amount_form">
        <table id="money_amount_div" style="z-index: 1000; position: absolute; display: none;" cellpadding="3" cellspacing="0" border="0">
            <tbody><tr>
                <td>
                    <div class="popup_global_container">
                        <div class="popup-top-left">
                            <div class="popup-top-right">
                                <div class="popup-top-center">

                                    <div class="popup_global_title" id="action_title_amount">Повысить ставку</div>

                                </div>
                            </div>

                            <div class="popup_global_close_btn" onclick="ask_money_amount();"></div>
                        </div>

                        <div class="popup-left-center">
                            <div class="popup-right-center">

                                <div class="popup_global_content" style="padding: 20px; text-align: center;">
                                    <div class="ask_money_h" id="ask_money_1">
                                    <span class="b-input">
                                        <span class="b-input__inner">
                                            <input type="text" id="money_amount3" name="form[money_amount3]" value="0" style="text-align:center;width:31px;" maxlength="6" class="cart_amount_sell_input">
                                        </span></span>&nbsp;<img src="/images/m_game3.gif">&nbsp;
                                    <span class="b-input"><span class="b-input__inner">
                                            <input type="text" id="money_amount2" name="form[money_amount2]" value="1" style="text-align:center;width:31px;" maxlength="2" class="cart_amount_sell_input"></span>
                                    </span>&nbsp;<img src="/images/m_game2.gif">
                                    <span class="b-input"><span class="b-input__inner"><input type="text" id="money_amount1" name="form[money_amount1]" value="0" style="text-align:center;width:31px;" maxlength="2" class="cart_amount_sell_input"></span></span>&nbsp;
                                    <img src="/images/m_game1.gif">
                                        </div>
                                <div class="ask_money_h" id="ask_money_2"><span class="b-input">
                                        <span class="b-input__inner"><input type="text" id="money_amount" name="form[money_amount]" value="0" style="text-align:center;width:31px;" maxlength="6" class="cart_amount_sell_input">
                                        </span></span><img src="/images/m_rub.gif">&nbsp;
                                </div>
                                    <br><br>
                                    <div style="text-align: center;">
                                        <b class="butt1 pointer"><b><input value="<?=$title_btn;?>" type="submit" onclick="if(document._submit)return false;document._submit=true;" style="width: 100px;"></b></b>										<b class="butt1 pointer"><b><input value="Отмена" type="button" onclick="ask_money_amount();" style="width: 100px;"></b></b>									</div>

                             </div>
                        </div>

                        <div class="popup-left-bottom">
                            <div class="popup-right-bottom">
                                <div class="popup-bottom-center"></div>
                            </div>
                        </div>

                    </div>
                </td>
            </tr>
            </tbody></table>
    </form>
    <?
}

/*
Альты для новостей на инфопортале
[[artXXX]] - картинка с альтом
[[artXXX_YY]] - картинка с альтом и количеством YY
[[artXXXtext]] - текстовая ссылка с названием
[[artXXXctext]] - текстовая ссылка с названием и цветом предмета
[[artXXXalt]] - альт для текстовых ссылок
[[artXXXicon]] - картинка без альта
*/
function tpl_index_alt($text='') {
	if (!$text) return false;

	preg_match_all('/\[\[art(\d*)(?:_(\d*))?\s*\]\]/', $text, $matches);
	if (is_array($matches[1]) && count($matches[1])) {
		$artikuls = make_hash(artifact_artikul_list(array('id' => $matches[1])));
		foreach ($artikuls as $artikul_id => $artikul) {
			$artikuls[$artikul_id]['object_class'] = OBJECT_CLASS_ARTIFACT_ARTIKUL;
		}
		tpl_artifact_alt_prepare($artikuls);
		$alts = '';
		foreach ($matches[1] as $k => $artikul_id) {
			$s = '';
			$artikul = $artikuls[$artikul_id];
			if ($artikul) {
				ob_start();
				tpl_artifact_alt($artikul);
				$alts .= ob_get_clean();
				ob_start();
				$cnt = intval($matches[2][$k]);
?><span style="position: relative; display: -moz-inline-stack; display: inline-block; vertical-align: middle; margin: 2px 1px; zoom: 1;">
	<img src="<?=PATH_IMAGE_ARTIFACTS.$artikul['picture'];?>" onMouseOver="artifactAlt(this,event,2)" onMouseOut="artifactAlt(this,event,0)" div_id="AA_<?=$artikul['id'];?>" onClick="showArtifactInfo(0, <?=$artikul['id'];?>); return false;" style="cursor: pointer;position:relative;z-index:1;" title="<?=$artikul['title'];?>">
	<?	if ($cnt > 1) { ?>
	<span style="position:absolute;left:2px;bottom:2px;_bottom:4px;z-index:3;width:31px;padding:2px;background:#6E534C;font-weight:bold;color:#F6D9A6;text-align:center;"><?=$cnt;?></span>
	<?	} ?>
</span><?
				$s = ob_get_clean();
			}
			$text = str_replace($matches[0][$k], $s, $text);
		}
		$text .= $alts;
	}
	return $text;
}

function tpl_artifact_print($artifact = array(), $param=array()){
    global $enchant_pics, $socket_pics; ?>
    <?if($param['li']){?>
    <li id="<?='AA_'.$artifact['id'];?>"
        aid="art_<?=$artifact['id']?>"
        sn="<?=$artifact['slot_num']?>"
        ord="<?=$artifact['slot_num']?>"
        data-id="<?=$artifact['id']?>"
        data-artikul-id="<?=$artifact['artikul_id']?>"
        data-dateti="<?=$artifact['id']?>"
        data-quality="<?=$artifact['quality']?>"
        data-kind="<?=$artifact['kind']?>"
        data-ttl="<?=$artifact['time_expire'] - time_current()?>"
        data-title="<?=$artifact['title']?>"
        data-noweight="<?=$artifact['weight']?>"
        data-cnt="<?=$artifact['cnt']?>"
        <?=($param['with_picture'] ? 'data-picture="'.$artifact['picture'].'"' : '');?>
        <?=($param['add_li'] ? $param['add_li'] : '')?> style="opacity: 1;">
    <?}?>

    <?//Ченджер

    //Можно ли надевать оправу?
    if($artifact['type_id'] == ARTIFACT_TYPE_ID_OPRAVA || $artifact['type_id'] == ARTIFACT_TYPE_ID_CHAR){
        $param['act1'] = 1;
        $param['act3'] = 0;
    }
//style="border-radius: 12px;"
    if(!$param['object_class']) $param['object_class'] = $artifact['object_class'];
    if($param['np']['cnt']) $artifact['cnt'] = $param['np']['cnt'];
    ?>

    <?=($param['add_html'] ? $param['add_html'] : '');?>

    <table    <?=$param['add_table'];?>  class="item pctntr <?=$param['add_class'];?>" data-id="<?=$artifact['id']?>"  width="60" height="60" cellpadding="0" cellspacing="0" border="0" <?=($param['style'] ? $param['style'] : 'style="float: left; margin: 1px"');?> background="/<?=PATH_IMAGE_ARTIFACTS.$artifact['picture'];?>">
        <tr>
            <td act1="<?=intval($param['act1']);?>"
                act2="<?=intval($param['act2']);?>"
                act3="<?=intval($param['act3']);?>"
                rune_h="0"
                style="position: relative;<?=$param['add_td_style'];?>"
                <?=($param['object_class'] == OBJECT_CLASS_ARTIFACT ? 'aid="'.$artifact['id'].'"' : '');?>
                <?=($param['store'] ? 'store="1"' : '');?>
                art_id="<?=($param['object_class'] == OBJECT_CLASS_ARTIFACT_ARTIKUL ? $artifact['id'] : ($param['store'] ? ($artifact['artikul_id'] ? $artifact['artikul_id'] : $artifact['id']) : ''));?>"
                cnt="<?=$artifact['cnt']?>"
                div_id="<?='AA_'.$artifact['id'];?>"
                <?=($param['psell'] ? 'sell_price="'.$param['psell'].'" psell="'.$param['psell'].'"' : '');?>
                <?if(!$param['no_alt'] && !$param['alt_simple']){?>onmouseover="artifactAlt(this,event,2)" onmouseout="artifactAlt(this,event,0)"<?}?>
                <?if($param['alt_simple']){?>onmouseover="artifactAltSimple(<?=$artifact['id'];?>,2,event)" onmouseout="artifactAltSimple(<?=$artifact['id'];?>,0,event)"<?}?>
                <?if($param['title']){?>title="<?=$artifact['title']?>"<?}?>
                valign="bottom"
                <?=($param['add_td_act'] ? $param['add_td_act'] : '')?>
                <?=($artifact['flags'] & ARTIFACT_FLAG_BOE) ? ' puton_confirm="1"' : ''; ?>>
                <?=($param['add_af_td_top'] ? $param['add_af_td_top'] : '')?>
                <?
                $artifact_socket = artifact_socket_get($artifact);
                if ($artifact['cnt'] > 1) {?>
                    <div class="bpdig" <?=($param['add_bcnt_style'] ? $param['add_bcnt_style'] : '')?>>
                        <?=$artifact['cnt'];?>
                    </div>
                <?}
                elseif ($artifact['enchant_id'] || $artifact['oprava_id']) {
                    if($artifact['enchant_id'] && !$artifact['enchant']){
                        $artifact['enchant'] = artifact_artikul_get($artifact['enchant_id']);
                    }
                    $enchant_level = $artifact['enchant']['aparam1'];
                    if(!$enchant_level) $enchant_level = $artifact['enchant']['param1'];
                    ?><span class="enchants"><?

                    if($artifact['flags'] & ARTIFACT_FLAG_ARMOR_STYLE && $artifact['enchant']){
                        ?><img src="<?=PATH_IMAGE_ENCHANTS.'enchant4_0.png';?>" alt="" class="enchant_png"><?
                    }else {
                        if (isset($enchant_pics[$artifact['enchant']['quality']][$enchant_level])) {?>
                            <img src="<?= PATH_IMAGE_ENCHANTS . $enchant_pics[$artifact['enchant']['quality']][$enchant_level] ?>" alt="" class="enchant_png">
                        <?}}

                    if ($artifact['oprava_id']) {?>
                        <img src="<?=PATH_IMAGE_ENCHANTS.'oprava.png'?>" alt="" class="enchant2_png">
                    <?}
                    ?></span><?
                }
                else {?>&nbsp;<?}
                for($i = 1; $i <= $artifact_socket['cnt']; $i++){
                    ?><div class="rune_<?=$artifact['id'];?> rune_png rune_<?=$i?>"><img src="<?=PATH_IMAGE_GEMS.$artifact_socket['socket_pic'][$i];?>" alt=""></div><?
                }
                ?>

                <?if($artifact['flags2'] & ARTIFACT_FLAG2_RUNIC_FRAME){?>
                    <div class="art_pulse"></div>
                <?}?>

                <?=($param['add_af_td_bottom'] ? $param['add_af_td_bottom'] : '')?>
            </td>
        </tr>
    </table>
    <?if($param['li']){?>
        </li>
    <?}?>
<?}

function tpl_artifact_info($artifact = array(), $cnt = false, $param = array()){
    if(!$artifact) return '';
    ob_start();
    ?><a <?=$param['add'];?> class="artifact_info b macros_artifact_quality<?=intval($artifact['quality']);?>" <?if($param['alt']){?>art_id="<?=$artifact['id'];?>" div_id="<?='AA_'.$artifact['id'];?>" onmouseover="artifactAlt(this,event,2)" onmouseout="artifactAlt(this,event,0)"<?}?> href="/artifact_info.php?id=<?=$artifact['id'];?>" onclick="showArtifactInfo(<?=$artifact['id'];?>);return false;"><?=($param['title'] ? $param['title'] : $artifact['title']);?></a><?=($cnt ? ' '.$cnt.' шт.' : '');?><?
    return ob_get_clean();
}

function tpl_artikul_info($artifact = array(), $cnt = false, $param = array()){
    if(!$artifact) return '';
    ob_start();
    if($param['small_img']){?><img width="20" height="20" src="/<?=PATH_IMAGE_ARTIFACTS.$artifact['picture'];?>">&nbsp;<?}?><a <?=$param['add'];?> class="artifact_info b macros_artifact_quality<?=intval($artifact['quality']);?>" <?if($param['alt']){?>art_id="<?=$artifact['id'];?>" div_id="<?='AA_'.$artifact['id'];?>" onmouseover="artifactAlt(this,event,2)" onmouseout="artifactAlt(this,event,0)"<?}?> href="/artifact_info.php?artikul_id=<?=$artifact['id'];?>" onclick="showArtifactInfo(false,<?=$artifact['id'];?>);return false;"><?=($param['title'] ? $param['title'] : $artifact['title']);?></a><?=($cnt ? ' '.$cnt.' шт.' : '');?><?
    return ob_get_clean();
}

function tpl_artikul_inline($artikul, $cnt = false, $param = array()) {
    global $quality_info;
    ob_start();
    ?>
    <b><a class="" href="/artifact_info.php?artikul_id=<?=$artikul['id'];?>" target="_blank" style="color:<?=$quality_info[$artikul['quality']]['color'];?>"
          div_id="AA_<?=$artikul['id'];?>" onmouseover="artifactAlt(this,event,2)"
          onmouseout="artifactAlt(this,event,0)" onclick="showArtifactInfo(false,<?=$artikul['id'];?>);return false;"><?=$param['add_title_prev'];?><?=$artikul['title'];?><?=($cnt && $cnt > 1 ? ' ('.$cnt.')' : '');?></a></b>
    <?php
    return ob_get_clean();
}