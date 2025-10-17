<?
function tpl_popup_top($param = array()){?>
    <div <?=$param['add_table']?> class="popup_global_container">
            <div class="popup-top-left<?=$param['p_class'];?>">
                <div class="popup-top-right<?=$param['p_class'];?>">
                    <div class="popup-top-center<?=$param['p_class'];?>">
                        <div class="popup_global_title<?=$param['p_class'];?>" id="action_title_amount" <?=$param['add_top'];?>>
                            <?=$param['title']?>
                        </div>
                    </div>
                    <div class="popup_global_close_btn<?=$param['p_class'];?>" title="<?=$param['close_title'];?>" onclick="<?=$param['close_func'];?>"></div>
                </div>
            </div>
            <div class="popup-left-center<?=$param['p_class'];?>">
            <div class="popup-right-center<?=$param['p_class'];?>">
            <div class="popup_global_content<?=$param['p_class'];?>" style="padding: 5px; <?=$param['style_bottom']?>">

<?}

function tpl_popup_bottom($param = array()){?>
    </div>
    </div>
    </div>
    <div class="popup-left-bottom<?=$param['p_class'];?>">
        <div class="popup-right-bottom<?=$param['p_class'];?>">
            <div class="popup-bottom-center<?=$param['p_class'];?>">
            </div>
        </div>
    </div>
    </div>
<?}

function tpl_table_top($param = array()){?>
    <table <?=$param['add_table']?> width="100%" border="0" cellspacing="0" cellpadding="0">
    <tbody><tr height="22">
        <td width="20" align="right" valign="bottom"><img src="images/tbl-shp_sml-corner-top-left.gif" width="20" height="22"></td>
        <td class="tbl-shp_sml-top" valign="top" align="center">
            <table border="0" cellspacing="0" cellpadding="0">
                <tbody><tr height="22">
                    <td width="27"><img src="images/tbl-usi_label-left.gif" width="27" height="22"></td>
                    <td align="center" class="tbl-usi_label-center"><?=$param['title'];?></td>
                    <td width="27"><img src="images/tbl-usi_label-right.gif" width="27" height="22"></td>
                </tr>
                </tbody></table>
        </td>
        <td width="20" align="left" valign="bottom"><img src="images/tbl-shp_sml-corner-top-right.gif" width="20" height="22"></td>
    </tr>
    <tr>
    <td class="tbl-usi_left">&nbsp;</td>
    <td class="tbl-usi_bg" valign="top" align="center" style="padding: 10 10 16 10">
<?}

function tpl_table_bottom(){?>
    </td>
    <td class="tbl-usi_right">&nbsp;</td>
    </tr>
    <tr height="18">
        <td width="20" align="right" valign="top"><img src="images/tbl-shp_sml-corner-bottom-left.gif" width="20" height="18"></td>
        <td class="tbl-shp_sml-bottom" valign="top" align="center">&nbsp;   </td>
        <td width="20" align="left" valign="top"><img src="images/tbl-shp_sml-corner-bottom-right.gif" width="20" height="18"></td>
    </tr>
    </tbody></table>
<?}