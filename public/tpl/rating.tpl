<?  # $Id: rating.tpl,v 1.18 2010-01-15 09:50:12 p.knoblokh Exp $
require_once("lib/rating.lib");
require_once("tpl/common.tpl");


function tplShowRatingBox($show_options = array('people', 'klans')) {
	global $kind_info;
	?>

<script type="text/javascript">
	var index_current_stat_type = 'rating_rating';
	var index_current_stat_type_clan = 'rating_rating';
	var index_current_kind_id = 'all';
	var index_current_kind_id_clan = 'all';
	var index_stat_type_list = new Array();
	function select_rating(kind_id, stat_type, rating_type) {
		rating_type = rating_type || '';
		if (stat_type) {
			if (rating_type == '_clan') {
				add_str = index_current_stat_type_clan + '_clan';
				index_current_stat_type_clan = stat_type;
			} else {
				add_str = index_current_stat_type;
				index_current_stat_type = stat_type;
			}
			var ClassName = document.getElementById('r_type_' + add_str).className;
			document.getElementById('r_type_' + add_str).className = ClassName.replace("-active", "");
			document.getElementById('r_type_' + stat_type + rating_type).className += '-active';
		} else {
			stat_type = (rating_type == "_clan") ? index_current_stat_type_clan : index_current_stat_type;
		}
		if (kind_id) {
			if (rating_type == '_clan') {
				add_str = index_current_kind_id_clan + '_clan';
				index_current_kind_id_clan = kind_id;
			} else {
				add_str = index_current_kind_id;
				index_current_kind_id = kind_id;
			}
			var ClassName = document.getElementById('r_kind_' + add_str).className;
			document.getElementById('r_kind_' + add_str).className = ClassName.replace("-active", "");
			document.getElementById('r_kind_' + kind_id + rating_type).className += '-active';
		} else {
			kind_id = (rating_type == "_clan") ? index_current_kind_id_clan : index_current_kind_id;
		}
		var index_list = (rating_type == '_clan') ? index_stat_type_list_clan : index_stat_type_list;
		for (var i = 0; i < index_list.length; ++i) {
			stype = index_list[i];
			document.getElementById(stype + rating_type + "_all").style.display = "none";
			document.getElementById(stype + rating_type + "_kind_1").style.display = "none";
			document.getElementById(stype + rating_type + "_kind_2").style.display = "none";
		}
		var elid = stat_type + rating_type + "_" + kind_id;
		document.getElementById(elid).style.display = "block";
	}
</script>

<? tpl_shablon_function(); ?>

<table align="center" width="236" border="0" cellspacing="0" cellpadding="0">

<? if (in_array('people', $show_options)) { ?>

		<tr height="22">
			<td width="20" align="right" valign="bottom" class="tbl-shp-sml lt"><b></b></td>
			<td valign="top" align="center" class="tbl-shp-sml tt">
				<table border="0" cellspacing="0" cellpadding="0">
					<tr height="22">
						<td width="27" class="tbl-usi-hdr lc"><b></b></td>
						<td align="center" class="tbl-usi-hdr mbg" nowrap>
							<img src="images/icon-rating-player.png">
							<?=translate('Лучшие игроки')?>
						</td>
						<td width="27" class="tbl-usi-hdr rc"><b></b></td>
					</tr>
				</table>
			</td>
			<td width="20" align="left" valign="bottom" class="tbl-shp-sml rt"><b></b></td>
		</tr>
		<tr>
			<td class="tbl-shp-sides ls">&nbsp;</td>
			<td class="tbl-usi_bg" valign="top" align="center" style="" nowrap>
				<div id="ratings_container">
					<?
					// this is inner function
					function html_rating_div_str(&$data, $users_details, $add) {
						global $kind_info;
						$str = '<div ' . $add . '>';
						$str .= '<table class="coll w100 p2v brd2-all">';
						$i = 0;
						foreach ($data as $item) {
							$user = $users_details[$item['user_id']];
							//$user = user_get($item['user_id']);
							//$user['rank'] = $item['rank'];
							$kind_icon = '<img src="images/' . $kind_info[$user['kind']]['picture'] . '" width="14" height="15" alt="' . $kind_info[$user['kind']]['title'] . '">';
							$str .= '<tr class="' . (++$i % 2 ? 'bg_l' : '') . '"><td class="brd2-top brd2-bt b" nowrap>' . html_user_info_light($user, array('noprivate' => true, 'all_ranks' => true, 'nick_len' => 14, 'external' => true), $session = array()) . '</td><td class="brd2-top brd2-bt" width="14">' . $kind_icon . '</td></tr>';
						}
						$str .= '</table>';
						$str .= '</div>';
						return $str;
					}
					function html_rating_div_str_clan(&$data, $clan_details, $add) {
						global $kind_info, $honor_title_hash;
						$str = '<div ' . $add . '>';
						$str .= '<table class="coll w100 p2v brd2-all">';
						$i = 0;
						foreach ($data as $item) {
							$clan = $clan_details[$item['clan_id']];
							$clan['title'] = htmlspecialchars($clan['title']);
							$title_str = (mb_strlen($clan['title']) < 19) ? $clan['title'] : mb_substr($clan['title'], 0, 17) . '..';
							$rank = max(0, $item['stat_rank'] - 1);
							$level = max(1, $item['stat_level']);
							$rank_img = '<img height="13" border="0" align="absmiddle" width="13" title="' . $honor_title_hash[$rank + 1]['title'] . '" src="/images/ranks/rank' . $rank . '.gif">';
							$kind_icon = '<img src="images/' . $kind_info[$item['kind']]['picture'] . '" width="14" height="15" alt="' . $kind_info[$item['kind']]['title'] . '">';
							$str .= '<tr class="' . (++$i % 2 ? 'bg_l' : '') . '"><td class="brd2-top brd2-bt b" nowrap>' .
									'<a title="' . $clan['title'] . '" href="#" onClick="showClanInfo(' . $clan['id'] . '); return false;"><img src="' . PATH_IMAGE_CLANS . $clan['picture'] . '" border=0 width=13 height=13 align="absmiddle"></a>&nbsp;' . $rank_img . '&nbsp;<a><b>' . $title_str . '&nbsp[' . $level . ']</b></a>'
									. '&nbsp<a title="' . translate('Информация о клане') . '" onclick="showClanInfo(' . $clan['id'] . ');return false;" href="#"><img border="0" align="absmiddle" src="/images/player_info.gif"></a>'
									. '</td><td class="brd2-top brd2-bt" width="14">' . $kind_icon . '</td></tr>';
						}
						$str .= '</table>';
						$str .= '</div>';
						return $str;
					}

					$rating_stat["rating_rating"] = total_rating_user_all_list(false, " AND rate_rating < 200 ORDER BY rate_rating ASC");
					$rating_stat["rating_rate"] = total_rating_user_all_list(false, " AND rate_honor < 200 ORDER BY rate_honor ASC");
					$rating_stat["rating_exp"] = total_rating_user_all_list(false, " AND rate_exp < 200 ORDER BY rate_exp ASC");
					$rating_stat["rep_rating"] = total_rating_user_all_list(false, " AND rate_rep_rating < 200 ORDER BY rate_rep_rating ASC");
					$rating_stat["rating_achievement"] = total_rating_user_all_list(false, " AND rate_achievements < 200 ORDER BY rate_achievements ASC");

					$rating_stat_inf["rating_rating"] = array("rating", translate('Общий рейтинг'));
					$rating_stat_inf["rating_rate"] = array("valour", translate('Доблесть'));
					$rating_stat_inf["rating_exp"] = array("exp", translate('Опыт'));
					$rating_stat_inf["rep_rating"] = array("rep_rating", translate('Репутации'));
					$rating_stat_inf["rating_achievement"] = array("progress", translate('Достижения'));

					$style_display = "display: block;";
					$stat_type_script = "";

					//get all users by one request
					$uids = array();
					foreach ($rating_stat as $stat) {
						foreach ($stat as $user) {
							$uids[] = $user['user_id'];
						}
					}
					$users_data_raw = $uids ? user_list(array('id' => $uids)) : array();
					$users_data = array();
					foreach ($users_data_raw as $user) {
						$users_data[$user['id']] = $user;
					}

                    //Расставим кинды
                    foreach ($rating_stat["rating_rating"] as &$rating_user){
                        $rating_user['kind'] = $users_data[$rating_user['user_id']]['kind'];
                    }
                    foreach ($rating_stat["rating_rate"] as &$rating_user){
                        $rating_user['kind'] = $users_data[$rating_user['user_id']]['kind'];
                    }
                    foreach ($rating_stat["rating_exp"] as &$rating_user){
                        $rating_user['kind'] = $users_data[$rating_user['user_id']]['kind'];
                    }
                    foreach ($rating_stat["rep_rating"] as &$rating_user){
                        $rating_user['kind'] = $users_data[$rating_user['user_id']]['kind'];
                    }
                    foreach ($rating_stat["rating_achievement"] as &$rating_user){
                        $rating_user['kind'] = $users_data[$rating_user['user_id']]['kind'];
                    }

                    foreach ($rating_stat as $stat_type => $data_list) {
						// list for all races(kind)
						echo html_rating_div_str(array_slice($data_list, 0, 10), $users_data, " ID=\"$stat_type" . "_all\" style=\"$style_display\"");
						$style_display = "display: none;"; // Only first div will be visible
						$data_kind_list = make_hash($data_list, 'kind', true);
						foreach ($kind_info as $inf) { // render races
							$kind_id = $inf["id"];
							$data_kind = &$data_kind_list[$kind_id];
							if (!$data_kind) $data_kind = array();
							echo html_rating_div_str(array_slice($data_kind, 0, 10), $users_data, " ID=\"$stat_type" . "_kind_$kind_id\" style=\"$style_display\"");
						}
						if ($stat_type_script) $stat_type_script .= ",";
						$stat_type_script .= "'$stat_type'";
					}
					// for javascript select_rating functiom
					echo "<script>index_stat_type_list = new Array($stat_type_script);</script>";
					// render statistic type navigator images
					echo '<img src="images/d.gif" alt="" height="6"><br>';
					echo "\n";
					echo '<ul class="rating-types">';
					$i = 0;
					foreach ($rating_stat_inf as $stat_type => $inf) {
						//		echo '<img hi="1" title="'.htmlspecialchars($inf[1]).'" src="images/'.$inf[0].'" width="42" height="23" onClick="select_rating(false, \''.$stat_type.'\');">';
						$cls_active = $i == 0 ? '-active' : '';
						$i++;
						echo '<li id="r_type_' . $stat_type . '" class="' . $inf[0] . $cls_active . '" title="' . htmlspecialchars($inf[1]) . '"><a href="#' . $stat_type . '" onclick="select_rating(false, \'' . $stat_type . '\'); return false;">' . htmlspecialchars($inf[1]) . '</a></li>';
					}
					echo '</ul>';
					?>

					<ul class="rating-races">
						<li id="r_kind_all" class="all-active">
							<a href="#rating_rate_all" onclick="select_rating('all'); return false;"><?=translate('Общий');?></a>
						</li>
						<li id="r_kind_kind_1" class="hum">
							<a href="#rating_rate_kind_1" onclick="select_rating('kind_1'); return false;"><?=translate('Люди');?></a>
						</li>
						<li id="r_kind_kind_2" class="mag">
							<a href="#rating_rate_kind_2" onclick="select_rating('kind_2'); return false;"><?=translate('Древние');?></a>
						</li>
					</ul>
				</div>
			</td>
			<td class="tbl-shp-sides rs">&nbsp;</td>
		</tr>
		<tr height="18">
			<td width="20" align="right" valign="top" class="tbl-shp-sml lb"><b></b></td>
			<td class="tbl-shp-sml bb" valign="top" align="center">&nbsp;</td>
			<td width="20" align="left" valign="top" class="tbl-shp-sml rb"><b></b></td>
		</tr>

	<? } ?>
<? if (in_array('klans', $show_options)) { ?>

		<tr height="22">
			<td width="20" align="right" valign="bottom" class="tbl-shp-sml lt"><b></b></td>
			<td valign="top" align="center" class="tbl-shp-sml tt">
				<table border="0" cellspacing="0" cellpadding="0">
					<tr height="22">
						<td width="27" class="tbl-usi-hdr lc"><b></b></td>
						<td align="center" class="tbl-usi-hdr mbg" nowrap>
							<img src="images/icon-rating-clan.png">
							<?=translate('Лучшие кланы')?></td>
						<td width="27" class="tbl-usi-hdr rc"><b></b></td>
					</tr>
				</table>
			</td>
			<td width="20" align="left" valign="bottom" class="tbl-shp-sml rt"><b></b></td>
		</tr>
		<tr>
			<td class="tbl-shp-sides ls">&nbsp;</td>
			<td class="tbl-usi_bg" valign="top" align="center" style="" nowrap>
				<div id="ratings_container">
					<?

					$rating_stat_clan["rating_rating"] = rating_clan_list(false, " ORDER BY rating DESC, honor DESC");
					$rating_stat_clan["rating_rate"] = rating_clan_list(false, " ORDER BY honor DESC");
					$rating_stat_clan["rating_exp"] = rating_clan_list(false, " ORDER BY exp DESC, honor DESC");
					$rating_stat_clan["rep_rating"] = rating_clan_list(false, " ORDER BY rep_rating DESC, honor DESC");

					$rating_stat_inf_clan = array();
					$rating_stat_inf_clan["rating_rating"] = array("rating", translate('Общий'));
					$rating_stat_inf_clan["rating_rate"] = array("valour", translate('Доблесть'));
					$rating_stat_inf_clan["rating_exp"] = array("exp", translate('Опыт'));
					$rating_stat_inf_clan["rep_rating"] = array("rep_rating", translate('Репутации'));
					$rating_stat_inf_clan["blocked1"] = array("", '');
					$clan_ids = array();
					foreach ($rating_stat_clan as $stat) {
						foreach ($stat as $clan) {
							$clan_ids[] = $clan['clan_id'];
						}
					}

					global $honor_title_hash;
					$honor_title_hash = make_hash(clan_stat_artikul_level_list(array('clan_stat_artikul_id' => $honor_artikul['id'])), 'level');
					$clan_data = $clan_ids ? make_hash(clan_list(array('id' => $clan_ids), '', 'id, title, picture')) : array();

					$style_display = "display: block;";
					$stat_type_script = '';
					foreach ($rating_stat_clan as $stat_type => $data_list) {
						// list for all races(kind)
						$show_honor = ($stat_type == 'rating_rate');
						echo html_rating_div_str_clan(array_slice($data_list, 0, 10), $clan_data, " ID=\"$stat_type" . "_clan_all\" style=\"$style_display\"");
						$style_display = "display: none;"; // Only first div will be visible
						$data_kind_list = make_hash($data_list, 'kind', true);
						foreach ($kind_info as $inf) { // render races
							$kind_id = $inf["id"];
							$data_kind = &$data_kind_list[$kind_id];
							if (!$data_kind) $data_kind = array();
							echo html_rating_div_str_clan(array_slice($data_kind, 0, 10), $clan_data, " ID=\"$stat_type" . "_clan_kind_$kind_id\" style=\"$style_display\"");
						}
						if ($stat_type_script) $stat_type_script .= ",";
						$stat_type_script .= "'$stat_type'";
					}
					echo "<script>index_stat_type_list_clan = new Array($stat_type_script);</script>";
					// render statistic type navigator images
					echo '<img src="images/d.gif" alt="" height="6"><br>';
					echo "\n";
					echo '<ul class="rating-types">';
					$i = 0;
					foreach ($rating_stat_inf_clan as $stat_type => $inf) {
						$cls_active = $i == 0 ? '-active' : '';
						$i++;
						echo '<li id="r_type_' . $stat_type . '_clan" class="' . $inf[0] . $cls_active . '" title="' . htmlspecialchars($inf[1]) . '">
            <a href="#' . $stat_type . '_clan" onclick="' . (isset($rating_stat_clan[$stat_type]) ? 'select_rating(false, \'' . $stat_type . '\',\'_clan\');' : '') . '
            return false;">' . htmlspecialchars($inf[1]) . '</a></li>';
					}
					echo '</ul>';
					?>
					<ul class="rating-races">
						<li id="r_kind_all_clan" class="all-active">
							<a href="#rating_rate_all" onclick="select_rating('all',false,'_clan'); return false;"><?=translate('Общий');?></a>
						</li>
						<li id="r_kind_kind_1_clan" class="hum">
							<a href="#rating_rate_kind_1" onclick="select_rating('kind_1',false, '_clan'); return false;"><?=translate('Люди');?></a>
						</li>
						<li id="r_kind_kind_2_clan" class="mag">
							<a href="#rating_rate_kind_2" onclick="select_rating('kind_2',false, '_clan'); return false;"><?=translate('Древние');?></a>
						</li>
					</ul>
				</div>
			<td class="tbl-shp-sides rs">&nbsp;</td>
		</tr>
		<tr height="18">
			<td width="20" align="right" valign="top" class="tbl-shp-sml lb"><b></b></td>
			<td class="tbl-shp-sml bb" valign="top" align="center">&nbsp;</td>
			<td width="20" align="left" valign="top" class="tbl-shp-sml rb"><b></b></td>
		</tr>

	<? } ?>

</table>

<script type="text/javascript">
	ratings = document.getElementById('ratings_container');
	ratings.innerHTML = ratings.innerHTML.replace(/\$(\d+)\$/g, shablon_substitution);
</script>

<? }

