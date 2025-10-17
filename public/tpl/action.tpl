<? # $Id: action.tpl,v 1.86 2010-02-13 14:18:19 p.knoblokh Exp $

function tpl_action_form(&$object, &$action, $in=false) {
	global $money_type_info, $gag_type_info, $gag_reason_info, $crime_type_info, $session_user, $user_body_slots, $user_smol_slots, $user_weapon_slots, $injury_slot_info;
	global $class_info,$prof_paks,$profession_info;
	if (!$object['object_class'] || !$action) return false;
	//if (!$action['visible']) return false;
	$noconfirm = false;
	$action_param = array();
	$hidden_param = array(
		'object_class' => $action['object_class'],
		'object_id' => $action['object_id'],
		'action_id' => $action['id'],
	);

	$param_check = false;
	$hidden_param = array_merge($hidden_param,get_params($in,'sbug_id,area_id,artifact_id,bug_id,user_id,clan_id,url_success,url_error',true));
	$action_param = array_merge($hidden_param,get_params($in,'param_success,param_error',true));
	$id = $action['object_class'].'_'.$action['object_id'].'_'.$action['id'];
	if ($hidden_param['url_success']) $hidden_param['url_success'] .= '&default='.$id;
	if ($hidden_param['url_error']) $hidden_param['url_error'] .= '&default='.$id;

	if ($object['object_class'] == OBJECT_CLASS_ARTIFACT) {
		$hidden_param['artifact_id'] = $object['id'];
	}

	$draw_run = true;
	$content = false;
	if ($action['flags'] & ACTION_FLAG_CONFCODE) $content .= '<tr><td><img id="codeimg_'.$id.'" src="images/spacer.gif" width="70" height="30" align="absmiddle"></td><td><input name="in[confcode]" type="text" size="6" class="dbgl2 brd b small"></td></tr>';
	if ($action['flags'] & ACTION_FLAG_ONBEHALF) $content .= '<tr><td><b>'.translate('Ник цели:').'</b></td><td><input name="in[target_nick]" type="text" size="16" value="'.htmlspecialchars($in['target_nick']).'" class="dbgl2 brd b small"></td></tr>';
	switch ($action['code']) {
		default:
			if (!$content) {
		
				if ($in['empty_form']){
					if ($action['flags'] & ACTION_FLAG_NOCONFIRM) $noconfirm = true;
					$content = '<tr><td><b>'.sprintf(translate('Подтвердите выполнение действия &laquo;%s&raquo;'),$action['title']).'</b></td></tr>';
				}
				else return false;
			}
			break;

        case 'PET_FEED':
            NODE_SWITCH(null, $session_user['id']);
            $pet_list = pet_list(false, $session_user['id'], sql_pholder(' AND satiation < ?', PET_SATIATION_MAX));
            $pet_artikul_ids = array();
            foreach($pet_list as $pet) {
                $pet_artikul_ids[$pet['artikul_id']] = $pet['artikul_id'];
            }
            if($pet_artikul_ids) $pet_artikul_list = pet_artikuls_list(array('id' => $pet_artikul_ids));

            $pet_hash = array();
            foreach($pet_list as $pet) {
                $pet_hash[$pet['id']] = ($pet['title'] ? $pet['title'] : $pet_artikul_list[$pet['artikul_id']]['title']).' ['.pet_get_satiation($pet, true).'%]';
            }

            if(!$pet_hash) {
                $content .= '<tr><td><b>Нет голодных питомцев!</b></td></tr>';
            }else{
                $content .= '<tr><td><b>'.translate('Питомец:').'</b></td><td>'.html_select('in[pet_id]',$pet_hash,false,false,'class="dbgl2 b small"').'</td></tr>';
            }
            break;

		case 'PET_RENAME':
			$action_param['name'] = $in['name'];
			$action_param['ref'] = $in['ref'];

			$content .= '<input name="in[ref]" type="hidden" value="'.$in['ref'].'">';
            $content .= '<tr><td><b>'.translate('Имя питомца:').'</b></td><td><input name="in[name]" type="text" size="16" value="'.htmlspecialchars($in['name']).'" class="dbgl2 brd b small"></td></tr>';
			if (!$content) {
				if ($in['empty_form']) $content = '<tr><td>'.sprintf(translate('Подтвердите выполнение действия &laquo;%s&raquo;'),$action['title']).'</td></tr>';
				else return false;
			}
			break;
			
		case 'TELEPORT':
			// сдампим потом все арии в яваскрипт
			if (!in_array($object['object_class'],array(OBJECT_CLASS_USER, OBJECT_CLASS_ARTIFACT))) break;
			if ($action['param2']) {
				$area_hash = area_select(intval($action['param1']));
				$content .= '<tr><td colspan="2"><b>'.translate('Пункт назначения.').'</b></td></tr>'
							.'<tr><td>'.translate('Из списка').':</td><td>'.html_select('in[to_area_id]', $area_hash, $session_user['area_id'], false, 'class="dbgl2 b small" id="area"').'</td></tr>'
							.'<tr><td>'.translate('Поиск').':</td><td><input type="text" size="20" onkeyup="search_area(this.value);"></td></tr>'
							.'<tr><td colspan="2" id="links"></tr>'
							.tpl_search_function();
			} else {
				if ($in['empty_form']) {
					$content = '<tr><td>'.sprintf(translate('Подтвердите выполнение действия &laquo;%s&raquo;'),$action['title']).'</td></tr>';
				}
				else return false;
			}
			break;

		case 'GAG':		// действие "поставить кляп"
			if (!in_array($object['object_class'],array(OBJECT_CLASS_USER,OBJECT_CLASS_ARTIFACT))) break;
			$param = explode(',',$action['param2']);
			$content .= '<tr><td><b>'.translate('Ник:').'</b></td><td><input name="in[nick]" type="text" size="16" value="'.htmlspecialchars($in['nick']).'" class="dbgl2 brd b small"></td></tr>';
			if ($action['param1'] == 1) {		// Вид "длительность"
				list($gag_type,$gag_type_range) = $param;
				$content .= '<tr><td><b>'.translate('Длительность:').'</b></td><td>';
				if ($gag_type && !$gag_type_range) $content .= '<b>'.$gag_type_info[$gag_type]['title'].'</b>';
				else {
					if ($gag_type_range) {
						$hash = array();
						foreach ($gag_type_info as $item) if ($item['id'] <= $gag_type) $hash[$item['id']] = $item['title'];
					}
					else $hash = get_hash($gag_type_info);
					$content .= html_select('in[type]',$hash,false,false,'class="dbgl2 b small" style="width:103px"');
				}
				$content .= '</td></tr>';
			} elseif ($action['param1'] == 2) {		// Вид "причина"
				list($gag_reason_ext) = $param;
				$content .= '<tr><td><b>'.translate('Причина:').'</b></td><td>'.html_select('in[reason]',get_hash($gag_reason_info, 'id', 'title_time'),false,false,'class="dbgl2 b small"').'</td></tr>';
			}
			break;

		case 'UNGAG':	// действие "снять кляп"
			$content .= '<tr><td><b>'.translate('Ник:').'</b></td><td><input name="in[nick]" type="text" size="16" value="'.htmlspecialchars($in['nick']).'" class="dbgl2 brd b small"></td></tr>';
			break;
		case 'SKIN_BODY':
			$content .= '<b><b style="color:red;">Внимание!</b> В ваших руках оказался <b style="color:#ec00ca;">предмет моды</b>. <br>Он позволяет навсегда изменить внешний вид персонажа.</b><br><br>';
			break;
		case 'SKIN_HAIR':
			$content .= '<b><b style="color:red;">Внимание!</b> В ваших руках оказался <b style="color:#ec00ca;">предмет моды</b>. <br>Он позволяет навсегда изменить прическу персонажа.</b><br><br>';
			break;
		case 'GIFT':		// действие "подарить"
		case 'CLAN_GIFT':		// действие "подарить"
			if (!in_array($object['object_class'],array(OBJECT_CLASS_USER,OBJECT_CLASS_ARTIFACT))) break;
			if ($action['code'] == 'GIFT') $content .= '<tr><td><b>'.translate('Ник:').'</b></td><td><input name="in[nick]" type="text" size="16" value="'.htmlspecialchars($in['nick']).'" class="dbgl2 brd b small"></td></tr>';
			if ($action['code'] == 'CLAN_GIFT') $content .= '<tr><td><b>'.translate('Клан:').'</b></td><td><input name="in[clan]" type="text" size="16" value="'.htmlspecialchars($in['clan']).'" class="dbgl2 brd b small"></td></tr>';



			$user_gag = ($session_user['gag_time'] > time_current() ?  true : false); //TODO: Нельзя отправлять сообщение если есть мут на персе

            if ($object['object_class'] != OBJECT_CLASS_ARTIFACT) {
				NODE_PUSH(null, $object['id']);
				$artifact_hash = user_get_artifact_list($object['id'],'',sprintf(' AND (flags & %d)',ARTIFACT_FLAG_GIFT));
				artifact_artikul_get_title($artifact_hash);
				$artifact_hash = get_hash($artifact_hash);
				NODE_POP();
				
				$content .= '<tr><td><b>'.translate('Подарок:').'</b></td><td>'.html_select('artifact_id',$artifact_hash,-1,false,'class="dbgl2 b small"').'</td></tr>';
			}
			if($user_gag) $content .= '<tr><td colspan=2>Вы не можете написать поздравление, т.к на вас наложено проклятие молчания.</td></tr>';
            $content .= '<tr><td><b>'.translate('Поздравление:').'</b></td><td><textarea name="in[note]" cols=50 rows=6 '.($user_gag ? 'disabled="disabled" style="background-color: #b5b5b57d;"' : '').' class="dbgl2 brd b small lscroll">'.htmlspecialchars($in['note']).'</textarea></td></tr>';
			break;

		case 'ATTACK':		// действие "напасть"
			$real_actions = array('' => translate('Напасть'),'HELP' => translate('Помочь в бою'));
			$content .= '<tr><td><b>'.translate('Действие:').'</b></td><td>'.html_select('in[real_action]',$real_actions,'','','').'</td></tr>';
			$content .= '<tr><td><b>'.translate('Ник:').'</b></td><td><input name="in[nick]" type="text" size="16" value="'.htmlspecialchars($in['nick']).'" class="dbgl2 brd b small"></td></tr>';
			break;

		case 'TRADE':		// действие "торговать"
			$content .= '<tr><td><b>'.translate('Ник:').'</b></td><td><input name="in[nick]" type="text" size="16" value="'.htmlspecialchars($in['nick']).'" class="dbgl2 brd b small"></td></tr>';
			$action_param['param_success']['url_close'] = 'service_trade.php';
			$action_param['param_success']['main_frame'] = 1;
			break;

		case 'ARMOR_UP':
            if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
            $_artikul = artifact_artikul_get($object['artikul_id']);
			if (!$_artikul) break;
            
			$artifact_list = user_get_artifact_list($object['user_id'], '', sql_pholder(' AND kind_id=?', $_artikul['kind_id'])); // Выводим артикулы, которые совпадают по виду с жетоном
			if ($artifact_list) {
				$artikul_ids = get_hash($artifact_list, 'artikul_id', 'artikul_id');
				$artifact_artikul_hash = make_hash(artifact_artikul_list(array('id' => $artikul_ids), " AND slot_id <> ''", 'id, quality, trend'));

				foreach ($artifact_list as $k => $artifact) {
					if (!($artifact['flags2'] & ARTIFACT_FLAG2_LEGENDARY)) {
						unset($artifact_list[$k]); //Исключить из списка не легендарные шмотки
						}
					if (!isset($artifact_artikul_hash[$artifact['artikul_id']])) {
						unset($artifact_list[$k]);
						continue;
					} // исключаем неслотовые вещи
					if (!($artifact['param1'] > 0)) {
						unset($artifact_list[$k]);
						} //исключаем предметы без параметра на следующий лвл шмотки
					$artikul = $artifact_artikul_hash[$artifact['artikul_id']];
				
				}
			}
			if (!$artifact_list) {
                $content .= 'У Вас нет ни одного легендарного предмета, который можно улучшить!';
                $draw_run = false;
            } else {
				artifact_artikul_get_title($artifact_list);
				$artifact_hash = make_hash($artifact_list);
				$content .= '<tr><td><b>'.translate('Выберите Легендарный предмет:').'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'rSelect' => true)).'</td></tr>';
			}
			$content = '<tr><td align=center>'.$content.'</td></tr>';
            break;
			//Создание стилевых предметов.
		case 'MAKE_STYLE':
            if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
            $_artikul = artifact_artikul_get($object['artikul_id']);
			if (!$_artikul) break;
			$artifact_list = user_get_artifact_list($object['user_id']); // 
			if ($artifact_list) {
				$artikul_ids = get_hash($artifact_list, 'artikul_id', 'artikul_id');
				$artifact_artikul_hash = make_hash(artifact_artikul_list(array('id' => $artikul_ids), " AND slot_id <> ''", 'id'));

				foreach ($artifact_list as $k => $artifact) {
					if (!($artifact['flags2'] & ARTIFACT_FLAG2_MAKE_STYLE)) {
						unset($artifact_list[$k]); //Исключить из списка вещи без флага о возможности предмет сделать стилевым.
						}
					if (!isset($artifact_artikul_hash[$artifact['artikul_id']])) {
						unset($artifact_list[$k]);
						continue;
					} // исключаем неслотовые вещи
					if (!($artifact['param2'] > 0)) {
						unset($artifact_list[$k]);
						} //исключаем предметы без параметра на шмотку стиля
					$artikul = $artifact_artikul_hash[$artifact['artikul_id']];
				
				}
			}
			if (!$artifact_list) {
                $content .= '<b>У вас нет предметов, которые можно превратить в <b style="color:teal;">вещи стиля</b>.</b>';
                $draw_run = false;
            } else {
				artifact_artikul_get_title($artifact_list);
				$artifact_hash = make_hash($artifact_list);
				$content .= '<tr><td><b>'.translate('Выберите предмет для преобразования:').'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'rSelect' => true)).'</td></tr>';
			}
			$content = '<tr><td align=center>'.$content.'</td></tr>';
            break;
			
			
		case 'ENCHANT_UP':	// действие "усилить руну"
			if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
			// получим ограничения на применение улучшалки, хранящиеся в энчанте
			$enchant = $object['param1'] ? artifact_artikul_get($object['param1']) : false;
			if (!$enchant || !$enchant['kind_id']) break;
			$artifact_list = user_get_artifact_list($object['user_id'], '', sql_pholder(' AND kind_id=?', $enchant['kind_id']));
			if ($artifact_list) {
				$artikul_ids = get_hash($artifact_list, 'artikul_id', 'artikul_id');
				$artifact_artikul_hash = make_hash(artifact_artikul_list(array('id' => $artikul_ids), " AND slot_id <> ''", 'id, quality, trend'));

				/**
				 * получаем id всех встроенных рун
				 */
				$enchant_ids = get_hash($artifact_list, 'enchant_id', 'enchant_id');
				$enchant_artikul_hash = make_hash(artifact_artikul_list(array('id' => $enchant_ids)));

				foreach ($artifact_list as $k => $artifact) {
					// исключаем неслотовые вещи
					if (!isset($artifact_artikul_hash[$artifact['artikul_id']])) {
						unset($artifact_list[$k]);
						continue;
					}
					// исключаем вещи вез встроенных рун
					if ($artifact['enchant_id'] <= 0) {
						unset($artifact_list[$k]);
						continue;
					}
					$art_enchant = $enchant_artikul_hash[$artifact['enchant_id']];
					// проверяем, встроена ли руна и можно ли ее улучшить
					if (!(is_array($art_enchant) && ($art_enchant['param1'] > 0))) {
						unset($artifact_list[$k]);
						continue;
					}
					// для "не серых" вещей должен совпадать тип раскачки
					$artikul = $artifact_artikul_hash[$artifact['artikul_id']];
					if ($enchant['trend'] && $artikul['quality'] && ($artikul['trend'] != $enchant['trend'])) {
						unset($artifact_list[$k]); 
						continue;
					}
					if (!artifact_enchant_compatible($art_enchant, $object)) {
						unset($artifact_list[$k]); // не подходит по уровню
					}
				}
			}
			if (!$artifact_list) {
				$content .= translate('У Вас нет ни одного предмета, на который можно применить этот элемент!');
				$draw_run = false;
			} else {
				artifact_artikul_get_title($artifact_list);
				$artifact_hash = make_hash($artifact_list);
				$content .= '<tr><td><b>'.translate('Выберите предмет:').'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'rSelect' => true)).'</td></tr>';
			}
			$content = '<tr><td align=center>'.$content.'</td></tr>';
			break;
			
		case 'ENCHANT':	// действие "наложить руну"
			
	
			if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
			$enchant = $object['param1'] ? artifact_artikul_get($object['param1']) : false;
			if (!$enchant || !$enchant['kind_id']) break;
			$artifact_list = user_get_artifact_list($object['user_id'], '', sql_pholder(' AND kind_id=?', $enchant['kind_id']));
			if ($artifact_list) {
				$artikul_ids = get_hash($artifact_list, 'artikul_id', 'artikul_id');
				$artifact_artikul_hash = make_hash(artifact_artikul_list(array('id' => $artikul_ids), " AND slot_id <> ''", 'id, quality, trend'));

				foreach ($artifact_list as $k => $artifact) {
					
					if (!isset($artifact_artikul_hash[$artifact['artikul_id']])) unset($artifact_list[$k]); // исключаем неслотовые вещи
					$artikul = $artifact_artikul_hash[$artifact['artikul_id']];
					if ($enchant['trend'] && $artikul['quality'] && ($artikul['trend'] != $enchant['trend'])) {
						unset($artifact_list[$k]); // для "не серых" вещей должен совпадать тип раскачки
					}
					if (!artifact_enchant_compatible($artifact, $enchant)) {
						unset($artifact_list[$k]); // не подходит по уровню
					}	
					if ($artifact['flags2'] & ARTIFACT_FLAG2_LEGENDARY) {
						unset($artifact_list[$k]); //Исключить из списка шмотки с флагом легендарный предмет
						}
					
				}
			}
			if (!$artifact_list) {
				$content .= translate('У Вас нет ни одного предмета, на который можно применить эту руну!');
				$draw_run = false;
			} else {
				artifact_artikul_get_title($artifact_list);
				$artifact_hash = make_hash($artifact_list);
				$content .= '<tr><td><b>'.translate('Выберите предмет:').'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'rSelect' => true)).'</td></tr>';
			}
			$content = '<tr><td align=center>'.$content.'</td></tr>';
			break;
			
			case 'ENCHANT_LEGEND':	// действие "наложить руну"
			if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
			$enchant = $object['param1'] ? artifact_artikul_get($object['param1']) : false;
			if (!$enchant || !$enchant['kind_id']) break;
			$artifact_list = user_get_artifact_list($object['user_id'], '', sql_pholder(' AND kind_id=?', $enchant['kind_id']));
			if ($artifact_list) {
				$artikul_ids = get_hash($artifact_list, 'artikul_id', 'artikul_id');
				$artifact_artikul_hash = make_hash(artifact_artikul_list(array('id' => $artikul_ids), " AND slot_id <> ''", 'id, quality, trend'));

				foreach ($artifact_list as $k => $artifact) {
					if (!($artifact['flags2'] & ARTIFACT_FLAG2_LEGENDARY)) {
						unset($artifact_list[$k]); //Исключить из списка не легендарные шмотки
						}
					if (!isset($artifact_artikul_hash[$artifact['artikul_id']])) unset($artifact_list[$k]); // исключаем неслотовые вещи
					$artikul = $artifact_artikul_hash[$artifact['artikul_id']];
					
					if (!artifact_enchant_compatible($artifact, $enchant)) {
						unset($artifact_list[$k]); // не подходит по уровню
					}
				}
			}
			if (!$artifact_list) {
				$content .= translate('У Вас нет ни одного предмета, на который можно применить эту руну!');
				$draw_run = false;
			} else {
				artifact_artikul_get_title($artifact_list);
				$artifact_hash = make_hash($artifact_list);
				$content .= '<tr><td><b>'.translate('Выберите предмет:').'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'rSelect' => true)).'</td></tr>';
			}
			$content = '<tr><td align=center>'.$content.'</td></tr>';
			break;

		case 'ENCHANT2':	// действие "наложить действие с артикула энчанта"
			if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
			$enchant = $object['param1'] ? artifact_artikul_get($object['param1']) : false;
			if (!$enchant) break;
			$artifact_list = user_get_artifact_list($object['user_id']);
			if ($artifact_list) {
				$artikul_ids = get_hash($artifact_list, 'artikul_id', 'artikul_id');
				$artifact_artikul_hash = make_hash(artifact_artikul_list(false, sql_pholder(" AND id IN (?@) AND slot_id IN (?@) ", $artikul_ids, $user_body_slots)));

				foreach ($artifact_list as $k => $artifact) {
					if (!isset($artifact_artikul_hash[$artifact['artikul_id']])) unset($artifact_list[$k]); // исключаем неслотовые вещи
				}
			}
			if (!$artifact_list) {
				$content .= translate('У Вас нет ни одного предмета, на который можно применить это действие!');
				$draw_run = false;
			} else {
				artifact_artikul_get_title($artifact_list);
				$artifact_hash = make_hash($artifact_list);
				$content .= '<tr><td><b>'.translate('Выберите предмет:').'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'rSelect' => true)).'</td></tr>';
			}
			$content = '<tr><td align=center>'.$content.'</td></tr>';
			break;

        case 'ENCHANT3':	// действие "наложить руну на стиль"
            if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;

            $object_artikul = artifact_artikul_get($object['artikul_id']);

            $enchant = $object_artikul['param1'] ? artifact_artikul_get($object_artikul['param1']) : artifact_artikul_get($object['param1']);
            if (!$enchant || !$enchant['slot_id']) break;
            $artifact_list = user_get_artifact_list($object['user_id'], '', sql_pholder(' AND flags & ?#ARTIFACT_FLAG_ARMOR_STYLE'));
            if ($artifact_list) {
                $artikul_ids = get_hash($artifact_list, 'artikul_id', 'artikul_id');
                $artifact_artikul_hash = make_hash(artifact_artikul_list(array('id' => $artikul_ids), "  AND quality = 5 AND slot_id = '".$enchant['slot_id']."'", 'id, quality, trend, slot_id, f_cfg'));
                foreach ($artifact_list as $k => $artifact) {
                    if (!isset($artifact_artikul_hash[$artifact['artikul_id']])) unset($artifact_list[$k]); // исключаем неслотовые вещи
                    $artikul = $artifact_artikul_hash[$artifact['artikul_id']];
                    if($enchant['f_cfg'] & ARTIFACT_PPT_MW && !($artikul['f_cfg'] & ARTIFACT_PPT_MW)){
                        unset($artifact_list[$k]); // для двуручных
                    }
                    if(!$artikul['slot_id'] || !$enchant['slot_id']){
                        unset($artifact_list[$k]); // для "не серых" вещей должен совпадать тип раскачки
                    }
                    if($artikul['slot_id'] != $enchant['slot_id']){
                        unset($artifact_list[$k]); // для "не серых" вещей должен совпадать тип раскачки
                    }
                }
            }
            if (!$artifact_list) {
                $content .= translate('У Вас нет ни одного предмета, на который можно применить эту руну!');
                $draw_run = false;
            } else {
                artifact_artikul_get_title($artifact_list);
                $artifact_hash = make_hash($artifact_list);
                $content .= '<tr><td><b>'.translate('Выберите предмет:').'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'rSelect' => true)).'</td></tr>';
            }
            $content = '<tr><td align=center>'.$content.'</td></tr>';
            break;

        case 'RUNED':	// действие "наложить самоцвет"
            if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
            $rune = artifact_artikul_get($object['artikul_id']);
            if (!$rune || !$rune['kind_id']) break;
            $artifact_hash = make_hash(user_get_artifact_list($session_user['id'], '', sql_pholder(' AND backgroup_id = 2 AND type_id<>?#ARTIFACT_TYPE_ID_GIFT AND NOT (flags & ?#ARTIFACT_FLAG_ARMOR_STYLE)')),'id');
            //$artifact_hash = make_hash(array_merge($artifact_hash, user_get_artifact_list($session_user['id'], '', sql_pholder(' AND backgroup_id = 2 AND NOT (flags & ?#ARTIFACT_FLAG_ARMOR_STYLE)'))), 'artikul_id');

            $artikul_ids = array();
            foreach ($artifact_hash as $art){
                $artikul_ids[$art['artikul_id']] = 1;
            }

            $artifact_artikul_hash = make_hash(artifact_artikul_list(array('id' => array_keys($artikul_ids)), sql_pholder(' AND slot_id != \'LHAND\' AND (slot_id IN (?@)) ',$user_body_slots)));

            artifact_artikul_get_title($artifact_hash);

            foreach ($artifact_hash as $k=>$artifact){
                if($artifact['type_id'] == ARTIFACT_TYPE_ID_SAMOCVET) unset($artifact_hash[$k]);
                if($artifact['type_id'] == ARTIFACT_TYPE_ID_ZATOCH) unset($artifact_hash[$k]);
                if(!$artifact_artikul_hash[$artifact['artikul_id']]) unset($artifact_hash[$k]);
                $socket = artifact_socket_get($artifact);
                if(!$socket['cnt']) unset($artifact_hash[$k]);
            }

            $artifact_list = make_hash($artifact_hash);

            if (!$artifact_list) {
                $content .= translate('У Вас нет ни одного предмета, в который можно вставить этот самоцвет!');
                $draw_run = false;
            } else {
                artifact_artikul_get_title($artifact_list);
                $artifact_hash = make_hash($artifact_list);
                $content .= '<tr><td><b>'.translate('Выберите предмет:').'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'add_socket' => true, 'rSelect' => true)).'</td></tr>';
            }
            $content = '<tr><td align=center>'.$content.'</td></tr>';
            break;

        case 'ADD_SOCKET':	// действие "вставить сокет"
            if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
            $rune = artifact_artikul_get($object['artikul_id']);
            if (!$rune || !$rune['kind_id']) break;
            $artifact_hash = make_hash(user_get_artifact_list($session_user['id'], '', sql_pholder(' AND backgroup_id = 2 AND type_id<>?#ARTIFACT_TYPE_ID_GIFT AND NOT (flags & ?#ARTIFACT_FLAG_ARMOR_STYLE)')),'id');
            //$artifact_hash = make_hash(array_merge($artifact_hash, user_get_artifact_list($session_user['id'], '', sql_pholder(' AND backgroup_id = 2 AND NOT (flags & ?#ARTIFACT_FLAG_ARMOR_STYLE)'))), 'artikul_id');

            $artikul_ids = array();
            foreach ($artifact_hash as $art){
                $artikul_ids[$art['artikul_id']] = 1;
            }

            if($artikul_ids) $artifact_artikul_hash = make_hash(artifact_artikul_list(array('id' => array_keys($artikul_ids)), sql_pholder(' AND slot_id != \'LHAND\' AND (slot_id IN (?@)) ',$user_body_slots)));

            artifact_artikul_get_title($artifact_hash);

            foreach ($artifact_hash as $k=>$artifact){
                if($artifact['type_id'] == ARTIFACT_TYPE_ID_SAMOCVET) unset($artifact_hash[$k]);
                if($artifact['type_id'] == ARTIFACT_TYPE_ID_ZATOCH) unset($artifact_hash[$k]);
                if(!$artifact_artikul_hash[$artifact['artikul_id']]) unset($artifact_hash[$k]);
                $socket = artifact_socket_get($artifact);
                if($socket['cnt'] == 0 || $socket['cnt'] >= 3) unset($artifact_hash[$k]);
            }

            $artifact_list = make_hash($artifact_hash);

            if (!$artifact_list) {
                $content .= translate('У Вас нет ни одного предмета, в котором можно добавить сокет!');
                $draw_run = false;
            } else {
                artifact_artikul_get_title($artifact_list);
                $artifact_hash = make_hash($artifact_list);
                $content .= '<tr><td><b>'.translate('Выберите предмет:').'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'add_socket_all' => true, 'rSelect' => true)).'</td></tr>';
            }
            $content = '<tr><td align=center>'.$content.'</td></tr>';
            break;

        case 'SOCKET_COPY':	// действие "переместить сокеты"
            if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
            $rune = artifact_artikul_get($object['artikul_id']);
            if (!$rune || !$rune['kind_id']) break;
            $artifact_hash = make_hash(user_get_artifact_list($session_user['id'], '', sql_pholder(' AND backgroup_id = 2 AND type_id<>?#ARTIFACT_TYPE_ID_GIFT AND NOT (flags & ?#ARTIFACT_FLAG_ARMOR_STYLE)')),'id');
            //$artifact_hash = make_hash(array_merge($artifact_hash, user_get_artifact_list($session_user['id'], '', sql_pholder(' AND backgroup_id = 2 AND NOT (flags & ?#ARTIFACT_FLAG_ARMOR_STYLE)'))), 'artikul_id');

            $artifact_hash1 = array(); //Откуда
            $artifact_hash2 = array(); //Куда

            $artikul_ids = array();
            foreach ($artifact_hash as $art){
                $artikul_ids[$art['artikul_id']] = 1;
            }

            if($artikul_ids) $artifact_artikul_hash = make_hash(artifact_artikul_list(array('id' => array_keys($artikul_ids)), sql_pholder(' AND slot_id != \'LHAND\' AND (slot_id IN (?@)) ',$user_body_slots)));

            artifact_artikul_get_title($artifact_hash);

            foreach ($artifact_hash as $k=>$artifact){
                if($artifact['type_id'] == ARTIFACT_TYPE_ID_SAMOCVET) unset($artifact_hash[$k]);
                if($artifact['type_id'] == ARTIFACT_TYPE_ID_ZATOCH) unset($artifact_hash[$k]);
                if(!$artifact_artikul_hash[$artifact['artikul_id']]) unset($artifact_hash[$k]);

                if($artifact_hash[$k]) {
                    $socket = artifact_socket_get($artifact);

                    //Если есть сокет помещаем в первый хеш
                    if ($socket['cnt'] != 0) $artifact_hash1[$k] = $artifact;
                    //Если нет сокет помещаем во второй хеш
                    if ($socket['cnt'] == 0) $artifact_hash2[$k] = $artifact;
                }
            }

            $artifact_list = make_hash($artifact_hash);
            $artifact_list1 = make_hash($artifact_hash1);
            $artifact_list2 = make_hash($artifact_hash2);

            if (!$artifact_list) {
                $content .= translate('У Вас нет ни одного предмета, чтобы скопировать сокеты!');
                $draw_run = false;
            } else {
                artifact_artikul_get_title($artifact_list1);
                artifact_artikul_get_title($artifact_list2);
                $content .= '<tr><td><b>'.translate('Из:').'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_list1,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'add_socket' => true, 'rSelect' => true, 'rSelect' => true)).'</td></tr>';
                $content .= '<tr><td><b>'.translate('В:').'</b></td><td>'.html_artifact_select('in[artifact_id2]',$artifact_list2,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'add_socket' => true, 'rSelect' => true, 'rSelect' => true)).'</td></tr>';
            }
            $content = '<tr><td align=center>'.$content.'</td></tr>';
            break;

		case 'DISENCHANT':	// действие "разобрать предмет"
			if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
            global $disenchant_slots;
			$artifact_hash = make_hash(user_get_artifact_list($object['user_id'], ''));
			if ($artifact_hash) {
				$artikul_ids = get_hash($artifact_hash, 'artikul_id', 'artikul_id');
				$artifact_artikul_hash = make_hash(artifact_artikul_list(array(
					'id' => $artikul_ids,
					'slot_id' => $disenchant_slots,
				), '', 'id, quality, level_min'));
				foreach ($artifact_hash as $k => $artifact) {
					if (!isset($artifact_artikul_hash[$artifact['artikul_id']])) {
						unset($artifact_hash[$k]); // исключаем неслотовые вещи
					}
					if ($artifact['flags'] & ARTIFACT_FLAG_CANT_BROKEN) {
						unset($artifact_hash[$k]); // исключаем вещи на которых висит флаг "Нельзя сломать"
					}
					$artikul = $artifact_artikul_hash[$artifact['artikul_id']];
					/* if (((int)$artikul['quality'] == 0) && in_array($artikul['level_min'], array(1,2))) {
						unset($artifact_hash[$k]); // нельзя разрушать серые артефакты с левелом 1 и 2
					} */
				}
			}
			if (!$artifact_hash) {
				$content .= '<b>'.translate('У Вас нет ни одного предмета, на который можно применить магию разрушения!').'</b>';
				$draw_run = false;
			} else {
				artifact_artikul_get_title($artifact_hash);
				$content .= '<b>'.translate('Выберите предмет:').'</b>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'rSelect' => true));
			}
			$content = '<tr><td align=center>'.$content.'</td></tr>';
			break;

		case 'DISENCHANT2':	// действие "раздробить предмет"
			if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
			$artifact_hash = make_hash(user_get_artifact_list($object['user_id'], ''));
			global $disenchant2_quality, $disenchant2_slots;
			if ($artifact_hash) {
				$artikul_ids = get_hash($artifact_hash, 'artikul_id', 'artikul_id');
				$artifact_artikul_hash = make_hash(artifact_artikul_list(array(
					'id' => $artikul_ids,
					'slot_id' => array_keys($disenchant2_slots),
					'quality' => $disenchant2_quality,
				), ' AND durability_max > 0', 'id'));
                foreach ($artifact_hash as $k => $artifact) {
					if (!isset($artifact_artikul_hash[$artifact['artikul_id']])) unset($artifact_hash[$k]); // исключаем неподходящие вещи
					if ($artifact['flags'] & ARTIFACT_FLAG_CANT_CRUSHED) unset($artifact_hash[$k]); // исключаем вещи, на которых висит флаг "нельзя раздробить"
				}
			}
			if (!$artifact_hash) {
				$content .= '<b>'.translate('У Вас нет ни одного предмета, на который можно применить магию дробления!').'</b>';
				$draw_run = false;
			} else {
				artifact_artikul_get_title($artifact_hash);
				$content .= '<b>'.translate('Выберите предмет:').'</b>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'rSelect' => true));
			}
			$content = '<tr><td align="center">'.$content.'</td></tr>';
			break;

		case 'SHATTER':	// действие "разбить бутылки (эликсиры и т.п.)"
			if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
			global $artifact_kind_id_shatter;
			$artifact_list = user_get_artifact_list($object['user_id'], '', sql_pholder(' AND kind_id in (?@)', $artifact_kind_id_shatter));
			if (!$artifact_list) {
				$content .= '<b>'.translate('У Вас нет подходящих предметов!').'</b>';
				$draw_run = false;
			} else {
				artifact_artikul_get_title($artifact_list);
				$artifact_hash = make_hash($artifact_list);
				$content .= '<tr><td><b>'.translate('Выберите предмет:').'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('rSelect' => true)).'</td></tr>';
				if (!$in['artifact_cnt']) $in['artifact_cnt'] = 1;
				$content .= '<tr><td><b>'.translate('Количество:').'</b></td><td><input name="in[artifact_cnt]" type="text" size="16" value="'.htmlspecialchars($in['artifact_cnt']).'" class="dbgl2 brd b small"></td></tr>';
			}
			$content = '<tr><td align=center>'.$content.'</td></tr>';
			break;

		case 'PUNISH':		// действие "наказать"
			if (!in_array($object['object_class'],array(OBJECT_CLASS_USER))) break;
			$crime_type_id = intval($action['param1']);
			require_once('lib/punishment.lib');
			$param = array();
			list($param['punishment_type'], $param['punishment_reason'], $param['punishment_money'], $param['punishment_time'], $param['ignore_kind'], $param['punishment_id']) = explode(',',$action['param2']);
			$content .= '<tr><td><b>'.translate('Ник:').'</b></td><td><input name="in[nick]" type="text" size="16" value="'.htmlspecialchars($in['nick']).'" class="dbgl2 brd b small"></td></tr>';
			if (empty($param['punishment_id'])) {
				$avail_mode_tree = punishment_avail_mode_get($crime_type_id, $param['punishment_type']);
			} else {
				$avail_mode_tree = punishment_get($param['punishment_id']);
				$avail_mode_tree = array($avail_mode_tree['crime_id'] => array($avail_mode_tree['type_id'] => array($avail_mode_tree)));
			}
			$content .= tpl_punishment_avail_mode_form($avail_mode_tree, $param);
			break;

		case 'AMNESTY':		// действие "снять наказание"
			$content .= '<tr><td><b>'.translate('Ник:').'</b></td><td><input name="in[nick]" type="text" size="16" value="'.htmlspecialchars($in['nick']).'" class="dbgl2 brd b small"></td></tr>';
			break;

		case 'CURE':		// действие "лечить травму"
			if (!in_array($object['object_class'],array(OBJECT_CLASS_USER,OBJECT_CLASS_ARTIFACT))) break;
			require_once('lib/injury.lib');
			if ($in['empty_form']) {
				list($injury_weight,$injury_time) = explode(',',$action['param1']);
				$content = '<tr><td colspan=2>'.sprintf(translate('Лечение травм со степенью тяжести не больше <b>%d</b>.<br>Ускорение выздоровления на <b>%s</b>.'),$injury_weight,html_period_str($injury_time,true)).'</td></tr>';
			}
			if ($action['param2'] & 1) {	// Ввод ника
				$content .= '<tr><td><b>'.translate('Ник:').'</b></td><td><input id="cure_nick" name="in[nick]" type="text" size="16" value="'.htmlspecialchars(($in['nick'] ? $in['nick'] : ($session_user['profession'] & PR_MEDIC ? $session_user['nick'] : ''))).'" class="dbgl2 brd b small"> <a href="#" onclick="gebi(\'cure_nick\').value=\'\';return false;">X</a></td></tr>';
			}
			if ($action['param2'] & 2) {	// Выбор слота
                $slot_inj = -1;
			    if($session_user['profession'] & PR_MEDIC) {
			        $user_injury_list = artifact_list(false, $session_user['id'], '*', true, false, sql_pholder(' AND type_id = ?', ARTIFACT_TYPE_ID_INJURY));
			        foreach ($user_injury_list as $user_injury_item) {
			            if(!$user_injury_item['slot_id']) continue;
                        $slot_inj = $user_injury_item['slot_id'];
                        break;
                    }
                }
				$content .= '<tr><td><b>'.translate('Травма:').'</b></td><td>'.html_select('in[slot_id]',get_hash($injury_slot_info,'slot_id','title'),$slot_inj,false,'class="dbgl2 b small"').'</td></tr>';
			}
			break;

		case 'MARRY':		// действие "поженить"
			if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
			$content .= '<tr><td><b>'.translate('Ник жениха:').'</b></td><td><input name="in[groom_nick]" type="text" size="16" value="'.htmlspecialchars($in['groom_nick']).'" class="dbgl2 brd b small"></td></tr>';
			$content .= '<tr><td><b>'.translate('Ник невесты:').'</b></td><td><input name="in[bride_nick]" type="text" size="16" value="'.htmlspecialchars($in['bride_nick']).'" class="dbgl2 brd b small"></td></tr>';
			$content .= '<tr><td><b>'.translate('Пожелание:').'</b></td><td><textarea name="in[note]" cols=50 rows=6 class="dbgl2 brd b small lscroll">'.htmlspecialchars($in['note']).'</textarea></td></tr>';
			break;

		case 'DIVORCE':		// действие "развести"
			$content .= '<tr><td><b>'.translate('Ник:').'</b></td><td><input name="in[nick]" type="text" size="16" value="'.htmlspecialchars($in['nick']).'" class="dbgl2 brd b small"></td></tr>';
			break;

		case 'ENGRAVE':	// действие "выгравировать"
			if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
			$artifact_hash = make_hash(user_get_artifact_list($object['user_id'], ''));
			if ($artifact_hash) {
				$artikul_ids = get_hash($artifact_hash, 'artikul_id', 'artikul_id');
				$artifact_artikul_hash = make_hash(artifact_artikul_list(false, sql_pholder(" AND id IN (?@) AND slot_id IN (?@)", $artikul_ids, $user_weapon_slots)));
				foreach ($artifact_hash as $k => $artifact) {
					if (!isset($artifact_artikul_hash[$artifact['artikul_id']])) unset($artifact_hash[$k]); // исключаем неслотовые вещи
				}
			}
			if (!$artifact_hash) {
				$content .= '<tr><td align=center><b>'.translate('У вас нет ни одного предмета на который можно нанести гравировку').'</b></td></tr>';
				$draw_run = false;
			} else {
				artifact_artikul_get_title($artifact_hash);
				$content .= '<tr><td colspan="2"><b>'.translate('Выберите предмет:').'</b> '.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small"', array('add_color'=>true, 'add_durability'=>true, 'rSelect' => true)).'</td></tr>';
				$content .= '<tr><td><b>'.translate('Текст надписи:').'</b></td><td><input type="text" name="in[note]" size=50  class="dbgl2 brd b small" value="'.htmlspecialchars($in['note']).'" maxlength="'.ARTIFACT_ENGRAVE_LENGTH.'"></td></tr>';
			}
			break;
		case 'SPECIAL_MSG':
			if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;			
			$count_target = intval($action['param2']);
			$taret_direction = intval($action['param1']);
			$to_clan = $taret_direction & SPECIAL_TO_CLAN;
			if ($count_target > 2 || $count_target < 0) {
				$count_target = 1;
			}
			$actions_messages_hash = make_hash(action_list(false, sql_pholder(" AND code=? AND type_id=?", 'SPECIAL_MSG', $action['type_id'])));
			$actions_hash = get_hash($actions_messages_hash);
			if (!$actions_hash) {
				$content .= '<tr><td align=center><b>'.translate('Нет сообщений!').'</b></td></tr>';
				$draw_run = false;
			} else {
				$content .= '<tr><td align=center>' . get_special_msg_html_function($actions_messages_hash) . '</td></tr>';
				$content .= '<tr><td><b>'.translate('Сообщение:').'</b></td><td>'.html_select('in[message_id]',$actions_hash,-1,false,'class="dbgl2 b small" id="special_msg" onchange="spec_msg_select(this)"').'</td></tr>';
				for($i=0; $i<2; $i++) {
					$visibility  = (($count_target <=$i)?'style="visibility:hidden"':"");
					$content .= '<tr ><td id="td1nick'.($i+1).'" '.$visibility.'><b>'. sprintf($to_clan?translate('Клан %s:'):translate('Ник %s:'),($i+1)) . '</b></td><td id="td2nick'.($i+1).'" '.$visibility.' ><input name="in[nick]['.$i.']" id ="nick'.($i+1).'" '.(($count_target <=$i)?'disabled="disabled"':"").'  type="text" size="16" value="'.htmlspecialchars($in['nick'][$i]).'" class="dbgl2 brd b small"></td></tr>';
				}
			}
			break;
			
		case 'CHANGE_CLASS':
			foreach ($class_info as $class_id => $class) {
				if (!isset($class['kind']) || ($class['kind'] != $session_user['kind']) || ($session_user['class'] == $class_id)) unset($class_info[$class_id]);
			}
			$class_hash = get_hash($class_info, 'id', 'title');
			$content .= '<tr><td><b>'.translate('Школа').'</b></td><td>'.html_select('in[class_id]', $class_hash, 0, false).'</td></tr>';
			break;
		case 'CHANGE_NICK':
			$in['nick'] = $session_user['nick'];
			$action_param['ref'] = $in['ref'];
			
			$content .= '<tr><td><b>'.translate('Новый ник:').'</b></td><td><input name="in[nick]" type="text" size="16" value="'.htmlspecialchars($in['nick']).'" class="dbgl2 brd b small"></td></tr>';
			
			if (!$content) {
				if ($in['empty_form']) $content = '<tr><td>'.sprintf(translate('Подтвердите выполнение действия &laquo;%s&raquo;'),$action['title']).'</td></tr>';
				else return false;
			}
			break;
		case 'BUYADDCELL':
			$content .= '<tr><td>'.sprintf(translate('На какой срок вы хотите арендовать ячейку?'),$action['title']).'</td></tr>';
			global $addcell_prices;
			foreach($addcell_prices as $k => $addcell_price) {
				$content .= '<tr><td valign="center"><input type="radio" name="in[price]" value="'.$k.'" '.($k ? '' : 'checked').'> '.sprintf(translate('%s дней за %s'), $addcell_price['days'], html_money_str(MONEY_TYPE_GAME, $addcell_price['cost'])).'</td></tr>';
			}
			break;
        case 'PREMIUM_ADD':
            $in['nick'] = $session_user['nick'];
            $action_param['ref'] = $in['ref'];

            $content .= '<tr><td align="center" colspan="2"><b>'.translate('Активировать премиум:').'</b></td></tr>';

            $days = $object['param1'];
            $points = $object['param2'];

            if(!$days || !$points){
                $out['error'] = 'Артефакт не настроен!';
                return false;
            }

            $k = false;
            $content .= '<tr><td align="center" valign="center"> <label for="_id_1" ><b style="color:blue;">'.$days.' '.format_by_count($days, 'день', 'дня', 'дней').'</b></label> </td>';
            $k = true;
            $content .= '<td align="center" valign="center"><input id="_id_2" type="radio" name="in[prem]" value="2" checked> <label for="_id_2" ><b style="color:#00b30f;">'.$points.' '.format_by_count($points, 'очко', 'окча', 'очков').'</b></label> </td></tr>';
            unset($k);
            $content .= '<tr></tr>';

            if (!$content) {
                if ($in['empty_form']) $content = '<tr><td>'.sprintf(translate('Подтвердите выполнение действия &laquo;%s&raquo;'),$action['title']).'</td></tr>';
                else return false;
            }
            break;
        case 'SMOL':	// действие "наложить смолу"
            if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
            global $user_body_slots;
            $smol = artifact_artikul_get($object['artikul_id']); //Получаем смолу
            if($smol) {
                $artifact_list = user_get_artifact_list($object['user_id'], '', sql_pholder(' AND !(flags2 & ?#ARTIFACT_FLAG2_NO_BREAK)'));
            }
            if ($artifact_list) {
                $artikul_ids = get_hash($artifact_list, 'artikul_id', 'artikul_id');
                $artifact_artikul_hash = make_hash(artifact_artikul_list(array('id' => $artikul_ids), " AND slot_id <> ''", 'id, kind_id, quality, trend, slot_id, flags2'));

                foreach ($artifact_list as $k => $artifact) {
                    if (!isset($artifact_artikul_hash[$artifact['artikul_id']])) unset($artifact_list[$k]); // исключаем неслотовые вещи
                    $artikul = $artifact_artikul_hash[$artifact['artikul_id']];
                    if($artikul['quality'] != $smol['quality']) unset($artifact_list[$k]);
                    if($artikul['kind_id'] != $smol['kind_id']) unset($artifact_list[$k]);
                    if(!($artikul['flags2'] & ARTIFACT_FLAG2_CAN_SMOL)) unset($artifact_list[$k]);
                    if (!in_array($artikul['slot_id'],$user_smol_slots)) {
                        unset($artifact_list[$k]);
                    }
                }
            }
            if (!$artifact_list) {
                $content .= translate('У Вас нет ни одного предмета, который можно укрепить данной смолой!');
                $draw_run = false;
            } else {
                artifact_artikul_get_title($artifact_list);
                $artifact_hash = make_hash($artifact_list);
                $content .= '<tr><td><b>'.translate('Выберите предмет:').'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'rSelect' => true)).'</td></tr>';
            }
            $content = '<tr><td align=center>'.$content.'</td></tr>';
            break;
        case 'CHECKPOINT_C':
            global $area_checkpoint_info;
            $checkpoint_access = true;
            $kind_plague = false; // Лока другой расы

            $checkpoint_list = make_hash(artifact_list(false, $session_user['id'], null, false, false, ' AND type_id = '.ARTIFACT_TYPE_ID_CHECKPOINT),'param1');
            $cnt_checkpoints = count($checkpoint_list);

            if($checkpoint_list[$session_user['area_id']]){
                $content .= 'У вас уже есть Свиток перемещения на данную локацию';
                $checkpoint_access = false;
                break;
            }

            if($cnt_checkpoints >= CHECKPOINT_MAX_CNT){
                $content .=  'У вас уже максимум чекпоинтов';
                break;
            }

            do{
                $user_area = area_get($session_user['area_id']);
                if ($user_area['code'] == 'stronghold' || $user_area['code'] == 'castle_hall') {
                    $content .= sprintf(translate('Подождите! Замок? Чекпоинт в замок? Нет!'));
                    $checkpoint_access = false;
                    break;
                }
                if ($user_area['code'] == 'store' || $user_area['huckster']) {
                    $content .= sprintf(translate('Подождите! Магазин? Чекпоинт в магазин? Нет!'));
                    $checkpoint_access = false;
                    break;
                }
                if ($user_area['code'] == 'post') {
                    $content .= sprintf(translate('Подождите! Почта? Чекпоинт в почту? Нет!'));
                    $checkpoint_access = false;
                    break;
                }
                if ($user_area['code'] == 'jail') {
                    $content .= sprintf(translate('Подождите! Тюрьма? Чекпоинт в тюрьму? Нет!'));
                    $checkpoint_access = false;
                    break;
                }
                if ($user_area['code'] == 'cusnica') {
                    $content .= sprintf(translate('Подождите! Кузница? Чекпоинт в кузницу? Нет!'));
                    $checkpoint_access = false;
                    break;
                }
                if ($user_area['code'] == 'clan') {
                    $content .= sprintf(translate('Подождите! Регистрационная палата? Чекпоинт в регистрационную палату? Нет!'));
                    $checkpoint_access = false;
                    break;
                }
                if ($user_area['code'] == 'cell') {
                    $content .= sprintf(translate('Подождите! Банковская ячейка? Чекпоинт в банковскую ячейку? Нет!'));
                    $checkpoint_access = false;
                    break;
                }
                if ($user_area['code'] == 'bg') {
                    $content .= sprintf(translate('Подождите! Поле битвы? Чекпоинт на поле битвы? Нет!'));
                    $checkpoint_access = false;
                    break;
                }
                if ($user_area['code'] == 'bank') {
                    $content .= sprintf(translate('Подождите! Банк? Чекпоинт в банк? Нет!'));
                    $checkpoint_access = false;
                    break;
                }
                if ($user_area['code'] == 'auction') {
                    $content .= sprintf(translate('Подождите! Аукцион? Чекпоинт в аукцион? Нет!'));
                    $checkpoint_access = false;
                    break;
                }

                if ($user_area['code']) {
                    $content .= sprintf(translate('На данную локацию нельзя создать чекпоинт!'));
                    $checkpoint_access = false;
                    break;
                }

                if ($user_area['flags'] & AREA_FLAG_NO_CHECKPOINT) {
                    $content .= sprintf(translate('На данную локацию нельзя создать чекпоинт!'));
                    $checkpoint_access = false;
                    break;
                }

                // Если текущая локация на территории людей или магмаров
                if ((($user_area['flags'] & AREA_FLAG_NO_INVISIBLE_MAGMAR) && ($session_user['kind'] == KIND_MAGMAR)) ||
                    (($user_area['flags'] & AREA_FLAG_NO_INVISIBLE_HUMAN) && ($session_user['kind'] == KIND_HUMAN))) {
                    $kind_plague = true;
                    //То цена в бриллиантах...
                }
            }while(0);

            if($checkpoint_access){
                $m_type = ($kind_plague ? MONEY_TYPE_GOLD : MONEY_TYPE_GAME);
                $m_val = $area_checkpoint_info[$cnt_checkpoints + 1][$m_type]['price'];

                if($cnt_checkpoints){$content .= 'Кол-во текущих чекпоинтов '.$cnt_checkpoints.'<br><br>';}

                $content .= 'Цена чекпоинта на локации "'.$user_area['title'].'" '.html_money_str($m_type, $m_val).'<br> Купить?';
            }

            break;
        case 'SAVE_PROF':
            $artikul_item = artifact_artikul_get($object['artikul_id']);
            if(!$artikul_item || !$artikul_item['profession']){
                $content .= 'Неизвестная профессия';
                break;
            }

            $prof_saved = intval($object['param1']);
            $prof_info = get_prof_info($session_user['profession']);
            $prof_skill = skill_object_get(OBJECT_CLASS_USER, $session_user, array('skill_id' => $profession_info[$artikul_item['profession']]['skill_id']));

            if($prof_saved){
                //Получение профессии
                //Если есть cur_pack, то пизда!
                if($prof_info['cur_pack'][$prof_paks[$artikul_item['profession']]]){
                    $content .= 'Вы не можете восстановить "'.$profession_info[$artikul_item['profession']]['title'].'", пока у вас есть профессия данного типа!';
                }else{
                    $content .= 'Подтвердите восстановление профессии "'.$profession_info[$artikul_item['profession']]['title'].'" +'.$prof_saved;
                }
            }elseif($prof_skill['value'] && !$prof_saved && $prof_info['cur_prof'][$artikul_item['profession']]){
                //Сохранение профессии
                $content .= 'Вы действительно хотите сохранить профессию "'.$profession_info[$artikul_item['profession']]['title'].'" +'.$prof_skill['value'].'?';
            }else{
                $content .= 'У вас нет такой профессии!';
            }
            break;
        case 'PUT_OPRAVA':	// действие "Вставить оправу"
            if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
            global $oprava_add_slots;
            $oprava = artifact_artikul_get($object['artikul_id']);
            if (!$oprava) break;
            $artifact_list = user_get_artifact_list($object['user_id']);
            if ($artifact_list) {
                $artikul_ids = get_hash($artifact_list, 'artikul_id', 'artikul_id');
                $artifact_artikul_hash = make_hash(artifact_artikul_list(false, sql_pholder(" AND !(flags & ?#ARTIFACT_FLAG_ARMOR_STYLE) AND type_id != ".ARTIFACT_TYPE_ID_OPRAVA." AND id IN (?@) AND slot_id = '".$oprava['slot_id']."' ", $artikul_ids)));

                $access_slots = array_merge($user_body_slots,$user_weapon_slots,$oprava_add_slots);

                $level_min = $oprava['level_min'];
                foreach ($artifact_list as $k => $artifact) {
                    if (!$artifact_artikul_hash[$artifact['artikul_id']]['slot_id'] || !in_array($artifact_artikul_hash[$artifact['artikul_id']]['slot_id'],$access_slots)) unset($artifact_list[$k]); // исключаем неслотовые вещи
                }
            }
            if (!$artifact_list) {
                $content .= 'У Вас нет ни одного предмета, на который можно применить эту оправу!';
                $draw_run = false;
            } else {
                $artifact_hash = make_hash($artifact_list);
                artifact_artikul_get_title($artifact_hash,$artifact_artikul_hash);
                $content .= '<tr><td><b>'.'Выберите предмет:'.'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'rSelect' => true)).'</td></tr>';
            }
            $content = '<tr><td align=center>'.$content.'</td></tr>';
            break;
        case 'SHADOW_TRANS':	// действие "Теневое преображение"
            if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
            $artikul = artifact_artikul_get($object['artikul_id']);
            if (!$artikul) break;
            $artifact_list = user_get_artifact_list($object['user_id'], '', sql_pholder(' AND flags2 & ?#ARTIFACT_FLAG2_SHADOW_TR'), false, array('companion_item' => PARAM_COMP_ITEMS_NO));
            if ($artifact_list) {
                $artikul_ids = get_hash($artifact_list, 'artikul_id', 'artikul_id');
                $artifact_artikul_hash = make_hash(artifact_artikul_list(false, sql_pholder(" AND !(flags & ?#ARTIFACT_FLAG_ARMOR_STYLE) AND c_art_id > 0 AND id IN (?@) ", $artikul['c_art_id'],$artikul_ids), 'id, title, quality, picture, durability, durability_max, c_art_id'));
                $c_art_ids = array();
                foreach ($artifact_list as $k=>$v){
                    if(!$artifact_artikul_hash[$v['artikul_id']]){
                        unset($artifact_list[$k]);
                    }
                    if(!$artifact_artikul_hash[$v['artikul_id']]['c_art_id']){
                        unset($artifact_list[$k]);
                    }
                    $artifact_list[$k]['picture'] = $artifact_artikul_hash[$v['artikul_id']]['picture'];
                    $artifact_list[$k]['c_art_id'] = $artifact_artikul_hash[$v['artikul_id']]['c_art_id'];
                    $c_art_ids[$artifact_artikul_hash[$v['artikul_id']]['c_art_id']] = $artifact_artikul_hash[$v['artikul_id']]['c_art_id'];
                }
                $c_art_hash = array();
                if($c_art_ids) $c_art_hash = make_hash(artifact_artikul_list(array('id' => $c_art_ids), '', 'id, title, quality, picture'));
            }
            if (!$artifact_list) {
                $content .= 'У Вас нет ни одного предмета, который можно преобразовать в теневой!';
                $draw_run = false;
            } else {
                $artifact_hash = make_hash($artifact_list);
                artifact_artikul_get_title($artifact_hash, $artifact_artikul_hash);
                $content .= '<tr><td><b>'.'Выберите предмет:'.'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'id="trans_info_select" class="dbgl2 b small rSelect" onchange="render_html_trans_info();"', array('add_color'=>true, 'add_durability'=>true, 'rSelect' => true)).'</td></tr>';
                $content .= '<tr><td colspan=3><div id="s_trans_info"></div></td></tr>';
            }

            /*
            tpl_artifact_alt_prepare($artifact_list, OBJECT_CLASS_ARTIFACT);
            foreach ($artifact_list as $artifact){
                tpl_artifact_alt($artifact);
            }
            tpl_artifact_alt_prepare($c_art_hash, OBJECT_CLASS_ARTIFACT_ARTIKUL);
            foreach ($c_art_hash as $artifact){
                tpl_artifact_alt($artifact);
            }*/

            ob_start();
            ?>
            <style>
                #s_trans_info{
                    text-align: center;
                    padding: 5px 10px 0px 10px;
                }
                .trans_info_art{
                    background: url('/images/slot-empty.png');
                    width: 62px;
                    height: 62px;
                    display: inline-block;
                    cursor: pointer;
                }
            </style>
            <script src="<?=static_get('js/jquery.js')?>"></script>
            <script>
                var div = $('#s_trans_info');
                var s_select = $('#trans_info_select');
                var artifact_list = <?=json_encode(make_hash($artifact_list));?>;
                var c_list = <?=json_encode($c_art_hash);?>;

                function render_html_trans_info(){
                    var html = '';
                    var artifact_id = parseInt(s_select.val());
                    if(artifact_id > 0){
                        var artifact = artifact_list[artifact_id];
                        var c_art = c_list[artifact['c_art_id']];
                        if(artifact && c_art){
                            html += '<div onclick="showArtifactInfo('+artifact['id']+'); return false;" class="trans_info_art"><img src="<?=PATH_IMAGE_ARTIFACTS?>'+artifact['picture']+'"></div>&nbsp;';
                            html += '<img src="/images/transform-arrow.png">';
                            html += '&nbsp;<div onclick="showArtifactInfo(false, '+c_art['id']+'); return false;" class="trans_info_art"><img src="<?=PATH_IMAGE_ARTIFACTS?>'+c_art['picture']+'"></div>';
                        }
                    }
                    div.html(html);
                }
            </script>
            <?
            $js = ob_get_clean();

            $content = '<tr><td align=center>'.$content.'</td></tr>';
            $content .= $js;
            break;
        case 'SHADOW_DISE':	// действие "раздробить теневой предмет"
            if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
            global $disenchant_shadow;
            $artifact_list = user_get_artifact_list($object['user_id'], '', sql_pholder(''), false, array('companion_item' => PARAM_COMP_ITEMS_ONLY, 'companion_slot' => ''));
            if ($artifact_list) {
                $artikul_ids = get_hash($artifact_list, 'artikul_id', 'artikul_id');
                $artifact_artikul_hash = make_hash(artifact_artikul_list(false, sql_pholder(" AND type_id = ? AND id IN (?@) ", ARTIFACT_TYPE_ID_COMPANION, $artikul_ids)));
                foreach ($artifact_list as $k=>$v){
                    if(!$artifact_artikul_hash[$v['artikul_id']]){
                        unset($artifact_list[$k]);
                    }
                    if(!$disenchant_shadow[$artifact_artikul_hash[$v['artikul_id']]['slot_id_c']]){
                        unset($artifact_list[$k]);
                    }
                }
            }
            if (!$artifact_list) {
                $content .= 'У Вас нет ни одного теневого предмета, который можно раздробить!';
                $draw_run = false;
            } else {
                $artifact_hash = make_hash($artifact_list);
                artifact_artikul_get_title($artifact_hash,$artifact_artikul_hash);
                $content .= '<tr><td><b>'.'Выберите предмет:'.'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'rSelect' => true)).'</td></tr>';
            }
            $content = '<tr><td align=center>'.$content.'</td></tr>';
            break;
        case 'MOUNT_STYLE':
            if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
            $_artikul = artifact_artikul_get($object['artikul_id']);
            if (!$_artikul) break;
            $artifact_list = user_get_artifact_list($object['user_id'], null, ' AND type_id = '.ARTIFACT_TYPE_ID_MOUNT.($_artikul['param1'] == -1 ? ' AND packet_id > 0' : ''));
            if ($artifact_list) {
                $artikul_ids = get_hash($artifact_list, 'artikul_id', 'artikul_id');
                $artifact_artikul_hash = make_hash(artifact_artikul_list(false, sql_pholder(" AND !(flags & ?#ARTIFACT_FLAG_ARMOR_STYLE) AND type_id = ".ARTIFACT_TYPE_ID_MOUNT." AND id IN (?@)", $artikul_ids)));
            }
            if (!$artifact_list) {
                $content .= 'У Вас нет ни одного предмета, на который можно применить эту магию!';
                $draw_run = false;
            } else {
                $artifact_hash = make_hash($artifact_list);
                artifact_artikul_get_title($artifact_hash,$artifact_artikul_hash);
                $content .= '<tr><td><b>'.'Выберите ездового:'.'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'rSelect' => true)).'</td></tr>';
            }
            $content = '<tr><td align=center>'.$content.'</td></tr>';
            break;
			
			
		
        case 'RF_ENCHANT':	// действие "наложить символ руны"
            global $rf_enchant_slots;
            if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
            $rune = artifact_artikul_get($object['artikul_id']);
            if (!$rune) break;
            $artifact_hash = make_hash(user_get_artifact_list($session_user['id'], '', sql_pholder(' AND flags2 & ?#ARTIFACT_FLAG2_RUNIC_FRAME AND backgroup_id = 2 AND type_id<>?#ARTIFACT_TYPE_ID_GIFT AND NOT (flags & ?#ARTIFACT_FLAG_ARMOR_STYLE)')),'id');
            //$artifact_hash = make_hash(array_merge($artifact_hash, user_get_artifact_list($session_user['id'], '', sql_pholder(' AND backgroup_id = 2 AND NOT (flags & ?#ARTIFACT_FLAG_ARMOR_STYLE)'))), 'artikul_id');

            $artikul_ids = array();
            foreach ($artifact_hash as $art){
                $artikul_ids[$art['artikul_id']] = 1;
            }

            if($artikul_ids) $artifact_artikul_hash = make_hash(artifact_artikul_list(array('id' => array_keys($artikul_ids)), sql_pholder(' AND slot_id != \'LHAND\' AND (slot_id IN (?@)) ',$rf_enchant_slots)));

            artifact_artikul_get_title($artifact_hash);

            foreach ($artifact_hash as $k=>$artifact){
                if(!($artifact['flags2'] & ARTIFACT_FLAG2_RUNIC_FRAME)) unset($artifact_hash[$k]);
                if(!$artifact_artikul_hash[$artifact['artikul_id']]) unset($artifact_hash[$k]);
            }

            $artifact_list = make_hash($artifact_hash);

            if (!$artifact_list) {
                $content .= translate('<b class=redd>У Вас нет ни одного предмета, на который можно наложить рунический символ!</b>');
                $draw_run = false;
            } else {
                artifact_artikul_get_title($artifact_list);
                $artifact_hash = make_hash($artifact_list);
                $content .= '<tr><td><b>'.translate('Выберите предмет:').'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'add_socket' => true, 'add_rf_enchant' => true, 'rSelect' => true)).'</td></tr>';
            }
            $content = '<tr><td align=center>'.$content.'</td></tr>';
            break;
            case 'ADDCHAR':	// действие "Наложить чары"
            if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
            $chara = artifact_artikul_get($object['artikul_id']);
            if (!$chara) break;
            $artifact_list = user_get_artifact_list($object['user_id'], null, sql_pholder(" AND !(flags & ?#ARTIFACT_FLAG_ARMOR_STYLE) AND type_id != ?", ARTIFACT_TYPE_ID_CHAR));
            if ($artifact_list) {
                $artikul_ids = get_hash($artifact_list, 'artikul_id', 'artikul_id');
                $artifact_artikul_hash = make_hash(artifact_artikul_list(false, sql_pholder(" AND quality > 0 AND !(flags & ?#ARTIFACT_FLAG_ARMOR_STYLE) AND type_id != ".ARTIFACT_TYPE_ID_CHAR." AND id IN (?@) AND slot_id = '".$chara['slot_id']."' ", $artikul_ids)));

                foreach ($artifact_list as $k => $artifact) {
                    if (!$artifact_artikul_hash[$artifact['artikul_id']]['slot_id']) unset($artifact_list[$k]); // исключаем неслотовые вещи
                }
            }
            if (!$artifact_list) {
                $content .= 'У Вас нет ни одного предмета, на который можно наложить чары!';
                $draw_run = false;
            } else {
                $artifact_hash = make_hash($artifact_list);
                artifact_artikul_get_title($artifact_hash,$artifact_artikul_hash);
                $content .= '<tr><td colpan=2><b>'.'Выберите предмет:'.'</b><br>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small"', array('add_color'=>true, 'add_durability'=>true)).'</td></tr>';
            }
            $content = '<tr><td align=center>'.$content.'</td></tr>';
            break;
        case 'RUNE_ENCHANT':	// действие "наложить руническую основу"
            global $rf_enchant_slots;
            if (!in_array($object['object_class'],array(OBJECT_CLASS_ARTIFACT))) break;
            $rune = artifact_artikul_get($object['artikul_id']);
            if (!$rune) break;
            $artifact_hash = make_hash(user_get_artifact_list($session_user['id'], '', sql_pholder(' AND !(flags2 & ?#ARTIFACT_FLAG2_RUNIC_FRAME) AND backgroup_id = 2 AND type_id<>?#ARTIFACT_TYPE_ID_GIFT AND NOT (flags & ?#ARTIFACT_FLAG_ARMOR_STYLE)')),'id');
            //$artifact_hash = make_hash(array_merge($artifact_hash, user_get_artifact_list($session_user['id'], '', sql_pholder(' AND backgroup_id = 2 AND NOT (flags & ?#ARTIFACT_FLAG_ARMOR_STYLE)'))), 'artikul_id');

            $artikul_ids = array();
            foreach ($artifact_hash as $art){
                $artikul_ids[$art['artikul_id']] = 1;
            }

            if($artikul_ids) $artifact_artikul_hash = make_hash(artifact_artikul_list(array('id' => array_keys($artikul_ids)), sql_pholder(' AND slot_id != \'LHAND\' AND kind_id = ? AND (slot_id IN (?@)) ', $rune['kind_id'], $rf_enchant_slots)));

            artifact_artikul_get_title($artifact_hash);

            foreach ($artifact_hash as $k=>$artifact){
                if($artifact['flags2'] & ARTIFACT_FLAG2_RUNIC_FRAME) unset($artifact_hash[$k]);
                if(!$artifact_artikul_hash[$artifact['artikul_id']]) unset($artifact_hash[$k]);
            }

            $artifact_list = make_hash($artifact_hash);

            if (!$artifact_list) {
                $content .= translate('<b class=redd>У Вас нет ни одного предмета, на который можно наложить руническую основу!</b>');
                $draw_run = false;
            } else {
                artifact_artikul_get_title($artifact_list);
                $artifact_hash = make_hash($artifact_list);
                $content .= '<tr><td><b>'.translate('Выберите предмет:').'</b></td><td>'.html_artifact_select('in[artifact_id]',$artifact_hash,-1,true,'class="dbgl2 b small rSelect"', array('add_color'=>true, 'add_durability'=>true, 'add_socket' => true, 'rSelect' => true)).'</td></tr>';
            }
            $content = '<tr><td align=center>'.$content.'</td></tr>';
            break;
        case 'MAGIC_PORT':
            require_once("lib/area_teleport.lib");
            $area_teleport_list = make_hash(area_teleport_list(array('kind' => $session_user['kind'])));
            $area_telep_hash = array();
            $area_ids = array();
            foreach ($area_teleport_list as $area){
                $area_ids[$area['area_id']] = $area['area_id'];
            }
            $area_teleport_user_hash = make_hash(area_teleport_user_list(array('user_id' => $session_user['id'])), 'area_teleport_id');
            if($area_ids) $area_hash = make_hash(area_list(array('id' => $area_ids)));
            $select_hash = array();
            $f_id = 0;
            foreach ($area_teleport_list as $area_teleport) {
                $area = $area_hash[$area_teleport['area_id']];
                $t = explode('.', $area['swf']);
                $area['pic2'] = PATH_SWF_AREAS . $t[0] . '.jpg';
                $area_telep_hash[$area_teleport['id']] = get_params($area, 'id,title,pic2');
                $info = area_teleport_info($session_user, $area_teleport, $area_teleport_user_hash, true);
                $select_hash[$area_teleport['id']] = $area['title'].' '.($area_teleport['cnt_day'] ? '' . $area_teleport['cnt_day'] - $info['area_teleport_user']['cnt'] . '/' . $area_teleport['cnt_day'] . '' : '').($info['status'] != 100 ? ' ['.$info['error'].']' : '');
            }
            asort($select_hash);
            $_1_keys = array_keys($select_hash);
            $content .= '<tr><td colpan=2><b><div class="w100" style="text-align:center;">'.'Выберите локацию:'.'</b><br>'.html_intel_select('in[ref]',$select_hash,$_1_keys[0],false,' onchange="select_location_action(false,this);" class="dbgl2 b small" style="border: 1px #a97e59 solid;margin-bottom: 2px;width: 100%;"',  '', -1, '--', array('add_inpsel' => '<br>',)).'</div></td></tr>';

            $content .= '<tr><td colpan=2><table class="w100 coll"><tr><td width="1"><div id="np_button_l" onclick="select_location_prev();" class="np_button"></div></td><td width="100%" align=center><div style="height:83px;text-align:center;width:100%;" id="action_telep_area_info"></div></td><td width="1"><div id="np_button_r" onclick="select_location_next();" class="np_button right"></div></td></td></table></td></tr>';

            ob_start();
            ?>
            <script>
                var safe_recurse = 0;
                var act_loc_hash = <?=json_encode($area_telep_hash);?>;
                select_check_after = function() {
                    select_location_action();
                    select_location_check_butt();
                };
                select_check_before = function () {
                    $('#action_telep_area_info').html('');
                };
                function select_location_action(id, obj) {
                    if(id === undefined && obj === undefined){
                        try{
                            var idx = parseInt(gebi('in[ref]').value);
                            if(!idx || idx <= 0) return false;
                            select_location_action(idx);
                        }catch (e) {}
                        return false;
                    }
                    try{
                        id = id !== false ? id : parseInt($(obj).val());
                        if(act_loc_hash[id] === undefined) return false;
                        $('#action_telep_area_info').html('<img style="border: 1px black solid;border-radius: 5px;" src="'+act_loc_hash[id]['pic2']+'">');
                    }catch (e) {}
                }
                $(function(){
                    select_location_action(<?=$_1_keys[0];?>);
                    select_location_check_butt();
                });
                function select_location_check_butt() {
                    $('.np_button').removeClass('active');
                    xk = gebi('in[ref]');
                    if(xk[xk.selectedIndex + 1] !== undefined){
                        $('#np_button_r').addClass('active');
                    }
                    if(xk[xk.selectedIndex - 1] !== undefined){
                        $('#np_button_l').addClass('active');
                    }
                }
                function select_location_next() {
                    xk = gebi('in[ref]');
                    if(xk[xk.selectedIndex + 1] !== undefined){
                        xk.selectedIndex++;
                        select_location_action(parseInt(xk.value));
                    }
                    select_location_check_butt();
                }
                function select_location_prev() {
                    xk = gebi('in[ref]');
                    if(xk[xk.selectedIndex - 1] !== undefined){
                        xk.selectedIndex--;
                        select_location_action(parseInt(xk.value));
                    }
                    select_location_check_butt();
                }
            </script>
            <?
            $content .= ob_get_clean();

            break;
	}
	
	// кол-во использований за период
	if ($action['per_value']) {
		$bkey_info = date_bkey($action['per_unit'], $action['per_value']);
		$bkey = $bkey_info[0];
		$action_stat = action_stat_get(false,sql_pholder(" AND action_id=? AND bkey=? and user_id=?",$action['action_id'],$bkey,$session_user['id']));
		// если несколько использований за период (например, телепорты), то сообщаем кол-во (не применяется в сочетании с глобальным использованием)
		if ($action['per_value'] > 1) {
			$used_count = isset($action_stat['cnt']) ? intval($action_stat['cnt']) : 0;
			$content .= '<tr><td colspan="2">'.sprintf(translate('Оставшееся количество использований: <b>%s</b>'),$action['per_value']-$used_count).'</td></tr>';
		// если 1, то сообщаем время до следующего использования  
		} else if (!empty($action_stat['dtime'])) {
			$content .= '<tr><td colspan="2">'.sprintf(translate('Время до следующего использования: <b>%s</b>'), html_period_str($action_stat['dtime'] - time_current() - 86400)).'</td></tr>'; // время минус "магическая константа" (добавляется при сохранении dtime возможно для того чтобы статистика удалялась только через сутки)
		}
	}
	
		
	// глобальное кол-во использований
	$action_object = action_object_get($object['object_class'],$object['id']);
	if ($action_object['n']) $content .= '<tr><td colspan="2">'.sprintf(translate('Оставшееся количество использований: <b>%s</b>'),$action_object['n']).'</td></tr>';
	
	
	if (($noconfirm && $draw_run) || $in['no_confirm']) {
		$params = $hidden_param;
		$params['in'] = $action_param;
		$url = action_run_request($params);
		common_redirect($url);
	}


	if ($content === false) return false;
	$html = '<table border=0 cellpadding=1 cellspacing=0 style="border: 0px; display: inline-block;">';
	$html .= '<form method="post" action="action_run.php?popup_feature='.intval($in['popup_feature']).'"'.($in['trgt'] ? ' target="'.$in['trgt'].'"': '').'>';
	$html .= html_hidden($hidden_param);
	$html .= html_hidden($action_param,'in');
	$html .= $content;
	$html .= '<tr style="border: 0px;"><td colspan=2 align=center style="border: 0px;"><br>'.($draw_run ? html_button(translate('Выполнить'),'butt1','submit','',array('add' => 'class="grnn"')) : '').($in['draw_cancel'] ? ' '.html_button(translate('отмена'),'butt1','button','',array('add' => 'class="redd" style="width:64px" onClick="hideActionFormsAbs()"')): '').'</td></tr>';
	$html .= '</form>';
	$html .= '</table>';

	//Другие действия!
	switch ($action['code']) {
        case 'OPEN_CASE':
            require_once("lib/casebox.lib");
            $object_artikul = artifact_artikul_get($object['artikul_id']);
            $html = '<iframe width="460" height="255" frameborder="0" src="case_box.php?ref='.$object_artikul['param1'].'" scrolling="no"></iframe>';
            $html .= '<script>$(".popup_global_content").css("padding", "0");</script>';
            break;
    }
	return $html;
}


