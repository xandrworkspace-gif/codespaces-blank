<? # $Id: common.tpl,v 1.36 2010-02-04 08:34:34 s.ignatenkov Exp $ 

require_once("/home/admin/web/dwar.fun/public_html/lib/artifact.lib");
require_once("/home/admin/web/dwar.fun/public_html/lib/bot.lib");
require_once("/home/admin/web/dwar.fun/public_html/lib/skill.lib");
require_once("/home/admin/web/dwar.fun/public_html/tpl/artifact.tpl");
require_once("/home/admin/web/dwar.fun/public_html/lib/levelup_resource.lib");

// Замена общих тегов в тексте
// Теги:
// #USER[id]#
// #BOT[id]#
// #ARTIFACT[id]#
// #ARTIFACT[id,title]#
// #ARTIFACT_IMG[id]#
// #ARTIFACT_IMG[id,title]#
// #ARTIFACT_IMG[id,title,num]#
// #ARTIFACT_IMG[id,num]#
// #MONEY[type,amount]#
// #MAP[name,title,is_underline]#
// #ARG[num]#
// #CHAR[id]#
//
// $param = array(
//      under ---   подчеркивать макросы
//      alt ------- выводить альт
// )
function tpl_common_tags($text,$param=array()) {
	global $session_user,$quality_info;
	if (!preg_match_all("/#[^#<>]+#/",$text,$matches)) return $text;
	$alt = $param['alt'];
	$args = func_get_args();
	$tags = $matches[0];
	foreach ($tags as $tag) {
		if (!preg_match("/#(\w+)(\[(.+)\])?#/",$tag,$matches)) continue;
		$name = strval($matches[1]);
		$params = $matches[3] ? explode(',',strval($matches[3])) : array();
		if ($name == 'USER') {
			if(!$params[0]) $params[0]=$param['user_id'];
			$text = str_replace($tag,'<span class="underline">'.html_user_info(user_get($params[0])).'</span>',$text);
		} elseif ($name == 'BOT') {
			$bot = bot_get_info(false,$params[0]);
			$div = '';
			$attr = '';
			if ($alt) {
				$pic = PATH_IMAGE_BOTS.$bot['picture'];
				$uid_bot = 'bot_'.$params[0].rand(1,1000000);
				$div .= '<div id="'.$uid_bot.'" style="position:absolute; display:none;"><img src="'.$pic.'" border="0"></div>';
				$attr .= 'onMouseOver="ShowDiv(this,event,2);return false;" onMouseOut="ShowDiv(this,event,0);return false;" div_id="'.$uid_bot.'"';
			}
			$text = str_replace($tag,'<span class="underline">'.$div.'<span '.$attr.' >'.html_bot_info($bot,false,$params[1]).'</span></span>',$text);
		} elseif ($name == 'ARTIFACT') {
			$attr = '';
			$div=''	;
				if ($alt) {
					$artikul_hash=artifact_artikul_list(array('id'=>$params[0]));
					foreach ($artikul_hash as $k=>$artikul) {$artikul['artikul_id']=$params[0];}
					tpl_artifact_alt_prepare($artikul_hash,OBJECT_CLASS_ARTIFACT_ARTIKUL);
					$uid_artifact = 'artifact_'.$params[0].rand(1,1000000);
					$artikul=array_shift($artikul_hash);
					tpl_artifact_alt($artikul,false,$uid_artifact);
					$attr .= 'onMouseOver="ShowDiv(this,event,2);return false;" onMouseOut="ShowDiv(this,event,0);return false;" div_id="'.$uid_artifact.'"';
				} else{
					$artikul = artifact_artikul_get($params[0]);
				}
				if(!$params[1]) $params[1]=$artikul["title"]; 
				$text = str_replace($tag,'<span class="underline"><a href="#" '.$attr.' style="color:'.$quality_info[$artikul['quality']]['color'].';" onClick="showArtifactInfo(false,'.$params[0].');return false;">'.$params[1].'</a></span>',$text);
		} elseif ($name == 'ARTIFACT_IMG') {
			$artikul_id = $params[0];
			$title = $params[1];
			$num = max(0, intval($params[2]));
			if (is_numeric($title) && !$num) {
				$num = $title;
				$title = '';
			}
			$artikul = artifact_artikul_get(array('id' => $artikul_id));
			if (!$artikul) {
				$text = str_replace($tag, '', $text);
			} else {
				$artikuls = array($artikul);
				tpl_artifact_alt_prepare($artikuls, OBJECT_CLASS_ARTIFACT_ARTIKUL);
				$uid_artifact = 'artifact_'.$artikul_id.'_'.rand(1,1000000);
				$artikul = array_shift($artikuls);
				if($param['alt_simple']){
                    $div = tpl_artifact_alt($artikul, array('mode' => 'return'));
                }else{
                    $div = tpl_artifact_alt($artikul, array('mode' => 'return'), $uid_artifact);
                }
				if (!$title)
					$title = $artikul['title'];
				$attr = ' cnt="'.$num.'" onMouseOver="'.($param['alt_simple'] ? 'artifactAltSimple('.$artikul['id'].', 2, event)' : 'artifactAlt(this, event, 2)').';" onMouseOut="'.($param['alt_simple'] ? 'artifactAltSimple('.$artikul['id'].', 0, event)' : 'artifactAlt(this, event, 0)').';" div_id="'.$uid_artifact.'"'.
					'style="cursor: pointer; position: relative; z-index: 1;"';
				$v = ' id="AA_'.$artikul['id'].'" aid="art_'.$artikul['id'].'" style="display: inline-block;" ';
				$div .= '<table '.($param['rt_simple'] ? $v : 'id="'.$uid_artifact.'"').' '.($param['alt_simple'] ? 'class="superman" style=""' : '').' div_id width="60" height="60" cellpadding="0" cellspacing="0" border="0" class="artifact-table" background="'.PATH_IMAGE_ARTIFACTS.$artikul['picture'].'">'.
					'<tr><td '.$attr.' onClick="showArtifactInfo(0, '.$artikul_id.'); return false;">';
				if ($num > 1)
					$div .= '<div class="bpdig">'.$num.'</div>';
				$div .= '</td></tr></table>';
				$text = str_replace($tag, $div, $text);
			}
		} elseif ($name == 'MONEY') {
			$text = str_replace($tag,'<span class="underline">'.html_money_str($params[0],$params[1]).'</span>',$text);
		} elseif ($name == 'MAP') {
			$attr='';
			$div='';	
			if ($alt) {
				$area=area_get(array("title"=>$params[0]));
				$pic=PATH_SWF_AREAS.str_replace(".swf",".jpg",$area["swf"]);
				if (!$area) {
					$area=npc_get(array("title"=>$params[0]));
					$pic=PATH_IMAGE_NPCS.$area["picture"];
				}
				$uid_map='map_'.rand(1,1000000);
				$div='<div id="'.$uid_map.'"  style="position:absolute; display:none;"><img src="'.$pic.'" border="0"></div>';
				$attr= 'onMouseOver="ShowDiv(this,event,2);return false;" onMouseOut="ShowDiv(this,event,0);return false;" div_id="'.$uid_map.'"';
			}
			$text = str_replace($tag,'<span class="underline">'.$div.'<a href="#" '.($params[2] ? 'style="text-decoration: underline;" ' : '').$attr.'onClick="'.htmlspecialchars('showMsg(\'navigator.php?name='.common_java_escape($params[0]).'\',\''.translate('Навигатор').'\',560,423);return false;').'">'.($params[1] ? $params[1] : $params[0]).'</a></span>',$text);
		} elseif ($name == 'ARG') {
			$text = str_replace($tag,$args[$params[0]+2],$text);
		} elseif ($name == 'CHAR') {
			global $session_user;
			$user_id = $session_user['id'] ? $session_user['id'] : $param['user_id'];
			if ($user_id) {
				$skill_hash = user_get_skill_info($user_id, $params[0]);
			} else {
				$skill_hash['skills'][$params[0]]['value'] = 0;
			}
			$text = str_replace($tag,intval($skill_hash['skills'][$params[0]]['value']),$text);
		} elseif ($name == 'STAT') {
			global $session_user;
			$type_id = intval($params[0]);
			$object_id = intval($params[1]);
			if ($session_user['id']) {
				$stat = user_stat_get(array('user_id' => $session_user['id'], 'type_id' => $type_id, 'object_id' => $object_id));
			} else {
				$stat['value'] = 0;
			}
			$text = str_replace($tag, intval($stat['value']), $text);
		} elseif( $name == 'ACHIEVEMENT'){
		    $achievement = achievement_get(intval($params[0]));
		    if($achievement){
		        $repl = '<img style="cursor:pointer;" onclick="showAchievementInfo('.$achievement['id'].');" src="/'.PATH_IMAGE_ACHIEVEMENTS.$achievement['picture'].'">';
                $text = str_replace($tag,$repl,$text);
            }
        }
	}
	if (!$param['under']) {
		$text = str_replace('<span class="underline">','<span>',$text);
	}
	return $text;
}
function admin_common_tpl_desc() {
	?>
	<pre style="font-family: Verdana,Helvetica,Arial;">
#USER[id]#

#MAP[name,title]#
#MAP[name,title,is_underline]#

#ARTIFACT[id]#
#ARTIFACT[id,title]#

#ARTIFACT_IMG[id]#
#ARTIFACT_IMG[id,title]#
#ARTIFACT_IMG[id,num]#
#ARTIFACT_IMG[id,title,num]#

#MONEY[type,amount]#

#BOT[id]#
#BOT[id,title]#

#CHAR[skill_id]#

#STAT[type_id]#
#STAT[type_id,object_id]#
</pre><br>
	<?
}

