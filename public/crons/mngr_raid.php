<?php
require_once 'include/common.inc';  // Подставь свой путь, если отличается

// ID рейда и ID локации
$raid_id = 587;
$lobby_location_id = 788;

// Логируем запуск крон-обработчика
file_put_contents('raid_log.txt', date('Y-m-d H:i:s') . " Проверка рейда $raid_id в локации $lobby_location_id\n", FILE_APPEND);

// Получаем всех игроков в локации 788 (или как у тебя называется)
$players = getPlayersInLocation($lobby_location_id);

if (count($players) > 0) {
    // Логируем запуск
    file_put_contents('raid_log.txt', date('Y-m-d H:i:s') . " Найдено " . count($players) . " игроков в лобби. Запускаем рейд.\n", FILE_APPEND);
    
    // Запускаем инстанс для всех игроков
    startRaidInstance($players, $raid_id);
    
sendGlobalMessage("Рейд на босса 587 начался!", CHAT_CHF_SYSTEM);


} else {
    // Логируем отмену
    file_put_contents('raid_log.txt', date('Y-m-d H:i:s') . " Никого нет в лобби, рейд отменён.\n", FILE_APPEND);
}
?>
