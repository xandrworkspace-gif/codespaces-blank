function odkl_login_window(reload) {
	odkl_login(true);
}

function odkl_login() {
	url = "http://www.odnoklassniki.ru/oauth/authorize?client_id=" + odkl_client_id + "&scope=VALUABLE ACCESS;MESSAGING;&response_type=code&redirect_uri=" + odkl_ret_url;
	var w = window.open(url, 'odkl_login', 'width=550, height=510, status=0, scrollbars=0, menubar=0, toolbar=0, resizable=1');
}

function odkl_logout(cb) {
	ODKL.init({apiId: odkl_client_id});
	ODKL.Auth.logout(cb);
}

function odkl_wall_post_send(owner_id, message, save_invitation) {
	params = {friend_uid: owner_id, text : message};
	ODKL.api(
		'messages.send',
		params,
		function(data) {
			if (data.error && data.error.error_code == 14) {
				alert('=(');
				//odkl_show_captcha_window(owner_id, message, attachment, save_invitation, data.error.captcha_img, data.error.captcha_sid);
			}

			if (data.response) {
				if (save_invitation) {
					top.frames['main_frame'].frames['main'].friend_invited(2, owner_id);
				}
			}
		}
	);
}

function odkl_wall_post(owner_id, message, save_invitation, ret_url) {
	url = "http://www.odnoklassniki.ru/oauth/authorize?client_id=" + odkl_client_id + "&scope=VALUABLE ACCESS;MESSAGING;&response_type=code&redirect_uri=" + ret_url;
	var w = window.open(url, 'odkl_login', 'width=550, height=510, status=0, scrollbars=0, menubar=0, toolbar=0, resizable=1');
	
	/*
	var xmlhttp = getXmlHttp()
	xmlhttp.open('GET', 'user_conf.php?mode=odkl_invite_friend&friend_uid='+owner_id, true);
	xmlhttp.onreadystatechange = function() {
	  if (xmlhttp.readyState == 4) {
		 if(xmlhttp.status == 200) {
			   if (xmlhttp.responseText){
				   top.frames['main_frame'].frames['main'].friend_invited(3, owner_id);
			   }
			 }
	  }
	};
	xmlhttp.send(null);
	*/
}

function odkl_show_captcha_window(owner_id, message, save_invitation, captcha_img, captcha_sid) {
	odkl_capt_container = gebi('odkl_capthca_container');
	if (!odkl_capt_container){
		odkl_capt_container = document.createElement('div');
		document.body.appendChild(odkl_capt_container);
		odkl_capt_container.setAttribute('style', 'position: relative; text-align: center;');
		odkl_capt_container.setAttribute('id', 'odkl_capthca_container');
	}
	
	odkl_capt_container.innerHTML = '<div style="position: fixed; top: 20%; width: 100%;"><div style="background: #cccccc; width: 250px; padding: 20px; margin: 0 auto;"><img src="' + captcha_img + '" /><br /><br /><input id="odkl_captcha_key" type="text" name="captcha_key" /><input id="odkl_captcha_sid" type="hidden" name="captcha_sid" value="' + captcha_sid + '" /><input type="hidden" name="odkl_owner_id" id="odkl_owner_id" value="' + owner_id + '" /><input type="hidden" name="odkl_owner_id" id="odkl_message" value="' + message + '" /><br /><br /><a href="javascript:void(0);" onclick="odkl_resend_wall_post()">GO!</a></div>';
}

function odkl_resend_wall_post() {
	captcha_sid = gebi('odkl_captcha_sid');
	captcha_key = gebi('odkl_captcha_key');
	owner_id = gebi('odkl_owner_id');
	message = gebi('odkl_message');

	odkl_wall_post_send(owner_id.value, message.value, captcha_sid.value, captcha_key.value);
	ODKL.api(
		'wall.post',
		{owner_id: owner_id.value, message: message.value, captcha_sid: captcha_sid.value, captcha_key: captcha_key.value}
	);
	odkl_capt_container = gebi('odkl_capthca_container');
	if (odkl_capt_container) odkl_capt_container.innerHTML = '';
}

function odkl_require_permission(permission, reload, cb, perm_mask) {
	ODKL.init({apiId: odkl_client_id});
	check = 0;
	if (perm_mask) check |= perm_mask;
	if (permission == 'friends') check |= 2;
	ODKL.api('getUserSettings',false, function (data) {
		if (!data.response || !(data.response & check)) {
			odkl_login(reload, cb, check);
		}
	}); 
}