function subclass(fn, obj ) {
	var newFn = function( ) { 
		if (this.init) {
			this.init.apply(this, arguments);
		}
	} 
	if (fn) {
		newFn.prototype = new fn();
		newFn.prototype._base = fn.prototype;
	}
	if (obj) {
		for (var i in obj) {
			newFn.prototype[i] = obj[i];
		}
	}
	return newFn;
}

var table = {
	smooth:  function(t) {
		var a = Array();
		for(var i in t) {
			a.push(t[i]);
		}
		return a;
	},
	keys: function(t) {
		var a = Array();
		for(var i in t) {
			a.push(i);
		}
		return a;
	}, 
	slice: function(t, keys) {
		var lt = {};
		for(var i in keys) {
			lt[ keys[i] ] = t[ keys[i] ];
		}
		return lt;
	},
	clone: function(t, deep) {
		return (typeof(deep) != 'undefined') ? $.extend(true, {}, t) : $.extend({}, t);
	},
	eq: function(t1, t2) {
		var o1 = typeof t1;
		var o2 = typeof t2;
		if (o1 != o2) {	return false; }
		if (o1 != 'object') { return t1 === t2; }
		var cnt1 = 0;
		var cnt2 = 0;
		for(var i in t1) {
			if (!table.eq(t1[i], t2[i])) { return false; }
			++cnt1;
		}
		for(var i in t2) {
			++cnt2;
		}
		return cnt1 == cnt2;
	},
	search: function(t, v) {
		for(var k in t) {
			if (t[k] === v) return k;
		}
		return null;
	},
	list: function(t, filterFn, sortFn) {
		var list = Array();
		for(var i in t) {
			if (filterFn(t[i])) {
				list.push(t[i]);
			}
		}
		if (sortFn) {
			list.sort(sortFn);
		}
		return list;
	},
	length: function(t) {
		var count = 0;
		for(var i in t) { count++; }
		return count;
	}
}


