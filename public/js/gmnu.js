function gmnu_brd(obj) {
    var c = obj.insertRow(-1).insertCell(-1);
    c.colSpan = 2;
    c.height = 2;
    c.style.padding = '0px';
    c.style.border = '#600 1px solid';
    c.innerHTML = '<img src="images/cell-horiz-brd.gif" width="100%" height="2"><br>';
}

function gmnu(obj, e, mitems, params) {
    e = e || window.event;
    obj = obj || false;
    params = params || {};
    var doc = params.window ? window.document : _top().document;

    var oldMenu = doc.getElementById('menutablediv');
    if (oldMenu) {
        var r = hasClass(oldMenu, 'help_menu') && obj == $(oldMenu).data('obj') && !params.show;
        oldMenu.destroy();
        if (r) return;
    }

    if (params.hide) return;

    var mobj = doc.createElement('ul');
    mobj.className = 'common-menu__list';
    var timer;

    if (typeof clipboardNick == 'undefined') clipboardNick = '';
    mobj.clipboardNick = clipboardNick;

    var focus;

    var mdivobj = document.createElement('div');
    mdivobj.id = 'menutablediv';
    mdivobj.className = 'common-menu' + (params.className ? ' ' + params.className : '');
    mdivobj.style.position = params.position || 'absolute';
    mdivobj.style.width = params.wide ? '300px' : '175px';
    mdivobj.style.maxWidth = params.wide ? '300px' : '175px';
    mdivobj.style.overflowY = 'auto';
    mdivobj.style.overflowX = 'hidden';

    mdivobj.destroy = function() {
        try { // mega hack for IE9
            if (this.parentNode) {
                var input = doc.getElementById('hackInput');

                if (input) {
                    this.parentNode.removeChild(input);
                }

                this.parentNode.removeChild(this);
            }
        } catch(e) {}
    };

    for (var i = 0; i < mitems.length; i++) {
        if (!mitems[i].txt) continue;

        var li = document.createElement('li');
        li.className = 'common-menu__item';

        if (mitems[i].head) {
            li.className += ' common-menu__item_title';
        }

        var el;

        if (mitems[i].href) {
            el = document.createElement('a');
            el.href = mitems[i].href;
            el.className = 'common-menu__item-link';
            el.target = '_blank';
            el.style.background = mitems[i].picture ? 'url(' + mitems[i].picture + ') 3px 50% no-repeat' : 'url(images/cell-arr-reg.gif) 5px 50% no-repeat';
            el.innerHTML = mitems[i].txt;
        } else if (mitems[i].input) {
            el = document.createElement('div');
            el.className = 'common-menu__item-input';
            var label = document.createElement('label');
            label.id = 'label_' + mitems[i].name;
            label.className = 'common-menu__item-input-label';
            label.innerHTML = mitems[i].txt;
            label.htmlFor =  mitems[i].name;
            if (mitems[i].value) {
                label.style.display = 'none';
            }
            el.appendChild(label);
            var input = document.createElement('input');
            input.className = 'common-menu__item-input-field';
            input.type = 'text';
            input.value = mitems[i].value || '';
            input.name = mitems[i].name;
            input.id = mitems[i].name;
            $(input)
                .on('focus', function() {
                    if ($(this).val().length) label.style.display = 'none';
                    else label.style.display = 'block';
                    clearTimeout(timer);
                })
                .on('blur', function() {
                    if (!$(this).val()) {
                        label.style.display = 'block';
                    }
                    if (!params.keep) onBlurHack();
                })
                .on('keyup', function() {
                    if ($(this).val().length) label.style.display = 'none';
                    else label.style.display = 'block';
                })
            ;
            if (mitems[i].keyup) {
                input.onkeyup = mitems[i].keyup;
            }
            el.appendChild(input);
            if (!focus) focus = input;
            var btn = document.createElement('img');
            btn.className = 'common-menu__item-input-clear';
            btn.src = 'images/common-menu-input-clear.png';
            btn.title = _top().TRANSLATE && _top().TRANSLATE.reset_close ? _top().TRANSLATE.reset_close : '';
            var clear = mitems[i].clear;
            $(btn).on('click', function() {
                if (input.value) {
                    clear();
                    input.value = '';
                    input.focus();
                } else {
                    mdivobj.destroy();
                }
            });
            el.appendChild(btn);
        } else if (mitems[i].head) {
            li.innerHTML = mitems[i].txt;
        } else {
            el = document.createElement('span');

            if (mitems[i].is_copy) {
                clipboardSetText(mobj.clipboardNick);
                /*
                el = window.top.document.getElementById('zeroclipboard-hack-container');
                el.style.position = 'static';
                el.style.width = 'auto';
                el.style.height = 'auto';
                el.setAttribute('data-clipboard-text', mobj.clipboardNick);
                 */
            }

            el.style.background = mitems[i].picture ? 'url(' + mitems[i].picture + ') 3px 50% no-repeat' : 'url(images/cell-arr-reg.gif) 5px 50% no-repeat';
            el.className = 'common-menu__item-link';
            el.innerHTML = mitems[i].txt;
        }
        if (mitems[i].style) {
            var styleString = el.getAttribute('style');
            if (styleString == null) styleString = '';

            el.setAttribute('style',styleString + mitems[i].style);
        }

        if (el && mitems[i].click) {
            if(typeof mitems[i].click == "function") {
                el.onclick = mitems[i].click;
            }else if(typeof mitems[i].click == "string") {
                el.setAttribute("onclick", mitems[i].click);
            }
        }

        if (el) li.appendChild(el);
        if (!mitems[i].head)

            $(li).hover(function() {
                $(this).addClass('common-menu__item_hover');
            }, function() {
                $(this).removeClass('common-menu__item_hover');
            });

        mobj.appendChild(li);
    }

    var mdivobj = document.createElement('div');
    if (!obj && params.className) mdivobj.className = params.className;

    mdivobj.id = 'menutablediv';
    mdivobj.className = 'common-menu' + (params.className ? ' ' + params.className : '');
    mdivobj.style.position = 'absolute';
    mdivobj.style.width = params.wide ? '300px' : '175px';
    mdivobj.style.maxWidth = params.wide ? '300px' : '175px';
    mdivobj.style.overflowY = 'auto';
    mdivobj.style.overflowX = 'hidden';
    mdivobj.style.left = '0px';
    mdivobj.style.top = '0px';

    mdivobj.destroy = function() {
        try { // mega hack for IE9
            if (this.parentNode) {
                var input = doc.getElementById('hackInput');

                if (input) {
                    this.parentNode.removeChild(input);
                }

                // remove zeroclip back to <body>

                if (window.top.document.getElementById('zeroclipboard-hack-container')) {
                    var zclip = window.top.document.getElementById('zeroclipboard-hack-container');
                    zclip.style.position = 'absolute';
                    zclip.style.width = '0';
                    zclip.style.height = '0';
                    zclip.innerHTML = '';

                    window.top.document.body.appendChild(zclip);
                }

                // ------

                this.parentNode.removeChild(this);
            }
        } catch(e) {}
    }

    doc.body.appendChild(mdivobj); // div
    mdivobj.appendChild(mobj); // ul // li
    mdivobj.oncontextmenu = function() { return false; };

    $(mdivobj).data('obj', obj);

    //Высота и положение контекстного меню
    //--------------------------------------------
    var content_block = gebi("body_content") || gebi("top_mnu");
    if (content_block) {
        var win = window;
        var o = obj;
        var frameOffset = {
            l: 0,
            t: 0
        };
        var elemOffset = {
            l: 0,
            t: 0
        };

        if (obj && params.align) {
            do {
                elemOffset.l += o.offsetLeft;
                elemOffset.t += o.offsetTop;
            } while (o = o.offsetParent);
            var scrollTop = window.pageYOffset || (document.documentElement && document.documentElement.scrollTop) || (document.body && document.body.scrollTop);
            var left = frameOffset.l + elemOffset.l + obj.offsetWidth;
            var top = frameOffset.t + elemOffset.t - scrollTop;
            mdivobj.style.left = left + (params.offsetLeft || 0) - mdivobj.offsetWidth + 'px';
            mdivobj.style.top = top + (params.offsetTop || 0) + 'px';
        } else if (obj) {
            if (!params.window) {
                do {
                    if (win.frameElement) {
                        var elem = win.frameElement;

                        do {
                            frameOffset.l += elem.offsetLeft;
                            frameOffset.t += elem.offsetTop;
                        } while (elem = elem.offsetParent);
                    }

                    if (win.parent == window.top) break;
                } while (win = win.parent);
            }
            do {
                elemOffset.l += o.offsetLeft;
                elemOffset.t += o.offsetTop;
            } while (o = o.offsetParent);

            var scrollTop = window.pageYOffset || (document.documentElement && document.documentElement.scrollTop) || (document.body && document.body.scrollTop);
            var left = frameOffset.l + event.pageX;
            var top = frameOffset.t + elemOffset.t - scrollTop;

            if (top + mdivobj.offsetHeight > doc.body.offsetHeight) {
                top -= mdivobj.offsetHeight;
            }

            if (left + mdivobj.offsetWidth + 10 > doc.body.offsetWidth) {
                left -= mdivobj.offsetWidth;
            }
            mdivobj.style.left = left + (params.offsetLeft || 5) + 'px';
            mdivobj.style.top = top + (params.offsetTop || 5) + 'px';
        } else {
            mdivobj.style.left = (params.left || 0) + 'px';
            mdivobj.style.top = (params.top || 0) + 'px';
        }
    }
    //--------------------------------------------

    if (focus) focus.focus();

    function onBlurHack() {
        timer = setTimeout(function() {
            mdivobj.destroy();
            $(obj).removeClass('selected');
        }, 150);
    }

    if (!params.keep) {
        // грязный хак (мы не можем повесить click на document из-за фреймов)
        var hackInput = doc.createElement('input');
        hackInput.id = 'hackInput';
        hackInput.type = 'text';
        hackInput.value = '';
        hackInput.style.cssText = 'position: absolute; top: -9999px; left: -9999px;';
        hackInput.onblur = onBlurHack;

        doc.body.appendChild(hackInput);
        hackInput.focus();
        //--------------------------------------------
    }

    return mdivobj;
}