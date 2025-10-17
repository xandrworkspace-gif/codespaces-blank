function ggp_login_window(reload) {
	if (reload) {
		doPost(window.location.href, 'do_assoc=1&soc_system_auth=6');
	} else {
		window.open("http://"+window.location.host+"/pub/googleplus.php","gplogin","menubar=no,location=yes,status=yes,width=800,height=600");
	}
	return false;
}