<? # $Id: constant.inc,v 1.199 2010/03/22 13:49:00 p.knoblokh Exp $

/* КОНСТАНТЫ и не изменяемые массивы. не нужно сюда добавлять функции. */

// ---------------------------------------------- define start ---------------------------------------------------------
// все константы, объявленные через define должны быть объявлены ТОЛЬКО в этом блоке

if (!defined('__CONSTANT_INC__')) {
	define('__CONSTANT_INC__', 1);

	define('CUR_YEAR', 2024);

    define('REG_UTF8', 'АаБбВвГгДдЕеЁёЖжЗзИиЙйКкЛлМмНнОоПпРрСсТтУуФфХхЦцЧчШшЩщЪъЫыЬьЭэЮюЯя');

    define('GUARD_BANK_HUM_ID', 1266);
    define('GUARD_BANK_MAG_ID', 1267);

    define('AREA_STORE_SELL_NEW', .1);
    define('AREA_STORE_SELL_USED', .01);
    define('AREA_STORE_SELL_LIMIT', 30);

    define('RANSOM_COST_ADD', 7.00); //Добавочная стоимость к выкупу
    define('RANSOM_MAX_CNT', 100); //100 вещи
    define('RANSOM_TIME', 3600*24*3); //3 суток

	//Статика
	define('STATIC_DEBUG_TYPE', 5);
	define('STATIC_CONST', 100);

	define('MAX_FILENAME_SIZE', 32);
	define('SLOT_WEIGHT_MAX',100);

	// В активном состоянии делается логгирование статистики запросов к базе
	define('DEBUG',0);

	// максимальный процент увеличения жизни при входе в бой для игроков с эффектом невидимости, в сотых долях процента
	define('INVISIBILITY_HP_INCREASE_PERCENT_MAX',0.05);

	// Для поиска автоботов при фарме. Подразумевается, что будет регулярно меняться
	define('SWF_SIG_SECRET', '41775e02da98ddb63c980dee');

	// типы статистик
	define('BG_STAT_TYPE_BATTLEGROUND', 1);
	define('BG_STAT_TYPE_SLAUGHTER',    2);

	// Интервалы фоновых менеджеров
    define('MNGR_BOSS_PLANNER_INTERVAL',30);
	define('MNGR_FIGHT_LAUNCH_INTERVAL',20);
	define('MNGR_FIGHT_FINISH_INTERVAL',10);
	define('MNGR_HUNT_INTERVAL',5);
	define('MNGR_FARM_INTERVAL',5);
	define('MNGR_DUNGEONS_INTERVAL', 15);
    define('MNGR_HUNT_ATTACK_INTERVAL', 15);
	define('MNGR_BG_INTERVAL',20);
    define('MNGR_RAID_INTERVAL',20);
	define('MNGR_BONUS_CACHE_INTERVAL',15);
	define('MNGR_CLAN_BATTLE_INTERVAL',10);
	define('MNGR_SLAUGHTER_INTERVAL',20);
	define('MNGR_CASTLE_INTERVAL',20);
	define('MNGR_EVENT_INTERVAL',10);
	define('MNGR_LOGSERV_INTERVAL',60);
	define('MNGR_SOUZ_INTERVAL',10);
	define('HUNT_CACHE_TIME',600);
	define('FARM_CACHE_TIME',600);

	// Классы объектов для действий и ограничений
	define('OBJECT_CLASS_AREA', 'AREA');
	define('OBJECT_CLASS_ARTIFACT', 'ARTIFACT');
	define('OBJECT_CLASS_ARTIFACT_ARTIKUL', 'ARTIFACT_ARTIKUL');
	define('OBJECT_CLASS_BOT', 'BOT');
	define('OBJECT_CLASS_BOT_ARTIKUL', 'BOT_ARTIKUL');
	define('OBJECT_CLASS_INSTANCE', 'INSTANCE');
	define('OBJECT_CLASS_INSTANCE_ARTIKUL', 'INSTANCE_ARTIKUL');
	define('OBJECT_CLASS_PARTY', 'PARTY');
	define('OBJECT_CLASS_CLAN', 'CLAN');
	define('OBJECT_CLASS_USER', 'USER');
	define('OBJECT_CLASS_COMPANION', 'COMPANION');
	define('OBJECT_CLASS_BG', 'BG');
    define('OBJECT_CLASS_RAID', 'RAID');
	define('OBJECT_CLASS_SLAUGHTER', 'SLAUGHTER');
	define('OBJECT_CLASS_CASTLE', 'CASTLE');
	define('OBJECT_CLASS_PET', 'PET');
	define('OBJECT_CLASS_PET_ARTIKUL', 'PET_ARTIKUL');
	define('OBJECT_CLASS_STRONGHOLD', 'STRONGHOLD');
	define('OBJECT_CLASS_RESTR_OBJ', 'RESTR_OBJ');

	# псевдо-объекты
    define('OBJECT_CLASS_ADV_BONUS', '_ADV_BONUS');
	define('OBJECT_CLASS_BONUS_ITEM', '_BONUS_ITEM');
    define('OBJECT_CLASS_CASE_BOX_ITEM', '_CASE_BOX_ITEM');
	define('OBJECT_CLASS_QUEST_POINT', '_QUEST_POINT');
	define('OBJECT_CLASS_STORE_ITEM', '_STORE_ITEM');
	define('OBJECT_CLASS_AREA_LINK', '_AREA_LINK');
    define('OBJECT_CLASS_AREA_LINK_BOTS', '_AREA_LINK_BOTS');
	define('OBJECT_CLASS_INSTANCE_LINK_BOTS', '_INSTANCE_LINK_BOTS');
	define('OBJECT_CLASS_INSTANCE_LINK', '_INSTANCE_LINK');
	define('OBJECT_CLASS_BUILDING', '_BUILDING');
	define('OBJECT_CLASS_CONVERSION', '_CONVERSION');
    define('OBJECT_CLASS_TALENT_BLOCK', '_TALENT_BLICK');
    define('OBJECT_CLASS_TALENT', '_TALENT');
    define('OBJECT_CLASS_ADV_MEDAL', '_MEDAL');
    define('OBJECT_CLASS_ADV_MEDAL_ITEM', '_MEDAL_ITEM');
    define('OBJECT_CLASS_AREA_RESSURECT', '_AREA_RESSURECT');
    define('OBJECT_CLASS_AREA_BOT', '_AREA_BOT');
    define('OBJECT_CLASS_INSTANCE_BOT', '_INSTANCE_BOT');
    define('OBJECT_CLASS_AREA_FARM', '_AREA_FARM');
    define('OBJECT_CLASS_INSTANCE_FARM', '_INSTANCE_FARM');
    define('OBJECT_CLASS_AREA_TELEPORT_ITEM', '_AREA_TELEPORT_ITEM');
    define('OBJECT_CLASS_AREA_TELEPORT_ITEM_KIND', '_AREA_TELEPORT_ALLKIND');
    define('OBJECT_CLASS_BANK_PACK', '_BANK_PACK_ITEM');
	define('MNGR_CASINO_INTERVAL',60);

	// Фиксированные id-шники
	define('ARTIFACT_TYPE_ID_INJURY', 1); // Тип артефакта "травма"
	define('ARTIFACT_TYPE_ID_SETBONUS', 9); 	// Тип предметов "Бонус сета"
	define('ARTIFACT_TYPE_ID_GIFT', 16); 	// Тип артефакта "Подарок"
    define('ARTIFACT_TYPE_ID_EAT', 13); 	// Тип артефакта "Еда"
	define('ARTIFACT_KIND_ID_GIFT', 71); 	// Вид артефакта "Подарок"
    define('ARTIFACT_BACKGROUPID_GIFT', 5); 	// Расположение подарков
	define('ARTIFACT_TYPE_ID_AWARD', 17); 	// Тип артефакта "Награды"
	define('ARTIFACT_TYPE_ID_SPELL', 32); 	// Тип артефакта "Заклинание"
	define('ARTIFACT_TYPE_ID_MARRIAGE', 36); 	// Тип артефакта "Свадебные кольца"
    define('ARTIFACT_TYPE_ID_MOUNT', 39); // Тип артефакта "Ездовое"
	define('ARTIFACT_TYPE_ID_DISENCHANT2', 63); // Тип артефактов обломков для действия DISENCHANT2
	define('ARTIFACT_TYPE_ID_TICKET', 64); // Тип артефактов "Билеты", который нумеруются и номер надо выводить из param2
	define('ARTIFACT_TYPE_ID_BOW_ARROWS', 79); // Тип арта "Стрелы"
	define('ARTIFACT_TYPE_ID_BOW', 78); // Тип арта "Луки"
	define('ARTIFACT_TYPE_ID_PROF_BAG',    71); // тип проф. сумки
	define('ARTIFACT_TYPE_ID_CLAN_TOTEM', 99); // Тип арта "Клановые тотемы"
	define('ARTIFACT_TYPE_ID_CLAN_TOTEM_BOOST', 100); // Тип арта "Усилители кланового тотема"
	define('ARTIFACT_TYPE_ID_AUCTION', 70); // Тип артефакта "Сертификат перекупщика"
    define('ARTIFACT_TYPE_ID_SAMOCVET', 74); // Тип артефакта "Самоцветы"
    define('ARTIFACT_TYPE_ID_ZATOCH', 75); // Тип артефакта "Заточки"
    define('ARTIFACT_TYPE_ID_OPRAVA', 76); // Тип артефакта "Оправы"
    define('ARTIFACT_TYPE_ID_CHECKPOINT', 77); // Тип артефакта "Чекпоинты"
    define('ARTIFACT_TYPE_ID_TALENT', 80); // Тип артефакта "Таланты"
    define('ARTIFACT_TYPE_ID_BONUS_LUCK', 81); // Тип артефакта "Бонус удачи"
    define('ARTIFACT_TYPE_ID_GARANT_PRIZE', 82); // Тип артефакта "Гарантированный приз"
    define('ARTIFACT_TYPE_ID_COMPANION', 83); //Тип артефакта "Вещь компаньена"
    define('ARTIFACT_TYPE_ID_THRONE_ROOM', 84); //Тип артефакта "Трон зал"
    define('ARTIFACT_TYPE_ID_SALE_ITEM', 85); //Тип артефакта "Купон на скидку"
    define('ARTIFACT_TYPE_ID_GLOBAL_EVENT_EFF', 89); //Тип артефакта "Усилитель эвента"

    define('ARTIFACT_TYPE_ID_CHAR', 103); // Тип артефакта "Чары"

	define('SKILL_GROUP_ID_FIGHT_STATS', 11); // Боевая статистика

	// Типы денег
	define('MONEY_TYPE_GAME',  1);		// Игровые
	define('MONEY_TYPE_SILVER',  2);	// Серебрянные
	define('MONEY_TYPE_GOLD', 3);		// Золотые

	// Типы операций с деньгами
	define('MONEY_STAT_OPERATION_RECEIVE', 1);
	define('MONEY_STAT_OPERATION_LOST', 2);
	define('MONEY_STAT_OPERATION_LOAN', 3);
	define('MONEY_STAT_OPERATION_COMPENSATION', 4);
	define('MONEY_STAT_OPERATION_REFERAL', 5);
	define('MONEY_STAT_OPERATION_CASINO_RECEIVE', 6);
	define('MONEY_STAT_OPERATION_CASINO_LOST', 7);
	define('MONEY_STAT_OPERATION_PURE_RECEIVE', 8);	// тоже самое что MONEY_STAT_OPERATION_RECEIVE, ЗА ИСКЛЮЧЕНИЕМ бабла, полученного от других игроков (через торговлю, аукцион и почту)
	define('MONEY_STAT_OPERATION_PURE_LOST', 9);	// тоже самое что MONEY_STAT_OPERATION_LOST, ЗА ИСКЛЮЧЕНИЕМ бабла, отданного другим игрокам (через торговлю, аукцион и почту)
	define('MONEY_STAT_OPERATION_SUM', 10);	// Денег на руках
	define('MONEY_STAT_OPERATION_AVG_SUM', 11);	// В среднем денег на руках
	define('MONEY_STAT_OPERATION_LOAN_SUM', 12);	// Всего одолжено
	define('MONEY_STAT_OPERATION_LOAN_AVG', 13);	// Средний кредит
	
	// Вид раскачки
	define('TREND_SLOPE',   1); // Ловкач
	define('TREND_CRIT',    2); // Критовик
	define('TREND_TANK',    3); // Танк

	// Типы фигур
	define('CASINO_FIGURE_NORMAL', 1);
	define('CASINO_FIGURE_BONUS',  2);
	define('CASINO_FIGURE_JOKER',  3);
	define('CASINO_FIGURE_SUPER',  4);

	// Расы
	define('KIND_HUMAN', 1);
	define('KIND_MAGMAR', 2);

	define('BANNER_SLOT_NUM', 114); // слот для знамени

	// профессии
	define('PR_TYPE_NONE',  0);
	define('PR_TYPE_CRAFT', 1);
	define('PR_TYPE_FARM',  2);
	define('PR_TYPE_FIGHT', 3);
    define('PR_TYPE_OTHER', 4);
	define('PR_FARM',      0x0001); // Травник
	define('PR_GEOLOGY',   0x0002); // Геолог
	define('PR_FISHING',   0x0004); // Рыбак
	define('PR_ALCHEMY',   0x0010); // Алхимик
	define('PR_MAGIC',     0x0020); // Колдун
	define('PR_JEWELRY',   0x0040); // Ювелир
	define('PR_INJURY',    0x0080); // Палач
	define('PR_MEDIC',     0x0100); // Целитель
	define('PR_SAFECRACK', 0x0200); // Взломщик
    define('PR_CHAR',      0x0400); // Кузнец
    define('PR_PRODUCT',   0x0800); // Производитель

	// #100834 - Механизм генерации akey.
	define('AKEY_MAX_FAILURES', 10); // максимальное кол-во неудачных логинов через akey, после которого юзер блокируется
	define('AKEY_RESTRICT_TIME', 60*60*24*7); // пока блокируем злодея на неделю
	
	// ограничения по времени проведенному в игре
	define('ONLINE_TIME_LONG', 600); // 5 часов
	define('ONLINE_TIME_MEDIUM', 300); // 4 часа
	define('ONLINE_TIME_SHORT', 100); // 3 часа

	// Общине имена и поля таблиц основных объектов
	define('TABLE_AREAS','areas');
	define('FIELD_AREAS','');
	define('TABLE_AREA_ACTIONS','area_actions');
	define('FIELD_AREA_ACTIONS','');
	define('TABLE_AREA_SKILLS','area_skills');
	define('FIELD_AREA_SKILLS','');
	define('TABLE_ARTIFACTS','artifacts');
	define('FIELD_ARTIFACTS','');
	define('TABLE_ARTIFACT_ACTIONS','artifact_actions');
	define('FIELD_ARTIFACT_ACTIONS','');
	define('TABLE_ARTIFACT_SKILLS','artifact_skills');
	define('FIELD_ARTIFACT_SKILLS','');
	define('TABLE_ARTIFACT_ARTIKULS','artifact_artikuls');
	define('FIELD_ARTIFACT_ARTIKULS','');
	define('TABLE_ARTIFACT_ARTIKUL_ACTIONS','artifact_artikul_actions');
	define('FIELD_ARTIFACT_ARTIKUL_ACTIONS','');
	define('TABLE_ARTIFACT_ARTIKUL_SKILLS','artifact_artikul_skills');
	define('FIELD_ARTIFACT_ARTIKUL_SKILLS','');
	define('TABLE_BOTS','bots');
	define('FIELD_BOTS','');
	define('TABLE_BOT_SKILLS','bot_skills');
	define('FIELD_BOT_SKILLS','');
	define('TABLE_BOT_ARTIKULS','bot_artikuls');
	define('FIELD_BOT_ARTIKULS','');
	define('TABLE_BOT_ARTIKUL_SKILLS','bot_artikul_skills');
	define('FIELD_BOT_ARTIKUL_SKILLS','');
	define('TABLE_INSTANCES','instances');
	define('FIELD_INSTANCES','');
	define('TABLE_INSTANCE_SKILLS','instance_skills');
	define('FIELD_INSTANCE_SKILLS','');
	define('TABLE_INSTANCE_ACTIONS','instance_actions');
	define('FIELD_INSTANCE_ACTIONS','');
	define('TABLE_INSTANCE_ARTIKULS','instance_artikuls');
	define('FIELD_INSTANCE_ARTIKULS','');
	define('TABLE_INSTANCE_ARTIKUL_SKILLS','instance_artikul_skills');
	define('FIELD_INSTANCE_ARTIKUL_SKILLS','');
	define('TABLE_INSTANCE_ARTIKUL_ACTIONS','instance_artikul_actions');
	define('FIELD_INSTANCE_ARTIKUL_ACTIONS','');
	define('TABLE_USERS','users');
	define('FIELD_USERS','id, nick, level, gender, kind, rank, dominance, class, cfg_phead, cfg_phair, cfg_pbody, cfg_sk, msg_color, money, money_silver, money_gold, area_id, area_id_transfer, garea_id, area_ftime, clan_id, party_id, raid_id, instance_id, fight_id, fight_akey, injury_time, injury_type, gag_time, gag_reason_str, trade_id, flags, marks, access, blocked, trade_sess_id, mount_id, companion_id, profession, trophy_id, trophy_server_id, referrer_id, pet_id, invisibility_time, time_registered, partner_pid, time_eff_registered, language, tutorial_step, flags2, settings_flags, flood, flood_id, f_speed, comm, cflags, bank_seed, attack_bot_id, anoty_cnt');
	define('TABLE_USER_INFO','user_info');
	define('FIELD_USER_INFO','uid, name, phone, phone_bind_time, birthdate, region_id, about, slogan, question, answer, notes, block_time, url_redirect, avatar_id, picture, url, fon_id, cloak_id, partner_data, playit_nick, clan_data, swf_data, country, action_url, action_target, u_settings, chat_settings');
	define('TABLE_USER_ACTIONS','user_actions');
	define('FIELD_USER_ACTIONS','');
	define('TABLE_USER_SKILLS','user_skills');
	define('FIELD_USER_SKILLS','');
	define('TABLE_PETS','pets');
	define('FIELD_PETS','');
	define('TABLE_PET_ARTIKULS','pet_artikuls');
	define('FIELD_PET_ARTIKULS','');
	define('TABLE_PET_ARTIKUL_EFFECTS','pet_artikul_effects');
	define('FIELD_PET_ARTIKUL_EFFECTS','');
    define('TABLE_COMPANION', 'user_companion');
    define('FIELD_COMPANION', '');
    define('TABLE_COMPANION_SKILLS', 'user_companion_skills');
    define('FIELD_COMPANION_SKILLS', '');

	// Пути к картинкам
    define("PATH_IMAGE_CLAN_TALENT","images/data/clan_talents/");
	define("PATH_IMAGE_ARTIFACTS","images/data/artifacts/");
	define("PATH_IMAGE_ANIMATION", "images/data/artifacts_animation/");
	define("PATH_IMAGE_BOTS","images/data/bots/");
    define("PATH_IMAGE_ADV_MEDALS","images/data/medals/");
    define("PATH_IMAGE_BUILDING_RES","images/data/building_res/");
	define("PATH_IMAGE_SMILES","images/data/smiles/");
	define("PATH_IMAGE_CLANS","images/data/clans/");
	define("PATH_IMAGE_CLAN_LOGOS","images/data/clan_logos/");
	define("PATH_IMAGE_CLAN_REQUESTS","share/tmp/clan_requests/");
	define("PATH_IMAGE_LOG_LOCALE","shared/tmp/log_locale/");
    define("PATH_IMAGE_HELP_LOG","shared/tmp/help_log/");
	define("PATH_IMAGE_MISC","images/data/misc/");
	define("PATH_IMAGE_AREA_BG","images/data/area_bg/");
	define("PATH_IMAGE_NPCS","images/data/npcs/");
	define("PATH_IMAGE_FARM","images/data/farm/");
	define("PATH_IMAGE_FARM_SWF","images/data/farm_swf/");
	define("PATH_IMAGE_BG_PICS","images/data/bg_pics/");
	define('PATH_IMAGE_RAID_PICS', "images/data/raid_pics/");
    define('PATH_IMAGE_PVP_EVENTS_PICS', "images/data/pvp_events/");
	define("PATH_IMAGE_SLAUGHTER_PICS","images/data/slaughter_pics/");
	define("PATH_IMAGE_CASTLE_PICS","images/data/castle_pics/");
	define("PATH_IMAGE_STRONGHOLD_PICS","images/data/stronghold_pics/");
	define("PATH_IMAGE_USER_PICTURE","images/data/user_pic/");
    define("PATH_IMAGE_USER_FON","images/data/fons/");
    define("PATH_IMAGE_USER_CLOAK","images/data/cloak/");
	define("PATH_IMAGE_RANKS","images/ranks/");
	define("PATH_IMAGE_ACHIEVEMENTS","images/data/achievements/");
	define("PATH_IMAGE_PUZZLES","images/puzzle/");
	define("PATH_IMAGE_PETS","images/data/pets/");
	define("PATH_IMAGE_FBFEED","images/data/fbfeed/");
	define("PATH_IMAGE_ENCHANTS","images/enchants/");
	define("PATH_IMAGE_BANK","images/data/bank/");
	define("PATH_IMAGE_CLAN_BATTLE_LEAGUES","images/data/clan_battle_leagues/");
	define("PATH_IMAGE_TUTORIAL","images/tutorial/");
	define("PATH_IMAGE_BUILDINGS","images/data/buildings/");
    define("PATH_IMAGE_THRONE_ROOM","images/data/buildings/throne_room/");
	define("PATH_IMAGE_LEVELUP","images/levelup/");
	define("PATH_IMAGE_PREMIUM", "images/premium/");
    define("PATH_IMAGE_CMENU", 'images/c_menu/');
    define("PATH_IMAGE_TODO", 'images/todo/');
    define("PATH_IMAGE_NEWS_BEST", 'images/for_best/');
    define("PATH_IMAGES_M_CASINO","images/m_casino/");
    define("PATH_IMAGE_GEMS", "images/gems/");
    define('PATH_IMAGE_SYMBOL', "images/rf_symbols/");
    define("PATH_IMAGE_RESSURECT_ICONS","images/data/point_resurrection/");
    define("PATH_IMAGE_ADV_MINIGAME","images/minigame/");
    define("PATH_IMAGE_CLAN_BONUS","images/clan_bonus/");
    define('PATH_IMAGE_BOT_SKIN','images/bot_skin/');
    define('PATH_IMAGE_CASE_BOX','images/case_box/');
    define('PATH_IMAGE_BANK_PACK','images/data/bank_pack_picture/');

	// Пути к партнерозависимому коду
	define("PATH_PARTNER","partner/");

	// Пути к locale
	define("PATH_LOCALE","images/locale/");
	define("PATH_DATA_LOCALE","images/data/locale/");	

	// Пути к swf
	define("PATH_SWF","images/swf/");
	define("PATH_SWF_PACKS","images/data/packs/");
	define("PATH_SWF_PACKS_SH","images/data/packs_sh/");
	define("PATH_SWF_SKELETONS","images/swf/skeletons/");
	define("PATH_SWF_MOUNTS","images/swf/mounts/");
	define("PATH_SWF_MAPS_HNT","images/swf/maps_hnt/");
	define("PATH_SWF_MAPS_PKS","images/swf/maps_pks/");

	define("PATH_SWF_AREAS","images/data/areas/");
    define("PATH_SWF_AREAS_DELAYED","images/data/areas/delayed/mid/");
	define("PATH_SWF_AREA_SKY","images/data/areas/sky/");
	define("PATH_SWF_AREA_XML_MAP","xml_map/");

	define("PATH_SWF_BOTS_HNT","images/data/bots_hnt/");
	define("PATH_SWF_IMGS","images/data/imgs/");
	define('PATH_SWF_FIGHT_BOT_SPELLS', 'images/data/fight_bot_spells/');

	// Пути к данным
	define("PATH_SCRIPTS","images/data/lua/");

	// Путь к логам
	define("PATH_LOGS","logs/");


	// Максимальная длина сообщения в чате
	define('CHAT_MSG_LEN_MAX', 200);
	// Максимальное кол-во макросов с артефактами в сообщениях чата. 0 - не ограничено
	define('CHAT_MSG_MACROSES_MAX_ARTIFACT_COUNT', 3);

	define('STRONGHOLD_WINNER_HONOR_MULTIPLIER', 2);
	define('STRONGHOLD_LOSER_HONOR_MULTIPLIER', 1.2);

	define('BONUS_HONOR_KOEF', 4);
	define('EXTRA_EXP_KOEF', 2);

	// Ограничение по времени для cron_artifact.php в секундах
	define('CRON_ARTIFACT_SCRIPT_TIME_LIMIT', 1600);

	define('LOAN_RETURN_PERIOD', 10);
	define('LOAN_RETURN_PERIOD2', 20);
	define('LOAN_RETURN_PERIOD3', 60); // в днях
	define('LOAN_SIZE', 10); // в процентах от общей суммы вложений в игру
	define('LOAN_MAX_SIZE', 300);

	define('MAX_SKILL_GREAT_FIGHT_REP', 3000);

	// максимальное кол-во брюлей на которые действует акция звезды
	define('LUCKY_STAR_MAX_PAYMENT', 250);

	// ID артикула "Звезды удачи" для акции (это пропуск)
	// 7502 Звезда Удачи
	define('LUCKY_STAR_ARTIKUL_ID', 7374);
	// Таблица с данными по акции "Звезда Удачи"
	define('TABLE_LUCKY_STAR_USERS', 'lucky_star_users');

	// бонусы для выигрыша
	define('LUCKY_START_BONUS_RESET', 9664);

	define('LUCKY_START_BONUS_1_1', 9937);
	define('LUCKY_START_BONUS_1_5', 9661);
	define('LUCKY_START_BONUS_2', 9662);
	define('LUCKY_START_BONUS_3', 9663);

	define('LOCAL_CACHE_TTL', 600);

	define('CROSSSERVER_CLAN_CACHE_TTL', 3600);

	define('SOC_SYSTEM_MM',			1);
	define('SOC_SYSTEM_VK',			2);
	define('SOC_SYSTEM_OK',			3);
	define('SOC_SYSTEM_FB',			4);
	define('SOC_SYSTEM_TW',			5);
	define('SOC_SYSTEM_GGP',		6);
	define('SOC_SYSTEM_WKW',		7);
	define('SOC_SYSTEM_SF',			8);
	define('SOC_SYSTEM_ML',			9);
	define('SOC_SYSTEM_NK',			10);
	define('SOC_SYSTEM_WYKOP',		11);
	define('SOC_SYSTEM_FOTKA',		12);
	define('SOC_SYSTEM_SKYROCK',	13);
	define('SOC_SYSTEM_TUENTI',		14);
	define('SOC_SYSTEM_FF',			15);
	define('SOC_SYSTEM_ORKUT',		16);
	define('SOC_SYSTEM_HI5',		17);

	define('DEFAULT_MAX_GIFTS', 10);

	//Использование кода для промо - акций
	define('ACTION_CODE_SHOW', 1);
	define('ACTION_CODE_HIDE', 2);
	define('ACTION_CODE_DISABLED', 3);


	// битовые маски определяющие кто участвует в сообщении SPECIAL_MSG
	define('SPECIAL_TO_CLAN', 0x01) ; // сообщение клану
	define('SPECIAL_FROM_CLAN', 0x02) ; // от клана

	// Стартовый id для кастомного контента
	define('CUSTOM_CONTENT_START_PK_INT', 1000000);
	define('CUSTOM_CONTENT_START_PK_SMALLINT', 1000);
	
	global $campaign_art_sets;
	$campaign_art_sets = array(
		1 => array(9965, 9970, 9966, 9968, 9969, 9963, 9964, 9967),
		2 => array(9947, 9948, 9953, 9949, 9951, 9952, 9945, 9946, 9950),
		3 => array(9956, 9957, 9962, 9958, 9960, 9961, 9954, 9955, 9959),
	);
	
	// Пол
	define('GENDER_MALE',   1); // Мужчина
	define('GENDER_FEMALE', 2); // Женщина
	
	define('PAID_RESURRECT_COST', 15);
	define('USER_QUEST_BIG_LEVEL_RESSURECT', 884);
}

