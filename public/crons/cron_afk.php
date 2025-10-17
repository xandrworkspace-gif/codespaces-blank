<?
#cron_afk by Igor 11.09.2016
chdir("..");
require_once("include/common.inc");
require_once("lib/common.lib");
require_once("lib/afk.lib");

common_define_settings();

$afk_users = afk_get_users();
//print_r($afk_users);
foreach($afk_users as $afk_user) {
    //echo $afk_user['user_id']." : "." Статус: ";
    $status = afk_check_exit($afk_user);
    if(!$status){
        //echo "Активен<br>";
    }else{
        if($status['exit_afk'] == 1){
            //echo "Вышел из локации<br>";
        }elseif($status['exit_afk'] == 2){
            //echo "Вышел из игры<br>";
        }
    }
}
?>