function tpl_punishment_avail_mode_form(&$avail_mode_tree, $param) {
	global $crime_type_info, $punishment_type_hash, $punishment_time_interval_hash, $money_type_info;
	$html = '';
	$crime_hash = array();
	$punish_type_hash = array();
	$punish_list = array();
	$money_type_hash = get_hash($money_type_info);

	if ($param['punishment_reason'] == PUNISH_INPUT_MANUAL) {
		$param['punishment_money'] = $param['punishment_time'] = PUNISH_INPUT_MANUAL; // если причина вводится руками, то и штрафы и время тоже руками
	}
	foreach ($avail_mode_tree as $crime_id => $punish_type_info) {
		$crime_hash[$crime_id] = $crime_type_info[$crime_id]['title'];
		foreach ($punish_type_info as $type_id => $punishment_list) {
			$punish_type_hash[$type_id] = $punishment_type_hash[$type_id];
			foreach ($punishment_list as $item) {
				$js_punish_id = implode('_', array($crime_id,$type_id,$item['id']));
				$punish_list[$js_punish_id] = $item;
			}
		}
	}
	$active_crime = reset(array_keys($crime_hash));
	if (sizeof($crime_hash) == 1) {
		$html .= '<tr><td><b>'.translate('Тип преступления:').'</b></td><td class="redd b">'.reset($crime_hash).'</td></tr>';
		$html .= '<input type="hidden" name="in[crime_id]" value="'.$active_crime.'" id="crime_select">';
	} elseif (sizeof($crime_hash) > 1) {
		$html .= '<tr><td><b>'.translate('Тип преступления:').'</b></td><td>'.html_select('in[crime_id]', $crime_hash, $active_crime, false,' id="crime_select" onchange="crime_change()" style="width:250px" class="dbgl2 b small"').'</td></tr>';
	}
	if (sizeof($punish_type_hash) == 1) {
		$html .= '<tr><td><b>'.translate('Мера пресечения:').'</b></td><td class="redd b">'.reset($punish_type_hash).'</td></tr>';
		$html .= '<input type="hidden" name="in[type_id]" value="'.reset(array_keys($punish_type_hash)).'" id="punish_type_select">';
	} else {
		$select_punish_type = ($active_crime == CRIME_FREEDOM ? PUNISH_TYPE_TIME : -1);
		$html .= '<tr><td><b>'.translate('Мера пресечения:').'</b></td><td>'.html_select('in[type_id]', $punish_type_hash, $select_punish_type, 
				false,' id="punish_type_select" onchange="punish_type_change()" style="width:250px" class="dbgl2 b small"').'</td></tr>';
	}
	$html .= '<tr><td><b>'.translate('Причина:').'</b></td><td>';
	if ($param['punishment_reason'] == PUNISH_INPUT_MANUAL) {
		$html .= '<textarea name="in[reason_str]" rows=3 style="width:250px" class="dbgl2 brd b small lscroll"></textarea>';
	} else {
		$html .= '<input type="hidden" name="in[punishment_id]" id="punish_id">';
		$html .= html_select('', array(), -1, false,' id="punish" onchange="punish_change()" style="width:250px" class="dbgl2 b small"');
	}
	$html .= '</td></tr>';
	$html .= '<tr id="type_id_1_TR"><td><b>'.translate('Время:').'</b></td><td>';
	$html .= html_select('in[days]',(($param['punishment_time'] == PUNISH_INPUT_MANUAL) ? $punishment_time_interval_hash : array()), -1, false, ' id="days" class="dbgl2 b small"').'</td></tr>';

	$html .= '<tr id="type_id_2_TR"><td><b>'.translate('Размер штрафа:').'</b></td><td>';
	if ($param['punishment_money'] == PUNISH_INPUT_MANUAL) {
		$html .= '<input type="text" name="in[money]" value="" style="width:70px">'.html_select('in[money_type]',$money_type_hash,-1, true, 'class="dbgl2 b small"');
	} else {
		$html .= '<input type="hidden" name="in[money]" value="" id="money">';
		$html .= '<input type="hidden" name="in[money_type]" value="" id="money_type">';
		$html .= '<div id="money_str"></div>';
	}
	$html .= '</td></tr>';

	ob_start();

?>
<script language="javaScript" src="<?=static_get('js/common.js');?>"></script>
<script language="javaScript" src="<?=static_get('js/punish.js');?>"></script>
<script>
var time_manual = <?=intval(($param['punishment_time'] == PUNISH_INPUT_MANUAL))?>;

var time_intervals = [];
<?
	$t = array();
	foreach($punishment_time_interval_hash as $days => $title) {
		$t[] = "time_intervals[$days] = {id:$days, title:'".addslashes($title)."'}";
	}
	echo implode(";\n",$t).';';

	if ($param['punishment_reason'] == PUNISH_INPUT_LIST) {
?>
var punishment=[];
<?
		$i = 0;
		foreach ($punish_list as $js_punish_id => $punishment) {

			$time_from = $punishment['days_min'];
			$time_to = $punishment['days_max'];

			$time_interval = array();
			if ($punishment['type_id'] == PUNISH_TYPE_TIME) {
				foreach ($punishment_time_interval_hash as $time=>$v) {
					if ($time >= $time_from && $time <= $time_to) {
						$time_interval[] = $time;
					}
				}
			}

?>;
punishment[<?=$i++?>] = {
	id: '<?=$js_punish_id?>',
	id2: <?=$punishment['id']?>,
	type: <?=$punishment['type_id']?>,
	time: [<?=implode(',',$time_interval)?>],
	money: <?=$punishment['money']?>,
	money_type: <?=$punishment['money_type']?>,
	money_str: '<?=addslashes(html_money_str($punishment['money_type'], $punishment['money']))?>',
	title: '<?=addslashes(htmlspecialchars($punishment['title']));?>'
};
<?		} //
	} // if (PUNISH_INPUT_LIST)
?>
crime_change(<?=$active_crime?>);

</script>
<?
	$html .= ob_get_contents();
	ob_end_clean();
	return $html;

}

