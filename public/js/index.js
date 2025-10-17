// $Id: index.js 1 2009-05-18 15:57:45Z sen $

function _offset(id, on) {
	if (on) {
		if (id == 'mnu_news') return 15;
		if (id == 'mnu_about') return 130;
		if (id == 'mnu_forum') return 227;
		if (id == 'mnu_client') return 330;
	} else {
		if (id == 'mnu_news') return 40;
		if (id == 'mnu_about') return 155;
		if (id == 'mnu_forum') return 255;
		if (id == 'mnu_client') return 350;
	}
}

function _w(id, on) {
	if (on) {
		if (id == 'mnu_news') return 122;
		if (id == 'mnu_about') return 122;
		if (id == 'mnu_forum') return 123;
		if (id == 'mnu_client') return 178;
	} else {
		if (id == 'mnu_news') return 74;
		if (id == 'mnu_about') return 72;
		if (id == 'mnu_forum') return 64;
		if (id == 'mnu_client') return 135;
	}
}

function _allOut() {
	rollOutMenu('mnu_news');
	rollOutMenu('mnu_about');
	rollOutMenu('mnu_forum');
	rollOutMenu('mnu_client');
}

function rollOverMenu(id) {
	_allOut();
	var obj = gebi(id);
	if (obj) obj.innerHTML = '<img src="images/index/'+id+'1.gif" border="0" height="35" width="'+_w(id, 1)+'" onMouseOut="rollOutMenu(\''+id+'\');" style="position: absolute; top: 0px; left: '+_offset(id, 1)+'px;">';
}

function rollOutMenu(id) {
	var obj = gebi(id);
	if (obj) obj.innerHTML = '<img src="images/index/'+id+'0.gif" border="0" height="35" width="'+_w(id, 0)+'" onMouseOver="rollOverMenu(\''+id+'\');" style="position: absolute; top: 0px; left: '+_offset(id, 0)+'px;">';
}

function rollOver(id) {
	var obj = gebi(id);
	if (obj) obj.src = 'images/index/'+id+'1.gif';
}

function rollOut(id) {
	var obj = gebi(id);
	if (obj) obj.src = 'images/index/'+id+'0.gif';
}

function loginSubmit() {
	var obj = gebi('loginForm');
	if (obj) obj.submit();
}