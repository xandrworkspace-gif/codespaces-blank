<?php

require_once("/home/admin/web/dwar.fun/public_html/include/common.inc");

common_init();

function mailer_finished_register_default($param=array()) {
	global $current_site_domain;
	$image_url = 'http://'.$current_site_domain.'/images/';
	ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body>
	  <table cellpadding="0" cellspacing="0" border="0" width="800">
		  <tbody>
			  <tr>
				  <td>
					  <table cellpadding="0" cellspacing="0" border="0" width="100%">
						  <tbody>
							  <tr>
									<td><a href="http://<?=$current_site_domain?>/"><img src="<?=$image_url?>mailer/bg-4_01.jpg" width="400" height="230" border="0" style="display: block;" /></a></td>
									<td><img src="<?=$image_url?>mailer/bg-4_02-new.jpg" width="400" height="230" style="display: block;" /></td>
							  </tr>
						  </tbody>
					  </table>
				  </td>
				  
			  </tr>
			  <tr>
				  <td>
					 <table cellpadding="0" cellspacing="0" border="0" width="100%">
						  <tbody>
							  <tr>
								  <td width="400">
									  <table cellpadding="0" cellspacing="0" border="0" width="400">
										  <tbody>
											  <tr>
												  <td><img src="<?=$image_url?>mailer/bg-4-small_01.jpg" width="400" height="71" style="display: block;" /></td>
											  </tr>
											  <tr>
												  <td>
													  <table cellpadding="0" cellspacing="0" border="0" width="100%">
														  <tbody>
															  <tr>
																  <td height="55" valign="top" align="left"><img src="<?=$image_url?>mailer/bg-4-small_02.jpg" width="191" height="55" style="display: block;" /></td>
																  <td width="209" valign="top" align="left" background="bg-4-small_03.jpg" bgcolor="#f6d69b" style="background-repeat: no-repeat;" height="55p">
																	  <table cellpadding="0" cellspacing="0" border="0">
																		  <tbody>
																			  <tr>
																				  <td height="9"></td>
																			  </tr>
																			  <tr>
																				  <td><font size="2" color="#343128" face="Tahoma, Verdana, Arial, sans-serif"><?=$param['email']?></font><br></td>
																			  </tr>
																			  <tr>
																				  <td height="3"></td>
																			  </tr>
																			  <tr>
																				  <td><font size="2" color="#343128" face="Tahoma, Verdana, Arial, sans-serif"><?=$param['password']?></font></td>
																			  </tr>
																		  </tbody>
																	  </table>
																  </td>
															  </tr>
														  </tbody>
													  </table>
												  </td>
											  </tr>
											  <tr>
												  <td><img src="<?=$image_url?>mailer/bg-4-small_04.jpg" width="400" height="155" style="display: block;" /></td>
											  </tr>
										  </tbody>
									  </table>
								  </td>
								  <td width="400"><img src="<?=$image_url?>mailer/bg-4_04.jpg" width="400" height="281" style="display: block;" /></td>
							  </tr>
						  </tbody>
					  </table>
				  </td>
			  </tr>
			  <tr>
				  <td>
					  <table cellpadding="0" cellspacing="0" border="0">
						  <tbody>
							  <tr>
								  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_05.jpg" width="92" height="76" style="display: block;" /></td>
								  <td valign="top"><a href="http://<?=$current_site_domain?>/"><img src="<?=$image_url?>mailer/bg-4_06.jpg" border="0" width="268" height="76" style="display: block;" /></a></td>
								  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_07.jpg" width="40" height="76" style="display: block;" /></td>
								  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_08.jpg" width="400" height="76" style="display: block;" /></td>
							  </tr>
						  </tbody>
					  </table>
				  </td>
			  </tr>
			  <tr>
				  <td>
					  <table cellpadding="0" cellspacing="0" border="0" width="800">
						  <tbody>
							  <tr>
									<td valign="top"><img src="<?=$image_url?>mailer/bg-4_09.jpg" width="400" height="77" style="display: block;" /></td>
									<td valign="top"><img src="<?=$image_url?>mailer/bg-4_10.jpg" width="400" height="77" style="display: block;" /></td>
							  </tr>
						  </tbody>
					  </table>
				  </td>
			  </tr>
		  </tbody>
	  </table>
  </body>
</html>
<?
	return ob_get_clean();
}

function mailer_not_finished_register() {
	global $current_site_domain;
	$image_url = 'http://'.$current_site_domain.'/images/';
	ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body>
	  <table cellpadding="0" cellspacing="0" border="0" width="800" align="center">
		  <tbody>
			  <tr>
				  <td>
					  <table cellpadding="0" cellspacing="0" border="0" width="799">
						  <tbody>
							  <tr>
								<td><a href="http://<?=$current_site_domain?>/lp3.php?show_lp_form=1"><img src="<?=$image_url?>mailer/bg-4_01.jpg" width="400" height="230" border="0" style="display: block;" /></a></td>
								<td><img src="<?=$image_url?>mailer/bg-4_02-new.jpg" width="400" height="230" style="display: block;" /></td>
							  </tr>
						  </tbody>
					  </table>
				  </td>
			  </tr>
			  <tr>
				  <td>
					  <table border="0" cellpadding="0" cellspacing="0">
						  <tr>
							  <td><img src="<?=$image_url?>mailer/bg-3_02_1.jpg" width="470" height="190" style="display: block;" /></td>
							  <td><a href="http://<?=$current_site_domain?>/lp3.php?show_lp_form=1"><img src="<?=$image_url?>mailer/bg-3_02_2.jpg" width="270" height="190" style="display: block;" border="0" /></a></td>
							  <td><img src="<?=$image_url?>mailer/bg-3_02_3.jpg" width="60" height="190" style="display: block;" /></td>
						  </tr>
					  </table>
				  </td>
			  </tr>
			  <tr>
				  <td><img src="<?=$image_url?>mailer/bg-3_03.jpg" width="800" height="189" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td><img src="<?=$image_url?>mailer/bg-3_04.jpg" width="800" height="271" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td><img src="<?=$image_url?>mailer/bg-3_05.jpg" width="800" height="300" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td><img src="<?=$image_url?>mailer/bg-3_06.jpg" width="800" height="235" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td><img src="<?=$image_url?>mailer/bg-3_07.jpg" width="800" height="198" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td>
					  <table cellpadding="0" cellspacing="0" border="0" width="100%">
						  <tbody>
							  <tr>
								  <td><img src="<?=$image_url?>mailer/bg-3_08_01.jpg" width="193" height="115" style="display: block;" /></td>
								  <td><a href="http://<?=$current_site_domain?>/lp3.php?show_lp_form=1"><img src="<?=$image_url?>mailer/bg-3_08_02.jpg" border="0" width="411" height="115" style="display: block;" /></a></td>
								  <td><img src="<?=$image_url?>mailer/bg-3_08_03.jpg" width="196" height="115" style="display: block;" /></td>
							  </tr>
						  </tbody>
					  </table>
				  </td>
			  </tr>
			  <tr>
				  <td><img src="<?=$image_url?>mailer/bg-3_09.jpg" width="800" height="63" style="display: block;" /></td>
			  </tr>
		  </tbody>
	  </table>
  </body>
</html>

<?
	return ob_get_clean();
}