function tutorialShow(step){
	
	fogClose();
	$(".user-tutorial").remove();
	
	if (typeof step == "undefined") step = current_tutorial;
	var step_data = Tutorial_data[step];
	
	if (!(step_data.flags & 0x2)){
		fogShow(step_data);
	}
	var click_html = 'tutorialClose();';
	if (step_data.flags & 0x1){
		try{
            if(Tutorial_data[step + 1].code == step_data.code){
                var next_step = step + 1;
                click_html = 'showNext('+next_step+');';
            }
		}catch(e){}
	} 

	console.log(Object.keys(Tutorial_data).length);
    console.log(step);
	if (Object.keys(Tutorial_data).length == step) {
		click_html = 'tutorialEndBtn();';
	}

	var tutorial_window = $('<div class="user-tutorial" style="height: 180px">\n\
		<div class ="tutorial" style ="height: 150px; width: 300px; position:relative;">\n\
			  <div class="tutorial-pers" style="position:absolute;left:-110px;right:0;width:125px">\n\
				  <img src="images/tutorial/bolvanka.png" alt="" title="" />\n\
				  <span>&nbsp;</span>\n\
			  </div>\n\
				<div>\n\
			  <table cellpadding="0" cellspacing="0" width ="100%" height ="100%">\n\
				  <tr>\n\
					  <td class="top-left"></td>\n\
					  <td class="top">&nbsp;</td>\n\
					  <td class="top-right"></td>\n\
				  </tr>\n\
				  <tr>\n\
					  <td class="left"></td>\n\
					  <td class="center">\n\
						 '+step_data.description+'<br><br>\n\
							<b class="butt2 pointer" style = "float:right;"><b><input type="button" style="width:55px;" onclick="'+click_html+'" value="Далее"></b></b>\n\
							<br><br><a href="#" onclick="tutorialEndBtn();">Пропустить</a>\n\
					  </td>\n\
					  <td class="right"></td>\n\
				  </tr>\n\
				  <tr>\n\
					  <td class="bottom-left"></td>\n\
					  <td class="bottom">&nbsp;</td>\n\
					  <td class="bottom-right"></td>\n\
				  </tr>\n\
			  </table>\n\
		  </div>\n\
	<div>\n\
	</div>\n\
');
	
	switch (step_data.arrow_type) {
	case '1' :
		var arrow =  $('<td valign="middle" rowspan="3" width="29"><span class="arrow-right">&nbsp;</span></td>');
		tutorial_window.find('table').find('tr').first().append(arrow);

		break;		
	case '2' :
		var arrow =  $('<tr>\n\
							<td height="29" align="center" colspan="3"><span class="arrow-top">&nbsp;</span></td>\n\
						</tr>');
		tutorial_window.find('table').prepend(arrow);
		
		tutorial_window.find('.tutorial-pers').css({'top': 20});
		break;
	case '3' :
		var arrow =  $('<tr>\n\
				  <td height="29" align="center" colspan="3"><span class="arrow-bottom">&nbsp;</span></td>\n\
			  </tr>');
		tutorial_window.find('table').append(arrow);

		break;		
	}

	switch (step_data.coord_type) {
	case '1' :
		tutorial_window.css({
								'left': parseInt(step_data.window_x),
								'top': parseInt(step_data.window_y)
							});
		break;
	case '2' :
		tutorial_window.css({
								'bottom': parseInt(step_data.window_x),
								'right': parseInt(step_data.window_y)
							});
		break;				
	case '3' :
		tutorial_window.css({
				'left': '50%',
				'top': '50%',
				'margin-left': parseInt(step_data.window_x),
				'margin-top': parseInt(step_data.window_y)											
		});
		break;
	case '4' :
			var selector = step_data.selector;
			var element = $(top.frames['main_frame'].gebi(selector));
			if (!element.length){
				element = $(top.frames['main_frame'].frames['main'].gebi(selector));
			}
			if (!element.length){
				return false;
			}
			var element_offset_x = element.offset().left;
			var element_offset_y = element.offset().top;
			var element_width = element.width();
			var element_height = element.height();
			
			var window_x = element_offset_x + element_width/2 + parseInt(step_data.window_x);	
			var window_y = element_offset_y + element_height/2 + parseInt(step_data.window_y);
			
			tutorial_window.css({
										'left': window_x,
										'top': window_y
									});			
			
			$(top).bind('resize', function() {
				var selector = step_data.selector;
				var element = $(top.frames['main_frame'].gebi(selector));
				if (!element.length){
					element = $(top.frames['main_frame'].frames['main'].gebi(selector));
				}
				if (!element.length){
					return false;
				}
				var element_offset_x = element.offset().left;
				var element_offset_y = element.offset().top;
				var element_width = element.width();
				var element_height = element.height();

				var window_x = element_offset_x + element_width/2 + parseInt(step_data.window_x);	
				var window_y = element_offset_y + element_height/2 + parseInt(step_data.window_y);

					tutorial_window.css({
												'left': window_x,
												'top': window_y,
												'z-index' : 2300
											});	
				});		
				
		break;
	}

						
	tutorial_window.appendTo('body');
	
}