define('CHEKCPOINT_VRAG_TIME', 600); //10 минут на чекпоинт в локации врагов
define('CHEKCPOINT_TIME', 120); //2 минуты на чекпоинт
define('CHECKPOINT_MAX_CNT', 3); // Максимум чекпоинтов
define('CHECKPOINT_ARTIKUL_ID' , 8411);
//Чекпоинты мать его
$area_checkpoint_info = array(
    1 => array(
        MONEY_TYPE_GAME => array('price' => 5000,),
        MONEY_TYPE_GOLD => array('price' => 50,),
    ),
    2 => array(
        MONEY_TYPE_GAME => array('price' => 10000,),
        MONEY_TYPE_GOLD => array('price' => 100,),
    ),
    3 => array(
        MONEY_TYPE_GAME => array('price' => 15000,),
        MONEY_TYPE_GOLD => array('price' => 150,),
    ),
);


$artifact_kind_id_shatter = array(22, 94); // Виды артефактов, которые можно разбить

// "цвет" вещи
$quality_info = array(
	0 => array('id' => 0, 'title' => translate('серый'), 'color' => '#666666', 'disenchant_bonus_id' => 165),
	1 => array('id' => 1, 'title' => translate('зеленый'), 'color' => '#339900', 'disenchant_bonus_id' => 166),
	2 => array('id' => 2, 'title' => translate('синий'), 'color' => '#3300ff', 'disenchant_bonus_id' => 167),
	3 => array('id' => 3, 'title' => translate('фиолетовый'), 'color' => '#990099', 'disenchant_bonus_id' => 168),
	4 => array('id' => 4, 'title' => translate('красный'), 'color' => '#ff0000', 'disenchant_bonus_id' => 169),
	5 => array('id' => 5, 'title' => translate('бирюзовый'), 'color' => '#016e71', 'disenchant_bonus_id' => 0),
    6 => array('id' => 6, 'title' => translate('оранжевый'), 'color' => '#ff4500', 'disenchant_bonus_id' => 0),
    7 => array('id' => 7, 'title' => translate('коричневый'), 'color' => '#b55e00', 'disenchant_bonus_id' => 0),
    8 => array('id' => 8, 'title' => translate('лиловый'), 'color' => '#ec00ca', 'disenchant_bonus_id' => 0),
	9 => array('id' => 9, 'title' => translate('Черный'), 'color' => '#2f2e2e', 'disenchant_bonus_id' => 0),
	
	
);

