#!/bin/sh
. /home/admin/web/dwar.fun/param.sh
while [ 1 ] ;
	do
  ulimit -c 100000
    ./fightd -h ${GLOBALIP} -p${FIGHTDP} -c${FIGHTCP} -l "-1" -d _fightd.log -f ${HTTP}://${FIGHTDOMAIN}/private/fsfeedback.php -srv ${SERVER_ADD}
  done

# -d _fightd_5466.log
