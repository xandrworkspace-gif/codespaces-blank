<? # $Id: achievement.tpl,v 1.6 2009-06-02 13:31:17 kastet Exp $

function tpl_achievement_chain(&$achievement_chain, $params = array()) {
	$icon_border = $params['icon_border'] ? $params['icon_border'] : 'medal_bg_c';
	$cell_height = $params['cell_height'] ? $params['cell_height'] : 52;
?>
<table cellpadding=0 cellspacing=0 border=0 align=left>
<tr>
<?

	$i = 0;
	foreach ($achievement_chain as $achievement) {
		$id = $achievement['id'];
		$img = '<a href="#" onClick="showAchievementInfo('.$id.', false);return false;" aid="'.$id.'" div_id="AA_'.$id.'" style="position: relative; display: block; width: 40px; height: 35px;">';
		$img .= '<img width=35 height=35 src="'.PATH_IMAGE_ACHIEVEMENTS.$achievement['picture'].'" alt="'.$achievement['title'].'" border=0 style="margin-left: 5px;"><div style="position: absolute;bottom:0px;left:6px;color:red;color:red;font-weight:bold"></div></a>';
?>
		<td class="<?=$icon_border?>" id="medal_<?=$i?>" height="<?=$cell_height?>" style="padding: 0"><?=$img?></td>
<?
	}
?>
</table>
<?
}

?>