$money_type_info = array(
	MONEY_TYPE_GAME => array(
		'id' => MONEY_TYPE_GAME,
		'title' => translate('Монеты'),
		'picture' => 'm_game1.gif',
		'picture1' => 'm_game1.gif',
		'picture2' => 'm_game2.gif',
		'picture3' => 'm_game3.gif',
		'alt' => translate('Монеты'),
		'alt1' => translate('Медный'),
		'alt2' => translate('Серебряный'),
		'alt3' => translate('Золотой'),
		'field' => 'money',
		'url' => '',
	),
	MONEY_TYPE_SILVER => array(
		'id' => MONEY_TYPE_SILVER,
		'title' => translate('Рубин'),
		'picture' => 'm_rub.gif',
		'alt' => translate('Рубин'),
		'field' => 'money_silver',
		'url' => '',
		'zerohide' => true,
	),
	MONEY_TYPE_GOLD => array(
		'id' => MONEY_TYPE_GOLD,
		'title' => translate('Бриллиант'),
		'picture' => 'm_dmd.gif',
		'alt' => translate('Бриллиант'),
		'field' => 'money_gold',
		'zerohide' => true,
	),
);

$money_type_info_js = array(
    'gold' => array('alt' => 'Золотой', 'picture' => 'm_game3.gif',),
    'bronze' => array('alt' => 'Медный', 'picture' => 'm_game1.gif',),
    'silver' => array('alt' => 'Серебряный', 'picture' => 'm_game2.gif',),
    'brilliante' => array('alt' => 'Бриллиант', 'picture' => 'm_dmd.gif',),
    'ruby' => array('alt' => 'Рубин', 'picture' => 'm_rub.gif',),
    'work' => array('alt' => 'Энергия', 'picture' => 'work.gif',),
);

