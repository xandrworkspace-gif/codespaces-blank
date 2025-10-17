<?php
chdir("..");
require_once("include/common.inc");

// Настройка логов
$log_file = '/home/admin/web/dwar.fun/public_html/logs/mngr_raid_queue_debug.log';

function logMessage($message) {
    global $log_file;
    $time = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$time] $message\n", FILE_APPEND);
}

logMessage("=== Запуск mngr_raid_queue.php ===");

// Ограничение времени работы
set_time_limit(60);
$stime1 = time();

common_define_settings();

if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) {
    logMessage("Проект остановлен. Выход.");
    sleep(MNGR_RAID_INTERVAL);
    return;
}

require_once("lib/raid.lib");
require_once("lib/party.lib");
require_once("lib/instance.lib");
require_once("lib/bonus.lib");
require_once("lib/fight.lib");
require_once("lib/crossserver.lib");

$copies = defined('MNGR_BG_QUEUE_COPIES') ? MNGR_BG_QUEUE_COPIES : 1;
$copy = intval($_SERVER['argv'][1]);

if ($copy < 1 || $copy > $copies) {
    logMessage("Ошибка: Copy is not defined!");
    sleep(MNGR_RAID_INTERVAL);
    die('Copy is not defined!');
}

$copy = $copy - 1;
logMessage("Запущена копия $copy из $copies");

// Получаем список всех рейдов
$raid_hash = make_hash(raid_list());
logMessage("Загружен список рейдов: " . count($raid_hash));

// Загружаем игроков в очереди
$raid_users = raid_user_list(array('status' => [RAID_USER_STATUS_PENDING, RAID_USER_STATUS_WAITING]));
logMessage("Найдено пользователей в очереди: " . count($raid_users));

raid_users_unset_inactive($raid_users);

// Удаляем просроченные заявки
$user_ids = [];
foreach ($raid_users as $k => $user) {
    if (($user['dtime'] > 0) && ($user['dtime'] < $stime1) && ($user['status'] == RAID_USER_STATUS_WAITING)) {
        $user_ids[] = $user['user_id'];
        unset($raid_users[$k]);
    }
}
if ($user_ids) {
    logMessage("Удаляем просроченные заявки: " . implode(", ", $user_ids));
    raid_user_delete(['user_id' => $user_ids]);
}

$raid_users_queue = $raid_users;

// Проверяем активные инстансы
$current_raid_active = 0;
$instance_hash = make_hash(instance_list(['parent_id' => 0, 'raid_active' => 1], "AND id % $copies = $copy"));
logMessage("Найдено активных инстансов: " . count($instance_hash));

// Собираем команды по рейдам
$raid_user_groups = [];
foreach ($raid_users as $raid_user) {
    $key = $raid_user['raid_id'] . "_" . $raid_user['party_id'];
    $raid_user_groups[$key][] = $raid_user;
}

// Логируем группы
foreach ($raid_user_groups as $group_key => $group) {
    logMessage("Группа $group_key, игроков: " . count($group));
}

// Создаём инстансы при полном составе
foreach ($raid_user_groups as $group_key => $group) {
    list($raid_id, $party_id) = explode('_', $group_key);

    if (count($group) < 2) {
        continue;  // Ждём минимум 2 человека для теста (порог можно изменить)
    }

    $raid = $raid_hash[$raid_id];
    if (!$raid) {
        logMessage("Ошибка: Не найден рейд $raid_id");
        continue;
    }

    $instance_id = instance_create($raid['inst_artikul_id'], [
        'raid_active' => 1,
        'raid_id' => $raid_id,
        'flags' => INST_FLAG_PREPARED
    ]);

    if (!$instance_id) {
        logMessage("Не удалось создать инстанс для рейда $raid_id");
        continue;
    }

    logMessage("Создан инстанс $instance_id для рейда $raid_id");

    // Распихиваем игроков в инстанс
    foreach ($group as $raid_user) {
        raid_user_save([
            'id' => $raid_user['id'],
            'instance_id' => $instance_id,
            'status' => RAID_USER_STATUS_PENDING
        ]);

        instance_user_save([
            'instance_id' => $instance_id,
            'user_id' => $raid_user['user_id'],
            'team' => $raid_user['user_kind'],
            'flags' => 0
        ]);

        logMessage("Игрок {$raid_user['user_id']} добавлен в инстанс $instance_id");
    }

    // Снимаем флаг подготовки
    instance_save([
        'id' => $instance_id,
        'flags' => INST_FLAG_READY,
        'dtime' => time() + $raid['duration']
    ]);

    logMessage("Инстанс $instance_id переведён в статус READY");
}

$stime2 = time();
$rtime = $stime2 - $stime1;
logMessage("Итерация завершена, время выполнения: {$rtime} сек");

if ($rtime > MNGR_RAID_INTERVAL) {
    error_log("(mngr_raid_queue {$copy}): Runtime $rtime sec");
}

sleep(max(MNGR_RAID_INTERVAL - $rtime, 0));
logMessage("Ожидание следующего запуска");
