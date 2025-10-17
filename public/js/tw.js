function tw_login_window(reload) {
	if (reload) {
		doPost(window.location.href, 'do_assoc=1&soc_system_auth=5');
	} else {
		window.open("http://"+window.location.host+"/pub/twitter.php?callback_domain="+document.domain,"twlogin","menubar=no,location=yes,status=yes,width=800,height=600");
	}
	return false;
}