function tplShowRatingBox2($show_options = array('people', 'klans')) {
    global $kind_info;
    ?>

    <script type="text/javascript">
        var index_current_stat_type = 'rating_rating';
        var index_current_stat_type_clan = 'rating_rating';
        var index_current_kind_id = 'all';
        var index_current_kind_id_clan = 'all';
        var index_stat_type_list = new Array();
        function select_rating(kind_id, stat_type, rating_type) {
            rating_type = rating_type || '';
            if (stat_type) {
                if (rating_type == '_clan') {
                    add_str = index_current_stat_type_clan + '_clan';
                    index_current_stat_type_clan = stat_type;
                } else {
                    add_str = index_current_stat_type;
                    index_current_stat_type = stat_type;
                }
                var ClassName = document.getElementById('r_type_' + add_str).className;
                document.getElementById('r_type_' + add_str).className = ClassName.replace("-active", "");
                document.getElementById('r_type_' + stat_type + rating_type).className += '-active';
            } else {
                stat_type = (rating_type == "_clan") ? index_current_stat_type_clan : index_current_stat_type;
            }
            if (kind_id) {
                if (rating_type == '_clan') {
                    add_str = index_current_kind_id_clan + '_clan';
                    index_current_kind_id_clan = kind_id;
                } else {
                    add_str = index_current_kind_id;
                    index_current_kind_id = kind_id;
                }
                var ClassName = document.getElementById('r_kind_' + add_str).className;
                document.getElementById('r_kind_' + add_str).className = ClassName.replace("-active", "");
                document.getElementById('r_kind_' + kind_id + rating_type).className += '-active';
            } else {
                kind_id = (rating_type == "_clan") ? index_current_kind_id_clan : index_current_kind_id;
            }
            var index_list = (rating_type == '_clan') ? index_stat_type_list_clan : index_stat_type_list;
            for (var i = 0; i < index_list.length; ++i) {
                stype = index_list[i];
                document.getElementById(stype + rating_type + "_all").style.display = "none";
                document.getElementById(stype + rating_type + "_kind_1").style.display = "none";
                document.getElementById(stype + rating_type + "_kind_2").style.display = "none";
            }
            var elid = stat_type + rating_type + "_" + kind_id;
            document.getElementById(elid).style.display = "block";
        }
    </script>

    <? tpl_shablon_function(); ?>

    <table align="center" width="236" border="0" cellspacing="0" cellpadding="0">

        <? if (in_array('people', $show_options)) { ?>

            <tr height="22">
                <td width="20" align="right" valign="bottom" class="tbl-shp-sml lt"><b></b></td>
                <td valign="top" align="center" class="tbl-shp-sml tt">
                    <table border="0" cellspacing="0" cellpadding="0">
                        <tr height="22">
                            <td width="27" class="tbl-usi-hdr lc"><b></b></td>
                            <td align="center" class="tbl-usi-hdr mbg" nowrap>
                                <img src="images/icon-rating-player.png">
                                <?=translate('Лучшие игроки')?>
                            </td>
                            <td width="27" class="tbl-usi-hdr rc"><b></b></td>
                        </tr>
                    </table>
                </td>
                <td width="20" align="left" valign="bottom" class="tbl-shp-sml rt"><b></b></td>
            </tr>
            <tr>
                <td class="tbl-shp-sides ls">&nbsp;</td>
                <td class="tbl-usi_bg" valign="top" align="center" style="" nowrap>
                    <div id="ratings_container">
                        <?
                        // this is inner function
                        function html_rating_div_str(&$data, $users_details, $add) {
                            global $kind_info;
                            $str = '<div ' . $add . '>';
                            $str .= '<table class="coll w100 p2v brd2-all">';
                            $i = 0;
                            foreach ($data as $item) {
                                $user = $users_details[$item['user_id']];
                                //$user = user_get($item['user_id']);
                                //$user['rank'] = $item['rank'];
                                $kind_icon = '<img src="images/' . $kind_info[$user['kind']]['picture'] . '" width="14" height="15" alt="' . $kind_info[$user['kind']]['title'] . '">';
                                $str .= '<tr class="' . (++$i % 2 ? 'bg_l' : '') . '"><td class="brd2-top brd2-bt b" nowrap>' . html_user_info_light($user, array('noprivate' => true, 'all_ranks' => true, 'nick_len' => 14, 'external' => true), $session = array()) . '</td><td class="brd2-top brd2-bt" width="14">' . $kind_icon . '</td></tr>';
                            }
                            $str .= '</table>';
                            $str .= '</div>';
                            return $str;
                        }
                        function html_rating_div_str_clan(&$data, $clan_details, $add) {
                            global $kind_info, $honor_title_hash;
                            $str = '<div ' . $add . '>';
                            $str .= '<table class="coll w100 p2v brd2-all">';
                            $i = 0;
                            foreach ($data as $item) {
                                $clan = $clan_details[$item['clan_id']];
                                $clan['title'] = htmlspecialchars($clan['title']);
                                $title_str = (mb_strlen($clan['title']) < 19) ? $clan['title'] : mb_substr($clan['title'], 0, 17) . '..';
                                $rank = max(0, $item['stat_rank'] - 1);
                                $level = max(1, $item['stat_level']);
                                $rank_img = '<img height="13" border="0" align="absmiddle" width="13" title="' . $honor_title_hash[$rank + 1]['title'] . '" src="/images/ranks/rank' . $rank . '.gif">';
                                $kind_icon = '<img src="images/' . $kind_info[$item['kind']]['picture'] . '" width="14" height="15" alt="' . $kind_info[$item['kind']]['title'] . '">';
                                $str .= '<tr class="' . (++$i % 2 ? 'bg_l' : '') . '"><td class="brd2-top brd2-bt b" nowrap>' .
                                    '<a title="' . $clan['title'] . '" href="#" onClick="showClanInfo(' . $clan['id'] . '); return false;"><img src="' . PATH_IMAGE_CLANS . $clan['picture'] . '" border=0 width=13 height=13 align="absmiddle"></a>&nbsp;' . $rank_img . '&nbsp;<a><b>' . $title_str . '&nbsp[' . $level . ']</b></a>'
                                    . '&nbsp<a title="' . translate('Информация о клане') . '" onclick="showClanInfo(' . $clan['id'] . ');return false;" href="#"><img border="0" align="absmiddle" src="/images/player_info.gif"></a>'
                                    . '</td><td class="brd2-top brd2-bt" width="14">' . $kind_icon . '</td></tr>';
                            }
                            $str .= '</table>';
                            $str .= '</div>';
                            return $str;
                        }

                        $rating_stat["rating_rating"] = total_rating_user_all_list(false, " AND rate_rating < 200 ORDER BY rate_rating ASC");
                        $rating_stat["rating_rate"] = total_rating_user_all_list(false, " AND rate_honor < 200 ORDER BY rate_honor ASC");
                        $rating_stat["rating_exp"] = total_rating_user_all_list(false, " AND rate_exp < 200 ORDER BY rate_exp ASC");
                        $rating_stat["rep_rating"] = total_rating_user_all_list(false, " AND rate_rep_rating < 200 ORDER BY rate_rep_rating ASC");
                        $rating_stat["rating_achievement"] = total_rating_user_all_list(false, " AND rate_achievements < 200 ORDER BY rate_achievements ASC");

                        $rating_stat_inf["rating_rating"] = array("rating", translate('Общий рейтинг'));
                        $rating_stat_inf["rating_rate"] = array("valour", translate('Доблесть'));
                        $rating_stat_inf["rating_exp"] = array("exp", translate('Опыт'));
                        $rating_stat_inf["rep_rating"] = array("rep_rating", translate('Репутации'));
                        $rating_stat_inf["rating_achievement"] = array("progress", translate('Достижения'));

                        $style_display = "display: block;";
                        $stat_type_script = "";

                        //get all users by one request
                        $uids = array();
                        foreach ($rating_stat as $stat) {
                            foreach ($stat as $user) {
                                $uids[] = $user['user_id'];
                            }
                        }
                        $users_data_raw = $uids ? user_list(array('id' => $uids)) : array();
                        $users_data = array();
                        foreach ($users_data_raw as $user) {
                            $users_data[$user['id']] = $user;
                        }

                        //Расставим кинды
                        foreach ($rating_stat["rating_rating"] as &$rating_user){
                            $rating_user['kind'] = $users_data[$rating_user['user_id']]['kind'];
                        }
                        foreach ($rating_stat["rating_rate"] as &$rating_user){
                            $rating_user['kind'] = $users_data[$rating_user['user_id']]['kind'];
                        }
                        foreach ($rating_stat["rating_exp"] as &$rating_user){
                            $rating_user['kind'] = $users_data[$rating_user['user_id']]['kind'];
                        }
                        foreach ($rating_stat["rep_rating"] as &$rating_user){
                            $rating_user['kind'] = $users_data[$rating_user['user_id']]['kind'];
                        }
                        foreach ($rating_stat["rating_achievement"] as &$rating_user){
                            $rating_user['kind'] = $users_data[$rating_user['user_id']]['kind'];
                        }

                        foreach ($rating_stat as $stat_type => $data_list) {
                            // list for all races(kind)
                            echo html_rating_div_str(array_slice($data_list, 0, 10), $users_data, " ID=\"$stat_type" . "_all\" style=\"$style_display\"");
                            $style_display = "display: none;"; // Only first div will be visible
                            $data_kind_list = make_hash($data_list, 'kind', true);
                            foreach ($kind_info as $inf) { // render races
                                $kind_id = $inf["id"];
                                $data_kind = &$data_kind_list[$kind_id];
                                if (!$data_kind) $data_kind = array();
                                echo html_rating_div_str(array_slice($data_kind, 0, 10), $users_data, " ID=\"$stat_type" . "_kind_$kind_id\" style=\"$style_display\"");
                            }
                            if ($stat_type_script) $stat_type_script .= ",";
                            $stat_type_script .= "'$stat_type'";
                        }
                        // for javascript select_rating functiom
                        echo "<script>index_stat_type_list = new Array($stat_type_script);</script>";
                        // render statistic type navigator images
                        echo '<img src="images/d.gif" alt="" height="6"><br>';
                        echo "\n";
                        echo '<ul class="rating-types">';
                        $i = 0;
                        foreach ($rating_stat_inf as $stat_type => $inf) {
                            //		echo '<img hi="1" title="'.htmlspecialchars($inf[1]).'" src="images/'.$inf[0].'" width="42" height="23" onClick="select_rating(false, \''.$stat_type.'\');">';
                            $cls_active = $i == 0 ? '-active' : '';
                            $i++;
                            echo '<li id="r_type_' . $stat_type . '" class="' . $inf[0] . $cls_active . '" title="' . htmlspecialchars($inf[1]) . '"><a href="#' . $stat_type . '" onclick="select_rating(false, \'' . $stat_type . '\'); return false;">' . htmlspecialchars($inf[1]) . '</a></li>';
                        }
                        echo '</ul>';
                        ?>

                        <ul class="rating-races">
                            <li id="r_kind_all" class="all-active">
                                <a href="#rating_rate_all" onclick="select_rating('all'); return false;"><?=translate('Общий');?></a>
                            </li>
                            <li id="r_kind_kind_1" class="hum">
                                <a href="#rating_rate_kind_1" onclick="select_rating('kind_1'); return false;"><?=translate('Люди');?></a>
                            </li>
                            <li id="r_kind_kind_2" class="mag">
                                <a href="#rating_rate_kind_2" onclick="select_rating('kind_2'); return false;"><?=translate('Древние');?></a>
                            </li>
                        </ul>
                    </div>
                </td>
                <td class="tbl-shp-sides rs">&nbsp;</td>
            </tr>
            <tr height="18">
                <td width="20" align="right" valign="top" class="tbl-shp-sml lb"><b></b></td>
                <td class="tbl-shp-sml bb" valign="top" align="center">&nbsp;</td>
                <td width="20" align="left" valign="top" class="tbl-shp-sml rb"><b></b></td>
            </tr>

        <? } ?>
        <? if (in_array('klans', $show_options)) { ?>

            <tr height="22">
                <td width="20" align="right" valign="bottom" class="tbl-shp-sml lt"><b></b></td>
                <td valign="top" align="center" class="tbl-shp-sml tt">
                    <table border="0" cellspacing="0" cellpadding="0">
                        <tr height="22">
                            <td width="27" class="tbl-usi-hdr lc"><b></b></td>
                            <td align="center" class="tbl-usi-hdr mbg" nowrap>
                                <img src="images/icon-rating-clan.png">
                                <?=translate('Лучшие кланы')?></td>
                            <td width="27" class="tbl-usi-hdr rc"><b></b></td>
                        </tr>
                    </table>
                </td>
                <td width="20" align="left" valign="bottom" class="tbl-shp-sml rt"><b></b></td>
            </tr>
            <tr>
                <td class="tbl-shp-sides ls">&nbsp;</td>
                <td class="tbl-usi_bg" valign="top" align="center" style="" nowrap>
                    <div id="ratings_container">
                        <?

                        $rating_stat_clan["rating_rating"] = rating_clan_list(false, " ORDER BY rating DESC, honor DESC");
                        $rating_stat_clan["rating_rate"] = rating_clan_list(false, " ORDER BY honor DESC");
                        $rating_stat_clan["rating_exp"] = rating_clan_list(false, " ORDER BY exp DESC, honor DESC");
                        $rating_stat_clan["rep_rating"] = rating_clan_list(false, " ORDER BY rep_rating DESC, honor DESC");

                        $rating_stat_inf_clan = array();
                        $rating_stat_inf_clan["rating_rating"] = array("rating", translate('Общий'));
                        $rating_stat_inf_clan["rating_rate"] = array("valour", translate('Доблесть'));
                        $rating_stat_inf_clan["rating_exp"] = array("exp", translate('Опыт'));
                        $rating_stat_inf_clan["rep_rating"] = array("rep_rating", translate('Репутации'));
                        $rating_stat_inf_clan["blocked1"] = array("", '');
                        $clan_ids = array();
                        foreach ($rating_stat_clan as $stat) {
                            foreach ($stat as $clan) {
                                $clan_ids[] = $clan['clan_id'];
                            }
                        }

                        global $honor_title_hash;
                        $honor_title_hash = make_hash(clan_stat_artikul_level_list(array('clan_stat_artikul_id' => $honor_artikul['id'])), 'level');
                        $clan_data = $clan_ids ? make_hash(clan_list(array('id' => $clan_ids), '', 'id, title, picture')) : array();

                        $style_display = "display: block;";
                        $stat_type_script = '';
                        foreach ($rating_stat_clan as $stat_type => $data_list) {
                            // list for all races(kind)
                            $show_honor = ($stat_type == 'rating_rate');
                            echo html_rating_div_str_clan(array_slice($data_list, 0, 10), $clan_data, " ID=\"$stat_type" . "_clan_all\" style=\"$style_display\"");
                            $style_display = "display: none;"; // Only first div will be visible
                            $data_kind_list = make_hash($data_list, 'kind', true);
                            foreach ($kind_info as $inf) { // render races
                                $kind_id = $inf["id"];
                                $data_kind = &$data_kind_list[$kind_id];
                                if (!$data_kind) $data_kind = array();
                                echo html_rating_div_str_clan(array_slice($data_kind, 0, 10), $clan_data, " ID=\"$stat_type" . "_clan_kind_$kind_id\" style=\"$style_display\"");
                            }
                            if ($stat_type_script) $stat_type_script .= ",";
                            $stat_type_script .= "'$stat_type'";
                        }
                        echo "<script>index_stat_type_list_clan = new Array($stat_type_script);</script>";
                        // render statistic type navigator images
                        echo '<img src="images/d.gif" alt="" height="6"><br>';
                        echo "\n";
                        echo '<ul class="rating-types">';
                        $i = 0;
                        foreach ($rating_stat_inf_clan as $stat_type => $inf) {
                            $cls_active = $i == 0 ? '-active' : '';
                            $i++;
                            echo '<li id="r_type_' . $stat_type . '_clan" class="' . $inf[0] . $cls_active . '" title="' . htmlspecialchars($inf[1]) . '">
            <a href="#' . $stat_type . '_clan" onclick="' . (isset($rating_stat_clan[$stat_type]) ? 'select_rating(false, \'' . $stat_type . '\',\'_clan\');' : '') . '
            return false;">' . htmlspecialchars($inf[1]) . '</a></li>';
                        }
                        echo '</ul>';
                        ?>
                        <ul class="rating-races">
                            <li id="r_kind_all_clan" class="all-active">
                                <a href="#rating_rate_all" onclick="select_rating('all',false,'_clan'); return false;"><?=translate('Общий');?></a>
                            </li>
                            <li id="r_kind_kind_1_clan" class="hum">
                                <a href="#rating_rate_kind_1" onclick="select_rating('kind_1',false, '_clan'); return false;"><?=translate('Люди');?></a>
                            </li>
                            <li id="r_kind_kind_2_clan" class="mag">
                                <a href="#rating_rate_kind_2" onclick="select_rating('kind_2',false, '_clan'); return false;"><?=translate('Древние');?></a>
                            </li>
                        </ul>
                    </div>
                <td class="tbl-shp-sides rs">&nbsp;</td>
            </tr>
            <tr height="18">
                <td width="20" align="right" valign="top" class="tbl-shp-sml lb"><b></b></td>
                <td class="tbl-shp-sml bb" valign="top" align="center">&nbsp;</td>
                <td width="20" align="left" valign="top" class="tbl-shp-sml rb"><b></b></td>
            </tr>

        <? } ?>

    </table>

    <script type="text/javascript">
        ratings = document.getElementById('ratings_container');
        ratings.innerHTML = ratings.innerHTML.replace(/\$(\d+)\$/g, shablon_substitution);
    </script>

<? }
?>