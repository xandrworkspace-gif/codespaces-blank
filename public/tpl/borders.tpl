<? // $Id: $
// =========================================================================
// Coder..........: AKEB, GameCore
// Work started...: 24.10.2008 16:47:39
// =========================================================================


function tpl_border_outside($page = '',$width=100,$height=100,$align='center',$valign='middle',$style='') {
    $page = trim($page);
    if (!$page) return '';
    ob_start();
    ?>
    <table cellpadding="0" cellspacing="0" style="width: 100%; height: 100%;" class="fr_lft_dyn_back"><tr><td align="center" class="fr_left_back">
                <table cellpadding="0" cellspacing="0" style="width: 100%; height: 100%;" class="fr_right_back"><tr><td align="left" style="background: transparent url('images/main/top_center.gif') repeat-x top;">
                            <table cellpadding="0" cellspacing="0" style="width: 100%; height: 100%; background: transparent url('images/main/top_left.gif') no-repeat top left;"><tr><td align="left" style=" background: transparent url('images/main/top_right.gif') no-repeat top right;">
                                        <table cellpadding="0" cellspacing="0" style="width: 100%; height: 100%; background: transparent url('images/main/bottom_center.gif') repeat-x bottom;"><tr><td style="background: transparent url('images/main/bottom_left.gif') no-repeat bottom left; ">
                                                    <table cellpadding="0" cellspacing="0" width="100%" height="100%" style="background: transparent url('images/main/bottom_right.gif') no-repeat bottom right;">
                                                        <tr>
                                                            <td align="<?=$align;?>" valign="<?=$valign;?>" width="100%" height="100%" style="<?=$style;?> " id="window_blue">
                                                                <table cellpadding="0" cellspacing="0" width="<?=$width;?>" height="<?=$height;?>">
                                                                    <tr>
                                                                        <td height="9">
                                                                            <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                                                                <tr>
                                                                                    <td width="27" valign="top" align="left" background="images/main/b1_1.gif"><img src="images/d.gif" width="27" height="1"></td>
                                                                                    <td width="100%" valign="top" align="center" background="images/main/b1_2.gif"><img src="images/d.gif" width="1" height="1"></td>
                                                                                    <td width="27" valign="top" align="right" background="images/main/b1_3.gif"><img src="images/d.gif" width="27" height="1"></td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td width="100%" height="100%">
                                                                            <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                                                                <tr>
                                                                                    <td width="15" valign="top" align="left">
                                                                                        <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                                                                            <tr>
                                                                                                <td height="92" valign="top" align="left" background="images/main/b21_1.gif"><img src="images/d.gif" width="15" height="92"></td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td height="100%" valign="middle" align="left" background="images/main/b21_2.gif"><img src="images/d.gif" width="1" height="1"></td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td height="46" valign="bottom" align="left" background="images/main/b21_3.gif"><img src="images/d.gif" width="1" height="46"></td>
                                                                                            </tr>
                                                                                        </table>
                                                                                    </td>
                                                                                    <td width="<?=$width;?>" valign="top" align="left" height="<?=$height;?>" background="images/main/b22_1.gif"><?=$page;?></td>
                                                                                    <td width="" valign="top" align="right">
                                                                                        <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                                                                            <tr>
                                                                                                <td height="92" valign="top" align="left" background="images/main/b23_1.gif"><img src="images/d.gif" width="15" height="92"></td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td height="100%" valign="middle" align="left" background="images/main/b23_2.gif"><img src="images/d.gif" width="1" height="1"></td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td height="46" valign="bottom" align="left" background="images/main/b23_3.gif"><img src="images/d.gif" width="1" height="46"></td>
                                                                                            </tr>
                                                                                        </table>
                                                                                    </td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td height="17" background="images/main/b3_2.gif">
                                                                            <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                                                                <tr>
                                                                                    <td width="137" height="100%" valign="bottom" align="left" background="images/main/b3_1.gif"><img src="images/d.gif" width="137" height="1"></td>
                                                                                    <td width="50%"><img src="images/d.gif" width="1" height="1"></td>
                                                                                    <td width="196" height="100%" valign="bottom" align="center" background="images/main/b3_3.gif"><img src="images/d.gif" width="196" height="1"></td>
                                                                                    <td width="50%"><img src="images/d.gif" width="1" height="1"></td>
                                                                                    <td width="136" height="100%" valign="bottom" align="right" background="images/main/b3_4.gif"><img src="images/d.gif" width="136" height="1"></td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td></tr></table>
                                    </td></tr></table>
                        </td></tr></table>
            </td></tr></table>
    <?
    return ob_get_clean();
}