// Замена общих тегов в тексте
// Теги:
// #NICK#
// #LEVEL#
// #KIND#

function tpl_common_fbtags($text,$user) {
	global $kind_info;
	if (!preg_match_all("/#[^#<>]+#/",$text,$matches)) return $text;
	$tags = $matches[0];
	foreach ($tags as $tag) {
		if (!preg_match("/#(\w+)(\[(.+)\])?#/",$tag,$matches)) continue;
		$name = strval($matches[1]);
		$params = $matches[3] ? explode(',',strval($matches[3])) : array();
		if ($name == 'NICK') {
			$text = str_replace($tag,$user['nick'],$text);
		} elseif ($name == 'LEVEL') {
			$text = str_replace($tag,$user['level'],$text);
		} elseif ($name == 'KIND') {
			$text = str_replace($tag,$kind_info[$user['kind']]['title'],$text);
		}
	}
	return $text;
}

function tpl_shablon_function()
{
	global $MENTOR_URL, $POSITIVE_URL, $MARRIAGE_URL, $JOKER_URL;
	?>
	<script type="text/javascript">
	function shablon_substitution($0,$1)
	{
		if(typeof shablon_substitution.shablons === 'undefined')
		{
			// static variable (xttp://www.tipstrs.com/tip/1084/Static-variables-in-Javascript)
			shablon_substitution.shablons = 
			[
				'<a href="#" onClick="userAttack(\'','\',\'\');return false;" title="<?=translate('Напасть')?>"><img src="/images/attack.gif" width="12" height="10" border=0 align="absmiddle"></a>&nbsp;',
				'<a href="#" onClick="userPrvTag(\'',
				'\');return false;" title="<?=translate('Приватное сообщение')?>"><img src="/images/users-arrow.gif" border=0 width=12 height=10 align="absmiddle"></a>&nbsp;',
				'<img src="/images/ico_help.png" border=0 width=13 height=13 align="absmiddle">&nbsp;',
				'<img src="/images/ico_jok.png" border=0 width=13 height=13 align="absmiddle" title="<?=translate('Арлекин')?>">&nbsp;',
				'<img src="/images/ico_obryad.png" border=0 width=13 height=13 align="absmiddle" title="<?=translate('Обрядник')?>">&nbsp;',
				'<a href="#" onClick="showPunishmentInfo(\'',
				'\');return false;" title="<?=translate('Наложено проклятие')?>"><img src="/images/punishment.gif" border=0 width=13 height=13 align="absmiddle"></a>&nbsp;',
				"<a href=\"#\" onClick=\"showClanInfo('",
				"');return false;\" title=\"",
				'"><img src="',
				'" border=0 width=13 height=13 align="absmiddle"></a>&nbsp;',
				'<img src="/images/ranks/rank',
				'.gif" border=0 width=13 height=13 align="absmiddle" title="',
				'">&nbsp;',
				' style="color: #c49485;"',
				' onClick="userToTag(\'',
				'\');return false;" title="<?=translate('Персональное сообщение')?>" style="cursor:hand"',
				']</b></a>',
				'<?=translate('Проклятие молчания, причина: ')?>',
				'<?=translate('. Осталось ')?>',
				'<?=translate('Проклятие молчания. Осталось ')?>',
				'&nbsp;<span title="',
				'"><img src="/images/gag.gif" width="12" height="12" align="absmiddle"></span>',
				'&nbsp;<a href="#" onClick="showInjuryInfo(\'',
				'\');return false;" title="',
				'"><img src="/images/injury.gif" width="10" height="10" border="0" align="absmiddle"></a>',
				'&nbsp;<a href="#" onClick="showUserInfo(\'',
				'\');return false;" title="<?=translate('Информация о персонаже')?>"><img src="/images/player_info.gif" border=0 align="absmiddle"></a>',
				'<img src="/images/m_poludrag.png" border=0 width=13 height=13 align="absmiddle" title="<?=translate('Субдилер')?>">&nbsp;',
				'<img src="/images/farm.gif" width="10" height="10" border="0" align="absmiddle" title="<?=translate('До завершения действия осталось')?> ',
				'<img src="/images/ico_event.png" border=0 width=13 height=13 align="absmiddle" title="<?=translate('Балагур')?>">&nbsp;',
				'<img src="/images/positive.gif" border=0 width=13 height=13 align="absmiddle" title="<?=translate('ПозитиФФщик')?>">&nbsp;',
				'<img src="/images/ico_admin.png" border=0 width=13 height=13 align="absmiddle" title="<?=translate('Администратор')?>">&nbsp;',
				'&nbsp;<img src="/',	'" border=0 height=11 align="absmiddle">&nbsp;',
				'<img src="',
				'" border=0 width=13 height=13 align="absmiddle" title="',
				'">&nbsp;',
				'<img src="/images/feather.gif" border="0" width="13" height="13" align="absmiddle" title="<?=translate('Летописец');?>">&nbsp;', // $40$
                '<img class="pointer" onclick="', //41
                '" src="/images/youtubeic.png" align="absmiddle">&nbsp;', //42
			];
		}
		
		return shablon_substitution.shablons[$1];
	}	
	</script>
	<?	
}

