<? # $Id: cron_stat_daily.php,v 1.2 2010-02-12 16:47:12 p.knoblokh Exp $

chdir("..");
require_once("include/common.inc");
require_once("lib/auth.lib");
require_once("lib/stat.lib");
require_once("lib/user.lib");
set_time_limit(0);

common_define_settings();

// Проверка, что проект не остановлен
if (defined('PROJECT_STOPPED') && PROJECT_STOPPED) return;

$t2 = strtotime('yesterday 23:59', time_current());
$t1 = strtotime('- 30 day', $t2);

$partners = make_hash($PARTNERS_CFG, 'partner_pid');
$stats = array();
$next_uid = 0;
$auth_limit = 1000;

while(1) {
	$auths_hash = make_hash(auth_list(sql_pholder(' AND uid > ? LIMIT '.$auth_limit, $next_uid ),false,'uid, server_id,auth_flags, time_login, partner_pid'),'uid');
	if(!$auths_hash) break;
	$nofridge_ids = array();
	foreach($auths_hash as $auth) {
		if (($auth['time_login'] >= $t1) && ($auth['time_login'] <= $t2)){
			$auths[$auth['partner_pid']] ++;
		}
		if (!($auth['auth_flags'] & AUTH_FLAG_FROZEN) && !($auth['auth_flags'] & AUTH_FLAG_DELETED) && ($auth['server_id'] == SERVER_ID)) {
			$nofridge_ids[] = $auth['uid'];
		}
	}
	// Сбор метрик по кол-ву денег для юзеров вне морозилки
	$data = array();
	if ($nofridge_ids) $data = make_hash(user_list(array('id' => $nofridge_ids), '', false, 'id,flags,level, kind, money, money_silver, money_gold'),'id');
	if ($data) {
		$user_credit_hash = make_hash(user_credit_list(false, sql_pholder(' AND loan_size > 0 AND user_id IN(?@)',array_keys($nofridge_ids)), 'user_id, loan_size'),'user_id');
		foreach($data as $user) {
			if ($user['flags'] & USER_FLAG_ADMIN) continue;
			$level = $user['level'];
			$kind = $user['kind'];
			$user_id = $user['id'];
			$sums[$level][$kind]['money'] += $user['money'];
			$sums[$level][$kind]['money_silver'] += $user['money_silver'];
			$sums[$level][$kind]['money_gold'] += $user['money_gold'];
			$sums[$level][$kind]['credit'] += $user_credit_hash[$user_id]['loan_size'];
			$user_counts[$level][$kind]++;
		}
	}

	$next_uid = max(array_keys($auths_hash));
}

foreach ($auths as $partner_pid => $cnt) {
	$field = (isset($partners[$partner_pid]) && isset($partners[$partner_pid]['m_active_cnt_field']) && $partners[$partner_pid]['m_active_cnt_field']) ? $partners[$partner_pid]['m_active_cnt_field'] : 'm_active_cnt';
	if (!isset($stats[$field])) $stats[$field] = 0;
	$stats[$field] += intval($cnt);
}

foreach ($stats as $stat => $val) {
	stat_update($stat, $val, 2);
}



if ($sums) {
	foreach($sums as $level => $item_kind) {
		foreach($item_kind as $kind => $item) {
			metric_group_add(METRIC_TYPES_MONEY, array('money_type' => MONEY_TYPE_GAME, 'level' => $level, 'kind' => $kind), array('money_sum' => round($item['money'])));
			metric_group_add(METRIC_TYPES_MONEY, array('money_type' => MONEY_TYPE_SILVER, 'level' => $level, 'kind' => $kind), array('money_sum' => round($item['money_silver'])));
			metric_group_add(METRIC_TYPES_MONEY, array('money_type' => MONEY_TYPE_GOLD, 'level' => $level, 'kind' => $kind), array('money_sum' => round($item['money_gold'])));
			metric_group_add(METRIC_TYPES_MONEY, array('level' => $level, 'kind' => $kind), array('credit_sum' => round($item['credit'])));
		}
	}

	foreach ($user_counts as $level => $item_kind) {
		foreach($item_kind as $kind => $count) {
			metric_group_add(METRIC_TYPES_MONEY, array('level' => $level, 'kind' => $kind), array('money_nofridge_cnt' => $count));
		}
	}
}
