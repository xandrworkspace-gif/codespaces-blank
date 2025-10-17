<?php

exit;

chdir('..');
require_once("include/common.inc");
require_once("lib/common.lib");
require_once("lib/counters.lib");


define('GLADIATORS_CACHE_TIME',60*60); // сек
common_init();

if (!defined('GLADIATORS_SERVER') || !GLADIATORS_SERVER) common_redirect('index.php');

$mode_cache = new Cache('GLADIATORS');

foreach ($SERVERS as $server_id => $server) {
	$server_data[$server_id] = unserialize(crossserver_gladiators_get($server_id));
	echo $server_id." processed\n";
	if (!$server_data[$server_id]['skills'] || !$server_data[$server_id]['users']) continue;
	$user_skills = $server_data[$server_id]['skills'];
	$users = $server_data[$server_id]['users'];
	$clans = $server_data[$server_id]['clans'];
	foreach($user_skills as $user_id => $user_skill) {
		$server_stat[$server_id]['cnt'] += $user_skill['EBG_CNT'];
		$server_stat[$server_id]['win'] += $user_skill['EBG_WIN'];
		$server_stat[$server_id]['id'] = $server_id;

		$level = intval($users[$user_id]['level']);
		if ($level == 20) {
			$group = '20-20';
		} else {
			$group = $level%2 ? ($level.'-'.($level+1)) : (($level-1).'-'.$level);
		}
		if ($user_skill['EBG_CNT'] >= 20) {
			$user_stat[$group][$server_id.'_'.$user_id]['percent'] = ceil(100 * $user_skill['EBG_WIN']/$user_skill['EBG_CNT']);
			$user_stat[$group][$server_id.'_'.$user_id]['win'] = $user_skill['EBG_WIN'];
			$user_stat[$group][$server_id.'_'.$user_id]['id'] = $server_id.'_'.$user_id;
		}

		$clan_id = intval($users[$user_id]['clan_id']);
		if (!$clan_id) continue;
		$clan_stat[$server_id.'_'.$clan_id]['cnt'] += $user_skill['EBG_CNT'];
		$clan_stat[$server_id.'_'.$clan_id]['win'] += $user_skill['EBG_WIN'];
		$clan_stat[$server_id.'_'.$clan_id]['id'] = $server_id.'_'.$clan_id;
	}
}

foreach($clan_stat as $key => $clan_stat_data) {
	if ($clan_stat_data['cnt'] < 20) unset($clan_stat[$key]);
}

foreach($server_stat as &$stat){
	$stat['percent'] = ceil(100 * $stat['win']/$stat['cnt']);
}
foreach($clan_stat as &$stat){
	$stat['percent'] = ceil(100 * $stat['win']/$stat['cnt']);
}

usort($server_stat, "cmp_percent");
usort($clan_stat, "cmp_percent");

foreach($user_stat as &$group_stat){
	usort($group_stat, "cmp_percent");
	$group_stat = array_slice($group_stat,0,125);
}
ksort($user_stat, SORT_NUMERIC);

$clan_stat = array_slice($clan_stat,0,125);



