<? # $Id: fight.tpl,v 1.24 2010-01-15 09:50:12 p.knoblokh Exp $

$c_lev = array(
    1 => '#239a2d',
    2 => '#2187b9',
    3 => '#ff0c0c',
);

function tpl_fight_print_team_summary(&$fight_user_list, $title, $user_id, $fight = array()) {
?>
						<table width="100%" border="0" cellspacing="0" cellpadding="0">
						  <tr height="22">
							<td width="20" align="right" valign="bottom"><img src="images/tbl-shp_sml-corner-top-left.gif" width="20" height="22" /></td>
							<td class="tbl-shp_sml-top" valign="top" align="center">
								<table border="0" cellspacing="0" cellpadding="0" >
									  <tr height="22">
											<td width="27"><img src="images/tbl-usi_label-left.gif" width="27" height="22"/></td>
											<td align="center" class="tbl-usi_label-center"><?=$title;?></td>
											<td width="27"><img src="images/tbl-usi_label-right.gif" width="27" height="22"/></td>
									  </tr>
								</table>
							</td>
							<td width="20" align="left" valign="bottom"><img src="images/tbl-shp_sml-corner-top-right.gif" width="20" height="22"/></td>
							</tr>
						  <tr>
									<td class="tbl-usi_left">&nbsp;</td>
									<td class="tbl-usi_bg" valign="top" align="center" style="padding: 10 10 16 10">
										<?tpl_fight_print_team_summary_inner($fight_user_list,false, $fight)?>
									</td>
									<td class="tbl-usi_right">&nbsp;</td>
						  </tr>
						  <tr height="18">
									<td width="20" align="right" valign="top"><img src="images/tbl-shp_sml-corner-bottom-left.gif" width="20" height="18" /></td>
									<td class="tbl-shp_sml-bottom" valign="top" align="center">&nbsp;   </td>
									<td width="20" align="left" valign="top"><img src="images/tbl-shp_sml-corner-bottom-right.gif" width="20" height="18"/></td>
						  </tr>
						</table>
<?
}
function tpl_fight_print_team_summary_inner(&$fight_user_list, $is_blind = false, $fight = array()) {
	global $mode_url, $c_lev;
?>
		<table class="coll w100 p6h p2v">
		<thead>
			<tr class="b th-sortable" align="center">
                <?$i=0;
                if($fight['type'] == FIGHT_TYPE_CHAOTIC){
                    $c_num=1;?>
                    <td><?=translate('Место');?></td>
                <?}?>
                <th data-sort-col="<?=($fight['type'] == FIGHT_TYPE_CHAOTIC ? ++$i : $i++);?>" data-sort-type="string">Игрок</th>
                <th data-sort-col="<?=($fight['type'] == FIGHT_TYPE_CHAOTIC ? ++$i : $i++);?>" data-sort-type="number" style="background: url('/images/cell-arr-desc.png') 100% 50% no-repeat">Опыт</th>
                <th data-sort-col="<?=($fight['type'] == FIGHT_TYPE_CHAOTIC ? ++$i : $i++);?>" data-sort-type="number">Урон</th>
                <th data-sort-col="<?=($fight['type'] == FIGHT_TYPE_CHAOTIC ? ++$i : $i++);?>" data-sort-type="number">Убийств</th>
                <th></th>
                <th></th>
			</tr>
		</thead>
		<tbody class="brd2">
		<?
            $user_hash = array();
			foreach ($fight_user_list as $fight_user) {
				$html_nick = '';
                $html_sort_nick = '';
				if ($fight_user['user_id']) {
					$user = cache_fetch($user_hash, $fight_user['user_id'], 'user_get'); //Чтобы не повторяться
					
					$user_invisible = $fight_user['flags'] & FIGHT_USER_FLAG_INVISIBLE || $is_blind;
					$show_icons = !($fight_user['flags'] & FIGHT_USER_FLAG_INVISIBLE);
					$html_nick = $user_invisible ? html_user_info_blind($show_icons) : html_user_info($user,array('url_add' => 'class="redd" id="UID'.$user['id'].'"', 'nick_len' => 15));
                    $html_sort_nick = $user_invisible ? 'Невидимка' : $user['nick'];
				} elseif ($fight_user['bot_id'] && $fight_user['companion_id']) {
					$companion = common_object_get(OBJECT_CLASS_COMPANION, $fight_user['companion_id']);
                    $user = cache_fetch($user_hash, $companion['user_id'], 'user_get');
					$html_nick = html_companion_info($companion, $user, array('fight_user_id' => $fight_user['id'],'nick_len' => 15));
                    $html_sort_nick = 'Тень - '.$user['nick'];
				} elseif ($fight_user['bot_id']) {
                    $bot = bot_get_info($fight_user['bot_id'],$fight_user['bot_artikul_id']);
                    $html_nick = html_bot_info($bot, array('nick_len' => 15));
                    $html_sort_nick = $bot['nick'];
                } else continue;
				list($exp,$dmg,$heal,$killCnt,$honor) = explode(':',$fight_user['data']);
				$flags = '';
				if ($fight_user['flags'] & FIGHT_USER_FLAG_GOTLOOT) $flags .= '<a title="'.translate('Получены ценности').'"><img src="images/ff_loot.gif" width=12 height=12></a>';
				if ($fight_user['flags'] & FIGHT_USER_FLAG_GOTINJURY) $flags .= '<a title="'.translate('Нанесена травма').'"><img src="images/ff_injury.gif" width=12 height=12></a>';
		?>
			<tr class="<?=(++$i % 2 ? 'bg_l': '')?>" align="center">
                <?if($fight['type'] == FIGHT_TYPE_CHAOTIC){?>
                    <td class="b" data-sort-type="continue" style="<?=($c_lev[$c_num] ? 'color:'.$c_lev[$c_num].';' : '')?>"><?=($c_num <= 5 ? $c_num : '-');?></td>
                <?$c_num++;}?>
				<td data-sort="<?=$html_sort_nick;?>" align=left><?=$html_nick;?></td>
				<td data-sort="<?=intval($exp);?>" nowrap><span title="<?=translate('Боевой опыт');?>"><?=intval($exp);?></span> / <span class=redd title="<?=translate('Доблесть');?>"><?=intval($honor);?></span></td>
				<td data-sort="<?=intval($dmg);?>" nowrap><span class=redd title="<?=translate('Нанесенный урон');?>"><?=intval($dmg);?></span> / <span class=grnn title="<?=translate('Вылечено жизни');?>"><?=intval($heal);?></span></td>
				<td data-sort="<?=intval($killCnt);?>"><?=intval($killCnt);?></td>
				<td><?=$flags;?></td>
				<td align="center" width="<?=(($fight_user['killer_id'] && $fight_user['killer_bonus_id']) ? '30' : '12');?>">
				<? if ($fight_user['killer_id']) { ?>
				<script>
				try {
					if (user_id == <?=$fight_user['killer_id'];?> && user_id == <?=$user['trophy_id'];?>) { 
						document.write('<a href="<?=$mode_url.'&action=set_trophy&ref='.$fight_user['id'];?>" title="<?=translate('Отнять трофей');?>"><img src="images/killer.gif" width="12" height="13" border="0"></a>');
					} else if(user_id == <?=$fight_user['killer_id'];?>) {
						document.write('<a href="<?=$mode_url.'&action=set_trophy&ref='.$fight_user['id'];?>" title="<?=translate('Получить трофей');?>"><img src="images/killer.gif" width="12" height="13" border="0"></a>');
					}
				} catch (e) {}
				</script>
				<? } ?>
                <? if ($fight_user['killer_id'] && $fight_user['killer_bonus_id']) { ?>
                        <script>
                            try {
                                document.write('<a href="<?=$mode_url.'&action=get_killer_bonus&ref='.$fight_user['id'];?>" title="<?=translate('Обыскать игрока');?>"><img src="images/ff_pvploot.png" width="14" height="16" border="0"></a>');
                            } catch (e) {}
                        </script>
                 <? } ?>
				</td>
			</tr>
		<?
			}
		?>
		</tbody>
		</table>
<?
}
?>