function mailer_finished_register_facebook() {
	global $current_site_domain;
	$image_url = 'http://'.$current_site_domain.'/images/';
	ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body>
	  <table cellpadding="0" cellspacing="0" border="0">
		  <tbody>
			  <tr>
				  <td valign="top" colspan="3"><a href="http://<?=$current_site_domain?>/"><img src="<?=$image_url?>mailer/bg-4_01.jpg" width="400" height="230" border="0" style="display: block;" /></a></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_02-new.jpg" width="400" height="230" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td valign="top" colspan="3"><img src="<?=$image_url?>mailer/bg-4-fb_01.jpg" width="400" height="281" style="display: block;" /></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4-fb_02.jpg" width="400" height="281" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_05.jpg" width="92" height="76" style="display: block;" /></td>
				  <td valign="top"><a href="http://<?=$current_site_domain?>/"><img src="<?=$image_url?>mailer/bg-4_06.jpg" border="0" width="268" height="76" style="display: block;" /></a></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_07.jpg" width="40" height="76" style="display: block;" /></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_08.jpg" width="400" height="76" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td valign="top" colspan="3"><img src="<?=$image_url?>mailer/bg-4_09.jpg" width="400" height="77" style="display: block;" /></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_10.jpg" width="400" height="77" style="display: block;" /></td>
			  </tr>
		  </tbody>
	  </table>
  </body>
</html>
<?
	return ob_get_clean();
}

function mailer_finished_register_moi_mir() {
	global $current_site_domain;
	$image_url = 'http://'.$current_site_domain.'/images/';
	ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body>
	  <table cellpadding="0" cellspacing="0" border="0">
		  <tbody>
			  <tr>
				  <td valign="top" colspan="3"><a href="http://<?=$current_site_domain?>/"><img src="<?=$image_url?>mailer/bg-4_01.jpg" width="400" height="230" border="0" style="display: block;" /></a></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_02-new.jpg" width="400" height="230" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td valign="top" colspan="3"><img src="<?=$image_url?>mailer/bg-4-mm_01.jpg" width="400" height="281" style="display: block;" /></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4-mm_02.jpg" width="400" height="281" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_05.jpg" width="92" height="76" style="display: block;" /></td>
				  <td valign="top"><a href="http://<?=$current_site_domain?>/"><img src="<?=$image_url?>mailer/bg-4_06.jpg" border="0" width="268" height="76" style="display: block;" /></a></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_07.jpg" width="40" height="76" style="display: block;" /></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_08.jpg" width="400" height="76" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td valign="top" colspan="3"><img src="<?=$image_url?>mailer/bg-4_09.jpg" width="400" height="77" style="display: block;" /></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_10.jpg" width="400" height="77" style="display: block;" /></td>
			  </tr>
		  </tbody>
	  </table>
  </body>
</html>
<?
	return ob_get_clean();
}

function mailer_finished_register_odnoklassniki() {
	global $current_site_domain;
	$image_url = 'http://'.$current_site_domain.'/images/';
	ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body>
	  <table cellpadding="0" cellspacing="0" border="0">
		  <tbody>
			  <tr>
				  <td valign="top" colspan="3"><a href="http://<?=$current_site_domain?>/"><img src="<?=$image_url?>mailer/bg-4_01.jpg" width="400" height="230" border="0" style="display: block;" /></a></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_02-new.jpg" width="400" height="230" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td valign="top" colspan="3"><img src="<?=$image_url?>mailer/bg-4-odnkl_01.jpg" width="400" height="281" style="display: block;" /></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4-odnkl_02.jpg" width="400" height="281" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_05.jpg" width="92" height="76" style="display: block;" /></td>
				  <td valign="top"><a href="http://<?=$current_site_domain?>/"><img src="<?=$image_url?>mailer/bg-4_06.jpg" border="0" width="268" height="76" style="display: block;" /></a></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_07.jpg" width="40" height="76" style="display: block;" /></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_08.jpg" width="400" height="76" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td valign="top" colspan="3"><img src="<?=$image_url?>mailer/bg-4_09.jpg" width="400" height="77" style="display: block;" /></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_10.jpg" width="400" height="77" style="display: block;" /></td>
			  </tr>
		  </tbody>
	  </table>
  </body>
</html>
<?
	return ob_get_clean();
}