function tpl_border_outside2($page = '',$width=100,$height=100,$align='center',$valign='middle',$style='') {
    $page = trim($page);
    if (!$page) return '';
    ob_start();
    ?>
    <table  cellpadding="0" cellspacing="0" width="100%" height="100%" >
        <tr>
            <td align="<?=$align;?>" valign="<?=$valign;?>" width="100%" height="100%" style="<?=$style;?> border:0px solid red; background: transparent url('images/fr_lft_dyn_bg.jpg') repeat left bottom;" >
                <table cellpadding="0" cellspacing="0" width="<?=$width;?>" height="<?=$height;?>" style="border:0px solid green;" id="window_blue">
                    <tr>
                        <td height="9">
                            <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                <tr>
                                    <td width="27" class="b_el_2 b_el_2-lt"><b></b></td>
                                    <td width="100%" class="b_el_1 b_el_1-t"><b></b></td>
                                    <td width="27" class="b_el_2 b_el_2-rt"><b></b></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td width="100%" height="100%">
                            <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                <tr>
                                    <td width="15" valign="top" align="left" class="b_el_1-s-l-col">
                                        <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                            <tr>
                                                <td height="92" valign="top" align="left" class="b_el_1 b_el_1-lt"><b></b></td>
                                            </tr>
                                            <tr>
                                                <td height="100%" class="b_el_1-s-l"></td>
                                            </tr>
                                            <tr>
                                                <td height="46" valign="bottom" align="left" class="b_el_1 b_el_1-lb"><b></b></td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="100%" valign="top" align="left" height="100%" background="images/main/b22_1.gif"><?=$page;?></td>
                                    <td width="" valign="top" align="right" class="b_el_1-s-r-col">
                                        <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                            <tr>
                                                <td height="92" valign="top" align="left" class="b_el_1 b_el_1-rt"><b></b></td>
                                            </tr>
                                            <tr>
                                                <td height="100%" class="b_el_1-s-r"></td>
                                            </tr>
                                            <tr>
                                                <td height="46" valign="bottom" align="left" class="b_el_1 b_el_1-rb"><b></b></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td height="17" class="b_el_1-s-l-row">
                            <table cellpadding="0" cellspacing="0" width="100%" height="100%" class="b_el_1 b_el_1-b">
                                <tr>
                                    <td width="137" height="100%" valign="bottom" class="b_el_2 b_el_2-lb"><b></b></td>
                                    <td width="50%"><img src="images/d.gif" width="1" height="1"></td>
                                    <td width="196" height="100%" valign="bottom" align="center" class="b_el_2 b_el_2-c"><b></b></td>
                                    <td width="50%"><img src="images/d.gif" width="1" height="1"></td>
                                    <td width="136" height="100%" valign="bottom" align="right" class="b_el_2 b_el_2-rb"><b></b></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?
    return ob_get_clean();
}

