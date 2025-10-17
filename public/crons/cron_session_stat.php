<?php
chdir("..");
require_once("include/common.inc");
require_once("lib/session_stat.lib");
require_once('lib/freekassa.lib');

//Удаление просроченных платежей
freekassa_payments_delete(false, ' AND intid = 0 AND currency = 0 AND time = 0 AND status = 0 AND time_create < '.(time_current() - TIME_FOR_ONE_PAY));

generate_session_stat_report();



