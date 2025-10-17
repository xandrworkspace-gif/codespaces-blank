#!/bin/sh

cd /home/admin/web/elizium.online/public_html/crons/

PHP="php -d memory_limit=32M"

${PHP} cron_send_mail.php > /dev/null 2>&1