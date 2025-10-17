function newImage(src) {
	var im = new Image()
	im.src=src
	return im
}
function init_imghi(){
	for(var i = 0; i < document.images.length; i++) {
		init_imghi1(document.images[i])
	}
	var inps = document.getElementsByTagName('INPUT')
	for(var i = 0; i < inps.length; i++) {
		init_imghi1(inps[i])
	}
}
function init_imghi1(o){
	if (o.getAttribute('hi') && o.getAttribute('src') && (o.tagName == 'IMG' || (o.tagName == 'INPUT' && o.getAttribute('type') == 'image'))) { 
		o.sl = newImage(o.src)
		o.sh = newImage(o.src.replace(/_off\.([^.]*)$/, "_on.$1"))
		o.onmouseover = function() {
			this.src = this.sh.src
		}
		o.onmouseout = function() {
			this.src = this.sl.src
		}
	}
}
var onload_bu = window.onload ? window.onload : false
window.onload = function() {
	if(onload_bu) {
		onload_bu()
	}
	init_imghi()
}
