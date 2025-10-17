CHAT.locale_timezone = 0;


function chat_timezone_init() {
    if (!document.chat_clock || !document.chat_clock.time_shift) {
setTimeout(chat_timezone_init, 1000);
return false;
}
CHAT.timezone_diff = - new Date().getTimezoneOffset() * 60 - session.time_offset;
var diff = CHAT.locale_timezone ? CHAT.timezone_diff : 0;
for (var i in chatOpts) {
    chat_timestamp_process(chatOpts[i].data);
}
if (diff) chat_clock_time_shift(diff);
else chat_clock_time_shift(-time_shifted);
return true;
}

function chat_change_timezone() {
    if (CHAT.timezone_diff == 0) return false;
    var $ctz = $('#chat_popup_change_timezone');
    if (!$ctz.length) return false;
    var times = $ctz.find('.server_time, .locale_time');
    times.removeClass('selected');
    times.filter(CHAT.locale_timezone ? '.locale_time' : '.server_time').addClass('selected');
    var server_time = current_server_time();
    if (!server_time) return false;
    times.filter('.server_time').find('.time').html('('+dateformat(server_time, false, false)+')');
    times.filter('.locale_time').find('.time').html('('+dateformat(server_time, false, true)+')');
    popupDialog($ctz.html(), CHAT.str.change_timezone);
    return true;
};

var chat_change_time_zone = chat_change_timezone;

function chat_change_timezone_confirm(locale) {
    if (CHAT.locale_timezone != locale) {
        $.ajax({
            url: '/pub/cht_data_save.php',
            cache: false,
            type: 'POST',
            data: 'action=locale_timezone&locale=' + (locale ? 1 : 0),
            success: function(data) {},
            error: function() {}
        });
    }
    CHAT.locale_timezone = locale ? 1 : 0;
    chat_timezone_init();
    _top().popupDialogObj.close();
};

var chat_user_list = $chat_user_list = null;

function chat_user_list_init() {
    chat_user_list = {};
    $chat_user_list = $('#chat_user_list');
    $chat_user_list.find(' > li').each(function (i, el) {
        var $el = $(el);
        var type = $el.data('type');
        chat_user_list[type] = $el;
    }).click(function (e) {
        var $el = $(this);
        if (!$el.hasClass('selected')) {
            $el.parent().find('> li.selected').removeClass('selected');
            $el.addClass('selected');
        }
        if ($el.data('type') == 'party') {
            chatActivateParty();
        } else {
            chatActivateNonparty();
        }
        var tab = $('#chat_user_list').find('li.selected').data('type');
        var default_filter = {field: 'nick', order: 'asc'};
        if (tab == 'clan') default_filter.field = 'clanid';
        var sortChatUsers = $.jStorage.get('sortChatUsers_' + tab, default_filter);
        userListSortFilter.sortField = sortChatUsers.field;
        userListSortFilter.sortOrder = sortChatUsers.order;
    });
};

function chat_browser_init() {
    var rsz = null;
    var gecko = navigator.userAgent.match(/Gecko/i);
    var opera = navigator.userAgent.match(/Opera/i);
    if (opera || gecko) {
        gebi('if1').scrolling = 'auto';
        gebi('if2').scrolling = 'auto';
        gebi('user_list').scrolling = 'auto';
        var rsz = function (a) {
            gebi('if1').style.height = '10px';
            gebi('if2').style.height = '10px';
            gebi('user_list').style.height = '10px';
            gebi('t1').style.height = '99%';
            var h = document.body.offsetHeight - 98;
            h = h < 46 ? 46 : h;
            gebi('if1').style.height = (h + 18) + 'px';
            gebi('if2').style.height = (h + 18) + 'px';
            gebi('user_list').style.height = h + 'px';
            gebi('t1').style.height = '100%';
        };
    } else {
        var rsz = function (a) {
            gebi('if1').style.height = '1px';
            gebi('if2').style.height = '1px';
            gebi('user_list').style.height = '1px';

            gebi('if1').style.height = '100%';
            gebi('if2').style.height = '100%';
            gebi('user_list').style.height = '100%';
        };
    }
    onresize = rsz;
    setTimeout(onresize, 3000);
};

