#!/bin/sh
echo running BOSS manager...
nohup ./cron_boss_scheduler.sh srv_elizium &

nohup ./cron_instance_planner.sh srv_elizium &