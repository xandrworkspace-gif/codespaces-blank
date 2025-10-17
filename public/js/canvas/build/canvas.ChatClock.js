canvas.modules.ChatClock = function(parent, ts, tm) {
    parent.style.backgroundImage = "url('images/tbl-main_chat-clock-bg.gif')";
    var img = document.createElement("img");
    parent.appendChild(img);
    img.src = "images/tbl-main_chat-clock-btn.gif";
    img.style.cursor = "pointer";
    img.style.position = "relative";
    img.style.top = 12;
    img.onclick = this.clickHandler.bind(this);
    var span = document.createElement("span");
    parent.appendChild(span);
    span.style.position = "relative";
    span.style.color = "#f9dfa1";
    span.style.fontSize = 13;
    span.style.fontWeight = "bold";
    span.style.top = 13;
    span.style.left = 2;
    this.span = span;
    var d1 = new Date;
    if (tm == undefined) {
        tm = d1.getHours() + ":" + d1.getMinutes() + ":" + d1.getSeconds()
    }
    var ar = tm.split(":");
    var d2 = new Date;
    d2.setHours(parseInt(ar[0]));
    d2.setMinutes(parseInt(ar[1]));
    d2.setSeconds(parseInt(ar[2]));
    this.delta = d2.getTime() - d1.getTime();
    setInterval(this.timerHandler.bind(this), 1e4);
    this.timerHandler()
};
canvas.modules.ChatClock.prototype.clickHandler = function() {
    chat_change_time_zone()
};
canvas.modules.ChatClock.prototype.timerHandler = function() {
    var d = new Date(Date.now() + this.delta);
    this.span.innerText = canvas.Functions.setNumberLen(d.getHours()) + ":" + canvas.Functions.setNumberLen(d.getMinutes())
};
canvas.modules.ChatClock.prototype.getTime = function() {
    return Date.now() + this.delta
};
canvas.modules.ChatClock.prototype.time_shift = function(minutes) {
    this.delta += parseInt(minutes) * 6e4
};