function tpl_password_strength_function () {
    ?>
<script language="Javascript" type="text/javascript">

function password_strength (passwd) {
    var numeric = "0123456789";
    var lower = "<?=translate('abcdefghijklmopqrstuvwxyzабвгдеёжзийклмнопрстуфхцчшщъыьэюя')?>";
    var upper = "<?=translate('ABCDEFGHIJKLMOPQRSTUVWXYZАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ')?>";
    var signs = "~`!@#$%^&*+-=_|\\/()[]{}<>,.;:?\"\'";
    var strenth = ['', '<?=translate('Очень простой пароль')?>', '<?=translate('Простой пароль')?>', '<?=translate('Удовлетворительный пароль')?>', '<?=translate('Хороший пароль')?>', '<?=translate('Надежный пароль')?>'];
    password_complexity = 0;
    hasLower = false;
    hasUpper = false;
    hasNumeric = false;
    hasSigns = false;

    for (var i=0; i < passwd.length; i++) {
        if (lower.indexOf(passwd.charAt(i)) >= 0 && !hasLower) {
            hasLower = true;
        }
        if (upper.indexOf(passwd.charAt(i)) >= 0 && !hasUpper) {
            hasUpper = true;
        }
        if (numeric.indexOf(passwd.charAt(i)) >= 0 && !hasNumeric) {
            hasNumeric = true;
        }
        if (signs.indexOf(passwd.charAt(i)) >= 0 && !hasSigns) {
            hasSigns = true;
        }

    }

    if (hasLower) {
        password_complexity++;
    }
    if (hasUpper) {
        password_complexity++;
    }
    if (hasNumeric) {
        password_complexity++;
    }
    if (hasSigns) {
        password_complexity++;
    }
    if (passwd.length > 15) {
        password_complexity++;
    }
    if (passwd.length > 2 && passwd.length < 6) {
        if (password_complexity>2) {
            password_complexity = 2;
        }
    }
    if (passwd.length > 5 && passwd.length < 8) {
        if (password_complexity>3) {
            password_complexity = 3;
        }
    }

    document.getElementById('password_strength').innerHTML = strenth[password_complexity];

    return password_complexity;
}

function eq_passwords (p1id, p2val) {
    if (document.getElementById(p1id).value != p2val) {
        document.getElementById('new_passwords_eq').innerHTML = "<?=translate('Пароли не совпадают!')?>";
    } else {
        document.getElementById('new_passwords_eq').innerHTML = '';
    }
}
</script>
    <?
}

