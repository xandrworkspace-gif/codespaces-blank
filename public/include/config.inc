<?
define('DEVS', 0); // Отключить при релизе
define('SERVER_URL', 'https://dwar.fun/');
define('SERVER_URL2', 'https://dwar.fun/');
define('HTTP_AUTH_SETTINGS', false);
define('SERVER_DOMAIN', 'dwar.fun');
define('SERVER_IP', '185.165.242.186');
define('SERVER_ROOT', '/home/admin/web/dwar.fun/public_html/'); //'/'
define('MAIN_TITLE', 'Dwar.Fun - Браузерная ММОРПГ в жанре DWAR/Легенда: Наследие драконов.');
define('SERVER_ID', '1');
define('CLAN_BATTLE_IS_ON', '1');
define('CLAN_BATTLE_RATING_IS_ON_FOR_ALL', '1');
define('MNGR_HUNT_COPIES', '1');
define('CLAN_BATTLE_SEASON_SERVER_ID', '1');
define('ADMIN_URL', 'admin/');

define('TELEGRAM_BOT_ERROR_TOKEN', '5017733926:AAHGjZNaGXVbTXsq0xc9C5SRGm-P3OGCRR0');
define('TELEGRAM_BOT_ERROR_CHAT_ID', '968711190');
$find_criminal_messages_ids = array(968711190, 362707138);

define('AKEY_SECRET', 'thMAgiccFEO2XCXMASN113');

define('SPI_KEY', 'XXXXPORNONET_MF_SH121!#');

// database
define('DB_HOST', 'localhost');
define('DB_NAME', 'elizium_1');
define('DB_USER', 'root');
define('DB_PASSWD', 'LocalDwar1200');

define('DB_2_HOST', 'localhost');
define('DB_2_NAME', 'elizium_2');
define('DB_2_USER', 'root');
define('DB_2_PASSWD', 'LocalDwar1200');

define('DB_3_HOST', 'localhost');
define('DB_3_NAME', 'elizium_3');
define('DB_3_USER', 'root');
define('DB_3_PASSWD', 'LocalDwar1200');

define('DB_4_HOST', 'localhost');
define('DB_4_NAME', 'elizium_4');
define('DB_4_USER', 'root');
define('DB_4_PASSWD', 'LocalDwar1200');

define('DB_AUTH_HOST', 'localhost');
define('DB_AUTH_NAME', 'elizium_auth');
define('DB_AUTH_USER', 'root');
define('DB_AUTH_PASSWD', 'LocalDwar1200');

define('DB_DIFF_HOST', 'localhost');
define('DB_DIFF_NAME', 'elizium_diff');
define('DB_DIFF_USER', 'root');
define('DB_DIFF_PASSWD', 'LocalDwar1200');

define('DB_PATH_HOST', 'localhost');
define('DB_PATH_NAME', 'elizium_path');
define('DB_PATH_USER', 'root');
define('DB_PATH_PASSWD', 'LocalDwar1200');

define('DB_PTEST_HOST', 'localhost');
define('DB_PTEST_NAME', 'elizium_ptest');
define('DB_PTEST_USER', 'root');
define('DB_PTEST_PASSWD', 'LocalDwar1200');

define('DB_INFO_HOST', 'localhost');
define('DB_INFO_NAME', 'elizium_info');
define('DB_INFO_USER', 'root');
define('DB_INFO_PASSWD', 'LocalDwar1200');

define('DB_ART_HOST', 'localhost');
define('DB_ART_NAME', 'elizium_art');
define('DB_ART_USER', 'root');
define('DB_ART_PASSWD', 'LocalDwar1200');

//define('PORTALDBHOST', '127.0.0.1');
//define('PORTALDBUSER', '	ndragons');
//define('PORTALDBPASSWORD', '');
//define('PORTALDBNAME', 'dwar_portal');
// node containers config

define('FRIDGE_NN', 100);	// fridge node
$DB_NODE_CFG = array(
    1 => array(
        'host' => 'localhost',
        'name' => 'elizium_node1',
        'user' => 'root',
        'passwd' => 'LocalDwar1200',
    ),
    2 => array(
        'host' => 'localhost',
        'name' => 'elizium_node2',
        'user' => 'root',
        'passwd' => 'LocalDwar1200',
    ),
    3 => array(
        'host' => 'localhost',
        'name' => 'elizium_node3',
        'user' => 'root',
        'passwd' => 'LocalDwar1200',
    ),
    100 => array(
        'host' => 'localhost',
        'name' => 'elizium_fridge',
        'user' => 'root',
        'passwd' => 'LocalDwar1200',
    ),
);

// trans
define('DB_TRANS_HOST', 'localhost');
define('DB_TRANS_NAME', 'elizium_dwar');
define('DB_TRANS_USER', 'root');
define('DB_TRANS_PASSWD', 'LocalDwar1200');
//define('_TRANS_MODE',2);
define('_TRANS_LANG','ru');

define("DB_PORTALDBHOST", "localhost");
define("DB_PORTALDBUSER", "root");
define("DB_PORTALDBPASSWORD", 'LocalDwar1200');
define("DB_PORTALDBNAME", "elizium_forum");

// chat
//define('WBDS_SERVERS','77.120.116.89:21, 77.120.116.89:80, 77.120.116.89:53');
define('WBDS_SERVERS','127.0.0.1:32867');


// fight server
define('FS_HOST',SERVER_IP);
define('FS_PORT',11112);

define('FS_CL_HOST',SERVER_IP);
define('FS_CL_PORT',5469);
define('FS_PROXY_SRV', SERVER_DOMAIN.'/fight');
// token queue server
define('TQ_SERVER','127.0.0.1:1808');
// memcache
define('MEMCACHE_HOST','127.0.0.1');
define('MEMCACHE_PORT',11211);

// locale
define("LC_COLLATE_VALUE",'ru_RU.UTF-8');
define("LC_CTYPE_VALUE",'ru_RU.UTF-8');
define("CHARSET_CODE",'UTF8');
define("CHARSET_CODE_HTML",'UTF-8');
//define(LC_COLLATE_VALUE,'ru_RU.CP1251');
//define(LC_CTYPE_VALUE,'ru_RU.CP1251');
//define(CHARSET_CODE,'UTF8');
//define(CHARSET_CODE_HTML,'Windows-1251');


define('ACC_WM_USER_ID', 3395);	// пользователь-кошелек
define('INFOPORTAL_NEWS_URL', SERVER_URL."info/news/index.php?3tbfkvebrt45t4=fvber3t4tefrgbhj54");
define("GAME_AGENT_URL", "http://localhost:4563/v1");

//define("FB_API_KEY", "a8859c38f8c9359477b9490833ef49f4");
//define("FB_SECRET_KEY", "350204854ef52310fa8e0ea666c53447");
//define("FB_APP_URL", "http://apps.facebook.com/");
//define("FB_APP_NAME", "Dwar");

$import_exclusions_quest_on = array();
$import_exclusions_quest_off = array();
$import_exclusions_bot_off = array();

//MAIL
$SMTP_MAIL = array(
    'host' => 'mail.adm.tools',
    'port' => '465',  // SSL-порт для SMTP
    'user' => 'promo@dwar.fun',
    'pass' => 'K5EB63ji3geuUT3',
    'secu' => 'ssl',
);
?>