function vk_stream_post(title, text, img_url, action_links, system_keys) {
	vk_wall_post(0, text, false, function(){
		_stream_post_locked = false;
	}, action_links);
}

function vk_login_window(reload) {
	vk_login(true);
}

function vk_login(reload, cb, perm_mask) {
	if (!perm_mask) perm_mask |= 2;
	authInfo = reload ? function(response) {
		if (reload && response.session) {
			doPost(window.location.href, 'do_assoc=1&soc_system_auth=2');
		}
	} : cb;
	VK.init({apiId: vk_api_id, nameTransportPath: "xd_receiver.html"});
	VK.Auth.login(authInfo, perm_mask);
}

function vk_logout(cb) {
	VK.init({apiId: vk_api_id, nameTransportPath: "xd_receiver.html"});
	VK.Auth.logout(cb);
}

vk_resend_wall_post = function(){};

function vk_wall_post_send(owner_id, message, mode, cb, captcha_sid, captcha_key) {
	vk_resend_wall_post = function() {
		captcha_sid = gebi('vk_captcha_sid');
		captcha_key = gebi('vk_captcha_key');
		owner_id = gebi('vk_owner_id');
		message = gebi('vk_message');

		vk_wall_post_send(owner_id.value, message.value, mode, cb, captcha_sid.value, captcha_key.value);

		vk_capt_container = gebi('vk_captcha_container');
		if (vk_capt_container) vk_capt_container.innerHTML = '';
	}

	params = {message: message};
	if (typeof vk_post_image != 'undefined' && vk_post_image.length > 0) params.attachment = vk_post_image;
	if (owner_id) params.owner_id = owner_id;
	if (captcha_sid) params.captcha_sid = captcha_sid;
	if (captcha_key) params.captcha_key = captcha_key;
	VK.api(
		'wall.post',
		params,
		function(data) {
			if (cb !== undefined && typeof(cb) == 'function') cb.call(this);

			if (data.error && data.error.error_code == 14) {
				vk_show_captcha_window(owner_id, message, mode, data.error.captcha_img, data.error.captcha_sid);
				return;
			}

			if (data.response) {
				if (mode == 'invitation') {
					top.frames['main_frame'].frames['main'].friend_invited(2, owner_id);
				} else if (mode == 'return') {
					top.frames['main_frame'].frames['main'].friend_returned(2, owner_id);
				} else {
					getUrl(mailru_api_server_url+'user_conf.php?mode=publish_success&soc_system_id=2' + (_stream_system_keys['vk'] != undefined ? '&key=' + _stream_system_keys['vk'] : ''));
				}
			}
		}
	);
}

function vk_wall_post(owner_id, message, mode, cb, act_l) {
	action_links = [];
	for (i in act_l) {
		action_links[i] = {href: act_l[i].href, text: act_l[i].text};
	}
	link = (typeof action_links !== 'undefined') ? action_links.shift() : false;
	if (link) {
		add = (mode!='return') ? '&soc_id=2' : '';
		link.href = link.href.split('#SITEID#').join(vk_soc_site_id);
		message = message.split('#LINK#').join(link.href+add);
	}
	VK.init({apiId: vk_api_id, nameTransportPath: "xd_receiver.html"});
	after_login_check = function () {
		vk_wall_post_send(owner_id, message, mode, cb);
	}

	vk_require_permission(false, false, after_login_check);
}

function vk_show_captcha_window(owner_id, message, mode, captcha_img, captcha_sid) {
	vk_capt_container = gebi('vk_captcha_container');
	if (!vk_capt_container){
		vk_capt_container = document.createElement('div');
		document.body.appendChild(vk_capt_container);
		vk_capt_container.setAttribute('style', 'text-align: center;');
		vk_capt_container.setAttribute('id', 'vk_captcha_container');
	}

	var content = '<div id="id_vk_captcha" style="position: absolute;">';
	content += '<table width="200" border="0" cellspacing="0" cellpadding="0" style="background-color:#FBD4A4;">';
	content += '<tr><td width="14" class="aa-tl"><img src="images/d.gif" width="14" height="24"><br></td>';
	content += '<td class="aa-t" align="center" style="vertical-align:middle"><b>Введите слово на картинке</b></td>';
	content += '<td width="14" class="aa-tr"><img src="images/d.gif" width="14" height="24"><br></td></tr>';
	content += '<tr><td class="aa-l" style="padding:0;"></td><td style="padding:0;">';
	content += '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center">';
	content += '<img src="' + captcha_img + '" alt="" /><br />';
	content += '<input id="vk_captcha_key" type="text" name="captcha_key" />';
	content += '<input id="vk_captcha_sid" type="hidden" name="captcha_sid" value="' + captcha_sid + '" />';
	content += '<input type="hidden" name="vk_owner_id" id="vk_owner_id" value="' + owner_id + '" />';
	content += '<input type="hidden" name="vk_owner_id" id="vk_message" value="' + message + '" /><br />';
	content += '<b class="butt2 pointer"><b><input type="submit" value="Отправить" onClick="vk_resend_wall_post();return false;" /></b></b>';
	content += '</td></tr></table>';
	content += '</td><td class="aa-r" style="padding:0px"></td></tr>';
	content += '<tr><td class="aa-bl"></td><td class="aa-b"><img src="images/d.gif" width="1" height="5"></td><td class="aa-br"></td></tr>';
	content += '</table></div>';
	
	vk_capt_container.innerHTML = content;

	vk_captcha = gebi('id_vk_captcha');

	var x = (document.body.clientWidth - 200)/2;
	var y = (document.body.clientHeight*3/10) + document.body.scrollTop;

	vk_captcha.style.left = x;
	vk_captcha.style.top = y;
	vk_captcha.style.display = 'block';
}

function vk_require_permission(permission, reload, cb, perm_mask) {
	VK.init({apiId: vk_api_id, nameTransportPath: "xd_receiver.html"});
	
	check = 0;
	if (perm_mask) check |= perm_mask;
	if (permission == 'friends') check |= 2;
	
	VK.Auth.getLoginStatus(function(response) {
	if (response.session) {
			VK.api('getUserSettings',false, function (data) {
				if (!data.response || !(data.response & check)) {
					vk_login(reload, cb, check);
				}
			}); 			
		} else {
			vk_login(reload, cb, check);
		}
	});
}