function esrv(proxyRef) {
	var obj = {};
	var refId = proxyRef.id;
	var connectParams;
	var sq = 0;
	var callStack = {};
	var connected = false;
	var authorized = false;
	
	var addCallback = function(type, func) {
		var fn = '_esrv_' + type + refId;
		window[fn] = function() {
			func.apply(obj, arguments);
		};
		proxyRef.addCallback(type, fn);
	};
	
	obj.isConnected = function() {
		return connected;
	};
	
	obj.isAuthorized = function() {
		return authorized;
	};
	
	obj.reconnect = function() {
		//
	};
	
	obj.connect = function(params) {
		connected = false;
		authorized = false;
		connectParams = params;
		proxyRef.connect(params.host, params.port, params.http, 'HTTP');
	};
	
	obj.getLog = function() {
		return proxyRef.getLog ? proxyRef.getLog() : '';
	};
	
	obj.request = function(data, success, fail) {
		data.sq = ++sq;
		callStack[data.sq] = {
			success: success,
			fail: fail
		};
		
		proxyRef.sendRequest(data);
	};
	
	obj.onConnect = function() {};
	obj.onDisconnect = function() {};
	obj.onError = function(code, msg) {};
	obj.onNotify = function(data) {};
	obj.onAuth = function() {};
	obj.onData = function(data) {};
	
	addCallback('onError', function(code, msg) {
		obj.onError(code, msg);
	});
	
	addCallback('onConnect', function() {
		connected = true;
		authorized = false;
		obj.onConnect();
		
		var timeout = setTimeout(function() {
			if (!connected || !authorized) {
				obj.onError('auth', 'authorization timed out');
				obj.onDisconnect();
			}
		}, 6000);		
		
		setTimeout(function() {
			obj.request({
				rc: 'auth',
				cid: connectParams.cid,
				eid: connectParams.eid,
				key: connectParams.key
			}, function() {
				authorized = true;
				clearTimeout(timeout);
				obj.onAuth();
			}, function() {
				connected = false;
				authorized = false;
				clearTimeout(timeout);
				obj.onError('auth', 'authorization failed');
				obj.onDisconnect();
			});
		}, 0);
	});
	
	addCallback('onData', function(data) {	
		obj.onData(data); // call on any data received
		if (!data.sq) {
			try {
				obj.onNotify(data);
			} catch (e) {
				console.error(e);
				throw e;
			}
			return;	
		}

		var callInfo = callStack[data.sq];
		if (callInfo) {
			callStack[data.sq] = null;
			var func = (data.rs && !data.err) ? callInfo.success : callInfo.fail;
			if (func) {
				try {
					func(data);
				} catch (e) {
					console.error(e);
					throw e;
				}
			}
		}		
	});
	
	addCallback('onDisconnect', function() {
		connected = false;
		authorized = false;
		obj.onDisconnect();
	});
	
	return obj;
}