function mailer_finished_register_vkontakte() {
	global $current_site_domain;
	$image_url = 'http://'.$current_site_domain.'/images/';
	ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body>
	  <table cellpadding="0" cellspacing="0" border="0">
		  <tbody>
			  <tr>
				  <td valign="top" colspan="3"><a href="http://<?=$current_site_domain?>/"><img src="<?=$image_url?>mailer/bg-4_01.jpg" width="400" height="230" border="0" style="display: block;" /></a></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_02-new.jpg" width="400" height="230" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td valign="top" colspan="3"><img src="<?=$image_url?>mailer/bg-4-vk_01.jpg" width="400" height="281" style="display: block;" /></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4-vk_02.jpg" width="400" height="281" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_05.jpg" width="92" height="76" style="display: block;" /></td>
				  <td valign="top"><a href="http://<?=$current_site_domain?>/"><img src="<?=$image_url?>mailer/bg-4_06.jpg" border="0" width="268" height="76" style="display: block;" /></a></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_07.jpg" width="40" height="76" style="display: block;" /></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_08.jpg" width="400" height="76" style="display: block;" /></td>
			  </tr>
			  <tr>
				  <td valign="top" colspan="3"><img src="<?=$image_url?>mailer/bg-4_09.jpg" width="400" height="77" style="display: block;" /></td>
				  <td valign="top"><img src="<?=$image_url?>mailer/bg-4_10.jpg" width="400" height="77" style="display: block;" /></td>
			  </tr>
		  </tbody>
	  </table>
  </body>
</html>
<?
	return ob_get_clean();
}

