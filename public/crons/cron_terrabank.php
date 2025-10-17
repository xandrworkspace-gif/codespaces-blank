<? # $Id: cron_terrabank.php,v 1.7 2010/03/25 12:54:17 p.knoblokh Exp $
exit;
chdir("..");
require_once("include/common.inc");
require_once("lib/terrabank.lib");

$dictionaries = terrabank_query_dictionaries();

$new_projects = make_hash($dictionaries['projects']);

if (!$dictionaries || !$new_projects || !$dictionaries['systems']) {
	error_log('Bad response from terrabank');
	exit;
}

////////////

$new_systems = array();
foreach ($new_projects as $project) {
	foreach ($project['currencies'] as $currency) {
		foreach ($currency['rates'] as $rate) {
			if ($rate['disabled'] && $rate['disabled'] != 'false') {
				continue;
			}
			
			// Если курс временный, но акция кончилась - игнорируем
			if ($tmp_rate && (strtotime($rate['expireDate']) < time_current)) continue;
			// Если есть действительный временный курс - оставляем его
			if (isset($new_systems[$project['id'].'_'.$rate['accountId'].'_1'])) continue;
			
			$new_systems[$project['id'].'_'.$rate['accountId'].'_'.$tmp_rate] = array(
				'project_id' => $project['id'],
				'account_id' => $rate['accountId'],
				'rate' => $rate['value'],
			);
		}
	}
}
$account_hash = get_hash($new_systems, 'account_id', 'account_id');

$systems = $dictionaries['systems'];
foreach ($systems as $system) {
	foreach ($system['accounts'] as $account) {
		$account_id = $account['id'];
		if (isset($account_hash[$account_id])) {
			foreach ($new_systems as &$new_system) {
				if ($new_system['account_id'] == $account_id) {
					$new_system['title'] = from_utf8($account['title']);
					$new_system['unit'] = $account['unit'];
					$new_system['xforms'] = $system['xforms'] ? $system['xforms'] : array();
					if (strtolower($system['uiType']) == 'direct') {
						$new_system['flags'] |= TERRABANK_PAYSYS_FLAG_NOPAY;
					}
				}
			}
		}
	}
}

$old_systems = terrabank_system_list();

foreach ($new_systems as &$system) {
	$system['key'] = $system['project_id'].'_'.$system['account_id'];
}
foreach ($old_systems as &$system) {
	$system['key'] = $system['project_id'].'_'.$system['account_id'];
}
$new_systems = make_hash($new_systems, 'key');
$old_systems = make_hash($old_systems, 'key');

// отключаем ненужные системы
$deleted_systems = array_diff_key($old_systems, $new_systems);
if ($deleted_systems) {
	foreach ($deleted_systems as $deleted_system) {
		terrabank_system_save(array(
			'_set' => 'active = 0',
			'_add' => sql_pholder(' AND project_id=? AND account_id=?', $deleted_system['project_id'], $deleted_system['account_id']),
		));
	}
}

// добавляем новые системы
$added_systems = array_diff_key($new_systems, $old_systems);
if ($added_systems) {
	foreach ($added_systems as $system) {
		$param = array(
			'project_id'  => (int)$system['project_id'],
			'account_id'  => (int)$system['account_id'],
			'title'       => $system['title'] ? $system['title'] : '',
			'rate'        => (float)$system['rate'],
			'unit'        => $system['unit'],
			'ord'         => $system['ord'] ? (int)$system['ord'] : 0,
			'flags'       => $system['flags'] ? (int)$system['flags'] : 0,
			'description' => '',
			'picture'     => '',
			'active'      => 0,
		);

		$new_id = terrabank_system_save($param);
		$param['id'] = $new_id;

		$key = $param['project_id'].'_'.$param['account_id'];
		$old_systems[$key] = $param;
	}
}

foreach ($new_systems as $id => &$new_system) {
	$old_system = $old_systems[$id];
	$new_system['id'] = $old_system['id'];

	if ($old_system['rate'] != $new_system['rate'] || $old_system['unit'] != $new_system['unit']) {
		terrabank_system_save(array(
			'rate' => $new_system['rate'],
			'unit' => $new_system['unit'],
			'_add' => sql_pholder(' AND project_id=? AND account_id=?', $new_system['project_id'], $new_system['account_id']),
		));
	}
}

///////////////

$new_forms = array();
foreach ($new_systems as $system) {
	foreach ($system['xforms'] as $field) {
		$id = $system['id'].'_'.$field['id'];

		$new_forms[$id] = array(
			'system_id' => (int)$system['id'],
			'name' => $field['id'],
			'type' => $field['type'],
			'data' => $field['data'] ? $field['data'] : '',
		);

		if (in_array($field['id'], array('nick', 'sum', 'bonus')) && $field['type'] == 'input') {
			$new_forms[$id]['flags'] = TERRABANK_FORM_FLAG_AUTO;
		}
	}
}

$old_forms = terrabank_form_list();
foreach ($old_forms as $id => $field) {
	$key = $field['system_id'].'_'.$field['name'];
	unset($old_forms[$id]);
	$old_forms[$key] = $field;
}

// удаляем ненужные формы
$deleted_forms = array_diff_key($old_forms, $new_forms);
if ($deleted_forms) {
	foreach ($deleted_forms as $deleted_form) {
		terrabank_form_delete(array('name' => $deleted_form['name'], 'system_id' => $deleted_form['system_id']));
	}
}

// добавляем новые формы
$added_forms = array_diff_key($new_forms, $old_forms);
if ($added_forms) {
	foreach ($added_forms as $form) {
		$param = array(
			'system_id' => (int)$form['system_id'],
			'name'      => $form['name'],
			'type'      => $form['type'] ? $form['type'] : '',
			'data'      => $form['data'] ? $form['data'] : '',
			'flags'     => $form['flags'] ? (int)$form['flags'] : 0,
			'title'     => '',
		);
		$id = $param['system_id'].'_'.$param['name'];
		terrabank_form_save($param);
		$old_forms[$id] = $param;
	}
}

foreach ($new_forms as $id => &$form) {
	$old_form = $old_forms[$id];

	if ($old_form['data'] != $form['data'] || $old_form['type'] != $form['type']) {
		$param = array(
			'data' => $form['data'],
			'type' => $form['type'],
			'_add' => sql_pholder(' AND system_id=? AND name=?', $form['system_id'], $form['name']),
		);
		terrabank_form_save($param);
	}
}

