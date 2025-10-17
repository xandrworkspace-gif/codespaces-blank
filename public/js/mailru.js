function mailru_stream_post(title, text, img_url, act_l) {
	action_links = [];
	for (i in act_l) {
		action_links[i] = {href: act_l[i].href+'&soc_id=1', text: act_l[i].text};
	}
	post = {};
	if (text) {
		post.text = text;
		post.text= post.text.split('#LINK#').join('');
	}
	if (title) post.title = title;
	if (img_url) post.img_url = img_url;
	if (action_links) {
		for (i in action_links) {
			action_links[i].href = action_links[i].href.split('#SITEID#').join(mailru_soc_site_id)
		}
		post.action_links = action_links;
	}

	mailru.loader.require('api', function() {
		mailru.connect.init(mailru_api_id, mailru_api_private);

		mailru.events.listen(mailru.connect.events.login, function(session){
			if (mairu_logged_in) {
				return false;
			}
			mairu_logged_in = true;
			mailru.common.stream.post(post);
		});
		mailru.connect.getLoginStatus(function(result) {
			if (result.is_app_user != 1) {
				return mailru_login_window();
			} else {
				mailru.common.stream.post(post);
			}
		});
		mailru.events.listen(mailru.common.events.streamPublish, function(event) {
			// закрытие диалога
			// event.status (варианты: authError/closed/publishSuccess/publishFail/opened)
			_stream_post_locked = false;
			if (event.status == 'publishSuccess') {
				getUrl(mailru_api_server_url+'user_conf.php?mode=publish_success&soc_system_id=1' + (_stream_system_keys['mailru'] != undefined ? '&key=' + _stream_system_keys['mailru'] : ''));
			}
		});
	});
}

function mailru_guestbook_post(uid, title, text, img_url, action_links, mode) {
	if (!text) return false;
    mailru.loader.require('api', function() {
	    mailru.connect.init(mailru_api_id, mailru_api_private);
	    post = {'text': text}
	    if (uid) post.uid = uid;
	    if (title) post.title = title;
	    if (img_url) post.img_url = img_url;
	    if (action_links) post.action_links = action_links;
	    mailru.events.listen(mailru.connect.events.login, function(session){
	    	if (mairu_logged_in) {
	    		return false;
	    	}
	    	mairu_logged_in = true;
	    	mailru.common.guestbook.post(post);
	    });
	    mailru.connect.getLoginStatus(function(result) {
		    if (result.is_app_user != 1) {
		    	return mailru_login_window();
		    } else {
		    	mailru.common.guestbook.post(post);
		    }
    	});
	    if (typeof(last_listener) == 'number') {
	    	mailru.events.remove(last_listener);
	    }
	    last_listener = mailru.events.listen(mailru.common.events.guestbookPublish, function(event) {
	      // закрытие диалога
	      // event.status (варианты: authError/closed/publishSuccess/publishFail/opened)
		  if (event.status == 'publishSuccess') {
			if (mode == 'invitation') {
				top.frames['main_frame'].frames['main'].friend_invited(1, uid);
			}
			if (mode == 'return') {
				top.frames['main_frame'].frames['main'].friend_returned(1, uid);
			}
		  }
	    });
    });
}

function mailru_require_permission(permission, widget_text) {
	if (!permission) return false;
	mailru.loader.require('api', function() {
	    mailru.connect.init(mailru_api_id, mailru_api_private);
	    mailru.events.listen(mailru.common.events.permissionsChange, function(event) {
	    	if ((event.status == 'success' || event.status == 'already') && widget_text) {
	    		getUrl(mailru_api_server_url+'user_conf.php?mode=widget_post&soc_system_id=1&text='+widget_text);
	    	} else {
	    		// TODO: если человек отказался, то можно вероятно этот факт где-то сохранить
	    		// если часто будет вылетать - можно будет подумать
	    	}
        });
	    mailru.common.users.requirePermission(permission);
    });
}

function mailru_login_window(reload) {
	mailru.loader.require('api', function() {
		mailru.connect.init(mailru_api_id, mailru_api_private);
		mailru.events.listen(mailru.connect.events.login, function(){
			mailru.connect.getLoginStatus(function(session, mrc){
				setCookie('mrc', mrc);
				doPost(window.location.href, 'do_assoc=1&soc_system_auth=1');			
			});
		});

		mailru.connect.login();
	});
	return false;
}