function chat_message_focus() {
    var $message = $('#message');
    if ($message.val() == $message.data('dummy-text')) {
        $message.removeClass('dummy').val('');
    }
};

function chat_message_blur() {
    var $message = $('#message');
    if (!$.trim($message.val()).length) {
        $message.addClass('dummy').val($message.data('dummy-text'));
    }
};

function chat_smiles_emo_toggle() {
    if (CHAT.smiles.emo_on) {
        CHAT.smiles.emo_on = false;
        chat_load_smiles(CHAT.smiles.current_page);
    } else {
        CHAT.smiles.emo_on = true;
        chat_load_emos(0);
    }
    return false;
};

function chat_smiles_emo_reset() {
    CHAT.smiles.by_page = {};
    CHAT.smiles.emos_by_page = {};
    CHAT.smiles.current_page = 0;
};

function chat_smiles_settings_toggle() {
    if (CHAT.smiles.settings_on) {
        CHAT.smiles.settings_on = false;
        CHAT.smiles.by_page = {};
        CHAT.smiles.current_page = 0;
        chat_load_smiles(0);
    } else {
        CHAT.smiles.settings_on = true;
        CHAT.smiles.by_page = {};
        chat_load_smiles(0);
    }
    return false;
};

function chat_smile_favorite(el) {
    var $el = $(el);
    var id = $el.data('id');
    if ($el.hasClass('favorite')) {
        $el.removeClass('favorite');
    } else {
        $el.addClass('favorite');
    }
    $.ajax({
        url: '/pub/cht_data_save.php',
        cache: false,
        type: 'POST',
        data: 'action=favorite_smiles&id=' + id,
        success: function (data) {
        },
        error: function () {
        }
    });
};

function cht_btn_select(btn) {
    $('.cht_buttons_state').removeClass('selected').filter('#cht_' + btn + '_btn').addClass('selected');
}

function cht_settings_carousel_width() {
    var parent_w = $('.channel-tabs-container').parent().outerWidth(true),
        child_w = 0;

    $('li.channel').not('.hid').each(function () {
        child_w += $(this).outerWidth(true);
    });

    if (child_w > (parent_w - 38)) {
        if ((parent_w - 38) <= 282) {
            $('.channel-tabs-container').css({
                'width': 282 + 'px'
            })
            $('.cht-arr').css({
                'display': 'inline-block'
            });
        } else {
            $('.channel-tabs-container').css({
                'width': (parent_w - 38) + 'px'
            })
            $('.cht-arr').css({
                'display': 'inline-block'
            });
        }
    } else {
        $('.channel-tabs-container').css({
            'width': '100%'
        })
        $('.cht-arr').css({
            'display': 'none'
        });
    }

    cht_settings_arrows();
}

function cht_settings_arrows() {
    if ($('#channel_tabs__left').data('current') == 0) {
        $('#channel_tabs__left').css({
            'background-position': '0 0'
        })
    } else {
        $('#channel_tabs__left').css({
            'background-position': '0 -30px'
        });
    }
    if ($('#channel_tabs__left').data('current') == $('li.channel').not('.hid').length - 1) {
        $('#channel_tabs__right').css({
            'background-position': '-16px 0'
        });
    } else {
        $('#channel_tabs__right').css({
            'background-position': '-16px -30px'
        });
    }
}

