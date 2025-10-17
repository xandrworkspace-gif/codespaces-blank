<? # $Id: cron_import.php,v 1.6 2009-10-27 09:00:49 i.hrustalev Exp $

// Данный крон может писать в директории, недоступные для записи из остальных кронов
// (запускается на машине, являющейся сервером nfs)

chdir("..");
require_once("include/common.inc");
require_once("lib/lua_script.lib");

lua_script_sync_files();

