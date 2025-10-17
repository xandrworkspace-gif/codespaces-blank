var WSProxy = function(options) {
    this.options = options || {};
    this.options.onConnect = this.options.onConnect || function() {
        console.warn("onConnect event", arguments)
    };
    this.options.onMessage = this.options.onMessage || function() {
        console.warn("onMessage event", arguments)
    };
    if(typeof this.options.onError !== "function") {
        this.options.onError = function() {
            console.warn("onError event", arguments)
        }
    }
    this.onOpenWs = this.onOpenWs.bind(this);
    this.onMessageWs = this.onMessageWs.bind(this);
    this.curPackSize = 0;
    this.curPack = ""
};
WSProxy.prototype.log = function() {
    if (this.options.logEnabled) {
        console.log.apply(console, arguments)
    }
};
WSProxy.prototype._connect = function() {
    var t = this.ws = io(this.connectOptions.ws.host, {
        path : '/fightwsIO/',
        transports: ["polling", "websocket"],
        protocols: ["my-protocol-v1"],
        query: {

        }
    });
    t.on('connect', this.onOpenWs);
    //t.onmessage = this.onMessageWs;
    t.on('event', this.onMessageWs);
    var e = this
    t.on('disconnect', function (t) {
        console.warn("WSProxy:onclose", t);
        e.reconnect();
    });
    t.on('connect_error', function (t) {
        console.error("WSProxy:onerror", t);
        e.reconnect();
    });
};
WSProxy.prototype._parseMessage = function(message) {
    if (this.storedMessage) {
        message = this.storedMessage + message;
        this.storedMessage = ""
    }
    var a = message.split("\0");
    var i, len = a.length;
    for (i = 0; i < len; i++) {
        if (a[i]) {
            if (a[i + 1] == undefined) {
                this.storedMessage = a[i]
            } else {
                this.options.onMessage(a[i])
            }
        }
    }
};
WSProxy.prototype.onOpenWs = function() {
    this.log("WSProxy:onOpenWs");
    this.ws.send(JSON.stringify({
        event: "connect",
        host: this.connectOptions.fs.host,
        port: this.connectOptions.fs.port
    }))
};
WSProxy.prototype.onMessageWs = function(event) {
    var message;
    try {
        message = JSON.parse(event)
    } catch (e) {
        console.error("parse json data", event.data, e);
        message = {}
    }
    switch (message.event) {
        case "connected":
            this.connected = true;
            this._tryCount = 0;
            this.sourcePool = "";
            this.options.onConnect();
            break;
        case "message":
            this._parseMessage(message.data);
            break
    }
};
WSProxy.prototype.connect = function(options) {
    this.log("WSProxy:connect", options);
    this.connectOptions = options;
    if (this.ws) {
        console.error("Connection already exists. Call destroy method");
        return
    }
    this._connect()
};
WSProxy.prototype.reconnect = function() {
    if (!this._tryCount) this._tryCount = 0;
    this._tryCount++;
    this.log("WSProxy:reconnectWs", this._tryCount);
    this.destroy();
    var self = this;
    this._reconnectTimeout = setTimeout(function() {
        self._connect()
    }, Math.pow(2, this._tryCount) * 1e3)
};
WSProxy.prototype.destroy = function() {
    this.log("WSProxy:destroy");
    this.connected = false;
    if (this.ws) {
        this.ws.onopen = null;
        this.ws.onmessage = null;
        this.ws.onclose = null;
        this.ws.onerror = null;
        try{ this.ws.close(); }catch (e){}
        this.ws = null
    }
    clearTimeout(this._reconnectTimeout)
};
WSProxy.prototype.send = function(data) {
    if (!this.connected) {
        console.error("WSProxy is not connected");
        return
    }
    this.ws.send(JSON.stringify({
        event: "message",
        data: data
    }))
};
canvas.app.battle.engine.Connection.prototype.wsMessageHandler = function(data) {
    if (!canvas.app.battle.model.online) return;
    this.recvData(data)
};