$(function () {

    $('#channel_tabs__left').data('current', 0);
    $('#channel_tabs__left').data('shift', 0);

    cht_settings_arrows();

    $('#channel_tabs__left').on('click', function () {
        if ($('#channel_tabs__left').data('current') != 0) {
            $('#channel_tabs__left').data('shift', $('#channel_tabs__left').data('shift') + $('li').not('.hid').eq($('#channel_tabs__left').data('current') - 1).outerWidth(true));
            $('#channel_tabs__left').data('current', $('#channel_tabs__left').data('current') - 1);
            $('#channel_tabs').css({
                'position': 'relative',
                'left': $('#channel_tabs__left').data('shift') + 'px'
            });
        }
        if ($('#channel_tabs__left').data('current') == 0) {
            $('#channel_tabs__left').css({
                'background-position': '0 0'
            })
        }
        if ($('#channel_tabs__left').data('current') == $('li.channel').not('.hid').length - 1) {
            $('#channel_tabs__right').css({
                'background-position': '-16px 0'
            });
        } else {
            $('#channel_tabs__right').css({
                'background-position': '-16px -30px'
            });
        }
    });

    $('#channel_tabs__right').on('click', function () {
        var container_w = $('.channel-tabs-container').outerWidth(true);
        var child_w = 0;
        $('li.channel').not('.hid').each(function () {
            child_w += $(this).outerWidth(true);
        });
        if (container_w > (child_w + $('#channel_tabs__left').data('shift'))) {
            return;
        } else if ($('#channel_tabs__left').data('current') < $('li.channel').not('.hid').length - 1) {
            $('#channel_tabs__left').data('shift', $('#channel_tabs__left').data('shift') - $('li').not('.hid').eq($('#channel_tabs__left').data('current')).outerWidth(true));
            $('#channel_tabs__left').data('current', $('#channel_tabs__left').data('current') + 1);
            $('#channel_tabs').css({
                'position': 'relative',
                'left': $('#channel_tabs__left').data('shift') + 'px'
            });
            if (container_w > (child_w + $('#channel_tabs__left').data('shift'))) {
                $('#channel_tabs__right').css({
                    'background-position': '-16px 0'
                });
            }
        }
        if ($('#channel_tabs__left').data('current') > 0) {
            $('#channel_tabs__left').css({
                'background-position': '0 -30px'
            });
        }
    });
});

$(window).resize(function () {
    cht_settings_carousel_width();
});

function chat_users_sort(frame, user_html) {
    var chat_users_list = $('#chat_users_list', frame);
    var chat_users = chat_users_list.find('div.chat_user_item');
    var i;

    var sortField = userListSortFilter.sortField ? userListSortFilter.sortField : 'nick';
    var sortOrder = userListSortFilter.sortOrder ? userListSortFilter.sortOrder : 'asc';

    var returnOrder = sortOrder == 'asc' ? 1 : -1;

    if (user_html) {
        var data = $(user_html);
        var res_i = null;
        var found = 0;
        for (i = 0; i < chat_users.length; ++i) {
            found = chat_user_sort_compare_items(data[0], chat_users[i], sortField, returnOrder);
            if (found < 0) {
                res_i = i;
                break;
            }
        }
        if (res_i === null) {
            chat_users_list.append(data);
        }
        else {
            chat_users.eq(res_i).before(data);
        }
        return;
    }

    // full sort
    chat_users.sort(function (a, b) {
        return chat_user_sort_compare_items(a, b, sortField, returnOrder);
    });

    chat_users.detach().appendTo(chat_users_list);
}

function chat_user_sort_compare_items(a, b, sortField, returnValue) {

    var tmp1 = a.getAttribute('data-' + sortField),
        tmp2 = b.getAttribute('data-' + sortField);
    if (userListSortFieldTypes[sortField] && userListSortFieldTypes[sortField] == 'i') {
        tmp1 = parseInt(tmp1, 10);
        tmp2 = parseInt(tmp2, 10);
    }

    if ($.inArray(sortField, ['profession', 'clanid', 'marks', 'injuryweight']) != -1) {
        //вот такой костыль (
        if (tmp1 == 0 && tmp2 != 0) {
            return 1;
        }

        if (tmp2 == 0 && tmp1 != 0) {
            return -1;
        }
    }
    if (sortField == 'profession' && tmp1 == 0 && tmp2 == 0) {
        //вот такой костыль (
        tmp1 = a.getAttribute('data-nick').toLowerCase();
        tmp2 = b.getAttribute('data-nick').toLowerCase();
        returnValue = 1;
    }
    if (tmp1 == tmp2) {
        //вот такой костыль (
        if (sortField == 'profession') {
            tmp1 = parseInt(a.getAttribute('data-profession_skill'), 10);
            tmp2 = parseInt(b.getAttribute('data-profession_skill'), 10);
        } else {
            tmp1 = a.getAttribute('data-nick').toLowerCase();
            tmp2 = b.getAttribute('data-nick').toLowerCase();
            returnValue = 1;
        }
    }
    return tmp1 < tmp2 ? -returnValue : returnValue;
}

