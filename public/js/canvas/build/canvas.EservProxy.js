canvas.modules.EservProxy = function() {
    this.callBacks = {};
    this.proxyRequest = null;
    this.ON_CONNECT = "onConnect";
    this.ON_DISCONNECT = "onDisconnect";
    this.ON_ERROR = "onError";
    this.ON_DATA = "onData";
    this.CONNECT_TIMEOUT = 1e4;
    this.stack = []
};
canvas.modules.EservProxy.prototype.connect = function(host, port, proxy, connectionType) {
    this.proxy = proxy;
    this.createProxy();
    this.firstRequest = this.proxyRequest;
    this.proxyLoad();
    this.connectRequest = this.proxyRequest;
    if (this.timer) clearInterval(this.timer);
    this.timer = setInterval(this.frameHandler.bind(this), 100);
    this.connectTime = Date.now();
    window[this.callBacks[this.ON_CONNECT]]()
};
canvas.modules.EservProxy.prototype.createProxy = function() {
    if (this.proxyRequest) {
        canvas.EventManager.removeEventListener(canvas.utils.URLRequestEvent.EVENT_COMPLETE, this.proxyRequest, this.proxyCompleteHandler, this);
        canvas.EventManager.removeEventListener(canvas.utils.URLRequestEvent.EVENT_ERROR, this.proxyRequest, this.proxyErrorHandler, this)
    }
    this.proxyRequest = new canvas.utils.URLRequest(this.proxy, "POST", null, "arraybuffer");
    canvas.EventManager.addEventListener(canvas.utils.URLRequestEvent.EVENT_COMPLETE, this.proxyRequest, this.proxyCompleteHandler, this);
    canvas.EventManager.addEventListener(canvas.utils.URLRequestEvent.EVENT_ERROR, this.proxyRequest, this.proxyErrorHandler, this)
};
canvas.modules.EservProxy.prototype.frameHandler = function() {
    if (this.firstRequest) {
        if (this.firstRequest.request.readyState == 4 && this.firstRequest.request.status == 200) {
            this.parseResponse(this.firstRequest.request.response);
            clearInterval(this.timer);
            this.firstRequest = null
        } else if (Date.now() - this.connectTime > this.CONNECT_TIMEOUT) {
            clearInterval(this.timer);
            this.firstRequest = null
        }
    }
};
canvas.modules.EservProxy.prototype.proxyCompleteHandler = function() {
    if (this.proxyRequest.request.response) {
        var size = this.proxyRequest.request.response.byteLength;
        if (size > 0) {
            this.parseResponse(this.proxyRequest.request.response)
        } else {}
    }
    this.proxyLoad()
};
canvas.modules.EservProxy.prototype.proxyErrorHandler = function() {
    this.proxyLoad()
};
canvas.modules.EservProxy.prototype.proxyLoad = function() {
    var data = this.stack.length > 0 ? this.stack.pop() : undefined;
    if (data == undefined) {
        var myArray = new ArrayBuffer(1);
        var longInt8View = new Uint8Array(myArray);
        longInt8View[0] = 0;
        this.proxyRequest.load(undefined, myArray, false)
    } else {
        this.proxyRequest.load(undefined, data, false)
    }
    if (this.timeout) clearTimeout(this.timeout);
    this.timeout = setTimeout(this.timerHandler.bind(this), 3e4)
};
canvas.modules.EservProxy.prototype.timerHandler = function() {
    this.proxyRequest.abort();
    this.proxyLoadData()
};
canvas.modules.EservProxy.prototype.proxyLoadData = function(data) {
    if (data != undefined) this.stack.push(data);
    this.createProxy();
    this.proxyLoad()
};
canvas.modules.EservProxy.prototype.sendRequest = function(params) {
    var str = canvas.px.AMF.stringify(this.objectToArray(params));
    this.proxyLoadData(str)
};
canvas.modules.EservProxy.prototype.addCallback = function(type, funcName) {
    this.callBacks[type] = funcName
};
canvas.modules.EservProxy.prototype.httpRequest = function() {};
canvas.modules.EservProxy.prototype.getLog = function() {};
canvas.modules.EservProxy.prototype.objectToArray = function(data) {
    var array = [];
    for (var key in data) {
        if (typeof data[key] == "string" || typeof data[key] == "number") {
            array[key] = data[key]
        } else {
            array[key] = this.objectToArray(data[key])
        }
    }
    return array
};
canvas.modules.EservProxy.prototype.parseResponse = function(buffer) {
    var str, dataLen, view = new Uint8Array(buffer);
    var j, i = 0,
        len = view.length;
    while (true) {
        if (i + 4 > len) break;
        dataLen = new DataView(buffer, i, 4).getUint32(0);
        i += 4;
        if (i + dataLen > len) break;
        str = "";
        i += dataLen;
        for (j = i - dataLen; j < i; j++) {
            str += String.fromCharCode(view[j])
        }
        str = canvas.px.AMF.parse(str);
        window[this.callBacks[this.ON_DATA]](this.arrayToObjectDoubleBackslash(str))
    }
};
canvas.modules.EservProxy.prototype.arrayToObjectDoubleBackslash = function(array) {
    var object = new Object;
    var key;
    var a = [];
    var value;
    for (key in array) {
        value = array[key];
        key = key.replace(/"/g, "");
        if (Array.isArray(value)) {
            object[key] = this.arrayToObjectDoubleBackslash(value)
        } else if (typeof value == "string") {
            object[key] = value.replace(/\\/g, "\\\\")
        } else {
            object[key] = value
        }
    }
    return object
};