$gender_info = array(
	0 => translate('Унисекс'),
	1 => translate('Мужской'),
	2 => translate('Женский'),
);

define('CLAN_STORE_AREA_ID', 690); //Клановый магазин

$kind_info = array(
	KIND_HUMAN => array(
		'id' => KIND_HUMAN,
		'title' => translate('Люди'),
		'title2' => translate('людей'),
		'picture' => 'ico_human.gif',
		'start_area_id' => 188,
		'city_area_id' => 5,
		'big_level_city_area_id' => 656,
		'jail_area_id'=> 115,
		'bank_area_id'=> 24,
		'soc_store_area_id'=> 571,
		//'start_link' => 'area.php', //sprintf('npc.php?f_id=%d&npc_id=%d&quest_id=%d&point_id=%d',2,114,225,6581)
	),
	KIND_MAGMAR => array(
		'id' => KIND_MAGMAR,
		'title' => translate('Магмары'),
		'title2' => translate('магмар'),
		'picture' => 'ico_magmar.gif',
		'start_area_id' => 191,
		'city_area_id' => 9,
		'big_level_city_area_id' => 663,
		'jail_area_id'=> 116,
		'bank_area_id'=> 25,
		'soc_store_area_id'=> 572,
		//'start_link' => 'area.php', //sprintf('npc.php?f_id=%d&npc_id=%d&quest_id=%d&point_id=%d',4,113,225,6583)
	),
	
);

$soft_register_info = array(
	'area_id' => 605,
	'first_quest_id' => 854,
	'first_npc_id' => 0,
	'second_quest_id' => 855,
	'second_npc_id' => 0,
);

$rank_info = array(
	0  => array('id' => 0,  'title' => translate('Рекрут'),					 'honor' => 0),
	1  => array('id' => 1,  'title' => translate('Солдат'),					 'honor' => 8000),
	2  => array('id' => 2,  'title' => translate('Боец'),					 'honor' => 38000),
	3  => array('id' => 3,  'title' => translate('Воин'),					 'honor' => 152000),
	4  => array('id' => 4,  'title' => translate('Элитный воин'),			 'honor' => 548000),
	5  => array('id' => 5,  'title' => translate('Чемпион'),				 'honor' => 848000),
	6  => array('id' => 6,  'title' => translate('Гладиатор'),				 'honor' => 1248000),
	7  => array('id' => 7,  'title' => translate('Полководец'),				 'honor' => 1748000),
	8  => array('id' => 8,  'title' => translate('Мастер войны'),			 'honor' => 2348000),
	9  => array('id' => 9,  'title' => translate('Герой'),					 'honor' => 3048000),
	10 => array('id' => 10, 'title' => translate('Военный эксперт'),		 'honor' => 3848000),
	11 => array('id' => 11, 'title' => translate('Магистр войны'),			 'honor' => 5048000),
	12 => array('id' => 12, 'title' => translate('Вершитель'),				 'honor' => 6848000),
	13 => array('id' => 13, 'title' => translate('Высший магистр'),			 'honor' => 9548000),
	14 => array('id' => 14, 'title' => translate('Повелитель'),				 'honor' => 13598000),
	15 => array('id' => 15, 'title' => translate('Легендарный завоеватель'), 'honor' => 20000000),
	16 => array('id' => 16, 'title' => translate('Властелин боя'),			 'honor' => 29500000),
	17 => array('id' => 17, 'title' => translate('Победоносец'),			 'honor' => 43500000),
	18 => array('id' => 18, 'title' => translate('Триумфатор '),			 'honor' => 65000000),
	19 => array('id' => 19, 'title' => translate('Избранник богов'),		 'honor' => 100000000),
);

//Превосходство

// школа магии
// kind пустой, если доступна всем расам
$class_info = array(
	0x0001 => array('id' => 0x0001, 'title' => translate('Воздух'), 'trend' => TREND_CRIT, 'picture' => 'wind.gif', 'kind' => KIND_HUMAN),
	0x0002 => array('id' => 0x0002, 'title' => translate('Вода'), 'trend' => TREND_TANK, 'picture' => 'water.gif', 'kind' => KIND_HUMAN),
	0x0004 => array('id' => 0x0004, 'title' => translate('Свет'), 'trend' => TREND_SLOPE, 'picture' => 'light.gif', 'kind' => KIND_HUMAN),
	0x0008 => array('id' => 0x0008, 'title' => translate('Огонь'), 'trend' => TREND_CRIT, 'picture' => 'fire.gif', 'kind' => KIND_MAGMAR),
	0x0010 => array('id' => 0x0010, 'title' => translate('Земля'), 'trend' => TREND_TANK, 'picture' => 'earth.gif', 'kind' => KIND_MAGMAR),
	0x0020 => array('id' => 0x0020, 'title' => translate('Тень'), 'trend' => TREND_SLOPE, 'picture' => 'shadow.gif', 'kind' => KIND_MAGMAR),
	0x0040 => array('id' => 0x0040, 'title' => translate('Жизнь')),
);


$profession_info = array (
	PR_FARM      => array('id' => PR_FARM   ,   'title' => translate('Травник'),  'type' => PR_TYPE_FARM,  'skill_id' => 'PR_FARM'),
	PR_GEOLOGY   => array('id' => PR_GEOLOGY,   'title' => translate('Геолог'),   'type' => PR_TYPE_FARM,  'skill_id' => 'PR_GEOLOGY'),
	PR_FISHING   => array('id' => PR_FISHING,   'title' => translate('Рыбак'),    'type' => PR_TYPE_FARM,  'skill_id' => 'PR_FISHING'),
	PR_ALCHEMY   => array('id' => PR_ALCHEMY,   'title' => translate('Алхимик'),  'type' => PR_TYPE_CRAFT, 'skill_id' => 'PR_ALCHEMY', 'collection_bonus_id' => 10311),
	PR_MAGIC     => array('id' => PR_MAGIC  ,   'title' => translate('Колдун'),   'type' => PR_TYPE_CRAFT, 'skill_id' => 'PR_MAGIC', 'collection_bonus_id' => 10313),
	PR_JEWELRY   => array('id' => PR_JEWELRY,   'title' => translate('Ювелир'),   'type' => PR_TYPE_CRAFT, 'skill_id' => 'PR_JEWELRY', 'collection_bonus_id' => 10312),
	PR_INJURY    => array('id' => PR_INJURY,    'title' => translate('Палач'),    'type' => PR_TYPE_FIGHT, 'skill_id' => 'PR_INJURY'),
	PR_MEDIC     => array('id' => PR_MEDIC,     'title' => translate('Целитель'), 'type' => PR_TYPE_NONE,  'skill_id' => 'PR_MEDIC'),
	PR_SAFECRACK => array('id' => PR_SAFECRACK, 'title' => translate('Взломщик'), 'type' => PR_TYPE_NONE,  'skill_id' => 'PR_SAFECRACK'),
    PR_CHAR      => array('id' => PR_CHAR, 'title' => translate('Кузнец'), 'type' => PR_TYPE_OTHER,  'skill_id' => 'PR_CHAR'),
    PR_PRODUCT   => array('id' => PR_PRODUCT, 'title' => translate('Повар'), 'type' => PR_TYPE_OTHER,  'skill_id' => 'PR_PRODUCT'),
);

$combo_info = array (
	1  => array('id' => 1,  'title' => translate('«Сила Волка»'),              'active' => 1, 'level' => 1,  'size' => 2, 'artikul_id' => 155,  'description' => translate('Наносит дополнительные 2 урона с ударом')),
	2  => array('id' => 2,  'title' => translate('«Великая Сила Волка»'),      'active' => 1, 'level' => 2,  'size' => 3, 'artikul_id' => 156,  'description' => translate('Наносит дополнительные 5 урона с ударом')),
	3  => array('id' => 3,  'title' => translate('«Жажда Крови»'),             'active' => 1, 'level' => 3,  'size' => 4, 'artikul_id' => 157,  'description' => translate('Восстанавливает 50% нанесенного ударом урона, есть шанс оглушить противника на 8 сек')),
	4  => array('id' => 4,  'title' => translate('«Ярость Пантеры»'),          'active' => 1, 'level' => 4,  'size' => 4, 'artikul_id' => 158,  'description' => translate('Сила удара увеличивается на 35%, а так же снимает эффект кровотечения')),
	5  => array('id' => 5,  'title' => translate('«Атака Змеи»'),              'active' => 1, 'level' => 5,  'size' => 5, 'artikul_id' => 159,  'description' => translate('Противник теряет 40 единиц жизни за 60 сек в результате кровотечения')),
	6  => array('id' => 6,  'title' => translate('«Великая Жажда Крови»'),     'active' => 1, 'level' => 6,  'size' => 5, 'artikul_id' => 160,  'description' => translate('Восстанавливает 75% нанесенного ударом урона, есть шанс оглушить противника на 10 сек')),
	7  => array('id' => 7,  'title' => translate('«Ярость Тигра»'),            'active' => 1, 'level' => 7,  'size' => 6, 'artikul_id' => 224,  'description' => translate('Сила удара увеличивается на 50%, снимает эффект кровотечения')),
	8  => array('id' => 8,  'title' => translate('«Смертельная Атака Змеи»'),  'active' => 1, 'level' => 8,  'size' => 6, 'artikul_id' => 225,  'description' => translate('Противник теряет 60 единиц жизни за 80 сек в результате кровотечения')),
	9  => array('id' => 9,  'title' => translate('«Гнев Дракона»'),            'active' => 1, 'level' => 10, 'size' => 7, 'artikul_id' => 227,  'description' => translate('Делает завершающий удар критическим')),
	10 => array('id' => 10, 'title' => translate('«Удар Медведя»'),            'active' => 1, 'level' => 9,  'size' => 7, 'artikul_id' => 226,  'description' => translate('Имеет существенный шанс оглушить противника на 10 сек')),
	11 => array('id' => 11, 'title' => translate('«Сила Великана»'),           'active' => 0, 'level' => 5,  'size' => 5, 'artikul_id' => 197,  'description' => translate('Увеличивает силу удара на 7 и повышает вероятность критического удара на 40 сек.')),
	12 => array('id' => 12, 'title' => translate('«Сокрушающий удар»'),        'active' => 0, 'level' => 6,  'size' => 5, 'artikul_id' => 200,  'description' => translate('Увеличивает силу завершающего удара на 35')),
	13 => array('id' => 13, 'title' => translate('«Безудержная ярость»'),      'active' => 0, 'level' => 8,  'size' => 6, 'artikul_id' => 12784,'description' => translate('Повышает травматизм на 30 единиц в течение 3-х ходов')),
		14  => array('id' => 14,  'title' => translate('«Сила Ворга»'),              'active' => 0, 'level' => 16,  'size' => 2, 'artikul_id' => 11067,  'description' => translate('Наносит дополнительные 2 урона всем противникам с ударом')),
		15  => array('id' => 15,  'title' => translate('«Великая Сила Ворга»'),              'active' => 0, 'level' => 16,  'size' => 3, 'artikul_id' => 11069,  'description' => translate('Наносит дополнительные 5 урона всем противникам с ударом')),
		16  => array('id' => 16,  'title' => translate('«Жажда Казни»'),              'active' => 0, 'level' => 16,  'size' => 4, 'artikul_id' => 11072,  'description' => translate('Восстанавливает 50% нанесенного ударом урона, есть шанс оглушить противника на 8 сек, а также увеличивает шанс критического удара на 30%')),
		17  => array('id' => 17,  'title' => translate('«Ярость Леопарда»'),              'active' => 0, 'level' => 16,  'size' => 4, 'artikul_id' => 11078,  'description' => translate('Сила удара увеличивается на 50%, а так же снимает эффект кровотечения и 100% оглушает противника на 1 секунду.')),
		18  => array('id' => 18,  'title' => translate('«Великая Жажда Возмездия»'),              'active' => 0, 'level' => 18,  'size' => 5, 'artikul_id' => 11074,  'description' => translate('Восстанавливает 50% жизни и 50% маны от нанесенного урона.')),
		19  => array('id' => 19,  'title' => translate('«Атака Кобры»'),              'active' => 0, 'level' => 18,  'size' => 5, 'artikul_id' => 11076,  'description' => translate('Противник теряет 6% жизни и 6% маны за 60 секунд в результате заклятого кровотечения (но всего не более 300 единиц).')),
	
);