function mailer_cron_7days($param) {
	global $current_site_domain;
	$image_url = 'http://'.$current_site_domain.'/images/mailer/';
	switch ($param['group']) {
		case 1:
			$gifts = array(
				0 => array (
					'artikul_id' => 10685,
					'image_url' => 'item-001.gif',
				),
				1 => array (
					'artikul_id' => 10687,
					'image_url' => 'item-002.gif',
				),
				2 => array (
					'artikul_id' => 10689,
					'image_url' => 'item-003.gif',
				),
			);
			break;
		default: return false;
	}
	ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body>
	  <table cellpadding="0" cellspacing="0" border="0" width="800">
		  <tbody>
			  <tr>
				  <td>
					  <table cellpadding="0" cellspacing="0" border="0" width="100%">
						  <tbody>
							  <tr>
									<td><a href="http://<?=$current_site_domain?>/"><img src="<?=$image_url?>bg-4_01.jpg" width="400" height="230" border="0" style="display: block;" /></a></td>
									<td><img src="<?=$image_url?>bg-4_02-new.jpg" width="400" height="230" style="display: block;" /></td>
							  </tr>
						  </tbody>
					  </table>
				  </td>
			  </tr>
			  <tr>
				  <td bgcolor="#f4d69a">
					 <table cellpadding="0" cellspacing="0" border="0" width="100%">
						  <tbody>
							  <tr>
								  <td width="63"><img src="<?=$image_url?>bg_04.jpg" width="63" height="542" style="display: block;" /></td>
								  <td align="center" valign="top">
									  <table cellpadding="0" cellspacing="0" border="0" width="100%">
										  <tbody>
											  <tr>
												  <td align="center">
													  <br><img src="<?=$image_url?>line.jpg" width="676" height="6" /><br>
												  </td>
											  </tr>
											  <tr>
												  <td>
													  <table cellpadding="0" cellspacing="0" border="0" width="100%">
														  <tbody>
															  <tr>
																  <td valign="top">
																	  <br>
																	  <table cellpadding="0" cellspacing="0" border="0" width="99%">
																		  <tbody>
																			  <tr>
																				  <td align="center"><font size="4" color="#000000" face="Tahoma, Verdana, Arial, sans-serif" style="font-size: 17px;"><i><?=sprintf(($param['gender'] == 1 ? translate('Уважаемый %s!') : translate('Уважаемая %s!')), $param['nick']) ?></i></font></td>
																			  </tr>
																			  <tr><td height="15"></td></tr>
																			  <tr>
																				  <td>
																					  <font size="3" color="#000000" face="Tahoma, Verdana, Arial, sans-serif" style="font-size: 15px;"><?=translate('В течение недели вы не заходили в игру "'.MAIN_TITLE.'". Приглашаем вас вновь посетить удивительный мир Фэо и продолжить восхождение к вершинам славы,
																						  воинских побед и свершений. <br>
																						  Вас ждет приятный <i>сюрприз - подарок</i> от администрации проекта. <br>
																						  Желаем удачи в сражениях!') ?>
																					  </font>
																				  </td>
																			  </tr>
																			  <tr><td height="15"></td></tr>
																			  <tr>
																				  <td align="right">
																					  <font size="3" color="#000000" face="Tahoma, Verdana, Arial, sans-serif" style="font-size: 15px;"><i><?=translate('С уважением, администрация игры<br> "'.MAIN_TITLE.'".') ?></i></font>
																				  </td>
																			  </tr>
																			  <tr><td height="30"></td></tr>
																			  <tr>
																				  <td align="center"><img src="<?=$image_url?>gift.png" width="134" heigh="34" /></td>
																			  </tr>
																			  <tr><td height="15"></td></tr>
																			  <tr>
																				  <td align="center">
																						<? foreach ($gifts as $gift) { ?>
																							<a target="_blank" href="<?=translate('http://asteria.top/artifact_info.php?artikul_id=').$gift['artikul_id']?>" style="text-decoration: none;"><img border="0" src="<?=$image_url.$gift['image_url']?>" width="60" height="60" alt=""></a>&nbsp;
																						<? } ?>
																				  </td>
																			  </tr>
																		  </tbody>
																	  </table>
																  </td>
																  <td valign="middle" align="center">
																		<img src="<?=$image_url?>img.jpg" width="330" height="317" />
																		<br><br>
																		<a href="http://<?=$current_site_domain?>/login.php?uid=<?=$param['uid']?>&use_code=<?=$param['code']?>" style="text-decoration: none;"><img src="<?=$image_url?>get_present.png" width="267" height="80" border="0" /></a>
																  </td>
															  </tr>
														  </tbody>
													  </table>
												  </td>
											  </tr>
											  <tr>
												  <td align="center">
													  <br><img src="<?=$image_url?>line.jpg" width="676" height="6" /><br>
												  </td>
											  </tr>
											  <tr>
												  <td height="10"></td>
											  </tr>
											  <tr>
												  <td align="right">
													  <a color="#998767" href="http://<?=$current_site_domain?>/lp3.php?uid=<?=$param['uid']?>&unsubscribe_code=<?=$param['unsubscribe_code']?>"><font size="2" color="#998767" face="Tahoma, Verdana, Arial, sans-serif" style="font-size:13px;"><?=translate('отказаться от рассылки')?></font></a>
												  </td>
											  </tr>
										  </tbody>
									  </table>
									  
									  
								  </td>
								  <td width="58"><img src="<?=$image_url?>bg_06.jpg" width="58" height="542" style="display: block;" /></td>
							  </tr>
						  </tbody>
					  </table>
				  </td>
			  </tr>
			  <tr>
				  <td valign="top"><img src="<?=$image_url?>bg-3_09.jpg" width="800" height="63" style="display: block;" /></td>
			  </tr>
		  </tbody>
	  </table>
  </body>
</html>
<?
	return ob_get_clean();
}

function mailer_cron_30days($param) {
	global $current_site_domain;
	$image_url = 'http://'.$current_site_domain.'/images/mailer/';
	switch ($param['group']) {
		case 1:
			$gifts = array(
				0 => array (
					'artikul_id' => 10686,
					'image_url' => 'item-001.gif',
				),
				1 => array (
					'artikul_id' => 10688,
					'image_url' => 'item-002.gif',
				),
				2 => array (
					'artikul_id' => 10690,
					'image_url' => 'item-003.gif',
				),
			);
			break;
		default: return false;
	}
	ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body>
	  <table cellpadding="0" cellspacing="0" border="0" width="800">
		  <tbody>
			  <tr>
				  <td>
					  <table cellpadding="0" cellspacing="0" border="0" width="100%">
						  <tbody>
							  <tr>
									<td><a href="http://<?=$current_site_domain?>/"><img src="<?=$image_url?>bg_01-new.jpg" width="800" height="232" border="0" style="display: block;" /></a></td>
							  </tr>
						  </tbody>
					  </table>
				  </td>
				  
			  </tr>
			  <tr>
				  <td bgcolor="#f4d69a">
					 <table cellpadding="0" cellspacing="0" border="0" width="100%">
						  <tbody>
							  <tr>
								  <td width="75"><img src="<?=$image_url?>bg_04-2.jpg" width="75" height="610" style="display: block;" /></td>
								  <td align="center" valign="top">
									  <table cellpadding="0" cellspacing="0" border="0" width="100%">
										  <tbody>
											  <tr><td height="10"></td></tr>
											  <tr>
												  <td align="left">
													  <img src="<?=$image_url?>line.jpg" width="652" height="6" />
												  </td>
											  </tr>
											  <tr>
												  <td>
													  <table cellpadding="0" cellspacing="0" border="0" width="100%">
														  <tbody>
															  <tr>
																  <td valign="top">
																	  <table cellpadding="0" cellspacing="0" border="0" width="100%">
																		  <tbody>
																			  <tr><td height="10"></td></tr>
																			  <tr>
																				  <td align="center"><font size="4" color="#000000" face="Tahoma, Verdana, Arial, sans-serif" style="font-size: 17px;"><i><?=sprintf(($param['gender'] == 1 ? translate('Уважаемый %s!') : translate('Уважаемая %s!')), $param['nick']) ?></i></font></td>
																			  </tr>
																			  <tr><td height="15"></td></tr>
																			  <tr>
																				  <td>
																					  <font size="3" color="#000000" face="Tahoma, Verdana, Arial, sans-serif" style="font-size: 15px;"><?=translate('Целый месяц вы не посещали игру "'.MAIN_TITLE.'".
																						  За время вашего отсутствия в мире Фэо произошло много интересных событий, стать участником которых мы приглашаем и вас. Присоединяйтесь к защитникам
																						  мира драконов!') ?></font>
																						  <font size="3" color="#660000" face="Tahoma, Verdana, Arial, sans-serif" style="font-size: 15px;"><i><?=translate('Приятный сюрприз') ?></i></font>
																						  <font size="3" color="#000000" face="Tahoma, Verdana, Arial, sans-serif" style="font-size: 15px;"><?=translate(', подарок от администрации проекта, уже ждет вас.<br>Желаем удачи в сражениях!') ?>
																					  </font>
																				  </td>
																			  </tr>
																			  <tr><td height="15"></td></tr>
																			  <tr>
																				  <td align="right">
																					  <font size="2" color="#000000" face="Tahoma, Verdana, Arial, sans-serif" style="font-size: 14px;"><i><?=translate('С уважением, администрация игры&nbsp;<br> "'.MAIN_TITLE.'".') ?>&nbsp;</i></font>
																				  </td>
																			  </tr>
																			  <tr><td height="26"></td></tr>
																			  <tr>
																				  <td align="center"><img src="<?=$image_url?>gift.png" width="134" heigh="34" /></td>
																			  </tr>
																			  <tr><td height="15"></td></tr>
																			  <tr>
																				  <td align="center">
																						<? foreach ($gifts as $gift) { ?>
																							<a target="_blank" href="<?=translate('http://asteria.top/artifact_info.php?artikul_id=').$gift['artikul_id']?>" style="text-decoration: none;"><img border="0" src="<?=$image_url.$gift['image_url']?>" width="60" height="60" alt=""></a>&nbsp;
																						<? } ?>
																				  </td>
																			  </tr>
																		  </tbody>
																	  </table>
																  </td>
																  <td valign="top" align="center">
																		<table cellpadding="0" cellspacing="0" border="0" width="100%">
																		  <tbody>
																			  <tr>
																				  <td align="center"><img src="<?=$image_url?>jpg.jpg" width="348" height="431" /></td>
																			 </tr>
																			 <tr>
																				  <td align="center"><a href="http://<?=$current_site_domain?>/login.php?uid=<?=$param['uid']?>&use_code=<?=$param['code']?>" style="text-decoration: none;"><img src="<?=$image_url?>get_present.png" width="267" height="80" border="0" /></a></td>
																			  </tr>
																		  </tbody>
																	  </table>
																  </td>
															  </tr>
															  <tr><td height="15"></td></tr>
														  </tbody>
													  </table>
												  </td>
											  </tr>
											  <tr>
												  <td align="left">
													  <img src="<?=$image_url?>line.jpg" width="652" height="6" />
												  </td>
											  </tr>
											  <tr>
												  <td height="10"></td>
											  </tr>
											  <tr>
												  <td align="right">
													  <a color="#998767" href="http://<?=$current_site_domain?>/lp3.php?uid=<?=$param['uid']?>&unsubscribe_code=<?=$param['unsubscribe_code']?>"><font size="2" color="#998767" face="Tahoma, Verdana, Arial, sans-serif" style="font-size:13px;"><?=translate('отказаться от рассылки') ?></font></a>&nbsp;&nbsp;&nbsp;&nbsp;
												  </td>
											  </tr>
										  </tbody>
									  </table>
								  </td>
								  <td width="52"><img src="<?=$image_url?>bg_06-2.jpg" width="52" height="610" style="display: block;" /></td>
							  </tr>
						  </tbody>
					  </table>
				  </td>
			  </tr>
			  <tr>
				  <td valign="top"><img src="<?=$image_url?>bg-3_09.jpg" width="800" height="63" style="display: block;" /></td>
			  </tr>
		  </tbody>
	  </table>
  </body>
</html>
<?
	return ob_get_clean();
}

function mailer_cron_90days($param) {
	global $current_site_domain;
	$image_url = 'http://'.$current_site_domain.'/images/mailer/90days/';
	
	ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body>
	  <table cellpadding="0" cellspacing="0" border="0" width="800">
		  <tbody>
			  <tr>
				  <td>
					  <table cellpadding="0" cellspacing="0" border="0" width="100%">
						  <tbody>
							  <tr>
									<td><a href="<?=$current_site_domain?>"><img src="<?=$image_url?>bg_01-new.jpg" width="800" height="239" border="0" style="display: block;" /></a></td>
							  </tr>
						  </tbody>
					  </table>
				  </td>
			  </tr>
			  <tr>
				  <td bgcolor="#f4d69a">
					 <table cellpadding="0" cellspacing="0" border="0" width="100%">
						  <tbody>
							  <tr>
								  <td width="75"><img src="<?=$image_url?>bg_02.jpg" width="59" height="1280" style="display: block;" /></td>
								  <td align="center" valign="top">
									  <table cellpadding="0" cellspacing="0" border="0" width="100%">
										  <tbody>
											  <tr>
												  <td align="left">
													  <br><img src="<?=$image_url?>line.jpg" width="652" height="6" style="display: block;" /><br>
												  </td>
											  </tr>
											  <tr><td height="30"></td></tr>
											  <tr>
												  <td valign="top">
													  <table cellpadding="0" cellspacing="0" border="0" width="100%">
														  <tbody>
															  <tr>
																  <td valign="top">
																	  <table cellpadding="0" cellspacing="0" border="0" width="100%">
																		  <tbody>
																			  <tr>
																				  <td align="center"><font size="4" color="#000000" face="Tahoma, Verdana, Arial, sans-serif" style="font-size: 17px;"><i><?=sprintf(($param['dwar']['gender'] == 1 ? translate('Уважаемый %s!') : translate('Уважаемая %s!')), $param['dwar']['nick']) ?></i></font></td>
																			  </tr>
																			  <tr><td height="15"></td></tr>
																			  <tr>
																				  <td>
																					  <font size="3" color="#000000" face="Tahoma, Verdana, Arial, sans-serif" style="font-size: 15px;"><?=translate('Три месяца вашего отсутствия в игре "'.MAIN_TITLE.'" не остались незамеченными нами. Мы надеемся, что вы не утратили тягу к приключениям и ожесточенным сражениям и вновь
																						  вернетесь на просторы Фэо, чтобы защищать родной материк и весь мир от врагов и порождений Хаоса. Если же вас влечет новое и неизведанное,
																						  мы приглашаем посетить другие миры нашей игровой вселенной.')?>
																					  </font>
																				  </td>
																			  </tr>
																			  <tr><td height="15"></td></tr>
																			  <tr>
																				  <td align="right">
																					  <font size="2" color="#000000" face="Tahoma, Verdana, Arial, sans-serif" style="font-size: 14px;"><i><?=translate('С уважением, администрация игры')?>&nbsp;<br> <?=translate('"'.MAIN_TITLE.'"')?>.&nbsp;</i></font>
																				  </td>
																			  </tr>
																		  </tbody>
																	  </table>
																  </td>
																  <td align="center">
																	  <a href="http://<?=$current_site_domain?>/login.php?uid=<?=$param['dwar']['uid']?>&use_code=<?=$param['dwar']['code']?>" target="_blank"><img src="<?=$image_url?>dwar_play.jpg" width="332" height="315" border="0" style="display: block;" /></a>
																  </td>
															  </tr>
														  </tbody>
													  </table>
												  </td>
											  </tr>
											   <tr>
												  <td align="left">
													  <br><img src="<?=$image_url?>line.jpg" width="652" height="6" style="display: block;" /><br>
												  </td>
											  </tr>
											  <tr>
												  <td>
													  <table cellpadding="0" cellspacing="0" border="0" width="100%">
														  <tbody>
															  <tr><td height="20"></td></tr>
															  <tr>
																  <td><a href="<?=$param['tks']['url']?>" target="_blank"><img src="<?=$image_url?>3klogo.jpg" width="321" border="0" height="300"  style="display: block;" /></a></td>
																  <td width="25"></td>
																  <td>
																	  <table cellpadding="0" cellspacing="0" border="0" width="100%">
																		  <tbody>
																			  <tr>
																				  <td>
																					  <font size="3" color="#000000" face="Tahoma, Verdana, Arial, sans-serif" style="font-size: 15px;">
																							<?=translate('Погрузитесь в древний мир проекта "Троецарствие", в основе создания сюжета которого четко
																							проработанная и известная вселенная, созданная Юрием Никитиным и описанная им в книгах одноименной серии.')?>
																					  </font>
																				  </td>
																			  </tr>
																			  <tr><td height="20"></td></tr>
																			  <tr>
																				  <td><img src="<?=$image_url?>3k_present.jpg" width="294" height="130" style="display: block;" /></td>
																			  </tr>
																			  <tr><td height="15"></td></tr>
																			  <tr>
																				  <td><a href="<?=$param['tks']['url']?>register.php?use_code=<?=$param['tks']['code']?>" target="_blank"><img src="<?=$image_url?>3k_get_present.jpg" border="0" width="300" height="49" style="display: block;" /></a></td>
																			  </tr>
																		  </tbody>
																	  </table>
																  </td>
															  </tr>
														  </tbody>
													  </table>
												  </td>
											  </tr>
											  <tr>
												  <td align="left">
													  <br><img src="<?=$image_url?>line.jpg" width="652" height="6" style="display: block;" /><br>
												  </td>
											  </tr>
											  <tr>
												  <td>
													  <table cellpadding="0" cellspacing="0" border="0" width="100%">
														  <tbody>
															  <tr><td height="20"></td></tr>
															  <tr>
																  <td><a href="<?=$param['jugger']['url']?>" target="_blank"><img src="<?=$image_url?>juggerlogo.jpg" border="0" width="315" height="266" style="display: block;" /></a></td>
																  <td width="25"></td>
																  <td>
																	  <table cellpadding="0" cellspacing="0" border="0" width="100%">
																		  <tbody>
																			  <tr>
																				  <td>
																					  <font size="3" color="#000000" face="Tahoma, Verdana, Arial, sans-serif" style="font-size: 15px;">
																							<?=translate('Примите участие в захватывающих трехмерных баталиях проекта "Джаггернаут", установившего новый
																							стандарт качества в сфере онлайн-развлечений.')?>
																					  </font>
																				  </td>
																			  </tr>
																			  <tr><td height="20"></td></tr>
																			  <tr>
																				  <td><img src="<?=$image_url?>jugger_present.jpg" width="300" height="113" style="display: block;" /></td>
																			  </tr>
																			  <tr><td height="15"></td></tr>
																			  <tr>
																				  <td><a href="<?=$param['jugger']['url']?>register.php?code=<?=$param['jugger']['code']?>" target="_blank"><img src="<?=$image_url?>jugger_get_present.jpg" border="0" width="295" height="38" style="display: block;" /></a></td>
																			  </tr>
																		  </tbody>
																	  </table>
																  </td>
															  </tr>
														  </tbody>
													  </table>
												  </td>
											  </tr>
											  <tr>
												  <td align="left">
													  <br><img src="<?=$image_url?>line.jpg" width="652" height="6" style="display: block;" /><br>
												  </td>
											  </tr>
											  <tr>
												  <td height="10"></td>
											  </tr>
											  <tr>
												  <td align="right">
													  &nbsp;&nbsp;&nbsp;&nbsp;
												  </td>
											  </tr>
											  <tr><td height="40"></td></tr>
											  <tr>
												  <td align="center">
													  <font size="4" color="#af3606" face="Tahoma, Verdana, Arial, sans-serif" style="font-size:22px;"><?=translate('Вас ждут яркие впечатления и подарки новичкам!')?></font>
												  </td>
											  </tr>
										  </tbody>
									  </table>
								  </td>
								  <td width="52"><img src="<?=$image_url?>bg_04.jpg" width="60" height="1280" style="display: block;" /></td>
							  </tr>
						  </tbody>
					  </table>
				  </td>
			  </tr>
			  <tr>
				  <td valign="top"><img src="<?=$image_url?>bg_05.jpg" width="800" height="70" style="display: block;" /></td>
			  </tr>
		  </tbody>
	  </table>
  </body>
</html>
<?
	return ob_get_clean();
}


function mailer_cron_5days($param) {
	global $current_site_domain, $SERVERS;
	$image_url = 'http://'.$current_site_domain.'/images/mailer/5days/';
	
	$partner_conf = array(
		1 => array('site_id' => 69092, 'p' => 75, 'sub_id' => 0),
		2 => array('site_id' => 69093, 'p' => 77, 'sub_id' => 0),
		3 => array('site_id' => 69094, 'p' => 118, 'sub_id' => 0),
		4 => array('site_id' => 69095, 'p' => 678, 'sub_id' => 0),
	);
	ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body>
	  <center>
		  <table cellpadding="0" cellspacing="0" border="0" width="700">
			  <tbody>
				  <tr>
					  <td>
						  <a href="http://<?=$current_site_domain?>/" target="_blank"><img src="<?=$image_url?>img_01.jpg" width="700" height="212" border="0" style="display: block;" /></a>
					  </td>
				  </tr>
			  </tbody>
		  </table>
		  <table cellpadding="0" cellspacing="0" border="0" width="700" bgcolor="#f4d69a">
			  <tbody>
				  <tr>
					  <td>
						  <img src="<?=$image_url?>img_02.jpg" width="40" height="91" style="display: block;" />
					  </td>
					  <td width="619" valign="top">
						  <table cellpadding="0" cellspacing="0" border="0">
							  <tbody>
								  <tr>
									  <td align="center">
										  <font face="Tahoma, Verdana, sans-serif" size="2" color="#af3606" style="font-size:16px;">
												<?=sprintf(($param['gender'] == 1 ? translate('Уважаемый %s!') : translate('Уважаемая %s!')), $param['nick']) ?>!
										  </font>
									  </td>
								  </tr>
								  <tr><td height="1"></td></tr>
								  <tr>
									  <td align="center">
										  <font face="Tahoma, Verdana, sans-serif" size="2" color="#6c5f4e" style="font-size:15px;">
												<?=translate('Вы не посещали нашу игру целый день, мы искренне надеемся, что 
												вы не забыли о');?> <a href="http://<?=$current_site_domain?>/effect_info.php?nick=<?=$param['nick']?>"><?=translate('бонусном благословении');?></a>, <?=translate('которое поможет вам в первые дни познания мира Фэо');?>!
										  </font>
									  </td>
								  </tr>
								  <tr><td height="9"></td></tr>
							  </tbody>
						  </table>
					  </td>
					  <td>
						  <img src="<?=$image_url?>img_04.jpg" width="41" height="91" style="display: block;" />
					  </td>
				  </tr>
			  </tbody>
		  </table>
		  <table cellpadding="0" cellspacing="0" border="0" width="700">
			  <tbody>
				  <tr>
					  <td>
						  <img src="<?=$image_url?>img_08.jpg" width="700" height="420" style="display: block;" alt="<?=translate('Вас ждет неповторимый волшебный мир, в котором можно в полной мере реализовать
							свою тягу к приключениям. Более 600 заданий, возможность развивать
							более 9 профессий и регулярные события для всех
							уровневых групп никого не оставят равнодушными.');?>" />
						  <img src="<?=$image_url?>img_09.jpg" width="700" height="301" style="display: block;" alt="" />
						  <img src="<?=$image_url?>img_10.jpg" width="700" height="355" style="display: block;" alt="" />
						  <img src="<?=$image_url?>img_11.jpg" width="700" height="323" style="display: block;" alt="" />
					  </td>
				  </tr>
			  </tbody>
		  </table>
		  <table cellpadding="0" cellspacing="0" border="0" width="700">
			  <tbody>
				  <tr>
					  <td><img src="<?=$image_url?>img_15.jpg" width="700" height="48" style="display: block;" /></td>
				  </tr>
			  </tbody>
		  </table>
	  </center>
  </body>
</html>

<?
	return ob_get_clean();
}

function mailer_user_delete($param) {
	
	$image_url = $param['current_site_domain'].'images/mailer/user_delete/';
	$remind_link = $param['current_site_domain'].'send_password.php';
	$unsubscribe_link = $param['current_site_domain'].'?unsubscribe='.$param['email'];	
	
	ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body leftmargin="0" rightmargin="0" topmargin="0" bottommargin="0" bgcolor="#ffffff">

	<center>
		<table width="700" border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td valign="bottom">
					<a href="<?=$param['current_site_domain']?>"><img src="<?=$image_url?>head.jpg" alt="" width="700" height="220" border="0" align="bottom"></a><br>
				</td>
			</tr>
			<tr>
				<td align="center" bgcolor="#f4d69a" background="<?=$image_url?>body.jpg">
					<table width="600" border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td valign="top" width="50%">
								<p align="center">
									<font size="4" color="#af3606" face="Tahoma, Verdana, Arial, sans-serif"><?=sprintf(($param['gender'] == 1 ? translate('УВАЖАЕМЫЙ %s!') : translate('УВАЖАЕМАЯ %s!')), $param['nick']) ?><br><?=translate('Вы давно не заходили в игру')?><br><?=translate(MAIN_TITLE)?></font>
								</p>
								
								<p>
									<font size="2" color="#5d5245" face="Tahoma, Verdana, Arial, sans-serif"><?=translate('Если у вас возникли сложности и вы не смогли разобраться в игровом процессе, можете найти ответы в');?> <a target="_blank" href="<?=$param['current_site_domain']?>info/info/index.php?obj=cat&id=29"><font color="#af3606"><?=translate('библиотеке игры');?></font></a> <?=translate('или обратиться к');?> <br><a target="_blank" href="<?=translate('http://mentor.dclans.ru');?>"><img src="<?=$image_url?>ico_mentor.gif" alt="" width="17" height="15" border="0" align="absbottom"></a> Менторам.</font>
								</p>
								
								<p>
									<font size="2" color="#5d5245" face="Tahoma, Verdana, Arial, sans-serif"><?=translate('Также полезную информацию можно найти на');?> <a target="_blank" href="<?=translate('http://mentor.dclans.ru');?>"><font color="#af3606"><?=translate('сайте менторов')?></font></a>: <?=translate('как качать репутации, как ходить в инстансы, как получить редкие вещи и т.д. ')?></font>
								</p>
								
								<p>
									<font size="2" color="#5d5245" face="Tahoma, Verdana, Arial, sans-serif"><?=translate('Сейчас ваш персонаж поставлен в очередь на удаление, все игровые деньги и вещи безвозвратно исчезнут');?> <b><?=$param['time_delete'];?></b> <?=translate('без возможности восстановления. Чтобы отменить удаление, достаточно')?> <a href="<?=$param['current_site_domain']?>"><font color="#af3606"><?=translate('зайти в игру');?></font></a>.</font>
								</p>
								
								<table width="300" border="0" cellpadding="0" cellspacing="0">
									<tr>
										<td valign="middle" width="30">
											<img src="<?=$image_url?>decor.gif" alt="" width="30" height="70" border="0" align="bottom">
										</td>
										<td width="10">&nbsp;</td>
										<td>
											<p>
												<font size="2" color="#5d5245" face="Tahoma, Verdana, Arial, sans-serif">
													<?=translate('Напоминаем Ваш логин');?>:<br>
													<font color="#af3606"><?=$param['email'];?></font><br>
													<?=translate('Для входа в игру перейдите');?><br>
													<?=translate('по ссылке');?>: <a href="<?=$param['current_site_domain'];?>"><font color="#af3606"><?=$param['current_site_domain'];?></font></a><br>
													<?=translate('или нажмите кнопку "играть".');?>
												</font>
											</p>
										</td>
									</tr>
								</table>
							</td>
							<td align="center" valign="top" width="50%">
								<a href="<?=$param['current_site_domain'];?>"><img src="<?=$image_url?>man.jpg" alt="" width="300" height="342" border="0" align="bottom"></a><br>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<p>
									<br>
									<font size="2" color="#5d5245" face="Tahoma, Verdana, Arial, sans-serif"><?=translate('Если Вы забыли свой пароль, Вы можете восстановить его по этой ссылке:');?><br><a href="<?=$remind_link?>"><font color="#af3606"><?=$remind_link?></font></a></font>
								</p>
								
								<p>
									<img src="<?=$image_url?>separator.jpg" alt="" width="600" height="7" border="0" align="bottom">
								</p>
							</td>
						</tr>
						<tr>
							<td align="center" colspan="2">
								<br><a href="<?=$param['current_site_domain'];?>"><img src="<?=$image_url?>but_play.jpg" alt="" width="381" height="93" border="0" align="bottom"></a><br>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td valign="top">
					<img src="<?=$image_url?>foot.jpg" alt="" width="700" height="58" border="0" align="bottom">
				</td>
			</tr>
		</table>
	</center>
	
</body>

</html>
<?
	return ob_get_clean();
}


function mailer_papa_jons($nick) {
	return;
	global $current_site_domain;
	$image_url = 'http://'.$current_site_domain.'/images/mailer/papa_jons/';
	ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title></title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  </head>
  <body>
	  <center>
		  <table cellpadding="0" cellspacing="0" border="0" width="700">
			  <tbody>
				  <tr>
					  <td>
						  <a href="#" target="_blank"><img src="<?=$image_url?>img_01.jpg" width="700" height="253" border="0" style="display: block;" alt="Сертификат" /></a>
					  </td>
				  </tr>
				  <tr>
					  <td><img src="<?=$image_url?>img_02.jpg" width="700" height="42"  style="display: block;" alt="<?=translate('Поздравляем!');?>" /></td>
				  </tr>
			  </tbody>
		  </table>
		  <table cellpadding="0" cellspacing="0" border="0" width="700" bgcolor="#f4d69a">
			  <tbody>
				  <tr>
					  <td>
						  <img src="<?=$image_url?>img_03.jpg" width="57" height="167" style="display: block;" />
					  </td>
					  <td width="593" valign="top">
						  <table cellpadding="0" cellspacing="0" border="0">
							  <tbody>
								  <tr><td height="4"></td></tr>
								  <tr>
									  <td align="center">
										  <font face="Tahoma, Verdana, sans-serif" size="3" color="#bd3811" style="font-size:16px;">
											  <?=translate('Вы стали победителем акции “'.MAIN_TITLE.'»”!');?>
										  </font>
									  </td>
								  </tr>
								  <tr><td height="5"></td></tr>
								  <tr>
									  <td align="left">
										  <font face="Tahoma, Verdana, sans-serif" size="2" color="#633113" style="font-size:14px;">
												<?=translate('Для получения бесплатной пиццы');?> &laquo;<?=translate(MAIN_TITLE);?>&raquo; <?=translate('вам необходимо распечатать это письмо
												и предъявить его сотрудникам любого');?> </font>
										  <a href="http://www.papajohns.ru/" target="_blank" style="color:#633113;">
											  <font face="Tahoma, Verdana, sans-serif" size="2" color="#633113" style="font-size:14px;"><?=translate('ресторана “Папа Джонс”');?></font></a>
										  <font face="Tahoma, Verdana, sans-serif" size="2" color="#633113" style="font-size:14px;"><?=translate('в Москве до');?> <strong><?=translate('16 июня 2012 года');?></strong>
												<?=translate('включительно.');?>
										  </font>
									  </td>
								  </tr>
								  <tr><td height="8"></td></tr>
								  <tr>
									  <td align="center"><img src="<?=$image_url?>code.jpg" width="518" height="23" alt="" style="display: block;" /></td>
								  </tr>
								  <tr><td align="center">
										  <font face="Tahoma, Verdana, sans-serif" size="2" color="#bd3811" style="font-size:16px;">
											  <?=translate('Промо-код');?>: <?=$nick?>
										  </font>
								  </td></tr>
								  <tr>
									  <td align="center"><img src="<?=$image_url?>code.jpg" width="518" height="23" alt="" style="display: block;" /></td>
								  </tr>
								  <tr><td height="6"></td></tr>
							  </tbody>
						  </table>
					  </td>
					  <td>
						  <img src="<?=$image_url?>img_05.jpg" width="61" height="167" style="display: block;" />
					  </td>
				  </tr>
			  </tbody>
		  </table>
		  <table cellpadding="0" cellspacing="0" border="0" width="700" bgcolor="#f4d69a">
			  <tbody>
				  <tr>
					  <td>
						  <img src="<?=$image_url?>img_06_01.jpg" width="356" height="49" style="display: block;" />
					  </td>
					  <td width="287" align="right">
						  <font face="Tahoma, Verdana, sans-serif" size="1" color="#633113" style="font-size:12px;">
								<?=translate('Хорошей игры и приятного аппетита!');?><br>
								<?=translate('С уважением, Администрация проекта');?><br> &laquo;<?=translate(MAIN_TITLE);?>&raquo;
						  </font>
					  </td>
					  <td>
						  <img src="<?=$image_url?>img_06_03.jpg" width="57" height="49" style="display: block;" />
					  </td>
				  </tr>
			  </tbody>
		  </table>
		  <table cellpadding="0" cellspacing="0" border="0" width="700">
			  <tbody>
				  <tr>
					  <td><img src="<?=$image_url?>img_07.jpg" width="700" height="377" style="display: block;" alt="<?=translate('Удивительные поля битв, где между собой
							   могут сразиться игроки Российских, Английских, Немецких и Польских серверов. За небольшую плату каждый игрок,
							   достигший 3 уровня, сможет одеть полный комплект фиолетовых доспехов на свой уровень и сразиться с равными противниками.');?>" /></td>
				  </tr>
			  </tbody>
		  </table>
	  </center>
  </body>
</html>

<?
	return ob_get_clean();
}
