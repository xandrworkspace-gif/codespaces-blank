var support_dom = document.createElement;
var is_mac = (navigator.userAgent.toLowerCase().indexOf("mac")!=-1);

function crime_change(crime) {
	var obj = gebi('crime_select');
	if (obj && crime) {
		obj.value = crime;
	}
	punish_type_change();
}

function punish_type_change() {
	var crime_id = gebi('crime_select').value;
	var type_id = gebi('punish_type_select').value;
	var prefix = crime_id+'_'+type_id;
	var punish_list = gebi('punish');
	if (punish_list) {
		clear_list(punish_list);
		var i = 0;
		for (var j = 0; j < punishment.length; ++j) {
			if (punishment[j].id.substr(0,prefix.length) == prefix) {
				punish_list.options[i++] = new Option(punishment[j].title, punishment[j].id);
			}
		}
	}
	depended_fields_toggle();
	depended_fields_refresh();
}

function punish_change() {
	depended_fields_refresh();
}

function depended_fields_toggle() {
	var type = gebi('punish_type_select').value;
	for (i=1; i<=2; i++) {
	    obj = gebi('type_id_'+i+'_TR');
	    if (obj) {
			if (i != type) {
				obj.style.display = 'none';
			} else {
				obj.style.display = '';
			}
	    }
    }
    money_str_refresh(-1);
	return true;
}                

function depended_fields_refresh() {
	var punish_list = gebi('punish');
	if (!punish_list || !punish_list.value) {
		depended_fields_toggle(); 
		return;
	}
	punish_id = punish_list.value;
	for (var j = 0; j < punishment.length; ++j) {
		if (punishment[j].id == punish_id) {
			if (!time_manual) { // время
				var time = punishment[j].time;
				var days_list = gebi('days');
				clear_list(days_list);
				for(var i=0; i < time.length; i++) {
					days_list.options[i] = new Option(time_intervals[time[i]].title, time_intervals[time[i]].id);
				}
			}
		    money_str_refresh(j); // деньги
			if (punish = gebi('punish_id')) { // id
				punish.value = punishment[j].id2;
			}
		}
	}
}

function money_str_refresh(punishment_id) {
	var money_str = gebi('money_str');
	if (!money_str) return; // ручной ввод
	if (punishment_id == -1) {
		gebi('money').value = 0;
		gebi('money_type').value = 0;
		gebi('money_str').value = 0;
	} else {
		gebi('money').value = punishment[punishment_id].money;
		gebi('money_type').value = punishment[punishment_id].money_type;
		money_str.innerHTML = punishment[punishment_id].money_str;
	}
}

function clear_list(list) {
	if (support_dom && !is_mac) {
		while (list.lastChild) list.removeChild(list.lastChild);
	} else {
		for (var i=list.options.length-1; i>=0; i--) {
			list.options[i]=null;
		}
	}
}