function chat_show_user_sort_modes(obj, e) {
    for (var i = userListSortMenu.length - 1; i >= 0; i--) {
        if (userListSortMenu[i].data && userListSortFilter.sortField == userListSortMenu[i].data) {
            userListSortMenu[i].picture = 'url(/images/cell-arr-' + userListSortFilter.sortOrder + '.png) 7px 50% no-repeat';
        } else {
            userListSortMenu[i].picture = 'url(images/blank.gif) no-repeat';
        }
    }

    $(obj).toggleClass('selected');

    gmnu(obj, e, userListSortMenu, {window: true, offsetLeft: -25, className: 'help_menu'});
}

function chat_show_user_search(obj, e) {
    gmnu(obj, e, userListSearchMenu, {window: true, offsetTop: 23, position: 'fixed', keep: true, className: 'help_menu'});
}

function chat_users_update_counters(frame) {
    var chat_users = $('#chat_users_list .chat_user_item', frame);
    chatUserCount = chat_users.not('.ghost').length;
    chatUserEnemyCount = chat_users.filter('.enemy').not('.ghost').length;
    chatUpdateUsersCounters();
}

function chat_get_users_frame() {
    var obj = null;
    if (!_top().chat || !_top().chat.chat_user) return false;
    if (_top().chat.chat_user.document && _top().chat.chat_user.loaded) obj = _top().chat.chat_user.document;
    return obj;
}

function chat_users_can_change(frame) {
    if (frame) {
        if ($('#chat_users_list .list_denied', frame).length) return false;
    } else {
        if ($chat_user_list && !$chat_user_list.find('.area.selected').length) return false;
        if (session.deaf) return false;
    }
    return true;
}

function chat_users_add(id, kind, raid, user_html_friend, user_html_enemy, force) {
    if (!chat_users_can_change()) return;
    var frame = chat_get_users_frame();
    if (!frame) {
        setTimeout(function () {
            chat_users_add(id, kind, raid, user_html_friend, user_html_enemy);
        }, 500);
        return;
    }
    if (!chat_users_can_change(frame)) return;
    if (!force && id == CHAT.my_id && $('#chat_users_list #chat_user_' + id, frame).length) return;
    var user_html = '';
    raid = parseInt(raid);
    if (raid && raid == parseInt(session.raid)) user_html = user_html_friend;
    else if (raid) user_html = user_html_enemy;
    else if (kind == session.kind) user_html = user_html_friend;
    else user_html = user_html_enemy;
    user_html = user_html.replace(/\$(\d+)\$/g, shablon_substitution);
    $('#chat_users_list #chat_user_' + id, frame).remove();
    chat_users_sort(frame, user_html);
    var is_after = chat_users_scrolled_after(frame, id, true);
    if (is_after) {
        var height = $('#chat_users_list #chat_user_' + id, frame).height();
        frame.defaultView.scrollBy(0, height);
    }
    chat_users_update_counters(frame);
}

function chat_users_update(id, kind, raid, user_html_friend, user_html_enemy) {
    if (!chat_users_can_change()) return;
    var frame = chat_get_users_frame();
    if (!frame) {
        setTimeout(function () {
            chat_users_update(id, kind, raid, user_html_friend, user_html_enemy);
        }, 500);
        return;
    }
    if (!chat_users_can_change(frame)) return;
    if (!$('#chat_users_list #chat_user_' + id, frame).length) return;
    var user_html = '';
    raid = parseInt(raid);
    if (raid && raid == parseInt(session.raid)) user_html = user_html_friend;
    else if (raid) user_html = user_html_enemy;
    else if (kind == session.kind) user_html = user_html_friend;
    else user_html = user_html_enemy;
    user_html = user_html.replace(/\$(\d+)\$/g, shablon_substitution);
    $('#chat_users_list #chat_user_' + id, frame).remove();

    chat_users_sort(frame, user_html);
}

