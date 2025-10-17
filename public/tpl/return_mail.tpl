<?php

require_once("include/common.inc");

common_init();

function return_mail_default($nick, $login_url){
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
	  <center>
		  <table cellpadding="0" cellspacing="0" border="0" width="700">
			  <tbody>
				  <tr>
					  <td>
						  <a href="http://<?=$current_site_domain.'/'?>" target="_blank"><img src="http://<?=$current_site_domain.'/'.locale_path().'images/return_tpl_1n.jpg'?>" width="700" height="256" border="0" style="display: block;" /></a>
					  </td>
				  </tr>
				  <tr>
					  <td><img src="http://<?=$current_site_domain.'/'.locale_path().'images/return_tpl_2n.jpg'?>" width="700" height="45"  style="display: block;" /></td>
				  </tr>
			  </tbody>
		  </table>
		  <table cellpadding="0" cellspacing="0" border="0" width="700" bgcolor="#f4d69a">
			  <tbody>
				  <tr>
					  <td>
						  <img src="<?=$image_url.'return_tpl_3n.jpg'?>" width="63" height="310" style="display: block;" />
					  </td>
					  <td width="593" valign="top">
						  <table cellpadding="0" cellspacing="0" border="0">
							  <tbody>
								  <tr><td height="20"></td></tr>
								  <tr>
									  <td align="left">
										  <font face="Tahoma, Verdana, sans-serif" size="2" color="#633113" style="font-size:14px;">
												<?=translate('Я');?>, <b><?=$nick?></b>, <?=translate('расстроен тем, что ты давно не появляешься в игре. В &laquo;'.MAIN_TITLE.'&raquo; произошло масштабное
												обновление, о котором я тебе расскажу, как только ты вернешься.');?>
										  </font>
									  </td>
								  </tr>
								  <tr><td height="10"></td></tr>
								  <tr>
									  <td align="center">
										  <font face="Tahoma, Verdana, sans-serif" size="2" color="#633113" style="font-size:14px;">
												<?=translate('Если откликнешься на мой призыв тебя ждет');?><br> <?=translate('награда');?> - </font>
										  <a href="http://<?=$current_site_domain.'/'?>artifact_info.php?artikul_id=10494" target="_blank" style="color:#bd3811; text-decoration: none;">
											  <font face="Tahoma, Verdana, sans-serif" size="3" color="#bd3811" style="font-size:17px;"><?=translate('Эликсир несокрушимости');?></font>
										  </a>
										  <font face="Tahoma, Verdana, sans-serif" size="2" color="#633113" style="font-size:14px;">!</font>
									  </td>
								  </tr>
								  <tr><td height="15"></td></tr>
								  <tr><td align="right">
										  <font face="Tahoma, Verdana, sans-serif" size="2" color="#633113" style="font-size:14px;">
											  <?=translate('С уважением');?>, <?=$nick?>.
										  </font>
								  </td></tr>
								  <tr><td height="25"></td></tr>
								  <tr>
									  <td align="center">
										  <a href="<?=$login_url;?>" target="_blank"><img src="http://<?=$current_site_domain.'/'.locale_path().'images/return_tpl_4n.jpg'?>" width="548" height="103" border="0" alt="<?=translate('Забрать подарок!');?>" /></a>
									  </td>
								  </tr>
							  </tbody>
						  </table>
					  </td>
					  <td>
						  <img src="<?=$image_url.'return_tpl_5n.jpg'?>" width="62" height="310" style="display: block;" />
					  </td>
				  </tr>
			  </tbody>
		  </table>
		  <table cellpadding="0" cellspacing="0" border="0" width="700">
			  <tbody>
				  <tr>
					  <td><img src="<?=$image_url.'return_tpl_12n.jpg'?>" width="700" height="57" style="display: block;" /></td>
				  </tr>
			  </tbody>
		  </table>
	  </center>
  </body>
</html>

<?
	return ob_get_clean();
}

