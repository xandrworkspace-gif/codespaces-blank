<?php

function raid_log($message, $context = []) {
    $logFile = '/home/admin/web/dwar.fun/public_html/logs/raid_manager.log';

    // Создаем папку logs если нет
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }

    // Формируем строку лога
    $date = date('Y-m-d H:i:s');
    $contextStr = '';
    if (!empty($context)) {
        $contextStr = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $line = "[$date] $message $contextStr\n";

    // Пишем в файл
    file_put_contents($logFile, $line, FILE_APPEND);
}