function chat_users_remove(id) {
    if (!chat_users_can_change()) return;
    if (id == CHAT.my_id) return;
    var frame = chat_get_users_frame();
    if (!frame) {
        setTimeout(function () {
            chat_users_remove(id);
        }, 500);
        return;
    }
    if (!chat_users_can_change(frame)) return;
    var is_after = chat_users_scrolled_after(frame, id);
    var el = $('#chat_users_list #chat_user_' + id, frame);
    var height = el.height();
    el.remove();
    if (is_after) {
        frame.defaultView.scrollBy(0, -height);
    }
    chat_users_update_counters(frame);
};

function chat_users_fight_update(id, fight_id) {
    if (!chat_users_can_change()) return;
    var frame = chat_get_users_frame();
    if (!frame) {
        setTimeout(function () {
            chat_users_fight_update(id, fight_id);
        }, 500);
        return;
    }
    if (!chat_users_can_change(frame)) return;
};

function chat_users_scrolled_after(frame, id, or_next) {
    var win = frame.defaultView;
    var y = win.scrollY ? win.scrollY : win.document.body.scrollTop;
    var chat_user_items = $('#chat_users_list .chat_user_item', frame);
    for (var i = 0; i < chat_user_items.length; ++i) {
        var el = chat_user_items.eq(i);
        if (or_next && chat_user_items.eq(i + 1).data('id') == id) {
            return true;
        } else if (el.data('id') == id) {
            return true;
        } else if (el.offset().top >= y) {
            return false;
        }
    }
    return false;
};

function chatReceiveObject(objects) {
    for (var i in objects) {
        var fnc, fnc_name = 'controller_' + i.replace('|', '_');
        try {
            fnc = eval(fnc_name);
            if (typeof fnc == 'function') {
                var object = {};
                object[i] = objects[i];
                fnc.call(this, object);
            }
        } catch (e) {
        }
    }
}

function controller_party_conf(data) {
    if (_top && _top().frames['chat']) _top().frames['chat']['party_leader'] = (data['party|conf'].is_party_leader);
}

function controller_chat_area_population_diff(object) {
    //обрабаотываем данные только если включена вкладка area
    if ($chat_user_list.find('li.selected').data('type') != 'area') {
        return;
    }

    object = object['chat|area_population_diff'] || null;
    if (!object || typeof(object) != 'object') return;
    object.replace = object.replace || false;
    object['delete'] = object['delete'] || {};
    object.update = object.update || {};
    object.add = object.add || {};

    if (object.replace && replace_chat_users(object.replace)) {
        chat_users_update_counters(chat_get_users_frame());
        return;
    }
    var i;
    for (i in object['delete']) {
        chat_users_remove(object['delete'][i].id);
    }
    for (i in object.update) {
        var user = object.update[i];
        chat_users_update(user.id, user.kind, user.raid_id, user.html_friend, user.html_enemy);
    }
    for (i in object.add) {
        var user = object.add[i];
        chat_users_add(user.id, user.kind, user.raid_id, user.html_friend, user.html_enemy);
    }
    chat_users_update_counters(chat_get_users_frame());
}

function replace_user_list(users) {
    if (!users) return;
    var user_html = '';
    for (var i in users) {
        var user = users[i];
        var raid = parseInt(user['raid_id']);
        var kind = parseInt(user['kind']);
        var html = '';
        if (raid && raid == parseInt(session.raid)) html = user['html_friend'];
        else if (raid) html = user['html_enemy'];
        else if (kind == session.kind) html = user['html_friend'];
        else html = user['html_enemy'];
        user_html += html;
    }
    user_html = user_html.replace(/\$(\d+)\$/g, shablon_substitution);
    var frame = chat_get_users_frame();
    $('#chat_users_list ', frame).eq(0).html(user_html);
    chat_users_sort(frame);
}

function replace_chat_users(users) {
    var frame = chat_get_users_frame(),
        user_list = $('#chat_users_list .chat_user_item', frame),
        scrolled_users = [],
        replaced_users_count = 0,
        current_scroll = 0,
        last_visible_user_scroll = 0;

    user_list.each(function () {
        var id = $(this).data('id');
        if (chat_users_scrolled_after(frame, id)) {
            scrolled_users.push(id);
        }
    });

    replace_user_list(users);
    user_list.each(function () {
        var id = parseInt($(this).data('id'));
        if ($.inArray(id, scrolled_users) != -1) {
            last_visible_user_scroll = current_scroll;
        }
        current_scroll += $('#chat_users_list #chat_user_' + id, frame).height();
        replaced_users_count++;
    });
    frame.defaultView.scrollTo(0, last_visible_user_scroll);

    return replaced_users_count;
}

