
/*
 * requires jQuery.getJSON
 */

var MAPI = {
	url: '/pub/api_marketing.php',
	smooth: true,
	nick: null,
	request_params: {
		site_id: ''
	}
};

MAPI._request = function(data, callback) {
	if (!(data instanceof Object))
		var data = {};
	if (!(callback instanceof Function))
		var callback = function(response, textStatus, jqXHR) {
			MAPI._error('Uncaught response', response, true);
		};
	data.t = new Date().getTime() + Math.random();
	$.getJSON(this.url, data, callback);
};

MAPI._error = function(str, data, warn) {
	if (!console)
		return false;
	if (!str)
		return false;
	str = '[MAPI] error: ' + str;
	if (data) {
		if (warn)
			console.warn(str, data);
		else
			console.error(str, data);
	} else {
		if (warn)
			console.warn(str);
		else
			console.error(str);
	}
	return true;
};

MAPI._check_callback = function(callback) {
	if (callback instanceof Function)
		return true;
	this._error('Wrong callback!');
	return false;
};

MAPI.status = function(callback) {
	if (!this._check_callback(callback))
		return false;
	var data = {
		mode: 'status',
		site_id: this.request_params.site_id
	};
	this._request(data, callback);
	return true;
};

MAPI.login_soc = function(soc_system_id, callback) {
	if (soc_system_id instanceof Function) {
		callback = soc_system_id;
		soc_system_id = 0;
	}
	soc_system_id = parseInt(soc_system_id) || 0;
	if (!this._check_callback(callback))
		return false;
	var data = {
		mode: 'login',
		soc_system_id: soc_system_id,
		smooth: this.smooth ? 1 : 0,
		site_id: this.request_params.site_id
	};
	this._request(data, callback);
	return true;
};

MAPI.login_email = function(email, passwd, callback) {
	if (!this._check_callback(callback))
		return false;
	var data = {
		mode: 'login',
		email: email,
		passwd: passwd,
		smooth: this.smooth ? 1 : 0,
		site_id: this.request_params.site_id
	};
	this._request(data, callback);
	return true;
};

MAPI.set_nick = function(nick) {
	this.nick = nick;
};
