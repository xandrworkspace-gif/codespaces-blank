<? # $Id: pet.tpl,v 1.7 2010-01-15 09:50:12 p.knoblokh Exp $

require_once("tpl/common.tpl");
require_once("lib/pet.lib");


function tpl_pet_alt(&$pet,$artikul=array(), $param=false,$pet_id=false) {
	global $quality_info;
	if (!$pet) return false;
	if (!$artikul || $artikul['id'] != $pet['artikul_id']) {
		$artikul = pet_artikul_get($pet['artikul_id']);
	}
	if (!$artikul) return false;
	$bg = true;
	if (!$pet_id) $pet_id=$param['div_prefix'].'PA_'.$pet['id'];
?>
<table id="<?=$pet_id;?>" width="300" border="0" cellspacing="0" cellpadding="0" style="position:absolute; display:none; background-color:#FBD4A4;">
	<tr height=24>
		<td width=14 class="aa-tl"  style="padding:0px;"><img src="images/d.gif" width=14 height=1><br></td>
		<td class="aa-t" align="center" style="vertical-align:middle"><b style="color:<?=$quality_info[$pet['quality']]['color'];?>"><?=pet_title($pet, $artikul);?></b></td>
		<td width=14 class="aa-tr" style="padding:0px;"><img src="images/d.gif" width=14 height=1><br></td>
	</tr>
	<tr>
		<td class="aa-l" style="padding:0px"></td>
		<td style="padding:0px">

		<table width="275" style=" margin: 3px" border="0" cellspacing="0" cellpadding="0">
		<tr>
		<td align="center" valign="top" width="60">
			<table width="60" height="60" cellpadding="0" cellspacing="0" border="0" style="margin: 2px" background="<?=PATH_IMAGE_PETS.$artikul['picture2'];?>">
			<tr><td valign="bottom">
			&nbsp;
			</td></tr>
			</table>
		</td>
		<td>
			<table cellpadding="0" cellspacing="0" width="100%">
				<tr>
					<td><img src="images/tbl-shp_level-icon.gif" width="11" height="10" align="absmiddle"><?=translate('Уровень ');?><b class="red"><?=$artikul['level'];?></b></td>
					<td><img src="images/tbl-shp_item-iznos.gif" width="11" height="10" align="absmiddle"> <?=translate('Сытость ');?><span class="red"><?=pet_get_satiation($pet);?>%</span></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td><img src="images/tbl-shp_item-iznos.gif" width="11" height="10" class="tbl-shp_item-ico"/><?=translate('Опыт ');?><span class="red"><?=intval($pet['exp']);?></span>/<?=$artikul['exp_max'];?></td>
				</tr>
			</table>
		</td>
		</tr>
		</table>

<table width="100%" cellpadding="0" cellspacing="0" border="0">
<? if ($artikul['description']) { ?>
	<tr class="skill_list<?if ($bg) {?> list_dark<?}?>"><td colspan=2><?=tpl_common_tags($artikul['description']);?></td></tr>
<? $bg = !$bg; } ?>
</table>
		</td>
		<td class="aa-r" style="padding:0px"></td>
	</tr>
	<tr height=5>
		<td class="aa-bl" style="vertical-align:middle"></td>
		<td class="aa-b" style="vertical-align:middle"><img src="images/d.gif" width=1 height=5></td>
		<td class="aa-br" style="vertical-align:middle"></td>
	</tr>
</table>
<?
}
?>