// конфигурация образа
$cfg_info = array(
	1 => array( // пол - мужской
		1 => array( // раса - человек
			'phead' => array(5,6,13,14,15),
			'phair' => array(0,30,31,32,33,34,102,103,104,105,106,900,901,902,903,904,905,906,907,909),
			'pbody' => array(1),
		),
		2 => array( // раса - магмар
			'phead' => array(505,506,507,508,509),
			'phair' => array(0,530,531,532,533,107,108,109,110,111,900,901,902,903,904,905,906,907,909),
			'pbody' => array(501),
		),
	),
	2 => array( // пол - женский
		1 => array( // раса - человек
			'phead' => array(5,6,13,14,15),
			'phair' => array(30,31,32,33,34,102,103,104,105,106,112,900,901,902,903,904,905,906,907,908,909),
			'pbody' => array(1),
		),
		2 => array( // раса - магмар
			'phead' => array(505,506,507,508,509),
			'phair' => array(530,531,532,533,107,108,109,110,111,900,901,902,903,904,905,906,907,908,909),
			'pbody' => array(501),
		),
	),
	

);

// конфигурация образа (цвет)
$cfgc_info = array(
	'chairH' => array(11,12,13,14,15),
	'chairM' => array(16,17,18,19,20),
	'cbodyH' => array(0,1,2,3,4,5),
	'cbodyM' => array(0,6,7,8,9,10),
);

// вид раскачки

$trend_info = array(
	0 => array('id' => 0, 'title' => '--'),
	TREND_SLOPE => array('id' => TREND_SLOPE, 'title' => translate('ловкач'), 'title2' => translate('Ловкач')),
	TREND_CRIT => array('id' => TREND_CRIT, 'title' => translate('критовик'), 'title2' => translate('Костолом')),
	TREND_TANK => array('id' => TREND_TANK, 'title' => translate('танк'), 'title2' => translate('Тяжеловес')),
	-1 => array('id' => -1, 'title' => translate('универсальная'), 'title2' => translate('Универсал')),
);



//Вероятности выпадения сокетов
$socket_prob = array(
    1 => 95, //50%
    2 => 35, //35%
    3 => 15, //15%
);

//Дополнительные вероятности для 2 и 3 сокетов от артефактов точилок
$quality_prob_socket = array(
    0 => array( // Серые
        1 => 1,
        2 => 5, // второй сокет
        3 => 2, // третий сокет
    ),
    1 => array( // Зеленые
        1 => 2,
        2 => 8,
        3 => 5,
    ),
    2 => array(
        1 => 3,
        2 => 10,
        3 => 8,
    ),
    3 => array(
        1 => 4,
        2 => 13,
        3 => 10,
    ),
    4 => array(
        1 => 5,
        2 => 15,
        3 => 13,
    ),
);

//Самоцветы
$rune_gems_skills = array(
    1 => array(
        0 => array('skill_id' => 'ENDUR', 'title' => 'Защита', 'value' => 1),
        1 => array('skill_id' => 'ENDUR', 'title' => 'Защита', 'value' => 1),
        2 => array('skill_id' => 'ENDUR', 'title' => 'Защита', 'value' => 1),
        3 => array('skill_id' => 'ENDUR', 'title' => 'Защита', 'value' => 1),
        4 => array('skill_id' => 'ENDUR', 'title' => 'Защита', 'value' => 1),
    ),
    2 => array(
        0 => array('skill_id' => 'INT', 'title' => 'Интуиция', 'value' => 1),
        1 => array('skill_id' => 'INT', 'title' => 'Интуиция', 'value' => 1),
        2 => array('skill_id' => 'INT', 'title' => 'Интуиция', 'value' => 1),
        3 => array('skill_id' => 'INT', 'title' => 'Интуиция', 'value' => 1),
        4 => array('skill_id' => 'INT', 'title' => 'Интуиция', 'value' => 1),
    ),
    3 => array(
        0 => array('skill_id' => 'VIT', 'title' => 'Живучесть', 'value' => 1),
        1 => array('skill_id' => 'VIT', 'title' => 'Живучесть', 'value' => 1),
        2 => array('skill_id' => 'VIT', 'title' => 'Живучесть', 'value' => 1),
        3 => array('skill_id' => 'VIT', 'title' => 'Живучесть', 'value' => 1),
        4 => array('skill_id' => 'VIT', 'title' => 'Живучесть', 'value' => 1),
    ),
    4 => array(
        0 => array('skill_id' => 'DEX', 'title' => 'Ловкость', 'value' => 1),
        1 => array('skill_id' => 'DEX', 'title' => 'Ловкость', 'value' => 1),
        2 => array('skill_id' => 'DEX', 'title' => 'Ловкость', 'value' => 1),
        3 => array('skill_id' => 'DEX', 'title' => 'Ловкость', 'value' => 1),
        4 => array('skill_id' => 'DEX', 'title' => 'Ловкость', 'value' => 1),
    ),
    5 => array(
        0 => array('skill_id' => 'STR', 'title' => 'Сила', 'value' => 1),
        1 => array('skill_id' => 'STR', 'title' => 'Сила', 'value' => 1),
        2 => array('skill_id' => 'STR', 'title' => 'Сила', 'value' => 1),
        3 => array('skill_id' => 'STR', 'title' => 'Сила', 'value' => 1),
        4 => array('skill_id' => 'STR', 'title' => 'Сила', 'value' => 1),
    ),
);

$symbol_skills_hash = array(
    1 => array(
        1 => array(
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 1),
        ),
        2 => array(
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 2),
        ),
        3 => array(
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 3),
        ),
        4 => array(
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 4),
        ),
        5 => array(
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 5),
        ),
    ),
    2 => array(
        1 => array(
            array('title' => 'Интуиция', 'skill_id' => 'INT', 'value' => 1),
        ),
        2 => array(
            array('title' => 'Интуиция', 'skill_id' => 'INT', 'value' => 2),
        ),
        3 => array(
            array('title' => 'Интуиция', 'skill_id' => 'INT', 'value' => 2),
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 1),
        ),
        4 => array(
            array('title' => 'Интуиция', 'skill_id' => 'INT', 'value' => 3),
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 1),
        ),
        5 => array(
            array('title' => 'Интуиция', 'skill_id' => 'INT', 'value' => 3),
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 2),
        ),
    ),
    3 => array(
        1 => array(
            array('title' => 'Ловкость', 'skill_id' => 'DEX', 'value' => 1),
        ),
        2 => array(
            array('title' => 'Ловкость', 'skill_id' => 'DEX', 'value' => 2),
        ),
        3 => array(
            array('title' => 'Ловкость', 'skill_id' => 'DEX', 'value' => 2),
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 1),
        ),
        4 => array(
            array('title' => 'Ловкость', 'skill_id' => 'DEX', 'value' => 3),
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 1),
        ),
        5 => array(
            array('title' => 'Ловкость', 'skill_id' => 'DEX', 'value' => 3),
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 2),
        ),
    ),
    4 => array(
        1 => array(
            array('title' => 'Сила', 'skill_id' => 'STR', 'value' => 1),
        ),
        2 => array(
            array('title' => 'Сила', 'skill_id' => 'STR', 'value' => 2),
        ),
        3 => array(
            array('title' => 'Сила', 'skill_id' => 'STR', 'value' => 2),
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 1),
        ),
        4 => array(
            array('title' => 'Сила', 'skill_id' => 'STR', 'value' => 3),
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 1),
        ),
        5 => array(
            array('title' => 'Сила', 'skill_id' => 'STR', 'value' => 3),
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 2),
        ),
    ),
    5 => array(
        1 => array(
            array('title' => 'Защита', 'skill_id' => 'ENDUR', 'value' => 1),
        ),
        2 => array(
            array('title' => 'Защита', 'skill_id' => 'ENDUR', 'value' => 2),
        ),
        3 => array(
            array('title' => 'Защита', 'skill_id' => 'ENDUR', 'value' => 2),
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 1),
        ),
        4 => array(
            array('title' => 'Защита', 'skill_id' => 'ENDUR', 'value' => 3),
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 1),
        ),
        5 => array(
            array('title' => 'Защита', 'skill_id' => 'ENDUR', 'value' => 3),
            array('title' => 'Живучесть', 'skill_id' => 'VIT', 'value' => 2),
        ),
    ),
    6 => array(
        6 => array(
            array('title' => 'Инициатива', 'skill_id' => 'INICIATIV', 'value' => 1),
        ),
    ),
    7 => array(
        6 => array(
            array('title' => 'Стойкость', 'skill_id' => 'STOIKOST', 'value' => 1),
        ),
    ),
);

$symbol_info = array(
    1 => array('id' => 1, 'title' => 'Рунический Символ Жизни', 'start_level' => 1),
    2 => array('id' => 2, 'title' => 'Рунический Символ Интуиции', 'start_level' => 1),
    3 => array('id' => 3, 'title' => 'Рунический Символ Ловкости', 'start_level' => 1),
    4 => array('id' => 4, 'title' => 'Рунический Символ Силы', 'start_level' => 1),
    5 => array('id' => 5, 'title' => 'Рунический Символ Защиты', 'start_level' => 1),
    6 => array('id' => 6, 'title' => 'Рунический Символ Инициативы', 'start_level' => 6),
    7 => array('id' => 7, 'title' => 'Рунический Символ Стойости', 'start_level' => 6),
);

