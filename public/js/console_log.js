
// Consloe LOG

function console_log() {
	try { console.log(arguments); } catch (e) {};
}

function console_debug() {
	try { console.debug(arguments); } catch (e) {};
}

function console_info() {
	try { console.info(arguments); } catch (e) {};
}

function console_warn() {
	try { console.warn(arguments); } catch (e) {};
}

function console_error() {
	try { console.error(arguments); } catch (e) {};
}

function console_dir(object) {
	try { console.dir(object); } catch (e) {};
}

function console_dirxml(object) {
	try { console.dirxml(object); } catch (e) {};
}

function console_group(name,collapse) {
	if (collapse) {
		try { console.groupCollapsed(name); } catch (e) {};
	} else {
		try { console.group(name); } catch (e) {};
	}
}

function console_groupEnd() {
	try { console.groupEnd(); } catch (e) {};
}

function console_time(name) {
	try { console.dir(name); } catch (e) {};
}

function console_timeEnd(name) {
	try { console.dir(name); } catch (e) {};
}

function console_profile(title) {
	try { console.profile(title); } catch (e) {};
}

function console_profileEnd() {
	try { console.profileEnd(); } catch (e) {};
}

function console_count(title) {
	try { console.count(title); } catch (e) {};
}