function tpl_special_popup($text, $param = array()) {
	if (!$text)	return false;
	
	$picture_src = $param['picture_src'] ? $param['picture_src'] : false;

	$button1_text = $param['button1_text'] ? $param['button1_text'] : false;
	$button1_action = $param['button1_action'] ? $param['button1_action'] : false;
	$button1_w = $param['button1_width'] ? $param['button1_width'] : '110px';

	$button2_text = $param['button2_text'] ? $param['button2_text'] : false;
	$button2_action = $param['button2_action'] ? $param['button2_action'] : false;
	$button2_w = $param['button2_width'] ? $param['button2_width'] : '110px';

	$button1_hidden_form = $param['button1_hidden_form'] ? $param['button1_hidden_form'] : false;

	$div_id = $param['div_id'] ? $param['div_id'] : false;
	?>
<div class="special-popup"<?=($div_id ? 'id="'.$div_id.'"' : ''); ?>>
	<div class="popup-bg"></div>
	<div class="popup-title"><?=translate('Сообщение'); ?></div>
	<div class="popup-pad">
		<? if ($picture_src) { ?>
		<div class="slot">
			<div class="slot-bg"></div>
			<img src="<?=PATH_IMAGE_ARTIFACTS.$picture_src; ?>" alt="" border="0">
		</div>
		<? } ?>
		<p><?=$text; ?></p>
		<br clear="all">
		<div class="btn-bar">
			<?	if ($button1_text) { ?>
					<b class="butt1 pointer" style="display: inline-block;">
					<b style="display: inline-block;">
					<?
						if ($button1_hidden_form && $button1_hidden_form['_action']) {
							$form_action = $button1_hidden_form['_action'];
							unset($button1_hidden_form['_action']);
							printf('<form action="%s" id="my_hidden_form" method="post">', htmlspecialchars($form_action));
							print html_hidden($button1_hidden_form);
							print '</form>';
						}
						if ($button1_hidden_form) {
					?>
						<input type="button" class="redd" onClick="javascript:gebi('my_hidden_form').submit();changeDivDisplay(<?=($div_id ? "'$div_id'" : 'false'); ?>, 'none');" value="<?=$button1_text; ?>" style="width: <?=$button1_w; ?>;">
					<?
						} else {
					?>
						<input type="button" class="redd" onClick="javascript:changeDivDisplay(<?=($div_id ? "'$div_id'" : 'false'); ?>, 'none');<?=$button1_action; ?>" value="<?=$button1_text; ?>" style="width: <?=$button1_w; ?>;">
					<?
						}
					?>
					
					</b>
					</b>
			<?	}
				if ($button2_text) {
			?>
					<b class="butt1 pointer" style="display: inline-block;"><b style="display: inline-block;"><input type="button" class="redd" onClick="javascript:changeDivDisplay(<?=($div_id ? "'$div_id'" : 'false'); ?>, 'none');" value="<?=$button2_text; ?>" style="width: <?=$button2_w; ?>;"></b></b>
			<?	} ?>
		</div>
	</div>
</div>
	<?
}