$symbol_rym_numbers = array(
    1 => 'I',
    2 => 'II',
    3 => 'III',
    4 => 'IV',
    5 => 'V',
);

// Длительности кляпа
global $gag_type_info;
$gag_type_info = array(
	1 => array('id' => 1, 'title' => translate('1 мин'), 'duration' => 60),
	2 => array('id' => 2, 'title' => translate('2 мин'), 'duration' => 120),
	3 => array('id' => 3, 'title' => translate('3 мин'), 'duration' => 180),
	4 => array('id' => 4, 'title' => translate('5 мин'), 'duration' => 300),
	5 => array('id' => 5, 'title' => translate('10 мин'), 'duration' => 600),
	6 => array('id' => 6, 'title' => translate('15 мин'), 'duration' => 900),
	7 => array('id' => 7, 'title' => translate('20 мин'), 'duration' => 1200),
	8 => array('id' => 8, 'title' => translate('30 мин'), 'duration' => 1800),
	9 => array('id' => 9, 'title' => translate('1 час'), 'duration' => 3600),
	10 => array('id' => 10, 'title' => translate('2 часа'), 'duration' => 7200),
	11 => array('id' => 11, 'title' => translate('3 часа'), 'duration' => 10800),
	12 => array('id' => 12, 'title' => translate('4 часа'), 'duration' => 14400),
	13 => array('id' => 13, 'title' => translate('5 часов'), 'duration' => 18000),
	14 => array('id' => 14, 'title' => translate('6 часов'), 'duration' => 21600),
	15 => array('id' => 15, 'title' => translate('9 часов'), 'duration' => 32400),
	16 => array('id' => 16, 'title' => translate('12 часов'), 'duration' => 43200),
	17 => array('id' => 17, 'title' => translate('24 часа'), 'duration' => 86400),
	18 => array('id' => 18, 'title' => translate('2 суток'), 'duration' => 172800),
	19 => array('id' => 19, 'title' => translate('3 суток'), 'duration' => 259200),
	20 => array('id' => 20, 'title' => translate('5 суток'), 'duration' => 432000),
	21 => array('id' => 21, 'title' => translate('10 суток'), 'duration' => 864000),
	22 => array('id' => 22, 'title' => translate('90 суток'), 'duration' => 7776000),
);

// Причины кляпов
define('GAG_REASON_WARN_ID', 1);
global $gag_reason_info;
/*$gag_reason_info = array(
	GAG_REASON_WARN_ID => array('id' => GAG_REASON_WARN_ID, 'title' => translate('предупреждение'), 'title_time' => translate('предупреждение (1 мин)'), 'duration' => 1 * 60),
	2 => array('id' => 2, 'title' => translate('флуд/флейм'), 'title_time' => translate('флуд/флейм (15 мин)'), 'duration' => 15 * 60),
	3 => array('id' => 3, 'title' => translate('оскорбления'), 'title_time' => translate('оскорбления (1 час)'), 'duration' => 3600),
	4 => array('id' => 4, 'title' => translate('ненормативная лексика'), 'title_time' => translate('ненормативная лексика (2 часа)'), 'duration' => 2 * 3600),
	5 => array('id' => 5, 'title' => translate('политика'), 'title_time' => translate('политика (5 часов)'), 'duration' => 5 * 3600),
	6 => array('id' => 6, 'title' => translate('разжигание межнациональной розни'), 'title_time' => translate('разжигание межнациональной розни (12 часов)'), 'duration' => 12 * 3600),
	7 => array('id' => 7, 'title' => translate('РВС'), 'title_time' => translate('РВС (1 сутки)'), 'duration' => 24 * 3600),
    8 => array('id' => 8, 'title' => translate('Завуал'), 'title_time' => translate('Завуал (1 час)'), 'duration' => 3600),
    9 => array('id' => 9, 'title' => translate('Обс. действий'), 'title_time' => translate('Обс. действий (5 часов)'), 'duration' => 5 * 3600),
);*/

$gag_reason_info = array(
    GAG_REASON_WARN_ID => array('id' => GAG_REASON_WARN_ID, 'title' => translate('предупреждение'), 'title_time' => translate('предупреждение (1 мин)'), 'duration' => 1 * 60),
    2 => array('id' => 2, 'title' => translate('п.2.1 - флуд'), 'title_time' => translate('п.2.1 - флуд (15 мин)'), 'duration' => 15 * 60),
    3 => array('id' => 3, 'title' => translate('п.2.2 - спам'), 'title_time' => translate('п.2.2 - спам (15 мин)'), 'duration' => 15 * 60),
    4 => array('id' => 4, 'title' => translate('п.2.3 - капс'), 'title_time' => translate('п.2.3 - капс (15 мин)'), 'duration' => 15 * 60),
    5 => array('id' => 5, 'title' => translate('п.2.4  флейм'), 'title_time' => translate('п.2.4  флейм (15 мин)'), 'duration' => 15 * 60),
    6 => array('id' => 6, 'title' => translate('п.3.1 - угрозы'), 'title_time' => translate('п.3.1 - угрозы (6 часов)'), 'duration' => 6 * 3600),
    7 => array('id' => 7, 'title' => translate('п.3.2. оскорбления'), 'title_time' => translate('п.3.2. оскорбления (1 час)'), 'duration' => 1 * 3600),
    8 => array('id' => 8, 'title' => translate('п.4.1 - нецензурная брань'), 'title_time' => translate('п.4.1 - нецензурная брань (2 часа)'), 'duration' => 2 * 3600),
    9 => array('id' => 9, 'title' => translate('п.4.1 оскорбления'), 'title_time' => translate('п.4.1 оскорбления (3 часа)'), 'duration' => 3 * 3600),
    10 => array('id' => 10, 'title' => translate('п.4.2 - завуалированный мат'), 'title_time' => translate('п.4.2 - завуалированный мат (1 час)'), 'duration' => 1 * 3600),
    11 => array('id' => 11, 'title' => translate('п.5 - дискриминация'), 'title_time' => translate('п.5 - дискриминация (12 часов)'), 'duration' => 12 * 3600),
    12 => array('id' => 12, 'title' => translate('п.5 политика'), 'title_time' => translate('п.5 политика (12 часов)'), 'duration' => 12 * 3600),
    13 => array('id' => 13, 'title' => translate('п.6.1 - подрыв игр. устоя'), 'title_time' => translate('п.6.1 - подрыв игр. устоя (1 сутки)'), 'duration' => 24 * 3600),
    14 => array('id' => 14, 'title' => translate('п 6.2'), 'title_time' => translate('п 6.2 (1 сутки)'), 'duration' => 24 * 3600),
    15 => array('id' => 15, 'title' => translate('п.7 - пропаганда'), 'title_time' => translate('п.7 - пропаганда (6 часов)'), 'duration' => 6 * 3600),
    16 => array('id' => 16, 'title' => translate('п.8.1 - Обсуждение действий'), 'title_time' => translate('п.8.1 - Обсуждение действий (5 часов)'), 'duration' => 5 * 3600),
    17 => array('id' => 17, 'title' => translate('п.8.2 - Клевета'), 'title_time' => translate('п.8.2 - Клевета (8 часов)'), 'duration' => 8 * 3600),
);


// Сводный массив связей объектов, их скиллов, действий, артикулов
$object_table_info = array(
    OBJECT_CLASS_COMPANION => array(
        'db' => &$db_2,
        'table' => TABLE_COMPANION,
        'field' => FIELD_COMPANION,
        'link' => 'companion_id',
        'skills' => array('db' => &$db_2, 'table' => TABLE_COMPANION_SKILLS, 'field' => FIELD_COMPANION_SKILLS, 'link' => 'companion_id'),
    ),
	OBJECT_CLASS_AREA => array(
		'table' => TABLE_AREAS,
		'field' => FIELD_AREAS,
		'link' => 'area_id',
		'skills' => array('table' => TABLE_AREA_SKILLS, 'field' => FIELD_AREA_SKILLS, 'link' => 'area_id'),
		'actions' => array('table' => TABLE_AREA_ACTIONS, 'field' => FIELD_AREA_ACTIONS, 'link' => 'area_id'),
	),
	OBJECT_CLASS_ARTIFACT => array(
		'table' => TABLE_ARTIFACTS,
		'field' => FIELD_ARTIFACTS,
		'link' => 'artifact_id',
		'skills' => array('table' => TABLE_ARTIFACT_SKILLS, 'field' => FIELD_ARTIFACT_SKILLS, 'link' => 'artifact_id'),
		'actions' => array('table' => TABLE_ARTIFACT_ACTIONS, 'field' => FIELD_ARTIFACT_ACTIONS, 'link' => 'artifact_id'),
		'artikul_class' => OBJECT_CLASS_ARTIFACT_ARTIKUL,
	),
	OBJECT_CLASS_ARTIFACT_ARTIKUL => array(
		'table' => TABLE_ARTIFACT_ARTIKULS,
		'field' => FIELD_ARTIFACT_ARTIKULS,
		'link' => 'artikul_id',
		'skills' => array('table' => TABLE_ARTIFACT_ARTIKUL_SKILLS, 'field' => FIELD_ARTIFACT_ARTIKUL_SKILLS, 'link' => 'artikul_id'),
		'actions' => array('table' => TABLE_ARTIFACT_ARTIKUL_ACTIONS, 'field' => FIELD_ARTIFACT_ARTIKUL_ACTIONS, 'link' => 'artikul_id'),
	),
	OBJECT_CLASS_BOT => array(
		'db' => &$db_4,
		'table' => TABLE_BOTS,
		'field' => FIELD_BOTS,
		'link' => 'bot_id',
		'skills' => array('table' => TABLE_BOT_SKILLS, 'field' => FIELD_BOT_SKILLS, 'link' => 'bot_id', 'db' => &$db_4),
		'artikul_class' => OBJECT_CLASS_BOT_ARTIKUL,
	),
	OBJECT_CLASS_BOT_ARTIKUL => array(
		'table' => TABLE_BOT_ARTIKULS,
		'field' => FIELD_BOT_ARTIKULS,
		'link' => 'artikul_id',
		'skills' => array('table' => TABLE_BOT_ARTIKUL_SKILLS, 'field' => FIELD_BOT_ARTIKUL_SKILLS, 'link' => 'artikul_id'),
	),
	OBJECT_CLASS_INSTANCE => array(
		'db' => &$db_4,
		'table' => TABLE_INSTANCES,
		'field' => FIELD_INSTANCES,
		'link' => 'instance_id',
		'skills' => array('table' => TABLE_INSTANCE_SKILLS, 'field' => FIELD_INSTANCE_SKILLS, 'link' => 'instance_id', 'db' => &$db_4),
		'actions' => array('table' => TABLE_INSTANCE_ACTIONS, 'field' => FIELD_INSTANCE_ACTIONS, 'link' => 'instance_id', 'db' => &$db_4),
		'artikul_class' => OBJECT_CLASS_INSTANCE_ARTIKUL,
	),
	OBJECT_CLASS_INSTANCE_ARTIKUL => array(
		'table' => TABLE_INSTANCE_ARTIKULS,
		'field' => FIELD_INSTANCE_ARTIKULS,
		'link' => 'artikul_id',
		'skills' => array('table' => TABLE_INSTANCE_ARTIKUL_SKILLS, 'field' => FIELD_INSTANCE_ARTIKUL_SKILLS, 'link' => 'artikul_id'),
		'actions' => array('table' => TABLE_INSTANCE_ARTIKUL_ACTIONS, 'field' => FIELD_INSTANCE_ARTIKUL_ACTIONS, 'link' => 'artikul_id'),
	),
	OBJECT_CLASS_USER => array(
		'db' => &$db_3,
		'table' => TABLE_USERS,
		'field' => FIELD_USERS,
		'link' => 'user_id',
		'skills' => array('table' => TABLE_USER_SKILLS, 'field' => FIELD_USER_SKILLS, 'link' => 'user_id'),
		'actions' => array('table' => TABLE_USER_ACTIONS, 'field' => FIELD_USER_ACTIONS, 'link' => 'user_id'),
	),
	OBJECT_CLASS_PET => array(
		'table' => TABLE_PETS,
		'field' => FIELD_PETS,
		'link' => 'pet_id',
		'artikul_class' => OBJECT_CLASS_PET_ARTIKUL,
	),
	OBJECT_CLASS_PET_ARTIKUL => array(
		'table' => TABLE_PET_ARTIKULS,
		'field' => FIELD_PET_ARTIKULS,
		'link' => 'artikul_id',
	),
);

