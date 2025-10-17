<? # $Id: log.inc,v 1.3 2009-10-29 06:58:57 s.ignatenkov Exp $

// Утилиты для логов

function log_format_values($values, $width) {
	$i = 0;
	if (!is_array($width)) $width = array($width);
	$return = '';
	foreach($values as $value) {
		$return .= str_pad($value, isset($width[$i]) ? $width[$i] : $width[0]);
		$i++;
	}
	return $return;
}

function rotate_table_to_file($db, $table, $filename, $condition = '1', $add = '', $width = 20) {
	if (!$db->db_GetQueryArray("DESCRIBE `$table`", $data)) return false;
	$fields = get_hash($data, 'Field', 'Field');	
	$result = $db->ExecSql('SELECT '.implode(', ', $fields).' FROM `'.$table.'` WHERE '.$condition.' '.$add);
	if (!$result) return false;
	if (($handle = @fopen($filename, 'w')) == false) return false;	
	fwrite($handle, log_format_values($fields, $width)."\n");	
	while($data = $db->db_NextRow($result)) fwrite($handle, log_format_values($data, $width)."\n");
	fclose($handle);
	$db->ExecSql('DELETE FROM `'.$table.'` WHERE '.$condition);
	return true;
}
