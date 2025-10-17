<? # $Id: instance.tpl,v 1.23 2010-03-02 15:49:15 p.knoblokh Exp $

function tpl_instance_print_team_summary_inner(&$instance_user_list, $slaughter=false) {
?>
		<table class="coll w100 p6h p2v brd2">
		<thead>
			<tr class="big b" align="center"><td><?=translate('Игрок');?></td><td><?=translate('Опыт');?></td><td><?=translate('Урон');?></td><td><?=translate('Убийств');?></td><td><?=translate('Смертей');?></td></tr>
		</thead>
		<tbody>
		<?
			foreach ($instance_user_list as $instance_user) {
				$user = user_get($instance_user['user_id']);
		?>
			<tr class="<?=(++$i % 2 ? 'bg_l': '')?>" align="center">
				<td align=left><?=html_user_info($user,array('url_add' => 'class="redd" id="UID'.$user['id'].'"'));?></td>
				<td nowrap><span title="<?=translate('Боевой опыт');?>"><?=intval($instance_user['exp']);?></span> / <span class=redd title="<?=translate('Доблесть');?>"><?=intval($instance_user['honor']);?></span></td>
				<td nowrap><span class=redd title="<?=translate('Нанесенный урон');?>"><?=intval($instance_user['dmg']);?></span> / <span class=grnn title="<?=translate('Вылечено жизни');?>"><?=intval($instance_user['heal']);?></span></td>
				<td><?=intval($instance_user['kill_cnt']);?></td>
				<td><?=intval($instance_user['death_cnt']);?></td>
			</tr>
		<?
			}
		?>
		</tbody>
		</table>
<?
}

function tpl_instance_print_slaughter_summary_inner(&$instance_user_list, $finish) {
	global $session_user;
	$win_cnt = 0;
	?>
	<table class="coll w100 p6h p2v">
		<thead>
			<tr class="big b" align="center"><td><?=translate('Игрок');?></td><td><?=translate('Опыт');?></td><td><?=translate('Урон');?></td><td><?=translate('Убийств');?></td><td></td></tr>
		</thead>
		<tbody class="brd2">
			<?
			foreach ($instance_user_list as $instance_user) {				
			?>
				<tr class="<?=(++$i % 2 ? 'bg_l': '')?>" align="center">
					<td align=left nowrap="nowrap" >
					<?
						if ($finish) {
							$user = user_get($instance_user['user_id']);
							$html = html_user_info($user,array('url_add' => 'class="'.($instance_user['death_cnt'] ? 'redd':'').'" id="UID'.$user['id'].'"'));
						}
						else {
							$html = html_user_info_blind(false, array('url_add' => 'id="'.md5($instance_user['user_id']).'"'));
						}
						echo $html;
					?>
					</td>
					<td nowrap><span title="<?=translate('Боевой опыт');?>"><?=intval($instance_user['exp']);?></span> / <span class=redd title="<?=translate('Доблесть');?>"><?=intval($instance_user['honor']);?></span></td>
					<td nowrap="nowrap" ><span class=redd title="<?=translate('Нанесенный урон');?>"><?=intval($instance_user['dmg']);?></span> / <span class=grnn title="<?=translate('Вылечено жизни');?>"><?=intval($instance_user['heal']);?></span></td>
					<td nowrap="nowrap" ><?=intval($instance_user['kill_cnt']);?></td>
					<td nowrap="nowrap" width="50"><?=(($finish && ($win_cnt < 5)) ? sprintf(translate('%s место'),++$win_cnt) : '');?></td>
				</tr>
				<?
			}
			?>
		</tbody>
	</table>
	<?
}
?>
