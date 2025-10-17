<? // $Id: console_log.inc,v 1.1 2009-08-06 13:17:05 vadim_b Exp $
// =========================================================================
// Coder			: AKEB, IT-Territory
// Work started		: 31.07.2009 0:05:16
// User				: v.babajanyan
// Description		: 
// =========================================================================


function console_vardump($v,$name=' ') {
	if(!is_array($v)) {
		if ($name && $name != ' ') console_log($name,' => ',$v);
		else console_log($v);
	} else {
		console_group($name,false);
		foreach($v as $k=>$i) {
			console_vardump($i,$k ? $k : ' ');
		}
		console_groupEnd($name);
	}
}

function _console_return_args($args) {
	$s = array();
	foreach ($args as $item) {
		$s[] = "'".addcslashes($item,"'")."'";
	}
	return implode(', ',$s);
}

// console_log(object[, object, ...])
// Writes a message to the console. You may pass as many arguments as you'd like, and they will be joined together in a space-delimited line.
function console_log() {
	?>
	<script> try { console.log(<?=_console_return_args(func_get_args());?>); } catch (e) {}; </script>
	<?
}

// console_debug(object[, object, ...])
// Writes a message to the console, including a hyperlink to the line where it was called.
function console_debug() {
	?>
	<script> try { console.debug(<?=_console_return_args(func_get_args());?>); } catch (e) {}; </script>
	<?
}

// console_info(object[, object, ...])
// Writes a message to the console with the visual "info" icon and color coding and a hyperlink to the line where it was called.
function console_info() {
	?>
	<script> try { console.info(<?=_console_return_args(func_get_args());?>); } catch (e) {}; </script>
	<?
}

// console_warn(object[, object, ...])
// Writes a message to the console with the visual "warning" icon and color coding and a hyperlink to the line where it was called.
function console_warn() {
	?>
	<script> try { console.warn(<?=_console_return_args(func_get_args());?>); } catch (e) {}; </script>
	<?
}

// console_error(object[, object, ...])
// Writes a message to the console with the visual "error" icon and color coding and a hyperlink to the line where it was called.
function console_error() {
	?>
	<script> try { console.error(<?=_console_return_args(func_get_args());?>); } catch (e) {}; </script>
	<?
}

// console_group(name[, collapsed=true])
// Writes a message to the console and opens a nested block to indent all future messages sent to the console. Call console.groupEnd() to close the block.
function console_group($name,$collapsed=true) {
	if ($collapsed) {
		?><script> try { console.groupCollapsed('<?=$name;?>'); } catch (e) {}; </script><?
	} else {
		?><script> try { console.group('<?=$name;?>'); } catch (e) {}; </script><?
	}
}

// console_groupEnd()
// Closes the most recently opened block created by a call to console.group() or console.groupCollapsed()
function console_groupEnd($name) {
	?><script> try { console.groupEnd('<?=$name;?>'); } catch (e) {}; </script><?
}

// console_time(name)
// Creates a new timer under the given name. Call console.timeEnd(name) with the same name to stop the timer and print the time elapsed..
function console_time($name) {
	?><script> try { console.time('<?=$name;?>'); } catch (e) {}; </script><?
}

// console_timeEnd(name)
// Stops a timer created by a call to console.time(name) and writes the time elapsed.
function console_timeEnd($name) {
	?><script> try { console.timeEnd('<?=$name;?>'); } catch (e) {}; </script><?
}

// console_profile([name])
// Creates a new timer under the given name. Call console.timeEnd(name) with the same name to stop the timer and print the time elapsed..
function console_profile($name='') {
	?><script> try { console.profile('<?=$name;?>'); } catch (e) {}; </script><?
}

// console_profileEnd([name])
// Stops a timer created by a call to console.time(name) and writes the time elapsed.
function console_profileEnd($name='') {
	?><script> try { console.profileEnd('<?=$name;?>'); } catch (e) {}; </script><?
}

// console_count([name])
// Stops a timer created by a call to console.time(name) and writes the time elapsed.
function console_count($name='') {
	?><script> try { console.count('<?=$name;?>'); } catch (e) {}; </script><?
}


?>