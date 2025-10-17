function fb_stream_post (title, text, img_url, act_l) {
	if (typeof text == 'undefined' || text == '' ) return;
	action_links = [];
	for (i in act_l) {
		action_links[i] = {href: act_l[i].href, text: act_l[i].text};
	}
	link = (typeof action_links !== 'undefined') ? action_links.shift() : false;
	if (link) {
		link.href = link.href.split('#SITEID#').join(fb_soc_site_id);
		message = text.split('#LINK#').join('');
	}
	var param = {
		 caption:title,
		 description: message,
		 name : link.text,
		 link : link.href,
		 picture:img_url,
		 actions:{ name:'dwar.ru', link:link.href }
	} ;

	fb_wall_post(param,  function(response) {
		if (!response || response.error) {}
		_stream_post_locked = false;
	});
}

function fb_publish_succes(){
	getUrl(mailru_api_server_url+'user_conf.php?mode=publish_success&soc_system_id=4');
}

function fb_login_window(reload) {
	FB.init({
            appId:fb_api_id, cookie:true,
            status:true, xfbml:false
     });
	FB.login(function(response) {
		 if (response.authResponse) {
			 doPost(window.location.href, 'do_assoc=1&soc_system_auth=4');	
		}

	}, {scope:'email, read_friendlists, publish_stream'});
	return false;
}


function fb_wall_post(param, callback){
	FB.init({appId:fb_api_id, cookie:true, status:true, xfbml:false});
	param.method = 'feed';
	resp = function(response) {
		if (response && response.post_id) {
			fb_publish_succes();
		} else {}
		callback(response);
	}
	FB.ui(param, resp);
	return true;
}

function fb_invite_post(uid, title, message, link, img_url,  callback){
	FB.init({appId:fb_api_id, cookie:true, status:true, xfbml:false});
	var param = {
		 method :'feed',
		 to:uid,
		 caption:title,
		 description: message,
		 name : link.text,
		 link : link.href,
		 picture:img_url,
		 actions:{ name:'dwar.ru', link:link.href }
	} ;

	resp = function(response) {
		if (response && response.post_id) { 
			fb_publish_succes();
		} else { }
		callback(response);
	}
	FB.ui(param, resp);
	return true;
}

function fb_require_permission(reload) {
	FB.init({
            appId:fb_api_id, cookie:true,
            status:true, xfbml:false
   });

   FB.getLoginStatus(function(response) {
	  if (response.status === 'connected') {
		  getUrl(reload);
	  } else {
		FB.login(function(response) {
			if (response.session) {
				 getUrl(reload);
			}
		}, {scope:'email, read_friendlists, publish_stream'});
	  }
	});
}
