function artifactAltSimple(artifact_id, show, evnt, effect) {
    var artifact_alt = top.gebi('artifact_alt');
    if (!effect) var div = gebi('AA_'+artifact_id);
    if (!artifact_alt) return;

    if (show == 2) {
        document.onmousemove = function(e) {
            artifactAltSimple(artifact_id, 1, e||event);
        };
        artifact_alt.innerHTML = (!effect) ? renderArtifactAlt('AA_'+artifact_id) : renderEffectAlt();
        //top.obj = obj;
        //top.show_alt();
    }

    if (!show) {
        artifact_alt.style.display = 'none';
        artifact_alt.innerHTML = '';
        document.onmousemove = function() { };
        return;
    }

    var coor = getIframeShift();
    var ex = coor.left;
    var ey = coor.top;

    if (evnt) {
        ex += evnt.clientX;
        ey += evnt.clientY;
    }

    if (top.noIframeAlt) {
        ex = evnt.clientX + Math.max(window.pageXOffset,top.document.body.scrollLeft);
        //ey = evnt.clientY + top.document.body.scrollTop + 200;
        ey = evnt.clientY + Math.max(window.pageYOffset,top.document.body.scrollTop);
    }

    var x = ex + artifact_alt.offsetWidth > top.document.body.clientWidth - 20 ? ex - artifact_alt.offsetWidth - 10 : ex + 10;
    var y = ey + artifact_alt.offsetHeight > top.document.body.clientHeight - 20 ? ey - artifact_alt.offsetHeight - 10 : ey + 10;

    if (x < 0 ) {
        x = ex - artifact_alt.offsetWidth/2;
    }
    if (x < 7 ) {
        x = 7;
    }
    if (x > top.document.body.clientWidth - artifact_alt.offsetWidth - 20) {
        x= top.document.body.clientWidth - artifact_alt.offsetWidth - 20;
    }

    artifact_alt.style.left = x + "px";
    artifact_alt.style.top = y + "px";

    if ((show == 1) && (artifact_alt.style.display != 'block')) {
        artifact_alt.style.display = 'block';
    }
}

function effectAltSimple(show, evnt) {
	if (show == 2) {
		document.onmousemove = function(e) {
			effectAltSimple(null, 1, e||event, 1);
		};
	}
	artifactAltSimple(null, show, evnt, 1);
}

function setAltTimer() {
	if (typeof(temp_effects) != 'undefined') {
		for (var n in temp_effects) {
			temp_effects[n].time_left_sec = Math.max(temp_effects[n].time_left_sec, 0);
			if (!temp_effects[n].time_left_sec) continue;
			var ss = Math.floor(temp_effects[n].time_left_sec) % 60;
			var mm = Math.floor(temp_effects[n].time_left_sec/60) % 60;
			var hh = Math.floor(temp_effects[n].time_left_sec/3600) % 24;
			var dd = Math.floor(temp_effects[n].time_left_sec/86400);
			temp_effects[n].time_left = '';
			if (dd) temp_effects[n].time_left += dd + strings.day;
			if (hh || (dd && mm)) temp_effects[n].time_left += hh + strings.hour;
			if (mm) temp_effects[n].time_left += mm + strings.min;
			if (ss && temp_effects[n].time_left_sec < 60) temp_effects[n].time_left += ss + strings.sec;
			temp_effects[n].time_left_sec--;
		}
	}
	setTimeout("setAltTimer()", 1000);
}

function renderEffectAlt() {
	if (typeof(temp_effects) == 'undefined') {
		if (typeof(top.frames['main_frame']) == 'undefined') return;
		if (typeof(top.frames['main_frame'].temp_effects) == 'undefined')  return;
	}
	var effects = (typeof(temp_effects) != 'undefined') ? temp_effects : top.frames['main_frame'].temp_effects;
	var rstrings = (typeof(strings) != 'undefined') ? strings : top.frames['main_frame'].strings;
	var content = '';
	var additional_count = 0;
	content += '<table width="300" border="0" cellspacing="0" cellpadding="0">';
	content += '<tr><td width="14" class="aa-tl"><img src="images/d.gif" width="14" height="24"><br></td>';
	content += '<td class="aa-t" align="center" style="vertical-align:middle"><b style="color: #666;">'+rstrings.title+'</b></td>';
	content += '<td width="14" class="aa-tr"><img src="images/d.gif" width="14" height="24"><br></td></tr>';
	content += '<tr><td class="aa-l" style="padding:0;"></td><td style="padding:0;background-color:#FBD4A4;">';
	content += '<table width="275" style=" margin: 3px" border="0" cellspacing="0" cellpadding="0"><tr>';
	content += '<td align="center" valign="top">';
	var k = 0;
	for (var n in effects) {
		effects[n].time_left_sec = Math.max(effects[n].time_left_sec, 0);
		try{ if(effects[n].additional_effect == true) additional_count++; } catch (e) {}
		if (!effects[n].time_left_sec) continue;
		content += '<tr>';
		content += '<td class="'+ ((k % 2) ? 'tbl-sts_bg-light ' : '') + 'tbl-usi_brd-bottom b" style="color: '+effects[n].color+';">'+effects[n].title+'</td>';
		content += '<td class="'+ ((k % 2) ? 'tbl-sts_bg-light ' : '') + 'tbl-usi_brd-bottom b" width=100 align=right>'+ (effects[n].del_after_fight ? rstrings.del_after_fight : effects[n].time_left)+ '</td>';
		content += '</tr>';
		k++;
	}
	if(additional_count > 0){
		content += '<tr>';
		content += '<td colspan=2 class="'+ ((k % 2) ? 'tbl-sts_bg-light ' : '') + 'tbl-usi_brd-bottom b" width=100 align=center>+' + additional_count + ' ' + formatByCount(additional_count, 'Дополнительный эффект', 'Дополнительных эффекта', 'Дополнительных эффектов') + '</td>';
		content += '</tr>';
		k++;
	}
	try{
        /*if(rstrings.cost){
            n++;
            content += '<tr>';
            content += '<td class="'+ ((n % 2) ? 'tbl-sts_bg-light ' : '') + 'tbl-usi_brd-bottom b">Сила усиления:</td>';
            content += '<td class="'+ ((n % 2) ? 'tbl-sts_bg-light ' : '') + 'tbl-usi_brd-bottom b" width=100 align=right>'+rstrings.cost+'</td>';
            content += '</tr>';
        }*/
    }catch(e){}
	content += '</td></tr></table>';
	content += '</td><td class="aa-r" style="padding:0px"></td></tr>';
	content += '<tr><td class="aa-bl"></td><td class="aa-b"><img src="images/d.gif" width="1" height="5"></td><td class="aa-br"></td></tr>';
	content += '</table>';

	return content;
}