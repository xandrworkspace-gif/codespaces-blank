function insertAtCaret(element, text) {
    if (document.selection) {
        element.focus();
        var sel = document.selection.createRange();
        text = text.split('%text%').join(sel.text); // заменяем %text% на выделенный текст
        sel.text = text;
        element.focus();
    } else if (element.selectionStart || element.selectionStart === 0) {
        var startPos = element.selectionStart;
        var endPos = element.selectionEnd;
        var scrollTop = element.scrollTop;
        var sel_text=element.value.substring(startPos, endPos);
        text = text.split('%text%').join(sel_text); // заменяем %text% на выделенный текст
        element.value = element.value.substring(0, startPos) + text + element.value.substring(endPos, element.value.length);
        element.focus();
        element.selectionStart = startPos + text.length;
        element.selectionEnd = startPos + text.length;
        element.scrollTop = scrollTop;
    } else {
        element.value += text;
        element.focus();
    }
}

(function($) {jQuery.fn.html_editor = function(_settings){
	
	 //Defaults
	 var settings = {
	 	counter: false,
	 	preview: false,
		img: false
	 };
	 $.extend(settings, _settings);
	
	var buttons = {
		'<STRONG>B</STRONG>':'<STRONG>%text%</STRONG>',
		'<I>I</I>':'<I>%text%</I>',
		'<U>U</U>':'<U>%text%</U>',
		'Добавить ссылку':'<A href="" target="_blank">%text%</A>',
		'Добавить картинку': '<img src="%text%" alt="" />'
	};

	return this.each(function(){
		var t=$(this);
		var panel=$('<div class="html_editor_panel"></div>').insertBefore(t);	
		
		
		$.each(buttons, function(key, value){						
			if (key != 'Добавить картинку') {
				$("<button type='button'>"+key+"</button>").appendTo(panel).click(function(){
					insertAtCaret(t.get(0), value);	
					t.change();
				});
			} else {
				if (settings.img) {
					$("<button type='button'>"+key+"</button>").appendTo(panel).click(function(){
						insertAtCaret(t.get(0), value);	
						t.change();
					});
				}
			}
		});
		
		
		$("<select>"+
			"<option value=''>Цвет текста</option>"+
			"<option style='background-color: red;' value='red'>			</option>"+
			"<option style='background-color: darkred;' value='darkred'>			</option>"+
			"<option style='background-color: indigo;' value='indigo'>			</option>"+
			"<option style='background-color: blue;' value='blue'>			</option>"+
			"<option style='background-color: darkblue;' value='darkblue'>			</option>"+
			"<option style='background-color: orange;' value='orange'>			</option>"+
			"<option style='background-color: green;' value='green'>			</option>"+
			"<option style='background-color: olive;' value='olive'>			</option>"+
			"<option style='background-color: #339900;' value='#339900'>			</option>"+
			"<option style='background-color: yellow;' value='yellow'>			</option>"+
			"<option style='background-color: black;' value='black'>			</option>"+
			"<option style='background-color: violet;' value='violet'>			</option>"+
			"<option style='background-color: cyan;' value='cyan'>			</option>"+
			"<option style='background-color: brown;' value='brown'>			</option>"+
			"<option style='background-color: #808080;' value='#808080'>			</option>"+
		"</select>").appendTo(panel).change(function(){
			if (!$(this).val()) return;
			insertAtCaret(t.get(0), '<SPAN style="color:'+$(this).val()+'">%text%</SPAN>');
			t.change();
			$(this).val('');
		});
		
		
		if (settings.preview){
			this.preview = $("<div class='preview' style='background-color: #FFE4AA; background-image: url(\"../images/lgb.gif\"); background-repeat: repeat; font-family: Tahoma; font-size: 11px; width: 517px; word-wrap: break-word;'></div>").insertAfter(t);
		}
		
		if (settings.counter){
			this.counter = $("<div class='counter'></div>").insertAfter(t);
		}
		
		function update_result(){
			if (this.preview) this.preview.html($(this).val().replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br />$2'));
			
			if (this.counter){
				var s = $(this).val();
				this.counter.html("Символов: "+s.length+" Сообщений: "+Math.ceil(s.length/200));
			}
		}
		
		t.keyup(update_result).change(update_result).change();
				
	});
};})(jQuery);