ob_start();
?>
<!DOCTYPE html>
<html>
<head>
	<title><?=MAIN_TITLE;?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?=charset_code_html()?>">
	<meta property="og:title" content="<?=translate('&laquo;'.MAIN_TITLE.' &raquo; &mdash; бесплатная онлайн игра.')?>">
	<meta property="og:description" content="<?=translate('&laquo;'.MAIN_TITLE.' &raquo; &mdash; бесплатная онлайн игра. Сейчас лучшее время, чтобы начать играть в «'.MAIN_TITLE.'»! Присоединяйся к миллионам игроков.')?>">

	<link rel="stylesheet" href="<?=static_get('style/gladiators.css');?>">
	<script type="text/javascript" src="<?=static_get('js/common.js');?>"></script>
	<script type="text/javascript" src="<?=static_get('js/jquery.js');?>"></script>
	<!--[if lt IE 9]>
	<script type="text/javascript" src="<?=static_get('js/html5.js');?>"></script>
	<![endif]-->
	<!--[if lt IE 8]>
	<style type="text/css">
		.b-ratings__bg-center {height: expression(parentNode.offsetHeight - 94 + 'px')}
		.b-ratings__bg-bottom {top: expression(parentNode.offsetHeight - 92 + 'px'); bottom: auto;}
	</style>
	<![endif]-->

	<script type="text/javascript">
		$(function() {
			var PAGE_SIZE = 15;

			$('nav.b-tabs').each(function() {
				var tabs = $(this);
				var links = $(this).find('a');
				links.removeClass('active').first().addClass('active');

				links.each(function() {
					if ($(this).hasClass('active'))
						$($(this).attr('href')).show();
					else
						$($(this).attr('href')).hide();
				});

				links.click(function(e) {
					e.preventDefault();
					if ($(this).hasClass('active'))
						return;
					$($(this).siblings('a.active').removeClass('active').attr('href')).hide();
					$(this).addClass('active');
					$($(this).attr('href')).fadeIn(500);

					var parent = tabs.parent('article');
					if (parent.length == 1) {
						parent.css('min-height', (tabs.height() + 10));
					}
				});
			});

			$('.b-table').each(function() {
				var $this = $(this);
				var tr = $this.find('tbody tr');

				if (tr.length > PAGE_SIZE) {
					var pager = $('<div></div>', {'class': 'b-pager'});
					var prev = $('<a></a>', {'href': '#', 'class': 'b-pager__link-prev'});
					var next = $('<a></a>', {'href': '#', 'class': 'b-pager__link-next'});
					var pagesCount = Math.ceil(tr.length / PAGE_SIZE);

					var switchPage = function() {
						tr.hide().slice(($this.data('page') - 1) * PAGE_SIZE, $this.data('page') * PAGE_SIZE).fadeIn(500);
						pager.find('a.b-pager__link').removeClass('active');
						pager.find('a.b-pager__link').eq($this.data('page')-1).addClass('active');

						prev.show();
						next.show();

						if ($this.data('page') == 1)
							prev.hide();
						else if ($this.data('page') == pagesCount)
							next.hide();
					};

					if (!$this.data('page'))
						$this.data('page', 1);

					prev.click(function(e) {
						e.preventDefault();

						if ($this.data('page') == 1)
							return;

						$this.data('page', ($this.data('page') - 1));
						switchPage();
					});

					next.click(function(e) {
						e.preventDefault();

						if ($this.data('page') == pagesCount)
							return;

						$this.data('page', ($this.data('page') + 1));
						switchPage();
					});

					pager.append(prev);
					for (var i = 1; i <= pagesCount; i++) {
						pager.append(
							$('<a></a>', {'href': '#', 'text': i, 'class': 'b-pager__link'}).data('page', i).click(function(e) {
								e.preventDefault();
								if ($this.data('page') == $(this).data('page'))
									return;

								$this.data('page', $(this).data('page'));
								switchPage();
							})
						);
					}
					pager.append(next);

					pager.insertAfter($this);

					switchPage();
				}
			});
		});
	</script>
</head>
<body>

<?
if (isset($_GET['error'])) {
?>
	<div id="error_div"></div>
	<iframe width="480" height="300" frameborder="0" id="error" name="error" src="error.php?error=<?=$_GET['error'];?>" scrolling="no" style="position: absolute; z-index: 1001; left: 50%; top: 50%; margin: -150px 0 0 -240px; display: block;" allowtransparency="true"></iframe>
<?
}
?>

<header>
	<a class="logo" href="/"><img src="images/gladiators-logo.png" alt=""></a>
</header>