function controller_chat_area_population(object) {
    if ($chat_user_list.find('li.selected').data('type') != 'area') {
        return;
    }

    object = object['chat|area_population'] || null;
    if (!object || !object.users) return;
    replace_user_list(object.users);
    chat_users_update_counters(chat_get_users_frame());
}

function controller_common_debug(object) {
    object = object['common|debug'] || null;
    if (object.request_statistics) {
        var str_stat = '';
        for (var i in object.request_statistics) {
            if (i == 0) {
                object.request_statistics[i] = '[' + object.server_time + '] ' + object.request_statistics[i];
                console_group(object.request_statistics[i], true);
            }
            else console_log(object.request_statistics[i]);

            str_stat += object.request_statistics[i] + '|';
        }
        console_groupEnd();
        _top().REQUEST_STATISTICS += "\n" + str_stat;
    }
    if (object.vardump) {
        for (var i in object.vardump) {
            console_log(object.vardump[i]);
        }
    }
}

function controller_user_bag_kinds_diff(object) {
    object = object['user|bag_kinds_diff'] || null;
    if (!object || typeof(object) != 'object' || !object.kind_ids) return;
    var kind_ids = [];
    for (var i in object.kind_ids) kind_ids.push(object.kind_ids[i])
    _top().tUnsetBackpackGroup(kind_ids);
}

function controller_front_conf(object) {
    swfTransfer('small', 'area', 'from_small@front_conf=1');//temporary
    swfObject('area', object);
}

function controller_pet_rename_success(object) {
    object = object['pet|rename_success'] || null;
    if (typeof(_top().frames.main_frame.frames.backpack.updatePetInList) == "function") {
        _top().frames.main_frame.frames.backpack.updatePetInList(object['pet_id'], {title: object['new_name']});
    }
}

function controller_pet_new_pet(object) {
    if (typeof(_top().frames.main_frame.frames.backpack) != "undefined") {
        var $top_menu_pet = $(_top().frames.main_frame.frames.backpack.document).find(".tbl-sts_top a.menu-pets");
        if (!$top_menu_pet.length) {
            return;
        }

        if (!$top_menu_pet.hasClass("tbl-shp_menu-center-noact")) {
            return;
        }

        $top_menu_pet
            .removeClass("tbl-shp_menu-center-noact")
            .addClass("tbl-shp_menu-center-inact")
            .attr({href: "?mode=pets", title: "", target: "_self"});
    }
}

function controller_user_view(object) {
    try {
        if (typeof(_top().frames['main_frame'].frames['main'].external_controller_queue) != "undefined") {
            if (!object['user|view'].skip_external) {
                object['user|view'].skip_external = true;
                _top().frames['main_frame'].frames['main'].external_controller_queue.push(object);
                return;
            }
        }

        swfObject('lvl', object);
        swfObject('inventory', object);

        var user_view = object['user|view'] || false;
        if (!user_view) return;

        if (typeof(user_view['is_mount']) != "undefined" && typeof(user_view['mount_id']) != "undefined") {
            swfObject('items_right', {
                'user|mount': {
                    'status': DATA_OK,
                    'is_mount': user_view['is_mount'],
                    'mount_id': user_view['mount_id']
                }
            });
        }

        var artifact_alts = user_view['artifact_alts'] || false;
        if (!artifact_alts) return;
        _top().main_frame.backpack = _top().main_frame.backpack || {};
        _top().main_frame.backpack.art_alt = _top().main_frame.backpack.art_alt || {};
        for (var i = 0; i < artifact_alts.length; i++) {
            var alt = artifact_alts[i];
            _top().main_frame.backpack.art_alt[alt.artifact_alt_id] = alt;
        }
        if (isInClient()) {
            var url = 'main_iframe.php?mode=update_swf&tar[]=lvl';
            _top().frames['main_frame'].frames['main_hidden'].location.href = url;
        }
        updateAltEffects(user_view.temp_effects);
    } catch (e) {
    }
}

