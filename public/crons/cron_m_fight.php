<?php


ini_set('memory_limit', '128M');
chdir("..");
require_once("include/common.inc");
require_once("lib/common.lib");

common_define_settings();
ini_set('default_socket_timeout',5);

require_once("lib/chat.lib");
require_once("lib/instance.lib");
require_once("lib/area.lib");
require_once("lib/arena.lib");
require_once("lib/pvp_fight.lib");

NODE_SWITCH(1);
//Арена равных
arena_equal_cron();
fights_clean_history_arena();
fights_prepare_launch();
fights_prepare_start();

arena_scheduler_cron();

//Дуэли
pvp_fight_cron();