function tpl_lucky_star_show_greeting() {
	global $session_user;
	require_once('lib/artifact.lib');

	if (!defined('LUCKY_STAR_ACTION') || !LUCKY_STAR_ACTION) {
		return false;
	}

	NODE_PUSH(null, $session_user['id']);
	// показывает pop-up от звезды удачи, если это не окончание боя в инстансе
	$query_add = sql_pholder(" AND (time_expire=0 OR time_expire>?)",time_current());
	$lucky_star_artifact = artifact_get(array('user_id' => $session_user['id'], 'artikul_id' => LUCKY_STAR_ARTIKUL_ID), $query_add);
	artifact_artikul_get_title($lucky_star_artifact);
	// и если не показывался уже (признак param2)
	if ($lucky_star_artifact && !$lucky_star_artifact['param2']) {
		$msg_text = sprintf(translate('
		Примите наши поздравления!
		Небеса благословили Вас и ниспослали величайший из даров - Звезду удачи!<br />
		Лишь <b class="red">раз в жизни</b> Вам выпадает шанс ощутить на себе таинство древней магии:
		используйте Звезду Удачи и сыграйте с банкиром, и в течение <b class="red">3 дней за следующий платеж</b>
		Вы сможете получить	<b class="red">в 1.5, 2 или даже 3 раза больше</b> %s бриллиантов или %s рубинов, чем приобретаете!<br />
		Срок жизни звезды - <b class="red">24 часа</b>. Не использовав шанс, Вы лишаетесь его навсегда.
		'), html_money_str(MONEY_TYPE_GOLD,1,array('currency_only' => true)), html_money_str(MONEY_TYPE_SILVER,1,array('currency_only' => true)));

		$play_game_action = action_object_get(OBJECT_CLASS_ARTIFACT, $lucky_star_artifact['id']);
		$button1_hidden_form = array(
			'_action' => 'action_run.php',
			'object_class' => OBJECT_CLASS_ARTIFACT,
			'object_id' => $lucky_star_artifact['id'],
			'action_id' => $play_game_action['id'],
			'url_success' => 'mini_game.php',
			'artifact_id' => $lucky_star_artifact['id'],
			'in[object_class]' => OBJECT_CLASS_ARTIFACT,
			'in[object_id]' => $lucky_star_artifact['id'],
			'in[action_id]' => $play_game_action['id'],
		);

		$msg_param = array(
			'div_id' => 'ls_msg',
			'picture_src' => $lucky_star_artifact['picture'],
			'button1_text' => translate('Играть'),
			'button1_action' => "location.href = 'user.php?mode=personage&submode=backpack&group=2';",
			'button2_text' => translate('Закрыть'),
			'button1_hidden_form' => $button1_hidden_form,
		);

		tpl_special_popup($msg_text, $msg_param);
		?>
			<script>
				if (gebi('<?=$msg_param['div_id']; ?>')) {
					changeDivDisplay('<?=$msg_param['div_id']; ?>', 'block');
				}
			</script>
		<?
	}
	NODE_POP();
}
/*
function tpl_user_chat_params_update($update_user_list=false) {
	global $session_user;
	$session = user_get_chat_session_state($session_user);
		
	?>
	<script type="text/javascript"> 
		try { 
			top.frames.chat.sessionUpdate(<?=json_encode($session)?>); 
			<? if ($update_user_list) { ?>
			top.frames.chat.chatRefreshUsers();		
			<? } ?>
		} catch(e) {  }
	</script>
	<?
}
*/
function tpl_levelup_popup($user) {
	global $session_user;
	ob_start();
	$user_level = $user['level'];
	$resources = make_hash(levelup_resource_list(array('level' => $user_level)));
	ksort($resources);

	?>
	<div style="position: absolute;  top: 0px; left:0px;  z-index: 10;" id="levelup_div">
		<table border="0" cellpadding="0" cellspasing="0" class="dialogWindow">
			<tr>
				<td class="levelup-left-top"><img src="images/levelup_top_left.png" class="levelup-top-left"></td>
				<td class="levelup_top_center"><div class="levelup_number"><?=$user_level;?></div><div class="levelup_text"><?=translate('уровень');?></div></td>
				<td><img src="images/levelup_top_right.png" class="levelup-top-right"></td>
			</tr>
			<tr>
				<td class="wind-left-repeat" ><div></div></td>
				<td class="sand-bg">
					<div class="levelup-innerdiv">
						<div class="levelup-abletext"><?=translate('Теперь вам доступно следующее:')?></div>
						<div class="levelup-content">
							<div class="levelup-content-inner">
							<?foreach($resources as $resource) {?>
								<div class="levelup-group-title"><?=$resource['title']?> <?if($resource['picture']):?><img src="<?=PATH_IMAGE_LEVELUP.$resource['picture']?>" title="<?=$resource['title']?>"><?endif;?> </div>
								<?if ($resource['type'] == LEVELUP_RESOURCE_TYPE_ARTIFACT) {
									$artifact_ids = unserialize($resource['content']);
									$artifact_artikul_hash = make_hash(artifact_artikul_list(array('id' => $artifact_ids)));

								?>
									<div class="levelup-group-content-pic">
										<?foreach ($artifact_artikul_hash as $artifact) {?>
											<img src="<?=PATH_IMAGE_ARTIFACTS.$artifact['picture'];?>" onMouseOver="artifactAlt(this,event,2)" onMouseOut="artifactAlt(this,event,0)" div_id="AA_<?=$artifact['id'];?>" onClick="showArtifactInfo(0, <?=$artifact['id'];?>); return false;" style="cursor: pointer;position:relative;z-index:1;" >
										<?}?>
										<div style="clear: left;"></div>

									</div>
							<?
								} elseif($resource['type'] == LEVELUP_RESOURCE_TYPE_TEXT) {?>
									<div class="levelup-group-content"><?=$resource['content'];?></div>
								<?}
							}?>
							</div>
						</div>
					<div class="levelup-button">
					<b onclick="gebi(\"levelup_div\").style.display = \"none\";" class="butt1 pointer">
						<b>
						<input class="redd" type="button" value="OK">
						</b>
					</b>
					</div>
					
					</div>
				</td>
				<td class="wind-right-repeat"></td>
			</tr>
			<tr>
				<td class="wind-left-bottom"></td>
				<td class="wind-bottom-repeat"></td>
				<td class="wind-right-bottom"></td>
			</tr>
		</table>
	</div>

	<?
return ob_get_clean();
}

function tpl_levelup_popup_alts($user) {
	global $session_user;
	ob_start();
	$user_level = $user['level'];
	$resources = levelup_resource_list(array('level' => $user_level));

	foreach($resources as $resource) {
		if ($resource['type'] == LEVELUP_RESOURCE_TYPE_ARTIFACT) {
			$artifact_ids = unserialize($resource['content']);
			$artifact_artikul_hash = make_hash(artifact_artikul_list(array('id' => $artifact_ids)));
			tpl_artifact_alt_prepare($artifact_artikul_hash, OBJECT_CLASS_ARTIFACT_ARTIKUL);
				foreach ($artifact_artikul_hash as $artifact) {
					tpl_artifact_alt($artifact);
				}
		}
	}
	$alts = strip_tags(ob_get_clean(),'<img><span><b>');
return $alts;
}


function tpl_systemConfirm_div() {
?>
    <div id="systemConfirm_close_div" class="error_div" class="btn_sys_confirm_close" style="display:none; z-index: 1000;"></div>
    <div class="popup_global_container" id="systemConfirm_div" style="z-index: 10010; position: absolute; display: none; width: 435px; top:0px; left: 0px;">
        <div class="popup-top-left">
            <div class="popup-top-right">
                <div class="popup-top-center">
                    <div class="popup_global_title" id="systemConfirm_title"></div>
                </div>
            </div>
            <div class="popup_global_close_btn" class="btn_sys_confirm_close"></div>
        </div>

        <div class="popup-left-center">
            <div class="popup-right-center">
                <div class="popup_global_content" style="padding: 20px;">
                    <div id="confirm_ms" style="text-align: center;">

                    </div>
                    <div style="text-align: center;">
                        <?=html_button('OK','butt1','submit','',array('add' => 'style="width:50px" ID="btnOk"'));?> <?=html_button(common_java_escape(translate('ОТМЕНА')),'butt1 btn_sys_confirm_close','button','',array('add' => 'style="width:60px"'));?>
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
<?
}

function tpl_common_achieve_table_top($param = array()) {
    ob_start();
    ?>
    <table cellspacing="0" cellpadding="0" border="0" class="achieve_info_table coll <?=$param['add_class'];?>" <?=$param['add'];?>>
                    <tbody><tr>
                        <td class="achieve_info_lt"><img src="/images/null.gif" width="10" height="10"></td>
                        <td class="achieve_info_tr"></td>
                        <td class="achieve_info_rt"><img src="/images/null.gif" width="10" height="10"></td>
                    </tr>
                    <tr>
                        <td class="achieve_info_lr"></td>
                        <td class="achieve_info_content" style="padding: 0;" valign="top">
<?php
    return ob_get_clean();
}

function tpl_common_achieve_table_bottom() {
    ob_start();
    ?>
    </td>
                        <td class="achieve_info_rr"></td>
                    </tr>
                    <tr>
                        <td class="achieve_info_lb"><img src="/images/null.gif" width="10" height="10"></td>
                        <td class="achieve_info_br"></td>
                        <td class="achieve_info_rb"><img src="/images/null.gif" width="10" height="10"></td>
                    </tr>
                    </tbody></table>
<?php
    return ob_get_clean();
}

function tpl_common_achieve2_table_top($param = array()) {
    ob_start();
    ?>
    <table cellspacing="0" cellpadding="0" border="0" width="100%" class="achieve_bg">
        <tr>
            <td class="achieve_bg_lt"><img src="/images/null.gif" width=10 height=10></td>
            <td class="achieve_bg_tr"></td>
            <td class="achieve_bg_rt"><img src="/images/null.gif" width=10 height=10></td>
        </tr>
        <tr>
            <td class="achieve_bg_lr"></td>
            <td>
    <?php
    return ob_get_clean();
}

    function tpl_common_achieve2_table_bottom($param = array()) {
    ob_start();
    ?>
            </td>
            <td class="achieve_bg_rr"></td>
        </tr>


        <tr>
            <td class="achieve_bg_lb"><img src="/images/null.gif" width=10 height=10></td>
            <td class="achieve_bg_br"></td>
            <td class="achieve_bg_rb"><img src="/images/null.gif" width=10 height=10></td>
        </tr>

    </table>
    <?php
    return ob_get_clean();
}
?>