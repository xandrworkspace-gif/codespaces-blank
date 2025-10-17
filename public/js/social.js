_stream_post_locked = false;
_stream_post_stack = [];
_stream_system_keys = {};
_stream_post_stack_timer = false;
_stream_posted_uniqids = [];
function soc_stream_post(systems, title, text, img_url, action_links, uniqid) {
	if (uniqid) {
		if (_stream_posted_uniqids.indexOf(uniqid) != -1)
			return;
		_stream_posted_uniqids.push(uniqid);
	}
	if (false == systems) systems = _stream_post_stack;
	_stream_post_stack = {};
	_stream_system_keys = systems;


	if (systems.length <= 0) {
		if (_stream_post_stack_timer) clearInterval(_stream_post_stack_timer);
		return;
	}

	for (var system_name in systems)
//for (var j = 0; j < systems.length; j++)
	{
		if (!_stream_post_locked) {
			if (window[system_name + '_stream_post'] !== undefined && typeof window[system_name + '_stream_post'] == 'function' ) {
				_stream_post_locked = true;
				window[system_name + '_stream_post'].call(this, title, text, img_url, action_links);
			}
		} else {
			_stream_post_stack[system_name] = systems[system_name];
		}
	}

	_stream_post_stack_timer = setInterval(function(){if (!_stream_post_locked) { clearInterval(_stream_post_stack_timer); soc_stream_post(false, title, text, img_url, action_links); } }, 500)
/*
	post = {};
	if (text) post.text = text;
	if (title) post.title = title;
	if (img_url) post.img_url = img_url;
	if (action_links) post.action_links = action_links;
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
			if (event.status == 'publishSuccess') {
				getUrl(mailru_api_server_url+'user_conf.php?mode=publish_success&soc_system_id=1');
			}
		});
	});*/
}
