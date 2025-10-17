<?# $Id: update_premiya_params.php, v.stepanov Exp $ %TRANS_SKIP%
exit;
chdir("..");
require_once("include/common.inc");
common_define_settings();
$banner_vars = array();
if (BANNER_MSG_VARS) {
	$banner_vars_tmp = explode(';',BANNER_MSG_VARS);
	foreach($banner_vars_tmp as $banner_var) {
		$params = explode('=',$banner_var);
		if (count($params)!=2) continue;
		$banner_vars[$params[0]] = $params[1];
	}
}

$legenda_name = iconv('utf-8','cp1251',''.MAIN_TITLE.'');
$html = file_get_contents('http://narod.premiaruneta.ru/');
if (!$html) exit;

$rows = explode('<tr onclick',$html);
unset($rows[0]);
$projects = array();
$i=0;
foreach($rows as $row) {
	$cols = explode('<td>',trim($row));
	if (count($cols)!=5) continue;
	$i++;
	$proj_name_mass = explode('</td>',$cols[3]);
	$proj_name = strip_tags($proj_name_mass[0]);
	$proj_value_mass = explode('</td>',$cols[4]);
	$proj_value = intval(str_replace(' ','',$proj_value_mass[0]));
	$projects[$proj_value] = $proj_name;
	if ($proj_name == $legenda_name) $legenda_place = $i;
}
if (!$projects) exit;

krsort($projects);
$projects_flipped = array_flip($projects);

$legenda_value = $projects_flipped[$legenda_name];
if (!$legenda_value) exit;
unset($projects[$legenda_value]);

$new_value = $legenda_value-max(array_keys($projects));
if ($new_value == 0 || $new_value>2000000 || $new_value<-300000) exit;
$last_diff = abs($new_value)-intval($banner_vars['diff']);

$banner_msg_vars = 'place='.$legenda_place.';value='.$legenda_value.';diff='.abs($new_value).';last_diff='.abs($last_diff).';dir='.(($last_diff < 0) ? translate('уменьшился') : translate('увеличился'));

common_save_settings(array('BANNER_MSG_VARS' => $banner_msg_vars));
