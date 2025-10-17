#!/bin/sh

NODE_NUMS='1';

SERVER_ADD=srv_elizium

echo COMMON:
./cron_minutely.sh ${SERVER_ADD} > /dev/null 2>&1 &
./cron_hourly.sh ${SERVER_ADD} > /dev/null 2>&1 &
./cron_10min.sh ${SERVER_ADD} > /dev/null 2>&1 &
./cron_daily.sh ${SERVER_ADD} > /dev/null 2>&1 &

./cron_m_casino.sh ${SERVER_ADD} > /dev/null 2>&1 &
./cron_m_fight.sh ${SERVER_ADD} > /dev/null 2>&1 &

echo running CHAOT manager...
nohup ./cron_adv_chaot.sh ${SERVER_ADD} &

echo running BOSS manager...
nohup ./cron_boss_scheduler.sh ${SERVER_ADD} &

echo running mngr_bonus_cache_update manager...
nohup ./mngr_bonus_cache_update.sh ${SERVER_ADD} &

echo running mngr_casino manager...
nohup ./mngr_casino.sh ${SERVER_ADD} &

echo running mngr_clan_battle manager...
nohup ./mngr_clan_battle.sh ${SERVER_ADD} &

echo running mngr_clan_battle_draw manager...
nohup ./mngr_clan_battle_draw.sh ${SERVER_ADD} &

echo running mngr_event manager...
nohup ./mngr_event.sh ${SERVER_ADD} &

echo running mngr_fight_finish manager...
nohup ./mngr_fight_finish.sh ${SERVER_ADD} &

echo running mngr_fight_launch manager...
nohup ./mngr_fight_launch.sh ${SERVER_ADD} &

echo running mngr_logserv manager...
nohup ./mngr_logserv.sh ${SERVER_ADD} &

echo running mngr_souz manager...
nohup ./mngr_souz.sh ${SERVER_ADD} &

echo running BG manager...
nohup ./mngr_bg.sh ${SERVER_ADD} &

echo running RAID manager...
nohup ./mngr_raid.sh ${SERVER_ADD} &

echo running CASTLE manager...
nohup ./mngr_castle.sh ${SERVER_ADD} &

echo running SLAUGHTER manager...
nohup ./mngr_slaughter.sh ${SERVER_ADD} &

echo running hunt managers...
nohup ./mngr_hunt.sh ${SERVER_ADD} &

echo running farm managers...
nohup ./mngr_farm.sh ${SERVER_ADD} &

nohup ./mngr_dungeons.sh ${SERVER_ADD} &
nohup ./mngr_hunt_attack.sh ${SERVER_ADD} &