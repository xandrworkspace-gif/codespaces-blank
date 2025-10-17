<?
chdir("..");
require_once("include/common.inc");
require_once("lib/chat.lib");

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) return;

find_criminal_messages_cron();

exit;

define('BUILD_FN', 'shared/build.html');

set_time_limit(0);

// создаем список файлов
$old_files = get_hash(common_list($db_diff,'_filelist'),'fname','ftime');
$new_files = array();
$build = false;
__add_dir('images/*');
__add_dir('js/*');
__add_dir('style/*');

// записываем номер билда
if ($build) {
// записываем список
common_truncate($db_diff,'_filelist');
foreach ($new_files as $fn=>$ft) {
	common_save($db_diff,'_filelist',array(
		'_mode' => CSMODE_REPLACE,
		'fname' => $fn,
		'ftime' => $ft,
	));
}
	$old = intval(@file_get_contents(BUILD_FN));
	$old++;
	$f = @fopen(BUILD_FN,"w");
	if ($f) {
		fwrite($f,$old);
		fclose($f);
		@chmod(BUILD_FN,0664);
	}
}

// =====================================================================================================

function __add_dir($path) {
	global $old_files, $new_files, $build;

	foreach (glob($path) as $fn) {
	    if(!in_array(pathinfo($fn, PATHINFO_EXTENSION), array(
	        'gif', 'bmp', 'cfg', 'css', 'js', 'jpeg', 'jpg', 'JPG', 'js', 'mp3', 'png', 'swg', 'xml'
        ))) continue;
		if (strpos($fn,'CVS') !== false) continue;
		if (strpos($fn,'lua') !== false) continue;
        if (strpos($fn,'php') !== false) continue;
		if (is_dir($fn)) {
			__add_dir($fn.'/*');
			continue;
		}
		$ft = filemtime($fn);
		$new_files[$fn] = $ft;
		if ($old_files[$fn] != $ft) $build = true;
	}
}

?>