function controller_user_effects(object) {
    swfObject('items', object);
}

function controller_instance_conf(object) {
    swfObject('instance', object);
}

function controller_common_event_conf(object) {
    swfObject('area', object);
}

function controller_fight_count(object) {
    swfObject('area', object);
}

function controller_common_area_conf(object) {
    try {
        swfObject('area', object);
        var area_conf = object['common|area_conf'] || null;
        if (!area_conf) return;
        var set_cookie = area_conf['set_cookie'] || null;
        if (!set_cookie) return;
        for (var i in set_cookie) {
            _top().setCookie(i, set_cookie[i]);
        }
    } catch (e) {
    }
}

function controller_common_top_menu(object) {
    swfObject('top_mnu', object);
}

function controller_common_current_slot(object) {
    swfObject('items', object);
}

function controller_common_resurrection_modes(object) {
    swfObject('area', object);
}

function controller_magic_mirror_end_time(object) {
    swfObject('area', object);
}

function controller_common_action(object) {
    object = object['common|action'] || null;
    if (!object) return;
    if (object['redirect_error'] && !object['redirect_url']) {
        showError(object['redirect_error'], object['redirect_crc']);
    }
}

function controller_common_action_complete(object) {
    swfObject('area', object);
    swfObject('items_right', {'instapockets|init': true});
}

function controller_chat_session_state(obj) {
    if (obj['chat|session_state']['session_state']) {
        sessionUpdate(obj['chat|session_state']['session_state']);
    }
}

function controller_bg_update_daystate(obj) {
    if (typeof(session) != "object") {
        swfObject('top_mnu', {"bgFilledState": 0});
        return;
    }

    if (session['no_update_bg_state']) {
        swfObject('top_mnu', {"bgFilledState": 0});
        return;
    }

    var states = obj['bg|update_daystate']['states'];
    if (typeof(states) != "object") {
        swfObject('top_mnu', {"bgFilledState": 0});
        return;
    }

    var state_keys = Object.keys(states),
        state_data,
        bg_state = 0;
    for (var i = state_keys.length - 1; i >= 0; i--) {
        state_data = state_keys[i].split('_');
        if (parseInt(state_data[1], 10) != parseInt(session.kind, 10)) {
            continue;
        }

        if (parseInt(state_data[2], 10) < parseInt(session.level, 10)) {
            continue;
        }

        if (parseInt(state_data[3], 10) > parseInt(session.level, 10)) {
            continue;
        }

        bg_state = parseInt(states[state_keys[i]], 10);
        break;
    }

    swfObject('top_mnu', {"bgFilledState": bg_state});
}

function controller_offauction_new_lot(obj) {
    if (typeof(session) != "object") {
        getSWF('top_mnu').blinkButton(9, false);
        return;
    }
    if (obj['offauction|new_lot'].user_kind && session.kind != obj['offauction|new_lot'].user_kind) {
        getSWF('top_mnu').blinkButton(9, false);
        return;
    }
    if (session['update_auc_state']) {
        getSWF('top_mnu').blinkButton(9, true);
        return;
    }
}

function controller_cube_puton(obj) {
    swfObject('cube', obj);
}

function controller_cube_putoff(obj) {
    swfObject('cube', obj);
}

function controller_cube_craft(obj) {
    swfObject('cube', obj);
}

function controller_cube_use_recipe(obj) {
    swfObject('cube', obj);
}

function controller_wheelfortune_init(obj) {
    swfObject('wheel_fortune', obj);
}

function controller_wheelfortune_new_game(obj) {
    swfObject('wheel_fortune', obj);
}

function controller_wheelfortune_spin(obj) {
    swfObject('wheel_fortune', obj);
}

function controller_user_puzzle_start(obj) {
    var params = obj['user|puzzle_start'];
    if (!params) {
        return;
    }
    startPuzzle({
        pictureURI: params['picture'],
        segmentsOnSide: params['complexity'],
        width: parseInt(params['width'], 10) + 42,
        height: parseInt(params['height'], 10) + 100,
        quickStart: params['quickStart']
    });
}