function tpl_search_function()
{
	ob_start();
	?>
	<script language="javascript">
	var select = document.getElementById('area');
	var links = document.getElementById('links');
	var trim_reg = new RegExp('[\160-\255+]+', 'ig');

	function search_area(name)
	{
		if(name.length <= 1)
			return links.innerHTML = '';
		
		reg = new RegExp('(^| )' + name, 'gi');
		text = '';
		for(i=0;i<select.length;i++)
		{
			area = ('' + select.options[i].text).replace(trim_reg, '');
			if(reg.test(area))
				text += '&raquo; <a style="cursor:pointer" onclick="select.selectedIndex = ' + i + '">'+area + '</a><br/>';
		}
		links.innerHTML = text;
	}

	</script>
	<?
	$cont = ob_get_contents();
	ob_end_clean();
	return $cont;
}

function get_special_msg_html_function($messages){
	ob_start();	
	$disable_rules = array(
			0 => '"nick1":"true", "nick2":"true"',
			1 => '"nick1":"", "nick2":"true"',
			2 => '"nick1":"", "nick2":""',
	);
	
	$text_rules = array(
		0 => translate('Ник'),
		1 => translate('Клан'),
	);

	?>

<script language="javascript">
	var messages_inputs = [];
	<? foreach ($messages as $k=>$m) { ?>
		messages_inputs[<?=$k?>] = {
			<?=$disable_rules[$m['param2']] ?>,
			"text":"<?=$text_rules[($m['param1'] & SPECIAL_TO_CLAN)] ?>"
		};
	<? }  ?>

	function get_vis(bool){
		if (bool == "") {	return 'visible';
		} else return 'hidden';
	}
	function spec_msg_select(select)
	{
		var ind = select.value;
		var nick1 = gebi('nick1');
		nick1.disabled = messages_inputs[ind].nick1;
		gebi('td1nick1').innerHTML = '<b>' + messages_inputs[ind].text + ' 1 </b>';
		gebi('td1nick1').style.visibility = get_vis(messages_inputs[ind].nick1);
		gebi('td2nick1').style.visibility = get_vis(messages_inputs[ind].nick1);

		var nick2 = gebi('nick2');
		nick2.disabled = messages_inputs[ind].nick2;
		gebi('td1nick2').innerHTML = '<b>' + messages_inputs[ind].text + ' 2 </b>';
		gebi('td1nick2').style.visibility = get_vis(messages_inputs[ind].nick2);
		gebi('td2nick2').style.visibility = get_vis(messages_inputs[ind].nick2);
	}

	</script>
	<?
	$cont = ob_get_contents();
	ob_end_clean();
	return $cont;
}

?>