// Опыт для уровней
$expvalues = array(1=>0, 720, 4500, 21564, 90864, 198864, 401364, 806364, 1301364, 2100000, 3700000, 5200000, 8000000, 12000000, 17000000, 22100000, 27300000, 32600000, 38000000, 43500000);

// дни недели
$week_day_info = array(1 => translate('ПН'), 2 => translate('ВТ'), 3 => translate('СР'), 4 => translate('ЧТ'), 5 => translate('ПТ'), 6 => translate('СБ'), 7 => translate('ВС'));

// Настройки для уровней боя (Великие битвы)
$fight_levels = array(
	1 => array('title' => translate('Великая битва'),   'cnt' => 5,  'honor' => 2,  'rep' => 10, 'rep_loss' => 1),
	2 => array('title' => translate('Эпическая битва'), 'cnt' => 25, 'honor' => 3,  'rep' => 20, 'rep_loss' => 5),
	3 => array('title' => translate('Побоище'),         'cnt' => 50, 'honor' => 5,  'rep' => 50, 'rep_loss' => 10),
);

if (defined('GREAT_FIGHT_HONOR_COEF') && GREAT_FIGHT_HONOR_COEF) {
	$fight_levels[1]['honor'] = floatval(GREAT_FIGHT_HONOR_COEF);
}

$MENTOR_URL = translate('#');
$POSITIVE_URL = translate('#');
$JOKER_URL = translate('#');
$MARRIAGE_URL = translate('#');

$first_exchange_banner_state = array(
	1 => array(
		'id'	=> 1,
		'title'	=> translate('Не показывать после первого размена'),
	),
	2 => array(
		'id'	=> 2,
		'title'	=> translate('Показывать всегда'),
	),
	3 => array(
		'id'	=> 3,
		'title'	=> translate('Не показывать никогда'),
	),
);

// действие DISENCHANT2 - разрешённые цвета вещей (серый, зелёный, синий)
$disenchant2_quality = array(0, 1, 2);
// действие DISENCHANT2 - разрешённые слоты и соответствующий уровень, для передачи в бонус
$disenchant2_slots = array(
	'ARMOR' => 1,
	'LEG' => 1,
	'RING' => 1,
	'AMULET' => 1,
	'QUIVER' => 2,
	'BOW' => 2,
	'BELT' => 2,
	'ACCESSORY' => 2,
	'LHAND' => 2,
	'RHAND' => 2,
	'BODY' => 3,
	'FOOT' => 3,
	'SHOULDER' => 4,
	'GLOVE' => 4,
	'HEAD' => 5,
);

// $array[$color][$level]
$enchant_pics = array(
	// серый комплект
	0 => array(
		1 => '1_0.png',
		2 => '2_0.png',
		3 => '3_0.png',
		4 => '4_0.png',
		5 => '5_0.png',
		6 => '6_0.png',
	),
	// зеленый
	1 => array(
		1 => '1_1.png',
		2 => '2_1.png',
		3 => '3_1.png',
		4 => '4_1.png',
		5 => '5_1.png',
		6 => '6_1.png',
	),
	// синий
	2 => array(
		1 => '1_2.png',
		2 => '2_2.png',
		3 => '3_2.png',
		4 => '4_2.png',
		5 => '5_2.png',
		6 => '6_2.png',
	),
	// фиолетовый
	3 => array(
		1 => '1_3.png',
		2 => '2_3.png',
		3 => '3_3.png',
		4 => '4_3.png',
		5 => '5_3.png',
		6 => '6_3.png',
	),
	// красный
	4 => array(
		1 => '1_4.png',
		2 => '2_4.png',
		3 => '3_4.png',
		4 => '4_4.png',
		5 => '5_4.png',
		6 => '6_4.png',
	),
	// оранжевый
	6 => array(
		1 => '1_6.png',
		2 => '2_6.png',
		3 => '3_6.png',
		4 => '4_6.png',
		5 => '5_6.png',
		6 => '6_6.png',
	),
);



// ID артикулов, для определения множителя бонуса, который выдать пользователю в добавок к оплаченной им сумме
//7717 Множитель акции 1.1 (утешительный)
//7499 Множитель акции 1.5
//7500 Множитель акции 2
//7501 Множитель акции 3
$lucky_star_multipliers = array(
	7718 => 0.1, // Бафф множитель 1.1 (утешительный)
	7499 => 0.5, // Бафф множитель 1.5
	7500 => 1,   // Бафф множитель 2
	7501 => 2,   // Бафф множитель 3
);

$armquality_levels = array(
	'1' => 10,
	'2' => 20,
	'3' => 30,
	'4' => 40,
	'5' => 60,
	'6' => 80,
	'7' => 90,
	'8' => 110,
	'9' => 130,
	'10' => 150,
	'11' => 170,
	'12' => 180,
	'13' => 200,
	'14' => 220,
	'15' => 240,
);

$armquality_levels_casino = array(
	1 => 5,
	2 => 10,
	3 => 15,
	4 => 20,
	5 => 30,
	6 => 40,
	7 => 50,
	8 => 60,
	9 => 70,
	10 => 80,
	11 => 90,
	12 => 100,
	13 => 110,
	14 => 120,
	15 => 140,
);

$armquality_levels_casino_backup = array(
    1 => 20,
    2 => 40,
    3 => 60,
    4 => 80,
    5 => 90,
    6 => 110,
    7 => 130,
    8 => 150,
    9 => 170,
    10 => 180,
    11 => 200,
    12 => 220,
    13 => 220,
    14 => 240,
    15 => 260,
);

// режимы видимости межсервеных полей битв
$crossserver_bg_visibly = array(
	0 => array('id'	=> 0, 'title' => translate('Никому')),
	1 => array('id'	=> 1, 'title' => translate('Только админам')),
	2 => array('id'	=> 2, 'title' => translate('Всем')),
	4 => array('id'	=> 3, 'title' => translate('Не видно обычную очередь')),
);

global $soc_systems;
$soc_systems = array();

global $souz_soc_systems;
$souz_soc_systems = array();
/*$souz_soc_systems = array(SOC_SYSTEM_MM, SOC_SYSTEM_VK, SOC_SYSTEM_OK, SOC_SYSTEM_FB);*/

define('SOC_SYSTEM_UNASSOCIATE_TIMEOUT', 86400);

// языки, поддержка которых реализована
// используется в основном для внутренних целей системы переводов и админки
global $known_languages;
$known_languages = array(
	'ru' => translate('Русский'),
	'en' => translate('Английский'),
	'de' => translate('Немецкий'),
	'tr' => translate('Турецкий'),
	'pl' => translate('Польский'),
	'it' => translate('Итальянский'),
	'es' => translate('Испанский'),
	'fr' => translate('Французский'),
);

// определяет поля, которые нужно переводить, для каждой сущности,
// например, в артикуле предмета, взятого из кеша, больше полей, чем в записи из таблицы артикулов предметов
$entity_translate_fields = array(
	OBJECT_CLASS_ARTIFACT_ARTIKUL => array('title', 'type_title', 'kind_title', 'type_description'),
	OBJECT_CLASS_ARTIFACT => array('title', 'description'),
);

// определяет поля, которые нужно переводить, для каждой таблицы
$table_translate_fields = array(
	// areas
	'areas' => array('title'),
	'area_settings' => array('value'),
	'area_auction_groups' => array('title'),
	'area_bank_bonuses' => array('title'),
	'area_links' => array('title'),
	'area_store_categories' => array('title'),
	'area_bank_packs' => array('title', 'description'),
	'area_bank_packs_groups' => array('title'),
	'area_chat_subchannels' => array('title'),
	'auction_groups' => array('title'),
	
	// artifact_artikuls
	'artifact_artikuls' => array('title', 'description'),
	'artifact_cd_groups' => array('title'),
	'artifact_groups' => array('title'),
	'artifact_kinds' => array('title'),
	'artifact_sets' => array('title', 'description'),
	'artifact_types' => array('title', 'description'),
	
	// bot_artikuls
	'bots' => array('nick'),
	'bot_artikuls' => array('nick', 'description'),
	'bot_groups' => array('title'),
	'bot_types' => array('title'),
	
	// instance_artikuls
	'instance_artikuls' => array('title', 'description', 'short_description'),
	'instance_artikul_links' => array('title'),
	
	// nps
	'npcs' => array('store_title', 'elsetext', 'description', 'title'),
	
	// pets
	'pet_artikuls' => array('title', 'description'),
	'pet_groups' => array('title'),
	'pet_types' => array('title'),
	
	// actions
	'actions' => array('chat_msg', 'title', 'description'),
	'action_types' => array('title'),
	
	// bgs
	'bgs' => array('description', 'title', 'rules'),
	
	// bonuses
	'bonuses' => array('description', 'title'),
	'bonus_items' => array('value1', 'value2'),
	'bonus_types' => array('title'),
	
	// castles
	'castles' => array('description', 'title', 'rules'),
	'castle_towers' => array('title'),
	
	// farm
	'farm' => array('title'),
	'farm_types' => array('title'),
	
	// injuries
	'injuries' => array('title'),
	
	// punishments
	'punishments' => array('title', 'reason_str'),
	
	// quests
	'quests' => array('title', 'description'),
	'quest_points' => array('bonus_description', 'title', 'target', 'description'),
	'quest_types' => array('title'),
	
	// recipes
	'recipes' => array('title'),
	
	// restrictions
	'restrictions' => array('title'),
	
	// skills
	'skills' => array('description', 'title'),
	'skill_groups' => array('title'),
	
	// slaughters
	'slaughters' => array('rules', 'description', 'title'),
	
	// slots
	'slots' => array('title'),
	
	// events
	'event_artikuls' => array('finish_cht_msg', 'description', 'title'),
	'event_points' => array('title', 'description', 'cht_msg'),
	'event_point_tasks' => array('title'),
	'event_point_types' => array('title'),
	
	// achievements
	'achievements' => array('title', 'description'),
	'achievement_groups' => array('title'),
	
	// clan_stat_artikuls
	'clan_stat_artikuls' => array('title'),
	'clan_stat_artikul_levels' => array('title'),
	
	// flood
	'flood_messages' => array('description', 'message'),
	'flood_messages_type' => array('title'),
	
	// strongholds
	'strongholds' => array('title', 'description', 'rules'),

	// tutorials
	'tutorials' => array('title', 'description'),

	// tutorials
	'levelup_resources' => array('title', 'content'),

	// conversions
	'conversions' => array('title'),

	// building_artikuls
	'building_artikuls' => array('title', 'description'),

	// user_avatar_artikuls
	'user_avatar_artikuls' => array('title', 'description'),
);


