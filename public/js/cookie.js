// $Id: cookie.js,v 1.2 2007-12-20 12:43:34 s.panferov Exp $

String.prototype.trim = function(){return this.replace(/^\s+/,'').replace(/\s+$/,'')}
function getCookie (name) {
	var cookies = document.cookie.split(/;/)
	var cookie;
	for (var i=0; i<cookies.length; i++) {
		cookie = cookies[i].split(/=/);
		if(name.trim() == cookie[0].trim()) return unescape(cookie[1])
	}
	return null
}
function setCookie (name, value) {
	var str = name + "=" + escape (value)
	for(var i=2; i<arguments.length; i++){
		var arg = arguments[i]
		switch (i){
			case 2:
				if (typeof(arg) != 'undefined') {
					if (typeof(arg) != 'object') {
						var s=1000, m=60*s, h=60*m, d=24*h, mon=30*d, y=12*mon
						arg = new Date(new Date().getTime() + eval('0' + arg.toString().toLowerCase().replace(/([0-9]+)([a-z]+)/g,'$1*$2')))
					}
					else if(arg.days!=undefined) arg=new Date(new Date().getTime() + arg.days*86400*1000)
					str += "; expires=" + arg.toGMTString()
					
				}
				break
			case 3:
				str += "; path=" + arg
				break
			case 4:
				str += "; domain=" + arg
				break
			case 5:
				str += "; secure"
				break
		}
	}
	document.cookie = str
}
function deleteCookie(name) {  
	setCookie(name, getCookie (name), -1)
}

// setCookie ('name', 'value', '+2y', '/')
// var n = getCookie('name')