function fogShow(step_data){
	var fogTop = $('<div class="window-fog transparent">&nbsp;</div>');
	var fogBottom = $('<div class="window-fog transparent">&nbsp;</div>');
	var fogLeft = $('<div class="window-fog transparent">&nbsp;</div>');
	var fogRight = $('<div class="window-fog transparent">&nbsp;</div>');	
	var noFog = $('<div class="window-nofog">&nbsp;</div>');	
	
	var noFog_x = parseInt(step_data.click_x);
	var noFog_y = parseInt(step_data.click_y);
	var noFog_width = parseInt(step_data.click_width);
	var noFog_height = parseInt(step_data.click_height);
	
	if (step_data.coord_type == 4) {
		var selector = step_data.selector;
		var element = $(top.frames['main_frame'].gebi(selector));
		if (!element.length){
			element = $(top.frames['main_frame'].frames['main'].gebi(selector));
		}
		if (!element.length){
			return false;
		}
		var element_offset_x = element.offset().left;
		var element_offset_y = element.offset().top;
		var element_width = element.width();
		var element_height = element.height();

		var noFog_x = element_offset_x + element_width/2 + parseInt(step_data.click_x);	
		var noFog_y = element_offset_y + element_height/2 + parseInt(step_data.click_y);		
		
	}
	fogTop.css({
			'left': 0,
			'top': 0,
			'width': '100%',
			'height': noFog_y
				});	
	fogBottom.css({
			'left': 0,
			'top': noFog_y + noFog_height,
			'width': '100%',
			'height': '100%'
				});	
	fogLeft.css({
			'left': 0,
			'top': noFog_y,
			'width': noFog_x,
			'height': noFog_height
				});					
	fogRight.css({
			'left': noFog_x + noFog_width,
			'top': noFog_y,
			'width': '100%',
			'height':  noFog_height
				});	
	noFog.css({
			'left': noFog_x,
			'top': noFog_y,
			'width': noFog_width,
			'height':  noFog_height		
		
	});			
	
	fogTop.appendTo('body');
	fogBottom.appendTo('body');	
	if (noFog_width!=0 && noFog_height!=0){
		fogLeft.appendTo('body');
		fogRight.appendTo('body');
		noFog.appendTo('body');
	}
	
	if (step_data.coord_type == 4) {
			$(top).bind('resize', function() {
			var selector = step_data.selector;
			var element = $(top.frames['main_frame'].gebi(selector));
			if (!element.length){
				element = $(top.frames['main_frame'].frames['main'].gebi(selector));
			}
			if (!element.length){
				return false;
			}
			var element_offset_x = element.offset().left;
			var element_offset_y = element.offset().top;
			var element_width = element.width();
			var element_height = element.height();

			var noFog_x = element_offset_x + element_width/2 + parseInt(step_data.click_x);	
			var noFog_y = element_offset_y + element_height/2 + parseInt(step_data.click_y);	

				fogTop.css({
						'left': 0,
						'top': 0,
						'width': '100%',
						'height': noFog_y
							});	
				fogBottom.css({
						'left': 0,
						'top': noFog_y + noFog_height,
						'width': '100%',
						'height': '100%'
							});	
				fogLeft.css({
						'left': 0,
						'top': noFog_y,
						'width': noFog_x,
						'height': noFog_height
							});					
				fogRight.css({
						'left': noFog_x + noFog_width,
						'top': noFog_y,
						'width': '100%',
						'height':  noFog_height
							});	
				noFog.css({
						'left': noFog_x,
						'top': noFog_y,
						'width': noFog_width,
						'height':  noFog_height		

				});								
			});			
	}
	
	// Tab key disable
	
	$(top.frames['main_frame'].frames['main'].document).bind('keydown', function(e) {
		 if(e.keyCode==9)
		 {
			e.preventDefault();
		 }
	});

}

function fogClose(){
	$(".window-fog").remove();
	$(".window-nofog").remove();
	$(top.frames['main_frame'].frames['main'].document).unbind('keydown');	
}

function tutorialClose(){
	fogClose();
	$(".user-tutorial").remove();
	incrementStep();
}

function showNext(step){
	tutorialClose();
	tutorialShow(step);
}

function incrementStep(){
	$.ajax({
		url: 'tutorial_ajax.php',
		data: 'action=next_step'
	});
}
function tutorialEnd(){
	$.ajax({
		url: 'tutorial_ajax.php',
		data: 'action=end'
	});
}

function tutorialEndBtn() {
	fogClose();
	$(".user-tutorial").remove();	
	tutorialEnd();
}