// Используется для отображения флажков и т.п. на внешних страницах, видимых пользователям
global $language_info;
$language_info = array(
	'ru' => array('lang' => translate('Русский'), 'url' => translate('/'), 'img' => 'images/language-rus.gif'),
);

global $country_info;
$country_info = array(
	'ae' => array('country' => translate('ОАЭ'),               'img' => 'images/countries/ae.gif'),
	'ar' => array('country' => translate('Аргентина'),         'img' => 'images/countries/ar.gif'),
	'at' => array('country' => translate('Австрия'),           'img' => 'images/countries/at.gif'),
	'au' => array('country' => translate('Австралия'),         'img' => 'images/countries/au.gif'),
	'be' => array('country' => translate('Бельгия'),           'img' => 'images/countries/be.gif'),
	'br' => array('country' => translate('Бразилия'),          'img' => 'images/countries/br.gif'),
	'ca' => array('country' => translate('Канада'),            'img' => 'images/countries/ca.gif'),
	'ch' => array('country' => translate('Швейцария'),         'img' => 'images/countries/ch.gif'),
	'cl' => array('country' => translate('Чили'),              'img' => 'images/countries/cl.gif'),
	'co' => array('country' => translate('Колумбия'),          'img' => 'images/countries/co.gif'),
	'cy' => array('country' => translate('Кипр'),              'img' => 'images/countries/cy.gif'),
	'de' => array('country' => translate('Германия'),          'img' => 'images/countries/de.gif'),
	'en' => array('country' => translate('Великобритания'),    'img' => 'images/countries/en.gif'),
	'es' => array('country' => translate('Испания'),           'img' => 'images/countries/es.gif'),
	'ie' => array('country' => translate('Ирландия'),          'img' => 'images/countries/ie.gif'),
	'in' => array('country' => translate('Индия'),             'img' => 'images/countries/in.gif'),
	'kz' => array('country' => translate('Казахстан'),         'img' => 'images/countries/kz.gif'),
	'lu' => array('country' => translate('Люксембург'),        'img' => 'images/countries/lu.gif'),
	'mx' => array('country' => translate('Мексика'),           'img' => 'images/countries/mx.gif'),
	'nz' => array('country' => translate('Новая зеландия'),    'img' => 'images/countries/nz.gif'),
	'pe' => array('country' => translate('Перу'),              'img' => 'images/countries/pe.gif'),
	'pl' => array('country' => translate('Польша'),            'img' => 'images/countries/pl.gif'),
	'pt' => array('country' => translate('Португалия'),        'img' => 'images/countries/pt.gif'),
	'ru' => array('country' => translate('Россия'),            'img' => 'images/countries/ru.gif'),
	'sa' => array('country' => translate('Саудовская аравия'), 'img' => 'images/countries/sa.gif'),
	'sg' => array('country' => translate('Сингапур'),          'img' => 'images/countries/sg.gif'),
	'tr' => array('country' => translate('Турция'),            'img' => 'images/countries/tr.gif'),
	'ua' => array('country' => translate('Украина'),           'img' => 'images/countries/ua.gif'),
	'us' => array('country' => translate('США'),               'img' => 'images/countries/us.gif'),
	've' => array('country' => translate('Венесуэла'),         'img' => 'images/countries/ve.gif'),
	'za' => array('country' => translate('ЮАР'),               'img' => 'images/countries/za.gif'),
);

global $tutorial_quest_params;

$tutorial_quest_params = array(
	'FINISHNPC' => array(
					'quest_id'	=> 225,
					'point_id' => 17426,
					),
	'BIGINING' => array(
					'quest_id'	=> 225,
					'point_ids' => array(6581, 6583),
					),	
	'GOSHOP' => array(
					'quest_id' => 225,
					'point_ids' => array(6582, 17351),
				),
	'GOHUNT' => array(
					'area_ids'	=> array(192,189),
					'quest_id'	=> 226,
					'point_ids' => array(2687, 2643),
				),			
	'NEWAREA' => array(
					'quest_id'	=> 226,
					'point_ids' => array(2640, 2684),
				),	
	
);

define('PAPA_JONS_BONUS_ID', 57768);

global $birthday_bonuses;
$birthday_bonuses = array(14142,14143,14144,14145,14146,14147,14148,14149,14150,14151);

global $addcell_prices;
$addcell_prices = array(
	array('days' => 30, 'cost' => 300),
	array('days' => 90, 'cost' => 600),
	array('days' => 180, 'cost' => 1000),
);

#Получение уровней
$level_up_global = array(
2=>		array('exp'=>720,'bonus_id'=>3),
3=>		array('exp'=>4500,'bonus_id'=>4),
4=>		array('exp'=>21564,'bonus_id'=>5),
5=>		array('exp'=>90864,'bonus_id'=>6),
6=>		array('exp'=>198864,'bonus_id'=>7),
7=>		array('exp'=>401364,'bonus_id'=>8),
8=>		array('exp'=>806364,'bonus_id'=>9),
9=>		array('exp'=>1301364,'bonus_id'=>10),
10=>	array('exp'=>2100000,'bonus_id'=>11),
11=>	array('exp'=>3700000,'bonus_id'=>1156),
12=>	array('exp'=>5200000,'bonus_id'=>2431),
13=>	array('exp'=>8000000,'bonus_id'=>2617),
14=>	array('exp'=>12000000,'bonus_id'=>2618),
15=>	array('exp'=>17000000,'bonus_id'=>2619),
16=>	array('exp'=>22100000,'bonus_id'=>10623), // Доделать
17=>	array('exp'=>27300000,'bonus_id'=>0),
18=>	array('exp'=>32600000,'bonus_id'=>0),
19=>	array('exp'=>38000000,'bonus_id'=>0),
20=>	array('exp'=>43500000,'bonus_id'=>0)
);

#LOGS
define('DEBUG_FILE_LOG_DEV', 'logs/debug.log');
define('BANK_FILE_LOG', 'logs/bank.log');
define('NODE_FILE_LOG', 'logs/node.log');
define('LOGSERV_FILE_LOG', 'logs/logserv.log');
define('FIGHT_FILE_LOG', 'logs/fight_file.log');
define('ARENA_FILE_LOG', 'logs/arena_file.log');
define('DICE_GAME_FILE_LOG', 'logs/dice_game_file.log');
define('DICE_GAME_FULL_LOG', 'logs/dice_game_full.log');
define('TROPHY_BONUS_FILE_LOG', 'logs/trophy_bonus.log');
define('MAGIC_MIRROR_LOG', 'logs/magic_mirror.log');
define('CHAT_UGLY_LOG', 'logs/chat_ugly_log.log');
define('ADV_STAT_LOG', 'logs/adv_stat.log');
define('PHP_ERRORS_LOG', 'logs/php_errors.log');
define('USER_REF_LOG', 'logs/user_ref.log');
define('HOLDING_USER_LOG', 'logs/holdings_user.log');
define('EXCHANGE_REQUEST_LOG_F', 'logs/exchange_request.log');
define('MASS_SELLOUT_LOG', 'logs/mass_sellout.log');
define('CLAN_HEAD_REQUEST_LOG', 'logs/clan_head_request.log');
define('FREEZE_PHP_FILES_LOG', 'logs/slow_log_requests.log');
$debug_a_file_hash = array(
	1 => DEBUG_FILE_LOG_DEV,
	2 => BANK_FILE_LOG,
	3 => NODE_FILE_LOG,
	4 => LOGSERV_FILE_LOG,
    5 => FIGHT_FILE_LOG,
    6 => ARENA_FILE_LOG,
    7 => DICE_GAME_FILE_LOG,
    8 => DICE_GAME_FULL_LOG,
    9 => TROPHY_BONUS_FILE_LOG,
    10 => MAGIC_MIRROR_LOG,
    11 => CHAT_UGLY_LOG,
    12 => ADV_STAT_LOG,
	13 => PHP_ERRORS_LOG,
    14 => USER_REF_LOG,
    15 => HOLDING_USER_LOG,
    16 => EXCHANGE_REQUEST_LOG_F,
    17 => MASS_SELLOUT_LOG,
    18 => CLAN_HEAD_REQUEST_LOG,
    19 => FREEZE_PHP_FILES_LOG,
);

$SUPPORTED_LANGUAGE_LIST = array('ru');

$msg_colors_hash = array(
    'Black' => array('id' => 'Black', 'color' => 'Black', 'title' => 'Черный'),
    'Blue' => array('id' => 'Blue', 'color' => 'Blue', 'title' => 'Синий'),
    'Fuchsia' => array('id' => 'Fuchsia', 'color' => 'Fuchsia', 'title' => 'Розовый'),
    'Maroon' => array('id' => 'Maroon', 'color' => 'Maroon', 'title' => 'Темнокрасный'),
    'Navy' => array('id' => 'Navy', 'color' => 'Navy', 'title' => 'Темносиний'),
    'Purple' => array('id' => 'Purple', 'color' => 'Purple', 'title' => 'Фиолетовый'),
    'Teal' => array('id' => 'Teal', 'color' => 'Teal', 'title' => 'Морской волны'),
    'Chocolate' => array('id' => 'Chocolate', 'color' => 'Chocolate', 'title' => 'Шоколадный'),
);

define('DEV_ACCOUNT_ID', 9999999);
define('REGISTRATION_BONUS_ID_GLOBAL', 1);

define('ADMIN_DIR_MAIN', 'sssadminkamm2352112/');
define('ADMIN_DIR_SECOND', 'm4admin/');

$soft_register_info = array(
    'area_id' => 188,
);

$socket_pics = array();
$socket_pics_req = false;