function tpl_border_outside3($page = '',$mode_hash=array(),$mode='',$back_url=array(),$width='100%',$height='100%',$align='center',$valign='middle',$style='',$mode_url=false) {
    //global $mode;
    ob_start();
    ?>
    <style>
        .top_but_off {
            color:white;
            cursor:pointer;
            padding: 0px 3px 7px 3px;
            font-weight: bold;
            background: URL(images/main/top_but_off.gif);

        }

        .top_but_on {
            color:white;
            cursor:pointer;
            padding: 0px 3px 7px 3px;
            font-weight: bold;
            background: URL(images/main/top_but_on.gif);


        }
    </style>
    <table cellpadding="0" cellspacing="0" width="100%" height="100%" class="fr_lft_dyn_back_trans">
        <tr>
            <td align="<?=$align;?>" valign="<?=$valign;?>" width="100%" height="100%" style="<?=$style;?> border:0px solid red;" >
                <table cellpadding="0" cellspacing="0" width="<?=$width;?>" height="<?=$height;?>" style="border:0px solid white;">
                    <tr>
                        <td height="26" width="100%" colspan="3">
                            <table width="100%" height="100%" cellpadding="0" cellspacing="0" >
                                <tr>
                                    <td height="26" width="33"><img src="images/main/top_left_<?=(is_array($mode_hash) && count($mode_hash) > 0 ? 'on':'off');?>.gif" width="33" height="26" border="0"></td>
                                    <td height="26" background="images/main/top_bg.gif" width="100%">
                                        <div style="background: url(images/main/top_center_png.gif) no-repeat top center; width:100%; height:26px;">
                                            <?
                                            if ($mode_hash) {
                                                ?>
                                                <table width="100%" height="100%" cellpadding="0" cellspacing="0">
                                                    <tr>
                                                        <td align="left" valign="top" width="1%" height="26">
                                                            <table cellpadding="0" cellspacing="0" width="100%" height="26" >

                                                                <tr>
                                                                    <?
                                                                    foreach ($mode_hash as $k=>$v) {
                                                                        if ($mode_url == 'npc') {
                                                                            global $key, $f_id;
                                                                            $url_go = secure_url($key,'?f_id='.$f_id.'&npc_id='.$k);
                                                                        } elseif ($mode_url == 'bg') {
                                                                            global $session_user;
                                                                            $bg_area_id = $v['t'.$session_user['kind'].'_area_id'];
                                                                            $url_go = '?area_id='.$bg_area_id;
                                                                            $k = $bg_area_id;
                                                                        } else {
                                                                            $url_go = '?mode='.$k;
                                                                        }
                                                                        $prf = ($k == $mode ? 'on' : 'off');

                                                                        if (count($mode_hash) > 1) {
                                                                            ?>

                                                                            <?
                                                                        }
                                                                        ?>
                                                                        <td nowrap="nowrap" valign="middle" align="center" class="top_but_<?=$prf;?>" title="<?=$v['alt'];?>" onmouseover="this.className='top_but_on';" onmouseout="this.className='top_but_<?=$prf;?>';" onclick="location.href='<?=$url_go;?>';"><?=$v['title'];?></td>
                                                                        <? /*
																		<td width="1" height="22"><img src="images/main/top_but_e.gif" width="1" height="22"></td>
																		*/
                                                                        ?>
                                                                        <?
                                                                    }
                                                                    ?>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td width="1%" height="26"><img src="images/<?=(is_array($mode_hash) && count($mode_hash) > 0 ? 'main/top_left2.gif':'d.gif');?>" width="33" height="26" border="0"></td>
                                                        <td width="100%"  height="26">
                                                            <img src="images/d.gif" width="1" height="1">
                                                        </td>
                                                    </tr>
                                                </table>
                                                <?
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <?
                                    if ($back_url['url']) {
                                        ?>
                                        <td height="26" onclick="location.href='<?=$back_url['url'];?>';" align="right" width="1%" nowrap="nowrap" height="26" class="white b" valign="top" title="<?=$back_url['title'];?>">
                                            <a href="<?=$back_url['url'];?>">
                                                <table cellpadding="0" cellspacing="0" width="1%" height="100%" class="pointer" onmouseover="gebi('exit_but').src='images/main/top_right_active.gif';" onmouseout="gebi('exit_but').src='images/main/top_right_on.gif';">
                                                    <tr>
                                                        <td valign="top"  background="images/main/top_bg.gif" align="right" width="1%" nowrap="nowrap" class="white b" style="padding:4px; "><?=$back_url['title'];?></td>
                                                        <td width="42" valign="top" align="right"><img id="exit_but" src="images/main/top_right_on.gif" width="42" height="26" border="0" style="cursor:pointer;"></td>
                                                    </tr>
                                                </table>
                                            </a>
                                        </td>
                                        <?
                                    } else {
                                        ?>
                                        <td height="26" width="42"><img src="images/main/top_right_off.gif" width="42" height="26" border="0"></td>
                                        <?
                                    }
                                    ?>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td width="15" valign="top" align="left" height="100%" class="b_el_1-s-l-col">
                            <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                <tr>
                                    <td height="92" valign="top" align="left" class="b_el_1 b_el_1-lt"><b></b></td>
                                </tr>
                                <tr>
                                    <td height="100%" valign="middle" align="left" class="b_el_1-s-l"></td>
                                </tr>
                                <tr>
                                    <td height="46" valign="bottom" align="left" class="b_el_1 b_el_1-lb"><b></b></td>
                                </tr>
                            </table>
                        </td>
                        <td width="100%" height="100%" valign="top" background="images/main/b22_1.gif">
                            <?=$page;?>
                        </td>
                        <td width="15" valign="top" align="right" height="100%" class="b_el_1-s-r-col">
                            <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                <tr>
                                    <td height="92" valign="top" align="left" class="b_el_1 b_el_1-rt"><b></b></td>
                                </tr>
                                <tr>
                                    <td height="100%" valign="middle" align="left" class="b_el_1-s-r"></td>
                                </tr>
                                <tr>
                                    <td height="46" valign="bottom" align="left" class="b_el_1 b_el_1-rb"><b></b></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td height="17" colspan="3" class="b_el_1-s-l-row">
                            <table cellpadding="0" cellspacing="0" width="100%" height="100%" class="b_el_1 b_el_1-b">
                                <tr>
                                    <td width="137" height="100%" valign="bottom" class="b_el_2 b_el_2-lb"><b></b></td>
                                    <td width="50%"><img src="images/d.gif" width="1" height="1"></td>
                                    <td width="196" height="100%" valign="bottom" align="center" class="b_el_2 b_el_2-c"><b></b></td>
                                    <td width="50%"><img src="images/d.gif" width="1" height="1"></td>
                                    <td width="136" height="100%" valign="bottom" class="b_el_2 b_el_2-rb"><b></b></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?
    return ob_get_clean();

}

function tpl_border_outside4($page = '',$width=100,$height=100,$align='center',$valign='middle',$style='') {
    $page = trim($page);
    if (!$page) return '';
    ob_start();
    ?>
    <table cellpadding="0" cellspacing="0" style="width: 100%; height: 100%;" class="fr_lft_dyn_back"><tr><td align="center" class="fr_left_back">
                <table cellpadding="0" cellspacing="0" style="width: 100%; height: 100%;" class="fr_right_back"><tr><td align="left" style="background: transparent url('images/main/top_center.gif') repeat-x top;">
                            <table cellpadding="0" cellspacing="0" style="width: 100%; height: 100%; background: transparent url('images/main/top_left.gif') no-repeat top left;"><tr><td align="left" style=" background: transparent url('images/main/top_right.gif') no-repeat top right;">
                                        <table cellpadding="0" cellspacing="0" style="width: 100%; height: 100%; background: transparent url('images/main/bottom_center.gif') repeat-x bottom;"><tr><td style="background: transparent url('images/main/bottom_left.gif') no-repeat bottom left; ">
                                                    <table cellpadding="0" cellspacing="0" width="100%" height="100%" style="background: transparent url('images/main/bottom_right.gif') no-repeat bottom right;">
                                                        <tr>
                                                            <td align="center" valign="middle" width="100%" height="100%" style="" id="window_blue" >
                                                                <table cellpadding="0" cellspacing="0" border="0" style="background:url('images/first_battle_bg.gif') no-repeat;" width="772" height="600">
                                                                    <tr>
                                                                        <td width="772" height="600" align="center" valign="top">
                                                                            <img src="images/d.gif" width="1" height="5"><br>
                                                                            <b class="while" style="color:white;">ТРЕНИРОВОЧНЫЙ БОЙ</b><br>
                                                                            <img src="images/d.gif" width="1" height="25"><br>
                                                                            <table cellpadding="0" cellspacing="0" width="1%" height="1%">
                                                                                <tr>
                                                                                    <td width="1%" valign="top" align="center" height="1%" ><center><?=$page;?></center></td>
                                                                                </tr>
                                                                            </table>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td></tr></table>
                                    </td></tr></table>
                        </td></tr></table>
            </td></tr></table>
    <?
    return ob_get_clean();
}




function tpl_border_cap($text='',$param=array()) {
    $html = '';
    $html .= '<table cellpadding="0" cellspacing="0" width="'.($param['width'] ? $param['width'] : '1%').'" height="20" class="default" '.($param['id'] ? 'ID='.$param['id']:'').'>';
    $html .= '<tr>';
    $html .= '<td class="button-3-off button-3-off-l"><b class="space"></b></td>';
    $html .= '<td width="100%" class="button-3-off button-3-off-c" nowrap="nowrap">'.$text.'</td>';
    $html .= '<td class="button-3-off button-3-off-r"><b class="space"></b></td>';
    $html .= '</tr>';
    $html .= '</table>';
    return $html;
}

/*function tpl_border_cap2($text='',$param=array()) {
	$html = '';
	$html .= '<table cellpadding="0" cellspacing="0" class="default" width="'.($param['width'] ? $param['width'] : '1%').'" height="23" '.($param['id'] ? 'ID='.$param['id']:'').'>';
	$html .= '<tr>';
	$html .= '<td width="29" height="23" background="images/main/butt1_l.gif"><img src="images/d.gif" width="29" height="23"></td>';
	$html .= '<td width="100%" height="23" background="images/main/butt1_c.gif" align="center" valign="middle" style="height:23px; color:#4cd5f4; font-weight: bold;" nowrap="nowrap">';
	$html .= '<img src="images/d.gif" width="100" height="1"><br>';
	$html .= $text;
	$html .= '</td>';
	$html .= '<td width="29" height="23" background="images/main/butt1_r.gif"><img src="images/d.gif" width="29" height="23"></td>';
	$html .= '</tr>';
	$html .= '</table>';
	return $html;
}*/
function tpl_border_cap2($text='',$param=array()) {
    $html = '';
    $html .= '<table cellpadding="0" cellspacing="0" class="default" width="'.($param['width'] ? $param['width'] : '1%').'" height="26" '.($param['id'] ? 'ID='.$param['id']:'').'>';
    $html .= '<tr>';
    $html .= '<td width="29" class="butt-1 butt-1-l"><b class="space"></b></td>';
    $html .= '<td width="100%" height="26" align="center" valign="top" nowrap="nowrap" class="butt-1 butt-1-c">';
    $html .= '<img src="images/d.gif" width="100" height="4"><br/>';
    $html .= $text;
    $html .= '</td>';
    $html .= '<td width="29" class="butt-1 butt-1-r"><b class="space"></b></td>';
    $html .= '</tr>';
    $html .= '</table>';
    return $html;
}

function tpl_border_cap3($text='',$param=array()) {
    $html = '';
    $html .= '<table cellpadding="0" cellspacing="0" class="default" width="'.($param['width'] ? $param['width'] : '1%').'" height="19" '.($param['id'] ? 'ID='.$param['id']:'').'>';
    $html .= '<tr>';
    $html .= '<td width="22" class="butt-3 butt-3-l"><b class="space"></b></td>';
    $html .= '<td width="100%" align="center" valign="middle" nowrap="nowrap" class="butt-3 butt-3-c">';
    $html .= '<img src="images/d.gif" width="100" height="1"><br>';
    $html .= $text;
    $html .= '</td>';
    $html .= '<td width="24" class="butt-3 butt-3-r"><b class="space"></b></td>';
    $html .= '</tr>';
    $html .= '</table>';
    return $html;
}

function tpl_border_cap4($text='',$page='',$param=array()) {
    $page = trim($page);
    $text = trim($text);

    if (!$param['width']) $param['width'] = '100%';
    if (!$param['height']) $param['height'] = '1%';

    ob_start();
    ?>
    <table cellpadding="0" cellspacing="0" width="<?=$param['width'];?>" height="<?=$param['height'];?>" border="0">
        <tr>
            <td height="28" width="100%" align="left" valign="top">
                <table cellpadding="0" cellspacing="0" width="100%" border="0">
                    <tr>
                        <td width="40" class="cap_new cap_new-lt"><b class="space"></b></td>
                        <td width="100%" class="cap_new cap_new-t" align="center" valign="top" style="padding:4px;"><b style="color:#4cd5f4;"><?=$text;?></b></td>
                        <td width="40" class="cap_new cap_new-rt"><b class="space"></b></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td height="100%" width="100%" align="left" valign="top">
                <table cellpadding="0" cellspacing="0" width="100%" height="100%" border="0">
                    <tr>
                        <td height="100%" width="11">
                            <table cellpadding="0" cellspacing="0" width="100%" height="100%" border="0">
                                <tr><td width="100%" height="100%" background="images/main/cap_new_2_1_bg.gif" valign="top" align="left"><img src="images/main/cap_new_2_1.gif" width="11" height="68"></td></tr>
                                <tr><td width="11" height="11" class="cap_new cap_new-lb"><b class="space"></b></td></tr>
                            </table>
                        </td>
                        <td height="100%" width="100%" background="images/main/cap_new_2_3_bg.gif">
                            <table cellpadding="0" cellspacing="0" width="100%" height="100%" border="0">
                                <tr>
                                    <td style="background: url('images/main/cap_new_2_3_bg2.gif') repeat-x top;" width="100%" height="100%" valign="top" align="left">
                                        <?=$page;?>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="100%" height="11" class="cap_new cap_new-b"></td>
                                </tr>
                            </table>
                        </td>
                        <td height="100%" width="11">
                            <table cellpadding="0" cellspacing="0" width="100%" height="100%" border="0">
                                <tr><td width="100%" height="100%" background="images/main/cap_new_3_1_bg.gif" valign="top" align="left"><img src="images/main/cap_new_3_1.gif" width="11" height="68"></td></tr>
                                <tr><td width="11" height="11" class="cap_new cap_new-rb"><b class="space"></b></td></tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?
    return ob_get_clean();
}

function tpl_border_cap5($text='',$param=array()) {
    //$text = trim($text);
    if (!$param['width']) $param['width'] = '100%';
    if (!$param['height']) $param['height'] = '1%';
    ob_start();
    ?>
    <table cellpadding="0" cellspacing="0" border="0" width="<?=$param['width'];?>" height="<?=$param['height'];?>">
        <tr>
            <td width="50%" align="left" valign="middle"><table cellpadding="0" cellspacing="0" border="0" width="100%" height="8"><tr><td align="center" valign="middle"><img src="images/d.gif" width="5" height="8" border="0"></td></tr></table></td>
            <td width="1%" nowrap="nowrap" style="color:black;" class="b">&nbsp;<?=$text;?>&nbsp;</td>
            <td width="50%" align="left" valign="middle"><table cellpadding="0" cellspacing="0" border="0" width="100%" height="8"><tr><td align="center" valign="middle"><img src="images/d.gif" width="5" height="8" border="0"></td></tr></table></td>
        </tr>
    </table>
    <?
    return ob_get_clean();
}

function tpl_border_cap6($text='',$param=array()) {
    //$text = trim($text);
    if (!$param['width']) $param['width'] = '100%';
    ob_start();	?>
    <table cellpadding="0" cellspacing="0" border="0" width="<?=$param['width'];?>" height="24">
        <tr>
            <td width="10" class="t1_el t1_el-l"><i></i></td>
            <td width="100%" nowrap="nowrap" valign="top" align="center" class="t1_el t1_el-c"><?=$text;?></td>
            <td width="12" class="t1_el t1_el-r"><i></i></td>
        </tr>
    </table>
    <?	return ob_get_clean();
}



/*function tpl_border_inside($page = '',$width='100%',$height='100%',$align='center',$valign='middle') {
	$page = trim($page);
	if (!$page) return '';
	ob_start();
	?>
	<table cellpadding="0" cellspacing="0" width="<?=$width;?>" height="<?=$height;?>" style="padding:0px; margin:0px;">
		<tr>
			<td width="11" height="100%"  style=" padding:0px;">
				<table cellpadding="0" cellspacing="0" width="100%" height="100%" style="padding:0px; margin:0px;">
					<tr><td width="11" height="18" valign="top" style="border:0px solid black; padding:0px;"><img src="images/main/win_1_1.gif" width="11" height="18"></td></tr>
					<tr><td width="11" height="100%" background="images/main/win_1_2.gif" style="border:0px solid black; padding:0px;"><img src="images/d.gif" width="11" height="1"></td></tr>
					<tr><td width="11" height="23" valign="top" style="border:0px solid black; padding:0px;"><img src="images/main/win_1_3.gif" width="11" height="23"></td></tr>
				</table>
			</td>
			<td width="100%" style=" padding:0px;">
				<table cellpadding="0" cellspacing="0" width="100%" height="100%">
					<tr><td width="100%" height="6" background="images/main/win_2_1.gif" style="padding:0px;"><img src="images/d.gif" width="1" height="6"></td></tr>
					<tr>
						<td width="100%" height="100%" background="images/main/win_2_2.gif" align="<?=$align;?>" valign="<?=$valign;?>" style="padding:0px;">
							<?=$page;?>
						</td>
					</tr>
					<tr><td width="100%" height="12" background="images/main/win_2_3_new.gif" style="padding:0px;"><img src="images/d.gif" width="1" height="12"></td></tr>
				</table>
			</td>
			<td width="17"  style=" padding:0px;">
				<table cellpadding="0" cellspacing="0" width="100%" height="100%">
					<tr><td width="17" height="18" style=" padding:0px;"><img src="images/main/win_3_1.gif" width="17" height="18"></td></tr>
					<tr><td width="17" height="100%" background="images/main/win_3_2.gif" style=" padding:0px;"><img src="images/d.gif" width="17" height="1"></td></tr>
					<tr><td width="17" height="23" style=" padding:0px;"><img src="images/main/win_3_3.gif" width="17" height="23"></td></tr>
				</table>
			</td>
		</tr>
	</table>
	<?
	return ob_get_clean();
}*/

function tpl_border_inside($page = '',$width='100%',$height='100%',$align='center',$valign='middle') {
    $page = trim($page);
    if (!$page) return '';
    ob_start();
    ?>
    <table cellpadding="0" cellspacing="0" width="<?=$width;?>" height="<?=$height;?>" style="padding:0px; margin:0px;">
        <tr>
            <td width="11" height="100%"  style=" padding:0px;">
                <table cellpadding="0" cellspacing="0" width="100%" height="100%" style="padding:0px; margin:0px;">
                    <tr><td width="11" height="18" valign="top" class="win_el_1 win_el_1-lt"><b></b></td></tr>
                    <tr><td width="11" valign=top height="100%" background="images/main/win_1_2.gif" style="border:0px solid black; padding:0px;">
                            <table cellpadding="0" cellspacing="0" width="100%" height="100%" style="padding:0px; margin:0px;" border=0>
                                <tr>
                                    <td style="background:url(images/main/win_1_2_1.gif) top no-repeat" valign=top><img src="images/d.gif" width="11" height="95" border=0></td>
                                </tr>
                            </table></td></tr>
                    <tr><td width="11" height="23" valign="top" class="win_el_1 win_el_1-lb"><b></b></td></tr>
                </table>
            </td>
            <td width="100%" style=" padding:0px;">
                <table cellpadding="0" cellspacing="0" width="100%" height="100%" border=0>
                    <tr><td width="100%" height="6" class="win_el_1 win_el_1-t"><b></b></td></tr>
                    <tr>
                        <td width="100%" height="100%" background="images/main/win_2_2.gif" align="<?=$align;?>" valign="<?=$valign;?>" style="padding:5px 0 5px 0">
                            <?=$page;?>
                        </td>
                    </tr>
                    <tr><td width="100%" height="12" class="win_el_1 win_el_1-b"><b></b></td></tr>
                </table>
            </td>
            <td width="17"  style=" padding:0px;">
                <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                    <tr><td width="17" height="18" class="win_el_1 win_el_1-rt"><b></b></td></tr>
                    <tr><td width="17" valign=top height="100%" background="images/main/win_3_2.gif" style=" padding:0px;">
                            <table cellpadding="0" cellspacing="0" width="100%" height="100%" style="padding:0px; margin:0px;" border=0>
                                <tr>
                                    <td style="background:url(images/main/win_3_2_1.gif) top no-repeat" valign=top><img src="images/d.gif" width="17" height="95" border=0></td>
                                </tr>
                            </table></td></tr>
                    <tr><td width="17" height="23" class="win_el_1 win_el_1-rb"><b></b></td></tr>
                </table>
            </td>
        </tr>
    </table>
    <?
    return ob_get_clean();
}


function tpl_border_inside2($page = '',$page2='',$width='100%',$height1='5%',$height2='5%',$align='center',$valign='middle') {
    $prf = ($page2 ? '_c' : '');
    if (!$page && $page2) {
        $page = $page2;
        unset($page2);
    }
    if (!$page2) {
        $height = $height1;
    } else {
        if (strpos($height1,'%') !== false || strpos($height2,'%') !== false) {
            $height = (intval($height1)+intval($height2)).'%';
        } else {
            $height = (intval($height1)+intval($height2));
        }
    }
    if (!$page) {
        return '';
    }
    ob_start();
    ?>
    <table cellpadding="0" cellspacing="0" width="<?=$width;?>" height="<?=$height;?>">



        <tr>
            <td align="<?=$align;?>" valign="<?=$valign;?>" class="white">
                <?=$page;?></td>
        </tr>



        <?
        if ($page2) {
            ?>
            <tr>
                <td width="100%" height="<?=$height2;?>">
                    <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                        <tr>
                            <td width="11" height="100%">
                                <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                    <tr><td width="11" height="4"><img src="images/main/win3_1_1.gif" width="11" height="4"></td></tr>
                                    <tr><td width="11" height="100%" background="images/main/win3_1_2.gif"><img src="images/d.gif" width="11" height="1"></td></tr>
                                    <tr><td width="11" height="12"><img src="images/main/win3_1_3.gif" width="11" height="12"></td></tr>
                                </table>
                            </td>
                            <td width="100%" height="100%">
                                <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                    <tr><td width="100%" height="4"><img src="images/main/win3_2_1.gif" width="100%" height="4"></td></tr>
                                    <tr><td width="100%" height="100%" background="images/main/win3_2_2.gif" align="<?=$align;?>" valign="<?=$valign;?>">
                                            <?=$page2;?>
                                        </td></tr>
                                    <tr><td width="100%" height="2" background="images/main/win3_2_3.gif"><img src="images/d.gif" width="100%" height="2"></td></tr>
                                </table>
                            </td>
                            <td>
                                <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                    <tr><td width="11" height="4"><img src="images/main/win3_3_1.gif" width="11" height="4"></td></tr>
                                    <tr><td width="11" height="100%" background="images/main/win3_3_2.gif"><img src="images/d.gif" width="11" height="1"></td></tr>
                                    <tr><td width="11" height="12"><img src="images/main/win3_3_3.gif" width="11" height="12"></td></tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <?
        }
        ?>
    </table>
    <?
    return ob_get_clean();
}

function tpl_border_inside3($page = '',$width='100%',$height='100%',$align='left',$valign='top') {
    $page = trim($page);
    if (!$page) return '';

    ob_start();

    ?>
    <table cellpadding="0" cellspacing="0" width="<?=$width;?>" height="<?=$height;?>" border="0">
        <tr>
            <td width="11" height="11"><img src="images/qst_p_top_left.gif" width="11" height="11" border="0"></td>
            <td width="100%" height="11" background="images/qst_p_bgtop.gif"><img src="images/d.gif" width="1" height="11"></td>
            <td width="13" height="11"><img src="images/qst_p_top_right.gif" width="13" height="11" border="0"></td>
        </tr>
        <tr>
            <td width="11" height="100%" background="images/qst_p_left.gif" valign="top"><img src="images/d.gif" width="11" height="1"></td>
            <td width="100%" height="100%" background="images/qst_p_bg.gif" align="<?=$align;?>" valign="<?=$valign;?>"><?=$page;?></td>
            <td width="13" height="100%" background="images/qst_p_right.gif" valign="top"><img src="images/d.gif" width="13" height="1"></td>
        </tr>
        <tr>
            <td width="11" height="12"><img src="images/qst_p_bottom_left.gif" width="11" height="12" border="0"></td>
            <td width="100%" height="12" background="images/qst_p_bgbottom.gif"><img src="images/d.gif" width="1" height="12"></td>
            <td width="13" height="12"><img src="images/qst_p_bottom_right.gif" width="13" height="12" border="0"></td>
        </tr>
    </table>
    <?
    return ob_get_clean();
}

function tpl_border_inside4($page = '',$width='100%',$height='100%',$align='center',$valign='middle', $id=false, $style=false) {
    $page = trim($page);
    if (!$page) return '';
    ob_start();
    ?>
    <table cellpadding="0" cellspacing="0" width="<?=$width;?>" height="<?=$height;?>"<?if($id){?> ID="<?=$id?>"<?}?><?if($style){?> style="<?=$style?>"<?}?> >
        <tr>
            <td width="13" height="100%">
                <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                    <tr><td width="13" height="12" class="win_el_4 win_el_4-lt"><b></b></td></tr>
                    <tr><td width="13" height="100%" background="images/main/win4_1_2.gif"><img src="images/d.gif" width="13" height="1"></td></tr>
                    <tr><td width="13" height="12" class="win_el_4 win_el_4-lb"><b></b></td></tr>
                </table>
            </td>
            <td  width="100%" height="100%">
                <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                    <tr><td width="100%" height="4" class="win_el_4 win_el_4-t"><b></b></td></tr></tr>
                    <tr>
                        <td width="100%" height="100%" background="images/main/win4_2_2_bg.gif">
                            <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                <tr>
                                    <td width="100%" height="100%" align="<?=$align;?>" valign="<?=$valign;?>" style="background: url('images/main/win4_2_2.gif') repeat-y left top">
                                        <?=$page;?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr><td width="100%" height="1" class="win_el_4 win_el_4-b"><b></b></td></tr></tr>
                </table>
            </td>
            <td width="13">
                <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                    <tr><td width="13" height="12" class="win_el_4 win_el_4-rt"><b></b></td></tr>
                    <tr><td width="13" height="100%" background="images/main/win4_3_2.gif"><img src="images/d.gif" width="13" height="1"></td></tr>
                    <tr><td width="13" height="12" class="win_el_4 win_el_4-rb"><b></b></td></tr>
                </table>
            </td>
        </tr>
    </table>
    <?
    return  ob_get_clean();
}

function tpl_border_inside5_f($text='',$param=array()) {
    $page = trim($page);
    $text = trim($text);

    if (!$param['width']) $param['width'] = '100%';
    if (!$param['height']) $param['height'] = '1%';

    ob_start();
    ?>
    <table cellpadding="0" cellspacing="0" width="<?=$param['width'];?>" height="<?=$param['height'];?>" border="0" style="padding:0">
        <tr  height="5">
            <td width="5" style="padding:0"><img src="images/main/q1_1.gif" width="5"></td>
            <td width="100%" style="background: url(images/main/q2_1.gif) top repeat-x;padding:0" align="center" valign="top"></td>
            <td width="5" style="padding:0"><img src="images/main/q3_1.gif" width="5" height="5"></td>
        </tr>
        <tr>
            <td height="100%" width="5" style='background: url(images/main/q1_2.gif) left repeat-y;padding:0' aligh=right><img src="images/d.gif" width="1" height="1"></td>
            <td height="100%" width="100%" style="padding:4px;background: transparent"><?=$text;?></td>
            <td height="100%" width="5" style='background: url(images/main/q3_2.gif) right repeat-y;padding:0'><img src="images/d.gif" width="1" height="1"></td>
        </tr>
        <tr  height="2">
            <td width="5" style="padding:0"><img src="images/main/q1_3.gif" width="5"></td>
            <td width="100%" style="background: url(images/main/q2_3.gif) bottom repeat-x;padding:0" align="center" valign="top"></td>
            <td width="5" style="padding:0"><img src="images/main/q3_3.gif" width="5"></td>
        </tr>
    </table>
    <?
    return ob_get_clean();
}
function tpl_border_inside5($text='',$param=array()) {
    $page = trim($page);
    $text = trim($text);

    if (!$param['width']) $param['width'] = '100%';
    if (!$param['height']) $param['height'] = '1%';

    ob_start();
    ?>
    <table cellpadding="0" cellspacing="0" width="<?=$param['width'];?>" height="<?=$param['height'];?>" border="0" style="padding:0">
        <tr>
            <td height="100%" width="5" style='background: url(images/main/q1_2.gif) left repeat-y;padding:0' align=right><img src="images/d.gif" width="1" height="1"></td>
            <td height="100%" width="100%" style="padding:4px;background: transparent;padding-top:2px 0 2px 0"><?=$text;?></td>
            <td height="100%" width="5" style='background: url(images/main/q3_2.gif) right repeat-y;padding:0'><img src="images/d.gif" width="1" height="1"></td>
        </tr>
        <tr  height="2">
            <td width="5" style="padding:0"><img src="images/main/q1_3.gif" width="5"></td>
            <td width="100%" style="background: url(images/main/q2_3.gif) bottom repeat-x;padding:0" align="center" valign="top"></td>
            <td width="5" style="padding:0"><img src="images/main/q3_3.gif" width="5"></td>
        </tr>
    </table>
    <?
    return ob_get_clean();
}
function tpl_border_inside6($text='',$param=array()) {
    $page = trim($page);
    $text = trim($text);

    if (!$param['width']) $param['width'] = '100%';
    if (!$param['height']) $param['height'] = '100%';
    if (!$param['valign']) $param['valign'] = 'middle';
    ob_start();
    ?>
    <table cellpadding="0" cellspacing="0" width="<?=$param['width'];?>" height="<?=$param['height'];?>" border="0" style="padding:0">
        <tr height="11">
            <td width="11" class="per_el_1 per_el_1-lt"><b></b></td>
            <td width="100%" class="per_el_1 per_el_1-t"><b></b></td>
            <td width="14" class="per_el_1 per_el_1-rt"><b></b></td>
        </tr>
        <tr>
            <td height="100%" width="11" style='background: url(images/main/per1_2.gif) left repeat-y;padding:0' valign=top><img src="images/main/per1_2_1.gif" alt="" width="11" height="136"></td>
            <td height="100%" width="100%" style="padding:0;background: url(images/main/per2_2.gif)" valign=top>
                <table cellpadding=0 cellspacing=0 border=0 width=100% style='background:url(images/main/per2_2_1.gif) top repeat-x;height:100%'>
                    <tr>
                        <td width="1%"><img src="images/d.gif" alt="" width="1" height="136"></td>
                        <td width="99%" height="100%" valign="<?=$param['valign'];?>"><?=$text;?></td>
                    </tr>
                </table>
            </td>
            <td height="100%" width="14" style='background: url(images/main/per3_2.gif) right repeat-y;padding:0' valign=top><img src="images/main/per3_2_1.gif" alt="" width="14" height="136"></td>
        </tr>
        <tr height="16">
            <td width="11" class="per_el_1 per_el_1-lb"><b></b></td>
            <td width="100%" class="per_el_1 per_el_1-b"><b></b></td>
            <td width="14" class="per_el_1 per_el_1-rb"><b></b></td>
        </tr>
    </table>
    <?
    return ob_get_clean();
}
function tpl_border_inside7($text='') {
    $text = trim($text);
    ob_start();
    ?>
    <table cellpadding="0" cellspacing="0" width="232" height="100%">
        <tr>
            <td width="11" height="100%">
                <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                    <tr><td width="12" height="8"><img src="images/qstb_top.gif" width="11" height="8"></td></tr>
                    <tr><td background="images/main/npc1_2.gif" width="11" height="100%"><img src="images/d.gif" width="11" height="1"></td></tr>
                </table>
            </td>
            <td valign="top" align="center" style="padding:6px 4px; 4px 4px;background:url(images/main/npc2_2.gif) top repeat-y;">
                <?=$text;?>
            <td width="14" height="100%">
                <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                    <tr><td width="14" height="8" align=right><img src="images/qstb_top_r.gif" width="12" height="8"></td></tr>
                    <tr><td background="images/main/npc3_2.gif" width="14" height="100%"><img src="images/d.gif" width="14" height="1"></td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="3" height="31" width="100%">
                <table cellpadding="0" cellspacing="0" width="100%" height="100%" border=0>
                    <tr>
                        <td width="31" height="31"><img src="images/main/npc1_3.gif" width="31" height="31"></td>
                        <td width="100%" height="31" style="background:url(images/main/npc2_3.gif) bottom repeat-x" valign=top><div style="height:18px;width:100%;background:url(images/main/npc2_3_2.gif) top repeat-y"><img src="images/d.gif" width="8" height="18"></td>
                        <td width="32" height="31"><img src="images/main/npc3_3.gif" width="32" height="31"></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?
    return ob_get_clean();
}
function tpl_border_artifact($artifact_id,$title,$text){
    ?>
    <table id="<?=$artifact_id;?>" cellpadding="0" cellspacing="0" width="330" style="position:absolute; display:none; " onmouseover="this.style.display='none';">
        <tr>
            <td width="20" height="100%">
                <table cellpadding="0" cellspacing="0" width="100%" height="100%" border="0">
                    <tr><td width="20" height="73"><img src="images/main/wina_1_1.gif" width="20" height="73"></td></tr>
                    <tr><td width="20" height="100%" background="images/main/wina_1_2.gif"><img src="images/d.gif" width="20" height="1"></td></tr>
                    <tr><td width="20" height="60"><img src="images/main/wina_1_3.gif" width="20" height="60"></td></tr>
                </table>
            </td>
            <td width="100%" height="100%">
                <table cellpadding="0" cellspacing="0" width="100%" height="100%" border=0>
                    <tr>
                        <td width="100%" height="5">
                            <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                <tr>
                                    <td align="left" width="10" height="5"><img src="images/main/win5_2_1_1.gif" width="10" height="5"></td>
                                    <td align="center" width="100%" height="5"><img src="images/main/win5_2_1_2.gif" width="152" height="5"></td>
                                    <td align="right" width="10" height="5"><img src="images/main/win5_2_1_3.gif" width="10" height="5"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td width="100%" height="26">
                            <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                <tr>
                                    <td align="left" width="10" height="26"><img src="images/main/win5_2_2_1.gif" width="10" height="26"></td>
                                    <td align="center" width="100%" height="26" background="images/main/win5_2_2_2.gif" style="padding:0;" valign="middle">
                                        <?=$title;?>
                                    </td>
                                    <td align="right" width="10" height="26"><img src="images/main/win5_2_2_3.gif" width="10" height="26"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td width="100%" height="100%" background="images/main/wina_2_3_1.gif">
                            <?=$text?>
                        </td>
                    </tr>
                    <tr>
                        <td width="100%" height="21">
                            <table cellpadding="0" cellspacing="0" width="100%" height="100%">
                                <tr>
                                    <td width="38" height="21"><img src="images/main/wina_2_4_1.gif" width="38" height="21"></td>
                                    <td width="50%" height="21" background="images/main/wina_2_4_2.gif"><img src="images/d.gif" width="1" height="21"></td>
                                    <td width="168" height="21"><img src="images/main/wina_2_4_3.gif" width="168" height="21"></td>
                                    <td width="50%" height="21" background="images/main/wina_2_4_4.gif"><img src="images/d.gif" width="1" height="21"></td>
                                    <td width="40" height="21"><img src="images/main/wina_2_4_5.gif" width="40" height="21"></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
            <td width="20" height="100%">
                <table cellpadding="0" cellspacing="0" width="100%" height="100%" border="0">
                    <tr><td width="20" height="73"><img src="images/main/wina_3_1.gif" width="20" height="73"></td></tr>
                    <tr><td width="20" height="100%" background="images/main/wina_3_2.gif"><img src="images/d.gif" width="20" height="1"></td></tr>
                    <tr><td width="20" height="60"><img src="images/main/wina_3_3.gif" width="20" height="60"></td></tr>
                </table>
            </td>
        </tr>
    </table>
    <?
}

function tpl_border_table($content,$param=array()){
    if (!$param['width']) $param['width']='100%';
    ?>
    <table width=<?=$param['width']?> cellpadding=0 cellspacing=0 style="border-left:1px solid #709fba; border-top:1px solid #709fba; border-bottom:1px solid #fff; background-color:#d4e9f3;border-right: 1px solid #fff;">
        <tr>
            <td>
                <?=$content;?>
            </td>
        </tr>
    </table>
    <?
}



?>