<section class="container">

	<section class="b-ratings">

		<div class="b-ratings__bg-center"></div>
		<div class="b-ratings__bg-top"></div>
		<div class="b-ratings__bg-bottom"></div>

		<div class="b-ratings__pad">

			<nav class="b-tabs">
				<a href="#servers" class="b-tabs__link"><span class="b-tabs__link-servers">Servers</span></a>
				<a href="#clans" class="b-tabs__link"><span class="b-tabs__link-clans">Clans</span></a>
				<a href="#players" class="b-tabs__link"><span class="b-tabs__link-players">Players</span></a>
			</nav>

			<div class="b-ratings__cont">
				<div class="b-ratings__cont-bg-top"></div>
				<div class="b-ratings__cont-bg-center">

					<article id="servers">
						<table class="b-table">
							<thead>
							<tr>
								<th><img src="images/gladiators-hdr-country.png" alt="Countries" title="Countries"></th>
								<th><img src="images/gladiators-hdr-servers.png" alt="Servers" title="Servers"></th>
								<th><img src="images/gladiators-hdr-procent.png" alt="Procent" title="Procent"></th>
							</tr>
							</thead>
							<tbody>
							<?
							$i=0;
							foreach($server_stat as $item) {
								$server_id = $item['id'];
								?>
								<tr class="<?=($i%2)? 'odd' : 'even';?>">
									<td class="b-ratings__country"><span class="b-ratings__country-icon"><img src="<?=$SERVERS[$server_id]['flag']?>" alt=""></span></td>
									<td class="b-ratings__server"><?=$SERVERS[$server_id]['name_eng']?></td>
									<td class="b-ratings__percent"><?=$item['percent']?>%</td>
								</tr>								
							<?
								$i++;
							}
							?>	
							</tbody>
						</table>
					</article>

					<article id="clans">
						<table class="b-table">
							<thead>
							<tr>
								<th><img src="images/gladiators-hdr-country.png" alt="Country" title="Country"></th>
								<th><img src="images/gladiators-hdr-name-clans.png" alt="Name clans" title="Name clans"></th>
								<th><img src="images/gladiators-hdr-win.png" alt="Win" title="Win"></th>
								<th><img src="images/gladiators-hdr-procent.png" alt="Procent" title="Procent"></th>
							</tr>
							</thead>
							<tbody>
							<?
							$i=0;

							foreach($clan_stat as $item) {
								list($server_id,$clan_id) = explode('_',$item['id']);
							
							?>
								<tr class="<?=($i%2)? 'odd' : 'even';?>">
									<td class="b-ratings__country"><span class="b-ratings__country-icon"><img src="<?=$SERVERS[$server_id]['flag']?>" alt=""></span></td>
									<td class="b-ratings__server">
										<a title="<?=$server_data[$server_id]['clans'][$clan_id]['title'];?>" href="#" onClick="showClanInfo('<?=($clan_id.'_'.$server_id);?>'); return false;"><img src="<?=$SERVERS[$server_id]['url'].PATH_IMAGE_CLANS.$server_data[$server_id]['clans'][$clan_id]['picture'];?>" border=0 width=13 height=13 align="absmiddle"></a>
										&nbsp;<?=$server_data[$server_id]['clans'][$clan_id]['title'];?>
										&nbsp;<a href="#" onclick="showClanInfo('<?=($clan_id.'_'.$server_id);?>'); return false;" title="Info"><img src="/images/player_info.gif" border="0"></a>
									</td>
									<td class="b-ratings__win"><?=$item['win']?></td>
									<td class="b-ratings__percent"><?=$item['percent']?>%</td>
								</tr>
							<?
								$i++;
							}
							?>									
							</tbody>
						</table>
					</article>

					<article id="players">

						<nav class="b-tabs b-tabs_vert">
							<?foreach ($user_stat as $grp => $grp_stat) {?>
								<a href="#players-<?=$grp?>" class="b-tabs__link"><?=$grp?></a>
							<?}?>
						</nav>
						<?foreach ($user_stat as $group => $group_stat) {?>
							<div id="players-<?=$group?>">
								<table class="b-table">
									<thead>
									<tr>
										<th><img src="images/gladiators-hdr-country.png" alt="Country" title="Country"></th>
										<th><img src="images/gladiators-hdr-name-players.png" alt="Name players" title="Name players"></th>
										<th><img src="images/gladiators-hdr-win.png" alt="Win" title="Win"></th>
										<th><img src="images/gladiators-hdr-procent.png" alt="Procent" title="Procent"></th>
									</tr>
									</thead>
									<tbody>
									<? 
									$i=0;
									foreach ($group_stat as $item) {
										list($server_id,$user_id) = explode('_',$item['id']);
										$clan_id = $server_data[$server_id]['users'][$user_id]['clan_id'];
									?>
										<tr class="<?=($i%2)? 'odd' : 'even';?>">
											<td class="b-ratings__country"><span class="b-ratings__country-icon"><img src="<?=$SERVERS[$server_id]['flag']?>" alt=""></span></td>
											<td class="b-ratings__server">
												<?if ($clan_id) {?>
													<a title="<?=$server_data[$server_id]['clans'][$clan_id]['title'];?>" href="#" onClick="showClanInfo('<?=($clan_id.'_'.$server_id);?>'); return false;"><img src="<?=$SERVERS[$server_id]['url'].PATH_IMAGE_CLANS.$server_data[$server_id]['clans'][$clan_id]['picture'];?>" border=0 width=13 height=13 align="absmiddle"></a>
												<?}?>
												&nbsp;<img src="/images/ranks/rank<?=$server_data[$server_id]['users'][$user_id]['rank'];?>.gif" border="0" width="13" height="13" title="Nickname">
												&nbsp;<b><?=$server_data[$server_id]['users'][$user_id]['nick'];?>&nbsp;[<?=$server_data[$server_id]['users'][$user_id]['level'];?>]</b>
												&nbsp;<a href="#" onclick="showUserInfo('<?=$server_data[$server_id]['users'][$user_id]['nick'];?>', '<?=trim($SERVERS[$server_id]['url'],'/');?>');return false;" title="Info"><img src="/images/player_info.gif" border="0"></a>
											</td>
											<td class="b-ratings__win"><?=$item['win']?></td>
											<td class="b-ratings__percent"><?=$item['percent']?>%</td>
										</tr>	
										
									<?
										$i++;
									}
									?>	
									</tbody>
								</table>
							</div>
						<?}?>
					</article>

				</div>
				<div class="b-ratings__cont-bg-bottom"></div>
			</div>

		</div>

	</section>

</section>

</body>
</html>
<?
$str = ob_get_contents();
var_dump(strlen($str));
$mode_cache->update($str,GLADIATORS_CACHE_TIME);

//ob_end_flush();


function cmp_percent($a, $b)
{
    if ($a['percent'] == $b['percent']) {
        return 0;
    }
    return ($a['percent'] > $b['percent']) ? -1 : 1;
}
