// $Id: common.js,v 1.156 2010-02-04 08:34:34 s.ignatenkov Exp $

var DATA_OK = 100;
var undefined;
var iam_sorting_now;

Number.prototype.toFixed = Number.prototype.toFixed || function(fractionDigits){
    return Math.floor( this * Math.pow(10, fractionDigits) + .5) / Math.pow(10, fractionDigits)
};
String.prototype.hashCode = function () {
    var hash = 0, i, c;
    if (this.length == 0) return hash;
    for (i = 0; i < this.length; i++) {
        c = this.charCodeAt(i);
        hash = ((hash << 5) - hash) + c;
        hash = hash & hash; // Convert to 32bit integer
    }
    return hash;
};
if (!Array.prototype.indexOf) {
    Array.prototype.indexOf = function (obj, fromIndex) {
        if (fromIndex == null) {
            fromIndex = 0;
        } else if (fromIndex < 0) {
            fromIndex = Math.max(0, this.length + fromIndex);
        }
        for (var i = fromIndex, j = this.length; i < j; i++) {
            if (this[i] === obj)
                return i;
        }
        return -1;
    };
}
function str_trim(str) {
    return str.replace(/^\s*/, "").replace(/\s*$/, "");
}
function array_filter(arr, fun) {
    var len = arr.length;
    if (typeof fun != "function")
        throw new TypeError();
    var res = new Array();
    var thisp = arguments[1];
    for (var i = 0; i < len; i++) {
        if (i in arr) {
            var val = arr[i];
            if (fun.call(thisp, val, i, arr))
                res.push(val);
        }
    }
    return res;
}
function array_unique (arr) {
    var res = [];
    var len = arr.length;
    for (var i = 0; i < len; i++) {
        for (var j = i + 1; j < len; j++) {
            if (arr[i] === arr[j]) {
                j = ++i;
            }
        }
        res.push(arr[i]);
    }
    return res;
}
function gebi(id){
    return document.getElementById(id) || document[id];
}

function jsquote(str){
    return str.replace(/'/g,'&#39;').replace(/>/g,'&gt;').replace(/</g,'&lt;').replace(/&/g,'&amp;') //'
}

function copyBoard(obj, txt){
    if (document.body.createTextRange) { // IE
        var d=document.createElement('INPUT');
        d.type='hidden';
        d.value=txt;
        document.body.appendChild(d).createTextRange().execCommand("Copy");
        document.body.removeChild(d);
        return;
    } else try { // FireFox
        netscape.security.PrivilegeManager.enablePrivilege('UniversalXPConnect');
        var gClipboardHelper = Components.classes["@mozilla.org/widget/clipboardhelper;1"].getService(Components.interfaces.nsIClipboardHelper);
        gClipboardHelper.copyString(txt);
    } catch (e) { // Google Chrome

    }
}

function getCoords(obj){
    var o=typeof(obj) == 'string' ? gebi(obj) : obj
    var ret={'l':o.offsetLeft,'t':o.offsetTop,'w':o.offsetWidth,'h':o.offsetHeight}
    while(o=o.offsetParent){
        ret.l+=o.offsetLeft
        ret.t+=o.offsetTop
    }
    return ret
}
var waitFuncId=0
function waitObj(id,evFunc){
    if(document.getElementById){
        if(typeof evFunc=='function'){
            window['waitFunc'+waitFuncId]=evFunc
            evFunc='waitFunc'+waitFuncId
            waitFuncId++
        }
        var obj=(id=='body')?document.body:document.getElementById(id)
        if(obj) window[evFunc]()
        else setTimeout("waitObj('"+id+"','"+evFunc+"')",100)
    }else{
        onload=evFunc
    }
}

function preloadImages() {
    var d = document;
    if(!d._prImg) d._prImg = new Array();
    var i, j = d._prImg.length, a = preloadImages.arguments;
    for (i=0; i<a.length; i++) {
        if (typeof a[i] != "string") continue;
        d._prImg[j] = new Image;
        d._prImg[j++].src = a[i];
    }
}

function checkbox_set(pfx, val) {
    var chk=document.getElementsByTagName('INPUT');
    for(var i=0;i<chk.length;i++){
        if(chk[i].name.indexOf(pfx)==0 || chk[i].getAttribute('grp')==pfx){
            chk[i].checked = (val == undefined ? !chk[i].checked: val);
        }
    }
}

// ==============================================================================

function showError(error, code) {
    var params = '';
    var save_error = error;
    code = parseInt(code);

    try{
        error = error.replace(/&/g, encodeURIComponent('&'));
        error = error.replace(/#/g, encodeURIComponent('#'));
    }catch (e) {
        error = save_error;
    }

    if (code & 1) {
        params += '&in_bank=1';
    }

    return showMsg2("error.php?error=" + error + params);
}

function luckyMsg(text, url) {
    var error_div = _top().window.gebi('error_div');

    error_div.errorCloseCallback = function() {
        _top().frames["main_frame"].frames["main"].location.href = url;
        error_div.errorCloseCallback = null;
    }

    showMsg2("error.php?error="+encodeURIComponent(text), "Сообщение");

}

function showMsg2(url, title, w, h) {
    try {
        w=w||480;
        h=h||300;
        var win = _top().window;
        var doc = _top().document;
        var width = doc.body.clientWidth;
        var height = doc.body.clientHeight;
        var div_width = Math.max(doc.compatMode != 'CSS1Compat' ? doc.body.scrollWidth : doc.documentElement.scrollWidth,width);
        var div_height = Math.max(doc.compatMode != 'CSS1Compat' ? doc.body.scrollHeight : doc.documentElement.scrollHeight,height);
        var obj = _top().gebi('error');
        var div = _top().gebi('error_div');
        if (!obj || !div) return false;
        obj.src=url;

        div.style.width = div_width;
        div.style.height = div_height;

        obj.width = w;
        obj.height = h;
        obj.style.left = ((width-w)/2);
        obj.style.top = ((height-h)/2);
        div.style.display = 'block';
        obj.style.display = 'block';
        win.scrollTo(0,0);
//		obj = _top().gebi('artifact_alt');
//		if (obj) obj.innerHTML='';
    } catch(e) {}
    return true;
}

function showMsg(url, title, w, h) {
    w=w||520
    h=h||320
    var win = top.window;
    if (win.showModelessDialog) {
        var sFeatures = 'dialogWidth:' + w + 'px; dialogHeight:' + h + 'px; center:yes; help:no; status:no; unadorned:yes; scroll:no;';
        return win.showModelessDialog("msg.php", {win: win, src: url, title: title}, sFeatures);
    } else {
        return win.open(url, "", 'width=' + w + ',height=' + h + ',location=no,menubar=no,resizable=no,scrollbars=no,status=no,toolbar=no');
    }
}

function closeMsg() {
    gebi('frame_content_hider').style.display = 'none';
    gebi('popup_styled').style.display = 'none';
}
function changeDivDisplay(div_id, display) {
    if (!div_id || !display) return false;
    div = gebi(div_id);
    if (!div) return false;
    div.style.display = display;
}

function showUserInfo(nick, server_url) {
    var url = '';
    if (server_url) url = server_url;
    else url += '/';
    url += "user_info.php?nick="+encodeURIComponent(nick);
    if (_top().js_popup) {
        tProcessMenu('b06', {url: url, force: true});
        tUnsetFrame('main');
    } else {
        window.open(url, "", "width=960,height=700,location=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
    }
    return  false;
}

function showCommonHelpAdminArtifact(obj, evnt) {
    let artifact_id = parseInt(obj.getAttribute('aid'));
    let artikul_id = parseInt(obj.getAttribute('art_id'));
    let cnt = parseInt(obj.getAttribute('cnt'));
    artifact_id = !isNaN(artifact_id) && artifact_id > 0 ? artifact_id : 0;
    artikul_id = !isNaN(artikul_id) && artikul_id > 0 ? artikul_id : 0;
    cnt = !isNaN(cnt) && cnt > 0 ? cnt : 0;
    if (typeof(iam_sorting_now) !== 'undefined' && iam_sorting_now)
        return false;
    if (evnt && evnt.altKey && artifact_id) {
        try{
            top.frames['main_frame'].frames['main'].location.href = 'area_post.php?&mode=outbox&action=send&check_art=1&form[artifact_id]=' + artifact_id + '&form[n]=' + cnt;
        }catch (e){}
        return true;
    }
    return  false;
}

function showArtifactInfo(artifact_id,artikul_id,set_id,evnt,user_store) {
    if (typeof(iam_sorting_now) !== 'undefined' && iam_sorting_now)
        return false;
    if (evnt && evnt.shiftKey && artifact_id) {
        chat_add_artifact_macros(artifact_id);
        return;
    }
    if (evnt && evnt.shiftKey && artikul_id) {
        chat_add_macros('artikul_'+artikul_id);
        return;
    }
    var url = "/artifact_info.php";
    if (artifact_id) url += "?artifact_id="+artifact_id;
    else if (artikul_id) url += "?artikul_id="+artikul_id;
    else if (set_id) url += "?set_id="+set_id;
    else return;
    if(user_store){
        url += '&user_store=1';
    }
    window.open(url, "", "width=830,height=650,location=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
}

function showCaseBoxInfo(id) {
    var url = "/case_box_award.php?ref=" + id;
    window.open(url, "", "width=1075,height=560,location=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
}

function showPetInfo(pet_id,artikul_id) {
    var url = "/pet_info.php";
    if (pet_id) url += "?pet_id="+pet_id;
    else if (artikul_id) url += "?artikul_id="+artikul_id;
    else return;
    window.open(url, "", "width=890,height=550,location=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
}

function showSmsForm(nick) {
    var url = "/area_post.php?&mode=sms&hide=1&nick=" + nick;
    window.open(url, "", "width=920,height=500,location=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
}

function time_online_get(){

}

function dialogEventCheck(event,param,close) {
    if (!param) param = '0';
    if (!_top().dialogOn && event != 'FAQ' && event != null) return false;
    if (event) {
        var id = _top().dialogEvent[event+'_'+param];
        if (id && id.length && id.length > 0) {
            for(var i=0;i<id.length;i++) {
                if (_top().dialogShow[id[i]]) continue;
                var k = false;
                for(var j=0;j<_top().dialogNeed.length;j++) {
                    if (_top().dialogNeed[j] == id[i]) {
                        k = true;
                        break;
                    }
                }
                if (_top().showNow == id[i]) k=true;
                if (!k) _top().dialogNeed.push(id[i]);
            }
        }
    }
    try {
        var div = _top().frames['main_frame'].gebi('dialog_div');
        //var frame = _top().frames['main_frame'].gebi('dialog_frame');
        if (div.style.display == 'none' || close) {
            var id = _top().dialogNeed.shift();
            if (id) {
                div.style.display = '';
                if (id > 1) _top().dialogShow[id] = id;
                _top().showNow = id;
            } else {
                div.style.display = 'none';
                _top().showNow = 0;
            }
        }
    } catch(e) {}
}


function sortTable(table, colIndex, sortType, reverse) {
    var tbody = table.tBodies[0],
        tr = Array.prototype.slice.call(tbody.rows, 0),
        i;

    reverse = parseInt(reverse) || 1;

    switch (sortType) {
        case 'string':
            tr = tr.sort(function(a, b) {
                return reverse * a.cells[colIndex].getAttribute('data-sort').localeCompare(b.cells[colIndex].getAttribute('data-sort'));
            });

            break;

        default: /* sort as number */
            tr = tr.sort(function(a, b) {
                return reverse * (parseFloat(a.cells[colIndex].getAttribute('data-sort')) - parseFloat(b.cells[colIndex].getAttribute('data-sort')));
            });
    }

    for (i = 0; i < tr.length; i++) {
        tbody.appendChild(tr[i]);
    }
}

function showFightInfo(fight_id, server_id) {
    var url = "/fight_info.php?fight_id="+fight_id;
    if (server_id) url += "&server_id="+server_id;
    window.open(url, "", "width=990,height=700,location=yes,menubar=yes,resizable=yes,scrollbars=yes,status=yes,toolbar=yes");
    return false;
}

function showTournamentFightInfo(fight_id) {
    var url = "/tournament_fight_info.php?id=" + fight_id;
    window.open(url, "", "width=990,height=700,location=yes,menubar=yes,resizable=yes,scrollbars=yes,status=yes,toolbar=yes");
    return false;
}
function showInstInfo() {
    var url = "/instance_stat.php";
    window.open(url, "", "width=990,height=700,location=yes,menubar=yes,resizable=yes,scrollbars=yes,status=yes,toolbar=yes");
}
function showInstanceInfo(instance_id, server_id) {
    var url = "/instance_stat.php?instance_id="+instance_id+'&outside=1&finish=1';
    if (server_id) url += "&server_id="+server_id;
    window.open(url, "", "width=990,height=700,location=yes,menubar=yes,resizable=yes,scrollbars=yes,status=yes,toolbar=yes");
}
function showClanBattleInfo(clan_battle_id, server_id) {
    var url = "/clan_battle_info.php?clan_battle_id="+clan_battle_id+'&server_id='+server_id;
    window.open(url, "", "width=990,height=700,location=yes,menubar=yes,resizable=yes,scrollbars=yes,status=yes,toolbar=yes");
}
function showClanFortInfo(url) {
    window.open(url, "", "width=990,height=700,location=yes,menubar=yes,resizable=yes,scrollbars=yes,status=yes,toolbar=yes");
    return false;
}
function showBotInfo(bot_id, artikul_id, fight_id, server_id) {
    var url = "/bot_info.php";

    if (bot_id) {
        if (fight_id) {
            url += "?fight_user_id="+bot_id+"&fight_id="+fight_id;
            if (typeof server_id != 'undefined' && server_id) {
                url += "&server_id=" + server_id;
            }
        } else {
            url += "?bot_id="+bot_id;
        }
    }
    else if (artikul_id) url += "?artikul_id="+artikul_id;
    try{
        if(top.frames["main_frame"].frames["main"].__fight_php__){
            url += "&f=1";
        }
    }catch(e){}

    window.open(url, "", "width=915,height=700,location=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
}

function showPunishmentInfo(nick) {
    var url = "/punishment_info.php?nick="+nick;
    window.open(url, "", "width=830,height=550,location=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
}

function showInjuryInfo(nick) {
    var url = "/injury_info.php?nick="+nick;
    window.open(url, "", "width=830,height=550,location=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
}

function showEffectInfo(nick) {
    var url = "/effect_info.php?nick="+nick;
    window.open(url, "", "width=830,height=550,location=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
}

function showClanInfo(clan_id) {
    /*if (evnt && evnt.shiftKey && clan_id) {
        chat_add_macros('clan_'+clan_id);
        return;
    }*/
    var url = "/clan_info.php?clan_id="+clan_id;
    window.open(url, "", "width=890,height=650,location=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
}

function clipboardSetText (text) {
    var input = document.createElement('textarea');
    input.innerHTML = text;
    document.body.appendChild(input);
    input.select();
    var result = document.execCommand('copy');
    document.body.removeChild(input);
    return result;
}

function showFriendsInfo(mode) {
    var url = "/friend_list.php" + (mode != undefined ? '?mode=' + mode : '');
    window.open(url, "contacts", "width=900,height=650,location=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
}

function showAchievementInfo(achievement_id) {
    var url = "/achievement_info.php?id="+achievement_id;
    window.open(url, "", "width=730,height=550,location=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
}

function userPrvTag() {
    var chatFrame = getChatFrame();
    var i = 0;
    try {
        for (i=0; i<arguments.length; i++) chatFrame.chatPrvTag(arguments[i]);
    }
    catch (e) {}
}

function userToTag() {
    var chatFrame = getChatFrame();
    var i = 0;
    try {
        for (i=0; i<arguments.length; i++) chatFrame.chatToTag(arguments[i]);
    }
    catch (e) {}
}


function userIgnore(name,status) {
    var chatFrame = getChatFrame();
    try {
        chatFrame.chatSyncIgnore(name,status);
    }
    catch (e) {}
}

var actionRun = new _ActionRun();

function _ActionRun(){

    /*Возвращает {'status', 'error'}*/
    this.artifact_action = function (artifact_id, action_id) {
        var obj_cl = "ARTIFACT";
        var act = 'action_run.php?ACTIONID_R&action_id='+action_id+'&object_id='+artifact_id+'&artifact_id='+artifact_id+'&object_class='+obj_cl+'&in[object_class]='+obj_cl+'&in[object_id]='+artifact_id+'&in[action_id]='+action_id;
        act += '&url_success=error_parser.php&url_error=error_parser.php';

        var out = {'status' : -2, 'error' : ''};

        try{
            $.ajax({
                type: 'POST',
                url: act,
                async: false,
                dataType: 'json',
                data: false,
                complete: function (XMLHttpRequest, textStatus) {
                    var data = XMLHttpRequest.responseText;
                    if(data.length > 0){
                        out['status'] = -1;
                        out['error'] = data;
                        return out;
                    }else{
                        out['status'] = 0;
                        return out;
                    }
                },
                error: function (request, status, error) {
                    out['status'] = -2;
                    out['error'] = error;
                    return out;
                }
            });
        }catch(e){
            out['status'] = -2;
            out['error'] = e;
            return out;
        }
        return out;
    };
}

function userAttack(nick, url_error) {
    var rnd = Math.floor(Math.random()*1000000000);
    var url_success = 'fight.php?'+rnd;
    var urlATTACK = 'action_run.php?code=ATTACK&url_success='+url_success+'&url_error='+escape(url_error||'area.php')+'&in[nick]='+(nick ? nick : '');
    tProcessMenu('b07', {force: true, lock: true, url: urlATTACK});
//	try {
//		if (!top.frames["main_frame"].frames["main"].__fight_php__) top.frames["main_frame"].frames["main"].location.href = urlATTACK;
//	}
//	catch (e) {}
}
function confirm_friend(url) {
    try {
        top.frames['main_frame'].frames['main_hidden'].location.href = url;
    }
    catch (e) {}
}

function confirm_bg(area_id) {
    try {
        top.frames['main_frame'].frames['main'].location.href = 'area_bgs.php?area_id=' + area_id + '&action=confirm';
    }
    catch (e) {}
}

function confirm_fhunt(id) {
    try {
        top.frames['main_frame'].frames['main'].location.href = 'hunt_attack.php?id=' + id + '&action=confirm_hunt';
    }
    catch (e) {}
}

function confirm_dungeon_anv(act){
    try {
        top.frames['main_frame'].frames['main_hidden'].location.href = 'dungeons.php?action=confirm_anv&ajax=1&act='+act;
    }
    catch (e) {}
}

function confirm_dungeon_act(data_id, act) {
    try {
        top.frames['main_frame'].frames['main'].location.href = 'dungeon_list.php?action=confirm_act&act='+(act ? 1 : 0)+'&data_id='+data_id;
    }
    catch (e) {}
}

function confirm_dungeon(data_id) {
    try {
        top.frames['main_frame'].frames['main'].location.href = 'dungeon_list.php?action=confirm&data_id='+data_id;
    }
    catch (e) {}
}

function confirm_raid(raid_id) {
    try {
        top.frames['main_frame'].frames['main'].location.href = 'area_bgs.php?&mode=raids&raid_id=' + raid_id + '&action=confirm';
    }
    catch (e) {}
}

function confirm_slaughter(area_id) {
    try {
        top.frames['main_frame'].frames['main'].location.href = 'area_bgs.php?area_id=' + area_id + '&action=confirm_slaughter';
    }
    catch (e) {}
}

function show_slaughter_stat(instance_id, finish, baseurl) {
    if (baseurl+'' == 'undefined') baseurl = '';
    var url = baseurl + 'instance_stat.php?outside=1&instance_id=' + instance_id + '&finish=' + finish;
    window.open(url, "", "width=730,height=550,location=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
//	try {
//		top.frames['main_frame'].frames['main'].location.href = 'instance_stat.php?instance_id=' + instance_id + '&finish=' + finish;
//	}
//	catch (e) {}
}

function getChatFrame() {
    var win = window;
    try {win = dialogArguments || window} catch(e) {};
    while (win.opener) {
        if (win === win.opener) break;
        win = win.opener;
    }
    if (win.closed) return;

    return win._top().frames['chat'];
}

function fightHelpRequest() {
    var chatFrame = getChatFrame();
    try {
        chatFrame.chatSendMessage('/HELP');
    } catch(e) {}
}

//fight_id where `target_nick` need help
function fightHelp(fight_id, target_nick, confirm_msg) {
    top.systemConfirm(confirm_msg + ' ' + target_nick + '?', 'Действие', false, function() {

        try {
            var rnd = Math.floor(Math.random()*1000000000);
            var err_url = top.frames["main_frame"].frames["main"].location;
            var url = 'action_run.php?code=FIGHT_HELP'+
                '&in[fight]=' + escape(fight_id) +
                '&in[target_nick]=' + target_nick +
                '&url_success=' + escape('fight.php?'+rnd) +
                '&url_error=' + escape(err_url) +
                '&' + rnd;
            top.frames["main_frame"].frames["main"].location.href = url;
        }
        catch (e) {
        }
    })
}

function botAttack(bot_id, url_error) {
    var rnd = Math.floor(Math.random()*1000000000);
    var url_success = 'fight.php?'+rnd;
    var urlATTACK = 'action_run.php?code=ATTACK_BOT&url_success='+url_success+'&url_error='+escape(url_error||'area.php')+'&bot_id='+bot_id;
    if (!bot_id) return;
    try {
        if (!top.frames["main_frame"].frames["main"].__fight_php__) top.frames["main_frame"].frames["main"].location.href = urlATTACK;
    }
    catch (e) {}
}

function huntAttack(bot_id) {
    botAttack(bot_id,'hunt.php');
}

function _background(obj, name) {
    if (obj.tagName == 'IMAGE') {
        obj.src = name;
    } else {
        obj.style.backgroundImage = 'url('+name+')'
    }
}

function getIframeShift() {
    var currentWindow = window,
        currentFrame = null,

        docElem = null,
        body = null,

        top = 0,
        left = 0,

        scrollTop = 0,
        scrollLeft = 0;

    while (currentFrame = currentWindow.frameElement) {
        currentWindow = currentWindow.parent;

        top += Math.round(currentFrame.getBoundingClientRect().top);
        left += Math.round(currentFrame.getBoundingClientRect().left);
    }

    return {
        top: top,
        left: left
    };
}

var debugArtAlt = true;
function artifactAlt(obj, evnt, show) {
    // Сортировка в рюкзаке
    if (typeof(iam_sorting_now) !== 'undefined' && iam_sorting_now) {
        show = 0;
    }
    var art_id = obj.getAttribute('div_id');
    var aid = obj.getAttribute('aid');
    var rune_h = obj.getAttribute('rune_h');
    var artifact_alt = top.gebi('artifact_alt');
    if (!artifact_alt) return;

    var collection = obj.getAttribute('collection') || false;
    var act1 = obj.getAttribute('act1');
    var act2 = obj.getAttribute('act2');
    var act3 = obj.getAttribute('act3');
    var user_store = obj.getAttribute('user_store');
    if(user_store !== undefined && user_store == 1){
        user_store = true;
    }else{ user_store = false;}
    if (act3 == 0) act3 = '' // костыль что бы не переименовывать картинки в локализациях
    if (act1 == null) act1 = 0;
    if (show == 2) {
        document.onmousemove=function(e) {artifactAlt(obj, e||event, 1);}

        if(art_alt !== undefined && art_alt[art_id] !== undefined) {
            if (!artifact_alt.getAttribute('art_id') || obj.getAttribute('div_id') != artifact_alt.getAttribute('art_id')) {
                if (art_alt[art_id] && art_alt[art_id] != undefined) {
                    artifact_alt.innerHTML = renderArtifactAlt(art_id);
                }
                artifact_alt.setAttribute('art_id',obj.getAttribute('div_id'));
            }

            top.obj = obj;
            if (top.show_alt) {
                top.show_alt();
            }
        }

        artifact_alt.style.display = 'block';
        if (act1 || act2 || act3) {
            _background(obj, (top.locale_path + "images/itemact-"+ act1) + act2 + (act3 +".gif"));
        }
    }
    if (!show) {
        if (act1 || act2 || act3) {
            if(rune_h == 1){
                try{$('.rune_' + aid).show();}catch(e){}
                try{obj.setAttribute('rune_h', 0);}catch(e){}
            }
            _background(obj, 'images/d.gif');
        }
        artifact_alt.style.display = 'none';
        document.onmousemove=function(){}
        return;
    }

    var coor = getIframeShift();
    var ex = evnt.clientX+coor.left;
    var ey = evnt.clientY+coor.top;

    if (top.noIframeAlt) {
        try{
            ex = evnt.clientX + top.document.body.scrollLeft;
            //ey = evnt.clientY + top.document.body.scrollTop + 200;
            ey = evnt.clientY + top.document.body.scrollTop;
        }catch(e){}
    }

    if (act1 || act2 || act3) {
        if(rune_h == 0){
            try{$('.rune_' + aid).hide();}catch(e){}
            try{obj.setAttribute('rune_h', 1);}catch(e){}
        }
        obj.style.cursor = 'pointer'
        obj.onclick = (act1 != 0 ? function(e){try{artifactAct(obj, act1, e||event)}catch(e){if(debugArtAlt){console.log(e);}}} : function(e){showArtifactInfo(obj.getAttribute('aid'), obj.getAttribute('art_id'), null, e||event, user_store)});
        if(!collection){
            _background(obj, (top.locale_path + "images/itemact-"+ act1) + act2 + (act3 + ".gif"));
        }
        var coord = getCoords(obj);
        var cont = gebi("item_list");
        var rel_x = (ex + cont.scrollLeft - coord.l - coor.left);

        if(evnt.altKey){
            obj.onclick = function(e){showCommonHelpAdminArtifact(obj, e||event)};
        }else{
            if (rel_x >= 40) {
                var rel_y = (ey + cont.scrollTop - coord.t - coor.top)
                if (rel_y < 20) {
                    if (obj.getAttribute('store')) { // в магазине при клике на info необходимо выводить товар по артикулу
                        obj.onclick = function(e){showArtifactInfo(false, obj.getAttribute('art_id'), null, e||event, user_store)};
                    } else {
                        obj.onclick = function(e){showArtifactInfo(obj.getAttribute('aid'), null, null, e||event, user_store)}
                    }
                    if(!collection){
                        _background(obj, top.locale_path + 'images/itemact_info' + act2 + (act3 + '.gif'));
                    }
                    try{obj.style.cursor = 'hand'} catch(e){}
                    try{obj.style.cursor = 'pointer'} catch(e){}
                }
                if (act2 != 0 && rel_y >= 40) {
                    obj.onclick = function(e){try{artifactAct(obj, act2, e||event)}catch(e){if(debugArtAlt){console.log(e);}}}
                    if(!collection){
                        _background(obj, top.locale_path + 'images/itemact_drop' + act2 + (act3 + '.gif'));
                    }
                    try{obj.style.cursor = 'hand'} catch(e){}
                    try{obj.style.cursor = 'pointer'} catch(e){}
                }
            }
            if (act3 > 0 && rel_x < 20) {
                var rel_y = (ey + cont.scrollTop - coord.t - coor.top);
                if (rel_y < 20) {
                    obj.onclick = function(e){try{artifactAct(obj, act3, e||event)}catch(e){if(debugArtAlt){console.log(e);}}};
                    if(!collection){
                        _background(obj, top.locale_path + 'images/itemact_use' + act2 + (act3 + '.gif'));
                    }
                    try {obj.style.cursor = 'hand'} catch(e){}
                    try {obj.style.cursor = 'pointer'} catch(e){}
                }
            }
        }
    }
    var x = ex + artifact_alt.offsetWidth > top.document.body.clientWidth - 20 ? ex - artifact_alt.offsetWidth - 10 : ex + 10;
    var y = ey + artifact_alt.offsetHeight - top.document.body.scrollTop > top.document.body.clientHeight - 20 ? ey - artifact_alt.offsetHeight - 10 : ey + 10;

    if (x < 0 ) {
        x = ex - artifact_alt.offsetWidth/2;
    }
    if (x < 7 ) {
        x = 7;
    }
    if (x > top.document.body.clientWidth - artifact_alt.offsetWidth - 20) {
        x= top.document.body.clientWidth - artifact_alt.offsetWidth - 20;
    }

    artifact_alt.style.left = x;
    artifact_alt.style.top = y;
    return;
}

function userAlt(obj, evnt, show) {
    var soc_id = obj.getAttribute('soc_id');
    var soc_user_id = obj.getAttribute('soc_user_id');
    var user_alt = _top().gebi('artifact_alt');
    if (!user_alt) return;

    if (show == 1) {
        document.onmousemove=function(e) {userAlt(obj, e||event, 1);}

        if (!user_alt.getAttribute('soc_id') || !user_alt.getAttribute('soc_user_id') || obj.getAttribute('soc_id') != user_alt.getAttribute('soc_id') || obj.getAttribute('soc_user_id') != user_alt.getAttribute('soc_user_id') ) {
            if (soc_user_alts[soc_id] && soc_user_alts[soc_id] != undefined || soc_user_alts[soc_user_id] && soc_user_alts[soc_user_id] != undefined) {
                user_alt.innerHTML = renderUserAlt(soc_id, soc_user_id);
            }
            user_alt.setAttribute('soc_id',obj.getAttribute('soc_id'));
            user_alt.setAttribute('soc_user_id',obj.getAttribute('soc_user_id'));
        }

        user_alt.style.display = 'block';

        _top().obj = obj;
        if (_top().show_alt) {
            _top().show_alt();
        }
    }
    if (!show) {
        user_alt.style.display = 'none';
        document.onmousemove=function(){}
        return;
    }

    var coor = getIframeShift();
    var ex = evnt.clientX+coor.l;
    var ey = evnt.clientY+coor.t;

    if (_top().noIframeAlt) {
        ex = evnt.clientX + _top().document.body.scrollLeft;
        ey = evnt.clientY + _top().document.body.scrollTop;
    }

    var x = ex + user_alt.offsetWidth > _top().document.body.clientWidth - 20 ? ex - user_alt.offsetWidth - 10 : ex + 10;
    var y = ey + user_alt.offsetHeight - _top().document.body.scrollTop > _top().document.body.clientHeight - 20 ? ey - user_alt.offsetHeight - 10 : ey + 10;

    if (x < 0 ) {
        x = ex - user_alt.offsetWidth/2;
    }
    if (x < 7 ) {
        x = 7;
    }
    if (x > _top().document.body.clientWidth - user_alt.offsetWidth - 20) {
        x= _top().document.body.clientWidth - user_alt.offsetWidth - 20;
    }

    user_alt.style.left = x;
    user_alt.style.top = y;

    return;
}

function renderUserAlt(soc_id, soc_user_id) {
    var a = soc_user_alts[soc_id][soc_user_id];
    var content = '';

    content += '<table width="200" border="0" cellspacing="0" cellpadding="0">';
    content += '<tr><td width="14" class="aa-tl"><img src="images/d.gif" width="14" height="24"><br></td>';
    content += '<td class="aa-t" align="center" style="vertical-align:middle"><b>' + a.name + '</b></td>';
    content += '<td width="14" class="aa-tr"><img src="images/d.gif" width="14" height="24"><br></td></tr>';
    content += '<tr class="bg_alt2"><td class="aa-l" style="padding:0;"></td><td class="bg_alt3" style="padding:0;">';
    content += '<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center">';
    content += '<img src="' + a.avatar + '" alt="">';
    content += '</td></tr></table>';
    content += '</td><td class="aa-r" style="padding:0px"></td></tr>';
    content += '<tr><td class="aa-bl"></td><td class="aa-b"><img src="images/d.gif" width="1" height="5"></td><td class="aa-br"></td></tr>';
    content += '</table>';

    return content;
}


function loadArtifactArtikulsData(artikul_ids, complete_func) {
    if (typeof(artikul_ids) != "object") {
        return;
    }

    var unloaded_artikul_ids = [],
        alt;
    for (var i = artikul_ids.length - 1; i >= 0; i--) {
        alt = get_art_alt('AA_' + artikul_ids[i]);
        if (!alt) {
            unloaded_artikul_ids.push(artikul_ids[i]);
        }
    }
    entry_point_request('info', 'alt_artikuls', {artikuls: unloaded_artikul_ids}, function(data) {
            if (data.status != 100 || !data['artikuls']) {
                if (complete_func != undefined) complete_func.call();
                return;
            }

            for (var i = data['artikuls'].length - 1; i >= 0; i--) {
                set_art_alt('AA_' + data['artikuls'][i]['id'], data['artikuls'][i]);
            }
            if (complete_func != undefined) complete_func.call();
        }
    );
}

function renderArtifactAlt(id) {
    var a = get_art_alt(id);

    if (!a) {
        console_log('art_alt[id] is empty', 'renderArtifactAlt', id, window);//js_error_log
        return '';
    }
    var bg = true;
    var i = 0;
    var content = '';

    content += '<table width="300" border="0" cellspacing="0" cellpadding="0" class="aa-table">';
    content += '<tr><td width="14" class="aa-tl"><img src="images/d.gif" width="14" height="24"><br></td>';
    content += '<td class="aa-t aa-table-t" align="center" style="vertical-align:middle"><b style="color:' + a.color + '">' + a.title + '</b></td>';
    content += '<td width="14" class="aa-tr"><img src="images/d.gif" width="14" height="24"><br></td></tr>';
    content += '<tr class="bg_alt2"><td class="aa-l" style="padding:0;"></td><td class="bg_alt3" style="padding:0;">';
    content += '<table width="275" style=" margin: 3px" border="0" cellspacing="0" cellpadding="0" class="aa-table-t"><tr>';
    content += '<td align="center" valign="top" width="60">';
    content += '<table class="pctntr" width="60" height="60" cellpadding="0" cellspacing="0" border="0" style="margin: 2px" background="' + a.image + '"><tr><td style="position: relative;" valign="bottom">';
    if (a.count && a.count != undefined) {
        content += '<div class="bpdig">' + a.count + '</div>';
    } else if ((a.enchant_icon && a.enchant_icon != undefined) || (a.oprava && a.oprava != undefined)) {
        content += '<span class="enchants">';
        if(a.enchant_icon){
            content += a.enchant_icon;
        }
        if(a.oprava){
            content += '<img src="/images/enchants/oprava.png" alt="" class="enchant2_png">';
        }
        content += '</span>';
    } else {
        content += '&nbsp;';
    }

    for(sc = 1; sc <= 4; sc++){
        try{
            if(a['socket_' + sc] == 'undefined' || !a['socket_' + sc] || a['socket_' + sc] == '') continue;
            content += '<div class="rune_png rune_'+sc+'"><img src="'+a['socket_' + sc]+'" alt=""></div>';
        }catch(e){}
    }

    if (a.symbol && a.symbol != undefined) {
        content += '<div class="art_pulse"></div>';
    }

    content += '</td></tr></table>';
    content += '</td><td>';
    content += '<div><img src="images/tbl-shp_item-icon.gif" width="11" height="10" align="absmiddle">&nbsp;' + a.kind + '</div>';
    if (a.dur && a.dur != undefined && !(a.nobreaks && a.nobreaks != undefined)) {
        content += '<div><img src="images/tbl-shp_item-iznos.gif" width="11" height="10" align="absmiddle"> <span class="red">' + a.dur + '</span>/' + a.dur_max + '</div>';
    }
    if(a.dur2 && (!a.dur || a.dur == undefined)){
        content += '<div><img src="images/tbl-shp_item-iznos.gif" width="11" height="10" align="absmiddle"><span class="red">' + a.dur2 + '</span></div>';
    }
    if (a.nobreaks && a.nobreaks != undefined) {
        content += '<div><img src="images/tbl-shp_item-iznos.gif" width="11" height="10" align="absmiddle"> <span class="red">' + a.nobreaks + '</span></div>';
    }

    if (a.price && a.price != undefined) {
        content += '<div class="b red">' + a.price + '</div>';
    }
    if (a.com && a.com != undefined) {
        content += '<div class="b red">' + a.com.title + ' ' + a.com.value + '</div>';
    }
    if (a.owner && a.owner != undefined) {
        content += '<div><b class="b red">' + a.owner.title + '</b>' + a.owner.value + '</div>';
    }
    content += '</td><td>';
    if (a.lev && a.lev != undefined) {
        content += '<div><img src="images/tbl-shp_level-icon.gif" width="11" height="10" align="absmiddle"> ' + a.lev.title + ' <b class="red">' + a.lev.value + '</b></div>';
    }
    if (a.trend && a.trend != undefined) {
        content += '<div><img src="images/tbl-shp_item-trend.gif" width="11" height="10" align="absmiddle">&nbsp;' + a.trend + '</div>';
    }
    if (a.cls && a.cls != undefined) {
        content += '<div><img src="images/class.gif" width="11" height="10" align="absmiddle"> ';
        for (i in a.cls) {
            content += a.cls[i];
        }
        content += '</div>'
    }
    content += '</td></tr></table>';
    content += '<table class="aa-table-t" width="100%" cellpadding="0" cellspacing="0" border="0">';

    if (a.energy_list && a.energy_list != undefined) {
        try{
            for (let energy of Object.values(a.energy_list)) {
                content += '<tr><td class="skill_list ' + (bg ? 'list_dark' : '') + '" colspan="2">'+energy['title']+'</td></tr>';
                content += '<tr><td class="skill_list ' + (bg ? 'list_dark' : '') + '" colspan="2"><div class="achieve_line_bg"><div class="achieve_line_bgred" style="width: '+energy['w']+'px;"></div><div class="achieve_line_info">'+energy['c']+'/'+energy['m']+'</div><img src="/images/achieve_line_bgtop.png" width="267" height="17" style="position: absolute;"></div></td></tr>';
                bg = !bg;
            }
        }catch(e_err){}
    }

    if (a.exp && a.exp != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td>' + a.exp.title + '</td><td class="grnn b" align="right">' + a.exp.value + '</td></tr>';
        bg = !bg;
    }
    if (a.skills && a.skills != undefined) {
        for (i in a.skills) {
            content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td>' + a.skills[i].title + '</td><td class="red" align="right">' + a.skills[i].value + '</td></tr>';
            bg = !bg;
        }
    }
    if (a.skills_e && a.skills_e != undefined) {
        for (i in a.skills_e) {
            content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td>' + a.skills_e[i].title + '</td><td class="red" align="right">' + a.skills_e[i].value + '</td></tr>';
            bg = !bg;
        }
    }

    if(a.mount_packet && a.mount_packet != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td class="b" style="color: #2196F3;">Стиль ездового животного</td><td class="b" style="color: #2196F3;" align="right">' + a.mount_packet + '</td></tr>';
        bg = !bg;
    }

    if (a.enchant && a.enchant != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td>' + a.enchant.title + '</td><td class="red" align="right">' + a.enchant.value + '</td></tr>';
        bg = !bg;
    }
    if (a.enchant_mod && a.enchant_mod != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td>' + a.enchant_mod.title + '</td><td class="red" align="right">' + a.enchant_mod.value + '</td></tr>';
        bg = !bg;
    }
    if (a.oprava && a.oprava != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td>' + a.oprava.title + '</td><td class="red" align="right">' + a.oprava.value + '</td></tr>';
        bg = !bg;
    }
    if (a.symbol && a.symbol != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td>' + a.symbol.title + '</td><td class="red" align="right">' + a.symbol.value + '</td></tr>';
        bg = !bg;
    }
    if (a.chars && a.chars != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td>' + a.chars.title + '</td><td class="red" align="right">' + a.chars.value + '</td></tr>';
        bg = !bg;
    }

    for(sc = 1; sc <= 3; sc++){
        try{
            if(a['socket_a_' + sc]['title'] == 'undefined' || !a['socket_a_' + sc]['title'] || a['socket_a_' + sc]['title'] == '') continue;
            content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td>' + a['socket_a_' + sc]['title'] + '</td><td class="red" align="right">' + a['socket_a_' + sc]['value'] + '</td></tr>';
            bg = !bg;
        }catch(e){}
    }

    if (a.set && a.set != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td>' + a.set.title + '</td><td class="red" align="right">' + a.set.value + '</td></tr>';
        bg = !bg;
    }
    if (a.change && a.change != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td colspan="2" class="grnn b">' + a.change + '</td></tr>';
        bg = !bg;
    }
    if (a.nogive && a.nogive != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td colspan="2" class="redd b">' + a.nogive + '</td></tr>';
        bg = !bg;
    }
    if (a.clan_thing && a.clan_thing != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td colspan="2" class="violet b">' + a.clan_thing + '</td></tr>';
        bg = !bg;
    }
    if (a.boe && a.boe != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td colspan="2" class="redd b">' + a.boe + '</td></tr>';
        bg = !bg;
    }
    if (a.noweight && a.noweight != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td colspan="2" class="grnn b">' + a.noweight + '</td></tr>';
        bg = !bg;
    }
    if (a.nosell && a.nosell != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td colspan="2" class="dark b">' + a.nosell + '</td></tr>';
        bg = !bg;
    }
    if (a.nofreeze && a.nofreeze != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td colspan="2" class="dark b">' + a.nofreeze + '</td></tr>';
        bg = !bg;
    }
    if (a.cant_broken && a.cant_broken != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td colspan="2" class="dark b"><img src="images/tbl-shp_item-iznos.gif" width="11" height="10" align="absmiddle">' + a.cant_broken + '</td></tr>';
        bg = !bg;
    }
    if (a.cant_crushed && a.cant_crushed != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td colspan="2" class="dark b"><img src="images/tbl-shp_item-iznos.gif" width="11" height="10" align="absmiddle">' + a.cant_crushed + '</td></tr>';
        bg = !bg;
    }

    if (a.cansmol && a.cansmol != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td colspan="2" class="b" style="color: #008080">' + a.cansmol + '</td></tr>';
        bg = !bg;
    }
    if (a.note && a.note != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td colspan="2">' + a.note + '</td></tr>';
        bg = !bg;
    }
    if (a.engrave && a.engrave != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td colspan="2">' + a.engrave + '</td></tr>';
        bg = !bg;
    }
    if (a.desc && a.desc != undefined) {
        content += '<tr class="skill_list ' + (bg ? 'list_dark' : '') + '"><td colspan="2">' + a.desc + '</td></tr>';
        bg = !bg;
    }
    content += '</table>';
    content += '</td><td class="aa-r" style="padding:0px"></td></tr>';
    content += '<tr><td class="aa-bl"></td><td class="aa-b"><img src="images/d.gif" width="1" height="5"></td><td class="aa-br"></td></tr>';
    content += '</table>';

    return content;
}

function renderAchievementAlt(id) {
    var a = art_alt[id];
    var bg = true;
    var i = 0;
    var content = '';

    content += '<table width="300" border="0" cellspacing="0" cellpadding="0" class="aa-table">';
    content += '<tr><td width="14" class="aa-tl"><img src="/images/d.gif" width="14" height="24"><br></td>';
    content += '<td class="aa-t" align="center" style="vertical-align:middle"><b>' + a.title + '</b></td>';
    content += '<td width="14" class="aa-tr"><img src="/images/d.gif" width="14" height="24"><br></td></tr>';
    content += '<tr class="bg_alt2"><td class="aa-l" style="padding:0;"></td><td class="bg_alt3" style="padding:0;">';
    content += '<table width="275" style=" margin: 3px" border="0" cellspacing="0" cellpadding="0"><tr>';
    content += '<td align="center" valign="top" width="60">';
    content += '<table width="60" height="60" cellpadding="0" cellspacing="0" border="0" style="margin: 2px" background="' + a.picture + '"><tr><td valign="bottom">';
    content += '&nbsp;';
    content += '</td></tr></table>';
    content += '</td><td>';
    content += '<div>' + a.weight.title + ' <img src="/images/achievement_icon.gif" width="11" height="10" align="absmiddle"> <b class="red">' + a.weight.value + '</b></div>';

    if (a.group && a.group != undefined) {
        content += '<div>' + a.group.title + ' <b class="red">' + a.group.value + '</b></div>';
    }

    content += '</td></tr></table>';
    if (a.description && a.description != undefined) {
        content += '<table width="100%" cellpadding="0" cellspacing="0" border="0">';
        content += '<tr class="skill_list list_dark2"><td colspan="2">' + a.description + '</td></tr>';
        bg = false;
        content += '</table>';
    }
    content += '</td><td class="aa-r" style="padding:0px"></td></tr>';
    content += '<tr><td class="aa-bl"></td><td class="aa-b"><img src="/images/d.gif" width="1" height="5"></td><td class="aa-br"></td></tr>';
    content += '</table>';

    return content;
}

function renderAchievementAltCompare(id) {
    var a = art_alt[id];
    var bg = true;
    var i = 0;
    var content = '';

    content += '<table width="300" border="0" cellspacing="0" cellpadding="0" class="aa-table">';
    content += '<tr><td width="14" class="aa-tl"><img src="/images/d.gif" width="14" height="24"><br></td>';
    content += '<td class="aa-t" align="center" style="vertical-align:middle"><b style="color:#6c382c; font-size: 13px">' + a.title + '</b></td>';
    content += '<td width="14" class="aa-tr"><img src="/images/d.gif" width="14" height="24"><br></td></tr>';
    content += '<tr class="bg_alt2"><td class="aa-l" style="padding:0;"></td><td class="bg_alt3" style="padding:0;">';
    content += '<table width="275" style=" margin: 3px" border="0" cellspacing="0" cellpadding="0">';
    content += '<tr><td align="center" valign="top"><div style="width: 60px; height: 60px; margin: 0 auto; background:url(' + a.picture + ');"></div></td></tr>';
    content += '<tr><td align="left" valign="top"><b class="redd">' + a.labels[0] + ': </b>' + a.group.value + '</td></tr>';
    content += '<tr><td align="left" valign="top"><b class="redd">' + a.labels[1] + ': </b>' + a.description + '</td></tr></table>';
    content += '</td><td class="aa-r" style="padding:0px"></td></tr>';
    content += '<tr><td class="aa-bl"></td><td class="aa-b"><img src="/images/d.gif" width="1" height="5"></td><td class="aa-br"></td></tr>';
    content += '</table>';

    return content;
}

function renderBotAlt(id) {
    var a = art_alt[id];
    var bg = true;
    var i = 0;

    var content = [
        '<table width="200" border="0" cellspacing="0" cellpadding="0" class="aa-table">',
        '<tr>',
        '<td width="14" class="aa-tl"><img src="/images/d.gif" width="14" height="24"><br></td>',
        '<td class="aa-t" align="center" style="vertical-align: middle;"><b>' + a.title + '</b></td>',
        '<td width="14" class="aa-tr"><img src="/images/d.gif" width="14" height="24"><br></td>',
        '</tr>',
        '<tr class="bg_alt2">',
        '<td class="aa-l" style="padding: 0;"></td>',
        '<td class="bg_alt3" style="padding: 0;">',
        '<table width="175" style="margin: 3px" border="0" cellspacing="0" cellpadding="0">',
        '<tr>',
        '<td align="center">',
        '<img src="' + a.picture + '" alt="" width="170" />',
        '</td>',
        '</tr>',
        '<tr>',
        '<td style="padding: 5px;">' + a.description + '</td>',
        '</tr>',
        '</table>',
        '</td>',
        '<td class="aa-r" style="padding: 0;"></td>',
        '</tr>',
        '<tr>',
        '<td class="aa-bl"></td>',
        '<td class="aa-b"><img src="/images/d.gif" width="1" height="5"></td>',
        '<td class="aa-br"></td>',
        '</tr>',
        '</table>'
    ].join('');

    return content;
}
function updateBag() {
    var win = window
    try {win = dialogArguments ?  dialogArguments.win || dialogArguments : window} catch(e) {}
    while (win.opener) win = win.opener;
    if (win.closed) return false;

    try{
        var win_main = win._top().frames['main_frame'].frames['main']
        if(win_main.is_userphp) {
            win_main.location.href = win_main.urlMODE + '&update_swf=1'
            return true;
        }
    }
    catch (e) {}
    return false;
}
function updateSwf(params) {
    var win = window
    try {win = dialogArguments ?  dialogArguments.win || dialogArguments : window} catch(e) {}
    while (win.opener) win = win.opener;
    if (win.closed) return;

    var url = 'main_iframe.php?mode=update_swf';
    if (!params) return;
    try {
        for (i in params) {
            url += '&tar[]='+i;
            if (params[i]) url += '&add['+i+']='+escape(params[i]);
        }
        win.top.frames['main_frame'].frames['main_hidden'].location.href = url;
    }
    catch (e) {}
}

function updateHP() {
    updateSwf({'lvl': '' ,'items': ''});
}
function fightRedirect(fight_id, cd) {
    if (!cd || isNaN(cd)) cd = false;
    else {
        setTimeout(function(){
            fightRedirect(fight_id);
        }, cd);
        return;
    }
    var rnd = Math.floor(Math.random()*1000000000);
    var url = 'fight.php?'+rnd;
    if (top.__lastFightId && (top.__lastFightId >= fight_id)) return;
    top.__lastFightId = fight_id;
//	top.frames["main_frame"].frames["main"].location.href = url;
    tProcessMenu('b06', {url: url, lock: true, force: true});
}

function fightFinished() {
    try {
        tUnlockFrame();
        tUnsetFrame('main');
        top.frames["main_frame"].frames["main"].__fight_php__ = false;
        updateHP();
    } catch (e) {}
}

function updatePartyLoot() {
    try {
        top.frames['main_frame'].frames['main_hidden'].location.href = 'main_iframe.php?mode=update_party';
    } catch (e) {};
}

function fightUpdateLog(ctime, nick1, level1, nick2, level2, code, i1, i2, i3, s1) {
    try {
        top.frames['main_frame'].frames['main'].fightUpdateLog(ctime, nick1, level1, nick2, level2, code, i1, i2, i3, s1);
    } catch (e) {};
}

function resurrect(paidResurrect) {
    addurl = '';
    if (paidResurrect) {
        addurl = '&in[paidResurrect]=1'
    }
    //top.frames["main_frame"].frames["main"].location.href = 'action_run.php?code=RESURRECT&url_success=area.php&url_error=area.php'+addurl;
    tProcessMenu('b06', {url: 'action_run.php?code=RESURRECT&url_success=area.php&url_error=area.php'+addurl});
}

// =======================================================================================

function js_money_input_assemble(id_prefix) {
    var m1 = 0.00;
    try{ m1=gebi(id_prefix+'1').value; } catch (e) {}
    var m2 = gebi(id_prefix+'2').value;
    var m3 = gebi(id_prefix+'3').value;

    if(m1 > 0.00 && m1.match(/[^0-9.]/)) m1 = m1.replace(/[^0-9].*$/, '');
    if (m2.match(/[^0-9.]/)) m2 = m2.replace(/[^0-9].*$/, '');
    if (m3.match(/[^0-9.]/)) m3 = m3.replace(/[^0-9].*$/, '');
    v = (m1 > 0.00 ? m1/100.0 : 0) + m2*1.0 + m3*100.0;
    res = (isNaN(v) || v <= 0) ? 0 : (1.0 * (1.0*v).toFixed(2)).toFixed(2);
    return res*1.0;
}

function js_money_input_fill(id_prefix, amount) {
    var m1 = gebi(id_prefix+'1');
    var m2 = gebi(id_prefix+'2');
    var m3 = gebi(id_prefix+'3');

    var str = ' ';
    var t=[];
    amount = amount * 100;
    for (i = 0; i < 2; i++) {
        t[i] = (amount % 100);
        amount = (amount - t[i]) / 100;
    }
    t[2] = amount;
    try{m1.value = t[0].toFixed(0);}catch (e) {}
    m2.value = t[1].toFixed(0);
    m3.value = t[2].toFixed(0);
}

// ========= swf data transfer functions ===============================================================

function getSWF(name) {
    var win = window;
    var mainFrame = win._top().frames.main_frame;
    try {win = dialogArguments || window} catch(e) {}
    //while (win.opener) win = win.opener;
    if (win.closed) return;
    win = window;

    switch (name) {
        case 'items_right':
            return mainFrame;
            break;
    }

    switch (name) {
        case 'top_mnu':
        case 'lvl':
        case 'items':
        case 'dialog':
            win = mainFrame;
            break;
        case 'game':
        case 'mem':
        case 'area':
        case 'instance':
        case 'wheel_fortune':
        case 'estate':
            win = mainFrame ? mainFrame.frames.main : null;
            break;
        case 'inventory':
        case 'magic':
        case 'cube':
            win = mainFrame ? mainFrame.frames.main : null;
            break;
        case 'world':
            win = win._top().opened_windows['world_map'];
            name = 'game';
            break;
    }
    if (navigator.appName.indexOf("Microsoft") != -1) {
        return win ? win[name] : null;
    } else {
        return win ? win.document[name] : null;
    }
}

function swfObject(tar, object) {
    var swf;
    try {
        swf = getSWF(tar);
        swf.swfObject(object);
        return true;
    } catch (e) {
        if (swf && swf.swfObject) {
            console_log("swfObject ERROR: tar=" + tar + ", error=" + e);
        }
    }
    return false;
}

function swfTransfer(name,tar,data) {
    try {
        switch (tar) {
            case 'items':
            case 'items_right':
                trig_swf_data(tar, name, data);
                break;
            case 'js':
                trig_js_data(name,data);
                break;
            default:
                getSWF(tar).swfData(name,data);
        }
        return true;
    } catch (e) {
        var swf = getSWF(tar);
        if (swf && swf.swfData) {
            console_log("swfData ERROR: name=" + name + ", tar=" + tar + ", method=" + data.split('@')[0] + ", error=" + e);
        }
    }
    return false;
}

function swfTransferEx(name, tar, data) {
    try {
        var vars = [];
        var obj = {};
        console.log('swfTransfer >>> name=' + name + ' tar=' + tar + ' min_data=' + data.slice(0, 40));
        switch (tar) {
            case 'inventory':
                vars = data.split("@", 2);
                obj = { 'user|view': JSON.parse(vars[1]) };
                swfObject('inventory', obj);
                break;
            case 'area':
                vars = data.split("@", 2);
                obj = { 'common|area_conf': JSON.parse(vars[1]) };
                swfObject('area', obj);
                break;
            case 'lvl':
                switch (name) {
                    case 'game':
                        getSWF(tar).swfData(name, data);
                        break;
                    default:
                        vars = data.split("@", 2);
                        obj = { 'user|view': JSON.parse(vars[1]) };
                        swfObject('lvl', obj);
                        break;
                }
                break;
            case 'event':
                vars = data.split("@", 2);
                obj = { 'common|event_conf': JSON.parse(vars[1]) };
                swfObject('area', obj);
                break;
            case 'game':
            case 'fight':
                vars = data.split("@", 2);
                switch (vars[0]) {
                    case 'EffList':
                        getSWF(tar).swfData(vars[0], vars[1]);
                        break;
                    default:
                        obj = { 'fight|count': JSON.parse(vars[1]) };
                        swfObject('area', obj);
                        break;
                }
                break;
            case 'items':
            case 'items_update':
                /*
                vars = data.split("@", 2);
                obj = { 'user|effects': JSON.parse(vars[1]) };
                swfObject('items', obj);
                 */
                swfTransfer(name, tar, data);
                break;
            case 'items_right':
                vars = data.split("@", 2);
                obj = { 'user|mount': JSON.parse(vars[1]) };
                var user_view = JSON.parse(vars[1]);
                if (typeof(user_view['is_mount']) != "undefined" && typeof(user_view['mount_id']) != "undefined") {
                    swfObject('items_right', {
                        'user|mount': {
                            'status': 100,
                            'is_mount': user_view['is_mount'],
                            'mount_id': user_view['mount_id']
                        }
                    });
                }else{
                    swfObject('items_right', obj);
                }
                break;
            default:
                getSWF(tar).swfData(name, data);
                break;
        }
        return true;
    } catch (e) { console.log(e); }
    return false;
}

/*
function swfTransfer(name,tar,data) {

    try {
        if (tar == "fight") {
            var vars = data.split("@", 2);
            var obj = { 'fight|count': JSON.parse(vars[1]) };
            swfObject('area', obj);
        } else if (tar == "items_update") {
            var vars = data.split("@", 2);
            var obj = { 'user|effects': JSON.parse(vars[1]) };
            console.log(obj);
            swfObject('items', obj);
        } else {
            getSWF(tar).swfData(name, data);
        }

        try{
            if(name == "mem" && tar == "game" && data == "FullReq@null" && !_top().fight_pass_turn){
                _top().fight_pass_turn = true;
                _top().frames['main_frame'].frames['main'].pass_turn();
            }
        }catch (e){};

        return true;
    } catch (e) {
        var swf = getSWF(tar);
        if (swf && swf.swfData) {
            console_log("swfData ERROR: name=" + name + ", tar=" + tar + ", method=" + data.split('@')[0] + ", error=" + e);
        }
    }
    return false;

    /!*
    try {
        try{ //Focus Fight.swf
            if(name == 'item' && tar == 'game' && data.indexOf('useEffect') !== -1){
                _top().frames['main_frame'].frames['main'].document.getElementById("game").focus();
            }
        }catch(s){}
        //console.log('name=' + name + " tar=" + tar + " data=" + data);
        getSWF(tar).swfData(name,data);

        try{
            if(name == "mem" && tar == "game" && data == "FullReq@null" && !_top().fight_pass_turn){
                _top().fight_pass_turn = true;
                _top().frames['main_frame'].frames['main'].pass_turn();
            }
        }catch (e){};

        return true;
    }
        catch (e) {};
    return false;
    *!/
}*/

function areaSwfReload() {
    var area_frame = _top().frames.main_frame.frames.main;
    var main = _top().frames.main_frame;
    if (!main) return false;
    if ($('embed[name="area"], object[name="area"], embed[name="instance"], object[name="instance"]', area_frame.document).length) {
        area_frame.location = location.protocol + '//' + location.host + location.pathname;
    }
}

function moveMedals(shift) {
    if (((shift < 0) && (position > 0)) || ((shift > 0) && (medals[position + MedalsOnPage]))) {
        position += shift;
        showMedals();
    }
}
function showMedals() {
    for(i=0;i<MedalsOnPage;i++) {
        document.getElementById('medal_' + i).innerHTML = medals[i + position] ? medals[i + position] : '&nbsp;';
    }
    if (position > 0) {
        document.getElementById('medal_l').src = "/images/medal_l_act.gif";
        document.getElementById('medal_l').style.cursor = "pointer";
    } else {
        document.getElementById('medal_l').src = "/images/medal_l.gif";
        document.getElementById('medal_l').style.cursor = "default";
    }
    if (medals[position + MedalsOnPage]) {
        document.getElementById('medal_r').src = "/images/medal_r_act.gif";
        document.getElementById('medal_r').style.cursor = "pointer";
    } else {
        document.getElementById('medal_r').src = "/images/medal_r.gif";
        document.getElementById('medal_r').style.cursor = "default";
    }
    return 1;
}

function ShowDiv(obj, evnt, show, param) {
    var div = gebi(obj.getAttribute('div_id'));
    if (!div) return;
    if (show == 2) {
        document.onmousemove=function(e) {artifactAlt(obj, e||event, 1)}
        div.style.display = 'block';
    }
    if (!show) {
        div.style.display = 'none';
        document.onmousemove=function(){}
        return;
    }
    var l = t = 0;

    try{
        l = (param.l ? param.l : 0);
        t = (param.t ? param.t : 0);
    }catch(e){}

    var ex = evnt.clientX + document.body.scrollLeft + l;
    var ey = evnt.clientY + document.body.scrollTop + t;

    var x = evnt.clientX + div.offsetWidth > document.body.clientWidth - 7 ? ex - div.offsetWidth - 10 : ex + 10;
    var y = evnt.clientY + div.offsetHeight > document.body.clientHeight - 7 ? ey - div.offsetHeight - 10 : ey + 10;

    if (x < 0 ) {
        x = ex - div.offsetWidth/2
    }
    if (x < 7 ) {
        x = 7
    }
    if (x > document.body.clientWidth - div.offsetWidth - 7) {
        x= document.body.clientWidth - div.offsetWidth - 7
    }

    div.style.left = x;
    div.style.top = y;
}

function refreshEvent (id) {document.location.href = 'user_event.php?mode=events&event_id='+id;}
function enterGreatFights () {document.location.href = 'area_fights.php?mode=great';}

function common_is_email_valid(email,all) {
    if (!email && !all) {
        return true;
    }
    var re = '';
    if (all) {
        re = /^([A-z0-9_\-]+\.)*[A-z0-9_\-]+@([A-z0-9][A-z0-9\-]*[A-z0-9]\.)+[A-z]{2,4}$/i;
    } else {
        re = /^([A-z0-9_\-]+\.)*[A-z0-9_\-]+(@)?([A-z0-9][A-z0-9\-]*[A-z0-9]\.)*(\.)?[A-z]{0,4}$/i;
    }
    if (!re.test(email)) {
        return false;
    }
    return true;
}

function petAlt(obj, evnt, show) {
    var div = gebi(obj.getAttribute('div_id'));
    if (!div) return;
    var act1 = obj.getAttribute('act1');
    var act2 = obj.getAttribute('act2');
    if (show == 2) {
        document.onmousemove=function(e) {petAlt(obj, e||event, 1)}
        div.style.display = 'block';
        if (act1 || act2) {
            _background(obj, (_top().locale_path + "images/itemact-"+ act1) + (act2 +".gif"));
        }
    }
    if (!show) {
        if (act1 || act2) {
            _background(obj, 'images/d.gif');
        }
        div.style.display = 'none';
        document.onmousemove=function(){}
        return;
    }

    var ex = evnt.clientX + document.body.scrollLeft;
    var ey = evnt.clientY + document.body.scrollTop;

    if (act1 || act2) {
        obj.style.cursor = 'default'
        obj.onclick = (act1 != 0 ? function(){try{petAct(obj, act1)}catch(e){}} : function(){showPetInfo(obj.getAttribute('aid'), obj.getAttribute('art_id'))});
        _background(obj, (_top().locale_path + "images/itemact-"+ act1) + (act2 +".gif"));
        var coord = getCoords(obj)
        var cont = gebi("item_list")
        var rel_x = (ex + cont.scrollLeft - coord.l)
        if (rel_x >= 40) {
            var rel_y = (ey + cont.scrollTop - coord.t)
            if (rel_y < 20) {
                obj.onclick = function(){showPetInfo(obj.getAttribute('aid'))}
                _background(obj, _top().locale_path + 'images/itemact_info' + act2 +'.gif');
                try{obj.style.cursor = 'hand'} catch(e){}
                try{obj.style.cursor = 'pointer'} catch(e){}
            }
            if (act2 != 0 && rel_y >= 40) {
                obj.onclick = function(){try{petAct(obj, act2)}catch(e){}}
                _background(obj, _top().locale_path + 'images/itemact_drop' + act2 +'.gif');
                try{obj.style.cursor = 'hand'} catch(e){}
                try{obj.style.cursor = 'pointer'} catch(e){}
            }
        }
    }
    var x = evnt.clientX + div.offsetWidth > document.body.clientWidth - 7 ? ex - div.offsetWidth - 10 : ex + 10;
    var y = evnt.clientY + div.offsetHeight > document.body.clientHeight - 7 ? ey - div.offsetHeight - 10 : ey + 10;

    if (x < 0 ) {
        x = ex - div.offsetWidth/2
    }
    if (x < 7 ) {
        x = 7
    }
    if (x > document.body.clientWidth - div.offsetWidth - 7) {
        x= document.body.clientWidth - div.offsetWidth - 7
    }

    div.style.left = x;
    div.style.top = y;
}

function fb_feed(lock_id,feed_id, data) {
    try{
        _top().frames['main_frame'].wall(lock_id,feed_id, data)
    } catch(e) {}
}


function updateMount(mount_id) {
    _top().frames['main_frame'].mountID = mount_id;
}

function switchSkillPanel(current, list) {
    for (i = 0; i <= list.length; ++i) {
        var item = gebi(list[i]);
        var link = gebi(list[i] + '_lnk');
        var left = gebi(list[i] + '_left');
        var right = gebi(list[i] + '_right');
        var bg = gebi(list[i] + '_bg');
        if (item) item.style.display = 'none';
        if (link) link.className = 'tbl-shp_menu-link_inact';
        if (left) left.src = 'images/tbl-shp_menu-left-inact.gif';
        if (right) right.src = 'images/tbl-shp_menu-right-inact.gif';
        if (bg) bg.className = 'tbl-shp_menu-center-inact';
    }
    for (i = 0; i <= current.length; ++i) {
        var item = gebi(current[i]);
        var link = gebi(current[i] + '_lnk');
        var left = gebi(current[i] + '_left');
        var right = gebi(current[i] + '_right');
        var bg = gebi(current[i] + '_bg');
        if (item) item.style.display = '';
        if (link) link.className = 'tbl-shp_menu-link_act';
        if (left) left.src = 'images/tbl-shp_menu-left-act.gif';
        if (right) right.src = 'images/tbl-shp_menu-right-act.gif';
        if (bg) bg.className = 'tbl-shp_menu-center-act';
    }
}

function getKeyCode(e) {
    return (window.event) ? event.keyCode : e.keyCode;
}

function toggle_visibility(id) {
    var obj = gebi(id);
    if (obj) {
        obj.style.display = obj.style.display=='' ? 'none' : '';
        return obj.style.display=='none';
    }
    return false;
}

function explode(str, delimeter) {
    return str ? str.split(delimeter ? delimeter : '') : [];
}

function implode(array, delimeter) {
    var str = '';
    if(array) {
        var array_length = array.length ? array.length-1 : 0;
        for(var id in array)
            str = str + array[id] + (array_length-- ? delimeter : '');
    }
    return str;
}

// Применительно к объектам Array
// IE сцуко не поддерживает Array::indexOf
function indexOf(arr, value) {
    for(var id in arr)
        if(arr[id] == value)
            return id;
    return -1;
}

function getXmlHttp(){
    try {
        return new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
        try {
            return new ActiveXObject("Microsoft.XMLHTTP");
        } catch (ee) {
        }
    }
    if (typeof XMLHttpRequest!='undefined') {
        return new XMLHttpRequest();
    }
}

function getUrl(url, cb) {
    var xmlhttp = getXmlHttp();
    xmlhttp.open("GET", url);
    if (cb) {
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4) {
                cb(
                    xmlhttp.status,
                    xmlhttp.getAllResponseHeaders(),
                    xmlhttp.responseText
                );
            }
        }
    }
    xmlhttp.send(null);
}

function chat_update_party(f_p_c){
    _top().frames['chat'].chat_users_party_control(undefined, f_p_c);
}

function doPost(actionUrl, params) {
    var newF = document.createElement("form");
    newF.action = actionUrl;
    newF.method = 'POST';
    var parms = params.split('&');
    for (var i=0; i<parms.length; i++) {
        var pos = parms[i].indexOf('=');
        if (pos > 0) {
            var key = parms[i].substring(0,pos);
            var val = parms[i].substring(pos+1);
            var newH = document.createElement("input");
            newH.name = key;
            newH.type = 'hidden';
            newH.value = val;
            newF.appendChild(newH);
        }
    }
    document.getElementsByTagName('body')[0].appendChild(newF);
    newF.submit();
}

//document.write('<script src="\/js\/console_log.js"><\/' + 'script>');

function updateAltEffects(effects) {
    _top().frames['main_frame'].temp_effects = effects;
}


function moveToClanBattleLobby() {
    try {
        top.frames['main_frame'].frames['main'].location.href = 'clan_battle_conf.php?clan_battle_request_confirm=1';
    }
    catch (e) {}
}

function tutorialHook(step, end){
    top.frames['main_frame'].tutorialShow(step);
    if (end) {
        top.frames['main_frame'].tutorialEnd();
    }
}

function getClientWidth()
{
    return document.compatMode=='CSS1Compat' && !window.opera?document.documentElement.clientWidth:document.body.clientWidth;
}

function getClientHeight()
{
    return document.compatMode=='CSS1Compat' && !window.opera?document.documentElement.clientHeight:document.body.clientHeight;
}

function chat_add_artifact_macros(id, end_space) {
    return chat_add_macros('artifact_'+id, end_space);
}

function chat_add_macros(name, end_space) {
    if (end_space === undefined)
        end_space = true;
    var text = '[['+name+']]';
    if (end_space)
        text += ' ';
    var win = window;
    if (!win._top().frames['chat']) return false;
    win._top().frames['chat'].chatAddToMsg(text);
    return true;
}
function chat_add_message(msg) {
    w_frm_chat = window.top.frames['chat'];
    w_frm_chat.chatTextHtml += msg+'<msg_end>';
    w_frm_chat.chatUpdateText();
    w_frm_chat.chatTextHtml = '';
}

function change_select_color(element) {
    var option = element.options[element.selectedIndex];

    if (option.style.color != "") {
        element.style.color = option.style.color;
    } else {
        element.style.color = "";
    }
}

function check_select_color() {
    var change_select = gebi('change_select_id');

    if (change_select) {
        change_select_color(change_select);
    }
}

function user_show_prof_bag(show) {
    var pr_bag_tab = _top().frames['main_frame'].frames['main'].gebi("tab_pr_bag");
    if (show) pr_bag_tab.style.display = 'block';
    else pr_bag_tab.style.display = 'none';
}

var client_exchange_store;

function isInClient() {
    return document.cookie.indexOf("isInClient") > -1;
}

function clientExchangePut(text) {
    if (!isInClient()) return false
    if(!client_exchange_store) {
        client_exchange_store = new Array();
    }
    return client_exchange_store.push(text);
}

function clientExchangeGet() {
    if (!isInClient()) return false;
    if(client_exchange_store && client_exchange_store.length) {
        return client_exchange_store.shift();
    }
    return null;
}

function vardump (object, maxdepth) {
    maxdepth = maxdepth || 50;
    switch (typeof(object)) {
        case 'boolean':
            return object ? 'true' : 'false';
            break;

        case 'number':
            return object.toString();
            break;

        case 'string':
            return '"' + object + '"';
            break;

        case 'object':
            if (maxdepth <= 0) {
                return "...";
            }

            maxdepth--;

            var ret = [];
            var isArray = object instanceof Array;
            for (var key in object) {
                isArray ? ret.push(vardump(object[key])) : ret.push('"'+key+'"' + ': ' + vardump(object[key], maxdepth));
            }

            maxdepth++;

            return (isArray ? '[' : '{') + ret.join(', ') + (isArray ? ']' : '}');
            break;

        case 'undefined':
            return 'undefined';
            break;

        case 'function':
            return 'function';
            break;
    }

    return '?';
};

function clientReceive(data, swf_name) {
    _top().frames['chat'].clientCallBack($.parseJSON(data));
}

function isInInstance() {
    return _top().frames['chat'].checkInInstance();
}


function systemConfirm(ms,title,obj,func){
    if (title){
        var systemConfirm_title = gebi('systemConfirm_title');
        systemConfirm_title.innerHTML = title;
    }
    if (ms){
        var confirm_ms = gebi('confirm_ms');
        confirm_ms.innerHTML = '<b>'+ms+'</b>';
    }

    var div = gebi('systemConfirm_div');
    div.style.top = document.body.scrollTop + (document.body.clientHeight - div.offsetHeight*2)/2;
    div.style.left = document.body.scrollLeft + ((document.body.clientWidth - div.offsetWidth)/2);
    div.style.display = 'block';
    div.style.top = document.body.scrollTop + (document.body.clientHeight - div.offsetHeight*2)/2;
    div.style.left = document.body.scrollLeft + ((document.body.clientWidth - div.offsetWidth)/2);

    var close_div = top.gebi('systemConfirm_close_div');
    close_div.style.width = document.body.clientWidth;
    close_div.style.height = document.body.clientHeight;
    close_div.style.display = 'block';

    var  btnOk = gebi("btnOk");
    btnOk.onclick = function () {
        if(!func) {
            if (obj.href) {
                location.href = obj.href;
            }
            else if (obj.submit) {
                obj.submit();
            }
        }
        else {
            func();
        }
        div.style.display = 'none';
        close_div.style.display = 'none';
        return true;
    };

    $('.btn_sys_confirm_close').click(function(){
        div.style.display = 'none';
        close_div.style.display = 'none';
    });

    $('.popup_global_close_btn').click(function(){
        div.style.display = 'none';
        close_div.style.display = 'none';
    });

    gebi('systemConfirm_close_div').onclick = function () {
        div.style.display = 'none';
        close_div.style.display = 'none';
        return true;
    };

    return false;
}

function hasClass(el, c) {
    return el.className.match(new RegExp('(^|\\s+)'+c+'($|\\s+)'));
}

function addClass(el, c) {
    if (!hasClass(el, c)) {
        el.className += ' '+c;
    }
}

function removeClass(el, c) {
    if (hasClass(el, c)) {
        el.className = el.className.replace(new RegExp('(^|\\s+)'+c+'($|\s+)', 'gi'), ' ').replace(/\s+/g, ' ').replace(/(^|$)/g, '');
    }
}

function backpack_diff(data) {
    var art_pfx = 'AA_';
    var frame_pfx = 'user_iframe_';
    var frame_def = 3;

    if (!top.frames['main_frame'].gebi('backpack') || top.frames['main_frame'].gebi('backpack').getAttribute('data-loaded') != 1) {
        return;
    }

    var f = top.frames['main_frame'].frames['backpack'];
    var uif = [];
    for (var i = 1; i < 6; i++) {
        if (f.gebi(frame_pfx + i).getAttribute('data-loaded') == 1) {
            uif.push(i);
        }
    }
    if (data.info && data.info.amount && data.info.amount_max) {
        for (var i = 0; i < uif.length; i++) {
            f.frames[frame_pfx + uif[i]].gebi('artifact_amount').innerHTML = data.info.amount;
            f.frames[frame_pfx + uif[i]].gebi('artifact_amount_max').innerHTML = data.info.amount_max;
        }
    }
    if (data.deleted && data.deleted.length > 0) {
        for (var i = 0; i < data.deleted.length; i++) {
            for (var j = 0; j < uif.length; j++) {
                if (f.frames[frame_pfx + uif[j]].gebi(art_pfx + data.deleted[i])) {
                    f.frames[frame_pfx + uif[j]].gebi('item_list').removeChild(f.frames[frame_pfx + uif[j]].gebi(art_pfx + data.deleted[i]));
                    if (f.frames[frame_pfx + uif[j]].gebi('item_list').children.length == 0) {
                        f.frames[frame_pfx + uif[j]].gebi('item_list_empty').style.display = 'block';
                        f.frames[frame_pfx + uif[j]].gebi('item_list').style.display = 'none';
                    } else {
                        f.frames[frame_pfx + uif[j]].gebi('item_list_empty').style.display = 'none';
                        f.frames[frame_pfx + uif[j]].gebi('item_list').style.display = 'block';
                    }
                }
            }
        }
    }
    if (data.changed) {
        for (var i in data.changed) {
            var uif_id = f.backpack_groups[data.changed[i].kind_id] ? f.backpack_groups[data.changed[i].kind_id] : frame_def;
            if (f.gebi(frame_pfx + uif_id).getAttribute('data-loaded') != 1) {
                continue;
            }
            var el = f.frames[frame_pfx + uif_id].gebi(art_pfx + i);
            if (el && f.frames[frame_pfx + uif_id].art_alt[art_pfx + i]) {
                el.getElementsByTagName('td')[0].setAttribute('cnt', data.changed[i].cnt ? data.changed[i].cnt : 0);
                if (data.changed[i].cnt && data.changed[i].cnt > 1) {
                    el.getElementsByTagName('td')[0].innerHTML = '<div class="bpdig">'+data.changed[i].cnt+'</div>';
                } else {
                    el.getElementsByTagName('td')[0].innerHTML = '&nbsp;';
                }
            } else {
                if (f.gebi(frame_pfx + uif_id).getAttribute('data-loaded') != 1) {
                    continue;
                }
                var ul = f.frames[frame_pfx + uif_id].gebi('item_list');
                if (parseInt(data.changed[i].slot_num) < 2) {
                    var li = document.createElement('li');
                    li.id = 'AA_'+data.changed[i].id;
                    li.setAttribute('aid', 'art_'+data.changed[i].id);
                    li.setAttribute('slot_num', data.changed[i].slot_num);
                    li.innerHTML = html_artifact_slot(data.changed[i]);
                    ul.insertBefore(li, ul.children[0]);
                } else {
                    var ch = ul.children;
                    var prev_num;
                    for (var k = 0; k < ch.length; k++) {
                        var sn = ch[k].getAttribute('sn');
                        vardump(sn);
                        prev_num = k;
                        if (sn && sn > parseInt(data.changed[i].slot_num)) {
                            break;
                        }
                    }
                    var li = document.createElement('li');
                    li.id = 'AA_'+data.changed[i].id;
                    li.setAttribute('aid', 'art_'+data.changed[i].id);
                    li.setAttribute('slot_num', data.changed[i].slot_num);
                    li.innerHTML = html_artifact_slot(data.changed[i]);
                    if (prev_num == ul.children.length - 1) {
                        ul.appendChild(li);
                    } else {
                        ul.insertBefore(li, ul.children[prev_num]);
                    }
                }
                ul.style.display = 'block';
                f.frames[frame_pfx + uif_id].gebi('item_list_empty').style.display = 'none';
            }
            f.frames[frame_pfx + uif_id].art_alt[art_pfx + i] = data.changed[i];
        }
    }
    if (data.money) {
        for (var i = 0; i < uif.length; i++) {
            for (var j in data.money) {
                f.frames[frame_pfx + uif[i]].gebi('money-type-' + j).innerHTML = data.money[j].replace(/&quot;/g, '"');
            }
        }
    }
}

function parse_str(str, array){	// Parses the string into variables
    //
    // +   original by: Cagri Ekin
    // +   improved by: Michael White (http://crestidg.com)

    var glue1 = '=';
    var glue2 = '&';

    var array2 = str.split(glue2);
    var array3 = [];
    for(var x=0; x<array2.length; x++){
        if (!array2[x]) continue;
        var tmp = array2[x].split(glue1);
        array3[unescape(tmp[0])] = unescape(tmp[1] || '').replace(/[+]/g, ' ');
    }

    if(array){
        array = array3;
    } else{
        return array3;
    }
}

function html_button(title, param) {
    var html = '';

    param = param || {};

    var add = param.add || '';
    var addClassName = param.className || '';
    html += '<b class="butt1 pointer ' + addClassName+ '"><b>';
    html +=	'	<button class="butt1" ' + add + '>' + title + '</button>';
    html +=	'</b></b>';

    return html;
}

function updateEstate() {
    if (_top().frames['main_frame'] && _top().frames['main_frame'].frames['main'] && _top().frames['main_frame'].frames['main'].__estate_php__) {
        if (_top().iframe == 'main') {
            _top().frames['main_frame'].frames['main'].location.reload();
        } else {
            tUnsetFrame('main');
        }
    }
}

function estateReloadCurrentBuilding() {
    var estateSwf = _top().frames.main_frame.frames.main;
    if (!estateSwf) {
        return;
    }

    estateSwf = estateSwf.document.getElementById('estate');
    if (!estateSwf) {
        return;
    }

    try {
        estateSwf.updateCurrentBuilding();
    } catch (exception) {
    }
}

function reloadArea() {
    _top().frames['main_frame'].frames['main'].location.href = 'area.php';
}
function html_artifact_slot(data) {
    var pfx = 'AA_';
    var html = '';

    data._act1 = data._act1 ? data._act1 : 0;
    data._act2 = data._act2 ? data._act2 : 0;
    data._act3 = data._act3 ? data._act3 : 0;

    html += '<table width="60" height="60" cellpadding="0" cellspacing="0" border="0" style="float: left; margin: 1px" background="'+data.image+'">';
    html += '<tbody><tr>';
    html += '<td act1="'+data._act1+'" act2="'+data._act2+'" act3="'+data._act3+'" puton_confirm="'+data._puton+'" aid="'+data.id+'" cnt="'+(data.cnt && data.cnt > 1 ? data.cnt : 0)+'" div_id="'+pfx+data.id+'" onmouseover="artifactAlt(this,event,2)" onmouseout="artifactAlt(this,event,0)" valign="bottom" style="cursor: pointer; background-image: url(/images/d.gif); ">';
    if (data.cnt && data.cnt > 1) {
        html += '<div class="bpdig">'+data.cnt+'</div>';
    } else {
        html += '&nbsp;';
    }
    html += '</td>';
    html += '</tr></tbody>';
    html += '</table>';

    return html;
}

function tProcessMenu(par, opt) {
    _top().frames['main_frame'].processMenu(par, opt);
}

function tSetFrameData(frame, value) {
    if (!frame)
        return;
    try {
        _top().frames['main_frame'].gebi(frame).setAttribute('data-loaded', value);
    } catch (e) {}
}

function tUnsetFrame(frame, full) {
    if (!frame)
        return;
    try {
        if (full) {
            _top().frames['main_frame'].frames[frame].location.href = 'blank.html';
        }
        var obj = _top().frames['main_frame'].gebi(frame);
        obj.setAttribute('data-loaded', 0);
        obj.setAttribute('data-par', '');
    } catch (e) {}
}

function tLockFrame() {
    _top().iframe_locked = true;
}

function tUnlockFrame() {
    _top().iframe_locked = false;
}

function return_link(url) {
    try {
        if (_top().frames['main_frame'].frames['main'].__location__ || typeof url == 'undefined') {
            _top().tProcessMenu('b06');
        } else {
            _top().frames['main_frame'].frames[_top().iframe].location.href = url;
        }
    } catch(e) {}
}

window.last_top = false;
function _top() {
    if (window.last_top) return window.last_top;
    var p = window;
    while (true) {
        try {
            if (p.location.href.match(/main\.php/) || p.parent === p) { break;}
        } catch (e) {
            window.last_top = p;
            return p;
        }
        p = p.parent.window;
    }
    window.last_top = p;
    return p;
}
try {
    window.top = top = _top();
} catch (e) {}

window.close_ = window.close;
window.close = function(e, id) {
    var win = _top().window;
    if (win.js_popup && id) {
        win.destroyPopup(e, id);
    } else {
        win.close_();
    }
};

if (_top().js_popup) {
    window.open_ = window.open;
    window.open = function(url, name, params) {
        if (params) {
            var w = params.match(/width=(\d+),?/)[1];
            var h = params.match(/height=(\d+),?/)[1];
        }
        _top().createPopup({
//			title: name,
            iframe: {
                src: url,
                height: h||400,
                width: w
            }
        });
    };
}

function windowClose(event) {
    if (!event) event = null;
    _top().close(event, window.name);
}

function table_add_red_border(table) {
    $table = $(table);
    $table.find('td').each(function(i, el) {
        $el = $(el);
        if ($el.hasClass('tbl-shp-sml')) {
            $el.removeClass('tbl-shp-sml').addClass('tbl-shp-sml_0');
        } else if ($el.hasClass('tbl-shp-sides')) {
            $el.removeClass('tbl-shp-sides').addClass('tbl-shp-sides_0');
        }
    });
}

function add_green_animated_arrow(el, dont_remove) {
    var arrow = $('<div />').addClass('big_green_arrow');
    arrow.insertBefore(el);
    for (var i = 0; i < 3; i++) {
        arrow.animate({left: '-58px'}, 1000)
            .delay(100)
            .animate({left: '-48px'}, 800);
    }
    arrow.animate({left: '-58px'}, 800)
        .delay(100)
        .animate({left: '-48px'}, 800, false, function() {
            if (!dont_remove)
                arrow.remove();
        });
}


var onerror_limit = [];
function window_onerror(message, url, linenumber) {
    if (!_top().js_debug) return false;
    if (!url || !url.length) return false;
    if (url.search('resource:///') !== -1) return false;
    if (url.search('chrome://') !== -1) return false;
    if (url.search('dwar.') === -1 && url.search('rudt.') === -1) return false;
    js_error_log(message, linenumber, url);
    return false;
};

function js_error_log(message, linenumber, url) {
    url = url || document.location.href;
    var data = {
        message: message,
        url: url,
        linenumber: linenumber,
        user_agent: navigator.userAgent
    };
    if (_top().location.href.search('/admin/') !== -1) {
        data['admin'] = 1;
    }
    console_log('js_error_log', data);
    if (!_top().js_debug) return false;
    var now = time_current();
    for (var i in onerror_limit) {
        if (onerror_limit[i] <= now - 10) {
            onerror_limit.splice(i, 1);
        }
    }
    if (onerror_limit.length > 5) return false;
    onerror_limit.push(now);
    _top().$.post('/pub/js_error.php', data);
    return true;
};

if (_top().js_debug) {
    window.onerror = window_onerror;
}

function time_current() {
    return Math.round(((new Date()).getTime()) / 1000);
}

function htmlspecialchars(data) {
    data = typeof(data) == 'undefined' ? '' : data + '';
    return data
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
function countSymbols(e, field, infoBlock, infoLabel) {
    e = e || window.event;
    var key = e.keyCode || e.which;
    var modifier = 0;

    if (document.selection || navigator.userAgent.indexOf("Opera") > -1) { // IE | Opera
        modifier = 1;

        if (key == 8) { // Backspace
            modifier = -1;
        }
    }

    if (key > 36 && key < 41) { //
        return;
    }

    if (e.ctrlKey || e.altKey || e.metaKey) { //
        return;
    }

    field = (typeof field === 'string') ? document.getElementById(field) : field;
    infoBlock = (typeof infoBlock === 'string') ? document.getElementById(infoBlock) : infoBlock;
    infoLabel = infoLabel.split('|');

    if (infoBlock.style.display == 'none') {
        infoBlock.style.display = 'block';
    }

    if (field.getAttribute('maxlength')) {
        //
        var caretPos = getCaretPosition(field);

        /*
         *
         *
         */
        setTimeout(function() {
            field.max = parseInt(field.getAttribute('maxlength'));
            field.value = field.value.substr(0, field.max);

            if (document.selection || navigator.userAgent.indexOf("Opera") > -1) { // IE | Opera
                setCaretPosition(field, caretPos + modifier);
            }

            infoBlock.innerHTML = infoLabel[0] + ' ' + (field.max - field.value.length) + ' ' + infoLabel[1] + ' ' + field.max;
        }, 0);
    }
}

function getCaretPosition(field) {
    var caretPos = 0;

    if (document.selection) { // IE
        var currentRange = document.selection.createRange();
        var workRange = currentRange.duplicate();

        field.select();

        var allRange = document.selection.createRange();
        var len = 0;

        while (workRange.compareEndPoints('StartToStart', allRange) > 0) {
            workRange.moveStart('character', -1);

            len++;
        }

        currentRange.select();

        caretPos = len;
    } else if (field.selectionStart || field.selectionStart == '0') { // W3C
        caretPos = field.selectionStart;
    }

    return caretPos;
}

function gui_styled(input, textarea) {
    input = input || false;
    textarea = textarea || false;

    if (input) {
        var w = '';

        $('.' + input).each(function() {
            if ($(this).attr('width')) {
                w = $(this).attr('width');
            } else if($(this).css('width')) {
                w = $(this).css('width');
                $(this).css('width', 'auto');
                $(this).attr('width', parseInt(w));
            } else {
                w = '';
            }

            $(this).wrap('<div class="ff__input-wrap" style="width: ' + w + '"><div class="ff__input-wrap-inner"><div class="ff__input-wrap-input"></div></div></div>');
        })
    }

    if (textarea) {
        var w = '';

        $('.' + textarea).each(function() {
            if ($(this).attr('width')) {
                w = $(this).attr('width');
            } else {
                w = '';
            }

            $(this).wrap('<div class="textarea-styled" style="width: ' + w + '"></div>');
        })
    }

    $('.textarea-styled').append('<div class="textarea-styled__right-top"></div><div class="textarea-styled__right-bottom"></div><div class="textarea-styled__left-bottom"></div><div class="textarea-styled__left-top"></div><div class="textarea-styled__top"></div><div class="textarea-styled__right"></div><div class="textarea-styled__bottom"></div><div class="textarea-styled__left"></div>');

    $('.ff__input-wrap')
        .on('mouseenter', function() {
            $(this).addClass('hover');
        })
        .on('mouseleave', function() {
            $(this).removeClass('hover');
        })
        .on('click', function() {
            $(this).children('input').focus();
        });

    $('.ff__input-wrap input')
        .on('focus', function() {
            $(this).parents('.ff__input-wrap').addClass('focus');
        })
        .on('blur', function() {
            $(this).parents('.ff__input-wrap').removeClass('focus');
        });

    $('.textarea-styled')
        .on('mouseenter', function() {
            $(this).addClass('hover');
        })
        .on('mouseleave', function() {
            $(this).removeClass('hover');
        });

    $('.textarea-styled textarea')
        .on('focus', function() {
            $(this).parents('.textarea-styled').addClass('focus');
        })
        .on('blur', function() {
            $(this).parents('.textarea-styled').removeClass('focus');
        });
}

window.last_top = false;
function _top() {
    if (window.last_top) return window.last_top;
    var p = window;
    while (true) {
        try {
            if (p.location.href.match(/main\.php/) || p.parent === p) { break;}
        } catch (e) {
            window.last_top = p;
            return p;
        }
        p = p.parent.window;
    }
    window.last_top = p;
    return p;
}
try {
    window.top = top = _top();

} catch (e) {}



if (_top().js_popup) {
    window.open_ = window.open;
    window.open = function(url, name, params) {
        if (params) {
            var w = params.match(/width=(\d+),?/)[1];
            var h = params.match(/height=(\d+),?/)[1];
        }
        _top().createPopup({
//			title: name,
            iframe: {
                src: url,
                height: h||400,
                width: w
            }
        });
    };
}

function error_close() {
    try {
        var win = _top().window;
        var obj = _top().gebi('error');
        var div = _top().gebi('error_div');
        if (!obj || !div) return false;

        obj.style.display = 'none';
        div.style.display = 'none';
        obj.src='';
        obj.width = 1;
        obj.height = 1;
        obj.style.left = 0;
        obj.style.top = 0;

        if (div.errorCloseCallback) {
            div.errorCloseCallback();
        }
    } catch(e) { return false; }

    return true;
}

function popupDialog(html, title, width, height, modal) {
    var options = {};
    if (width) options.width = width;
    if (height) options.height = height;
    if (modal) options.modal = modal;
    var cont = $('#popup_global .popup_global_container', _top().document);
    options.closeContent = '';
    if (_top().popupDialogObj) _top().popupDialogObj.close();
    var name = _top().$.Popup;
    _top().popupDialogObj = new name(options);
    var popup = _top().popupDialogObj;
    html = $(html);
    cont.find('.popup_global_content').html(html.html());
    cont.find('.popup_global_title').html(title);
    var el = cont.get(0);
    if (!el || !el.outerHTML) return;
    popup.open(el.outerHTML, 'html');
    _top().$('input').chStyler();
}

function popupDialogClose() {
    if (_top().popupDialogObj) {
        _top().popupDialogObj.close();
    }

    if (typeof(showNextPopupWindow) == "function") {
        showNextPopupWindow();
    }
}

function relaod_user_iframe_timeout(time) {
    top.setTimeout(relaod_user_iframe, time);
}

function relaod_user_iframe() {
    try{
        top.frames['main_frame'].frames['main'].frames['user_iframe'].location.reload();
    }catch (e){}
}

function entry_point_request_bag(object, action, params, funcx, error_callback) {
    params = params || {};
    params = $.extend({
        json_mode_on: 1,
        object: object,
        action: action
    }, params);

    var send_data = {
        url: '/entry_point.php?object='+object+'&action='+action+'&json_mode_on=1',
        dataType: 'json', cache: false, type: "POST"
    };
    if (params.ajaxParam) {
        send_data = $.extend(send_data, params.ajaxParam);
        delete params.ajaxParam;
    }

    send_data.data = params;

    return $.ajax(send_data)
        .done(function(data) {
            if(funcx){
                location.href = funcx;
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            if (error_callback instanceof Function) {
                error_callback.call(this, textStatus);
            }
        });
}

function popup2(url,title,w,h){
    w=w||480;
    h=h||300;
    var win = _top().window;
    var doc = _top().document;
    var width = doc.body.clientWidth;
    var height = doc.body.clientHeight;
    var div_width = Math.max(doc.compatMode != 'CSS1Compat' ? doc.body.scrollWidth : doc.documentElement.scrollWidth,width);
    var div_height = Math.max(doc.compatMode != 'CSS1Compat' ? doc.body.scrollHeight : doc.documentElement.scrollHeight,height);

    var content = "<iframe style=\"z-index: 100000;\" src="+url+" id=\"provocation_frame\" width=\"100%\" height=\"100%\" frameborder=\"0\" name=\"provocation_frame\" scrolling=\"yes\"></iframe>";
    var popup_global = _top().$('#popup_global_s');
    var popup_global_title = _top().$('#popup_global_title_s');
    var popup_global_content = _top().$('#popup_global_content_s');
    popup_global_title.html(title);
    popup_global_content.html('');
    popup_global_content.html(content);
    popup_global.show();
    popup_global.css('top', ((height-h)/2));
    popup_global.css('left', ((width-w)/2));
    popup_global.css('width',w);
    popup_global.css('height',h);
    popup_global.css('position', 'absolute');
    win.scrollTo(0,0);
}

function delete_user_drop(id){
    $.ajax('/pub/api.php', {
        dataType : 'json',
        data : {
            'mode' : 'delete_user_drop',
            'ref' : id,
        },
        complete : function(data, statusCode){
            if(data.responseText){
                var out = JSON.parse(data.responseText);
                if(out['status'] == 0){
                    $('#drop_info_' + id).remove();
                }
            }
        },
        async: false,
    });
}

function resurrect(modeId) {
    var addurl = '';
    if (modeId) {
        addurl = '&in[mode_id]=' + modeId;
    }
    tProcessMenu('b06', {url: 'action_run.php?code=RESURRECT&url_success=area.php&url_error=area.php'+addurl});
}

function big_windows_show(url) {
    try {
        w = (top.document.body.clientWidth - 40);
        h = top.frames['main_frame'].document.body.clientHeight;
        var win = top.window;
        var doc = top.document;
        var width = doc.body.clientWidth;
        var height = doc.body.clientHeight;
        var div_width = Math.max(doc.compatMode != 'CSS1Compat' ? doc.body.scrollWidth : doc.documentElement.scrollWidth,width);
        var div_height = Math.max(doc.compatMode != 'CSS1Compat' ? doc.body.scrollHeight : doc.documentElement.scrollHeight,height);
        var obj = top.gebi('error');
        var div = top.gebi('error_div');
        if (!obj || !div) return false;
        obj.src=url;

        obj.width = w;
        obj.height = h;
        obj.style.left = ((width-w)/2);
        obj.style.top = 0;
        div.style.display = 'none';
        obj.style.display = 'block';
        win.scrollTo(0,0);
    } catch(e) {}
    return true;
}

function showShadowInfo(nick, fight_user_id, bot_id, fight_id, server_id) {
    var url = "/companion_info.php";
    if (typeof nick != 'undefined' && nick) {
        url += "?nick=" + nick;
    } else if (typeof fight_user_id != 'undefined' && fight_user_id) {
        url += "?fight_user_id=" + fight_user_id;
        if ((typeof fight_id != 'undefined' && fight_id) && (typeof server_id != 'undefined' && server_id)) {
            url += "&fight_id=" + fight_id + "&server_id=" + server_id;
        }
    } else if (typeof bot_id != 'undefined' && bot_id) {
        url += "?bot_id=" + bot_id;
    }
    window.open(url, "", "width=915,height=700,location=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
}

function closeHeavensGift(){
    confirmCenterDivClose();
    entry_point_request('HeavensGift', 'close');
}

function showAltInHeavensGift(artikul_id, p1, p2) {
    artifactAltSimple(artikul_id, p1, p2);
}
function openHeavensGift(useCanvas) {
    useCanvas = true;
    var html;
    var par = 'dice_game_controller_url=entry_point.php&locale_file='+_top().locale_file+'&width=460&height=520';
    var options = {width:460,height:520};
    if (useCanvas) {
        html = document.createElement("div");
        new canvas.app.CanvasDiceGame(par,html);
    } else {
        html = '<object type=\"application/x-shockwave-flash\" data=\"/images/swf/dice_game.swf\" width=\"460\" height=\"520\">\
						<param name=\"wmode\" value=\"transparent\">\
						<param name=\"movie\" value=\"/images/swf/dice_game.swf\">\
						<param name=\"FlashVars\" value=\"' + par + '\" />\
				</object>';
    }
    confirmCenterDiv(html, options);
}

function openHeavensGift2(useCanvas) {
    useCanvas = true;
    var html;
    var par = 'dice_game_controller_url=entry_point.php&locale_file='+_top().locale_file+'&width=460&height=520';
    var options = {width:460,height:520};
    if (useCanvas) {
        html = document.createElement("div");
        new canvas.app.CanvasDiceGame(par,html);
        options = {width:1040,height:520};
        getUrl('dice_game_adv.php', function (s,h,html) {
            confirmCenterDiv(html, options);
        });
        return;
    } else {
        html = '<object type=\"application/x-shockwave-flash\" data=\"/images/swf/dice_game.swf\" width=\"460\" height=\"520\">\
						<param name=\"wmode\" value=\"transparent\">\
						<param name=\"movie\" value=\"/images/swf/dice_game.swf\">\
						<param name=\"FlashVars\" value=\"' + par + '\" />\
				</object>';
    }
    confirmCenterDiv(html, options);
}


function confirmCenterDiv(html, options) {

    try{
        switch (html) {
            case '#exp_table':
                showMsg2('_table_exp.php?mode=1', 'Таблица опыта', 619, 375);
                return;
            case '#rank_table':
                showMsg2('_table_exp.php?mode=2', 'Таблица доблести', 619, 375);
                return;
        }
    }catch (e) {}

    var name = _top().$.Popup;
    if (_top().popup != null && _top().popup != undefined) {
        _top().popup.close();
    }
    _top().popup = new name(options);
    if (options.withoutQuotes) {
        html = html.replace(/&quot;/g, '"').replace(/&backslash;/g, '\\');
    }
    _top().popup.open(html, options.type || 'html');
}

function showPopupDialog(url, title, w, h){
    try {
        w=w||480;
        h=h||300;
        var win = top.window;
        var doc = top.document;
        var width = doc.body.clientWidth;
        var height = doc.body.clientHeight;
        var div_width = Math.max(doc.compatMode != 'CSS1Compat' ? doc.body.scrollWidth : doc.documentElement.scrollWidth,width);
        var div_height = Math.max(doc.compatMode != 'CSS1Compat' ? doc.body.scrollHeight : doc.documentElement.scrollHeight,height);
        var obj = top.gebi('popup_dialog_iframe');
        var div = top.gebi('popup_dialog_div');
        if (!obj || !div) return false;


        var win = top.window;
        var popup = win.open(url, 'popup_dialog_iframe', 'width=' + w + ',height=' + h + ',location=no,menubar=no,resizable=no,scrollbars=no,status=no,toolbar=no', false);

        //obj.src=url;

        div.style.width = div_width;
        div.style.height = div_height;

        obj.width = w;
        obj.height = h;
        obj.style.left = ((width-w)/2);
        obj.style.top = ((height-h)/2);
        div.style.display = 'block';
        obj.style.display = 'block';
        win.scrollTo(0,0);
//		obj = top.gebi('artifact_alt');
//		if (obj) obj.innerHTML='';
    } catch(e) {}
    return true;
}

function showExBackpack(url, title, w, h, l_cor, t_cor) {
    if(l_cor === undefined) l_cor = -495;
    if(t_cor === undefined) t_cor = 0;
    try {
        w=w||480;
        h=h||300;
        var win = top.window;
        var doc = top.document;
        var width = doc.body.clientWidth;
        var height = doc.body.clientHeight;
        var div_width = Math.max(doc.compatMode != 'CSS1Compat' ? doc.body.scrollWidth : doc.documentElement.scrollWidth,width);
        var div_height = Math.max(doc.compatMode != 'CSS1Compat' ? doc.body.scrollHeight : doc.documentElement.scrollHeight,height);
        var obj = top.gebi('battle_backpack_iframe');
        var div = top.gebi('battle_backpack_div');
        var pos = _top().frames['main_frame'].$('#items_right_td').offset();
        if (!obj || !div) return false;

        obj.src=url;
        var win = top.window;
        var popup = win.open(url, 'battle_backpack_iframe', 'width=' + w + ',height=' + h + ',location=no,menubar=no,resizable=no,scrollbars=no,status=no,toolbar=no', false);


        div.style.width = div_width;
        div.style.height = div_height;

        obj.width = w;
        obj.height = h;
        if(pos) {
            $(obj).css('left', pos.left + l_cor);
            $(obj).css('top', pos.top + t_cor);
        }else{
            obj.style.left = ((width-w)/2);
            obj.style.top = ((height-h)/2);
        }
        div.style.display = 'block';
        obj.style.display = 'block';
        win.scrollTo(0,0);
//		obj = top.gebi('artifact_alt');
//		if (obj) obj.innerHTML='';
    } catch(e) {}
    return true;
}

function closeExBackpack() {
    try {
        var obj = top.gebi('battle_backpack_iframe');
        var div = top.gebi('battle_backpack_div');
        obj.src = "about:blank";
        div.style.display = 'none';
        obj.style.display = 'none';
    } catch(e) {}
    try{
        top.frames['main_frame'].right_menu_vars[2].state = 0;
        top.frames['main_frame'].right_menu_vars[6].state = 0;
        top.frames['main_frame'].right_menu_init();
    }catch (e){}
}

function closePopupDialog(){
    try {
        var obj = top.gebi('popup_dialog_iframe');
        var div = top.gebi('popup_dialog_div');
        obj.src = "";
        div.style.display = 'none';
        obj.style.display = 'none';
    } catch(e) {}
}

function entry_point_request(object, action, params, callback, error_callback) {
    params = params || {};
    params = $.extend({
        json_mode_on: 1,
        object: object,
        action: action
    }, params);

    var send_data = {
        url: '/entry_point.php?object='+object+'&action='+action+'&json_mode_on=1',
        dataType: 'json', cache: false, type: "POST"
    };
    if (params.ajaxParam) {
        send_data = $.extend(send_data, params.ajaxParam);
        delete params.ajaxParam;
    }

    send_data.data = params;

    return $.ajax(send_data)
        .done(function(data) {
            var key = object + '|' + action;
            var action_data = data[key] || data;
            if (callback instanceof Function) {
                callback.call(this, action_data, data);
            }
            if (_top().chat && _top().chat.chatReceiveObject) {
                _top().chat.chatReceiveObject(data);
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            if (error_callback instanceof Function) {
                error_callback.call(this, textStatus);
            }
        });
}

function artifact_get_color(artifact_id) {
    var artifact_alt = get_art_alt('AA_' + artifact_id);
    return artifact_alt.color;
}

function get_art_alt(id, win) {
    if (win) {
        if (win.art_alt && win.art_alt[id]) return win.art_alt[id];
        for (var i = 0; i < win.frames.length; ++i) {
            var res = get_art_alt(id, win.frames[i]);
            if (res !== false) return res;
        }
        return false;
    }
    if (art_alt && art_alt[id]) return art_alt[id];
    if (_top().items_alt && _top().items_alt[id]) return _top().items_alt[id];
    return get_art_alt(id, _top().frames['main_frame']);
}

function set_art_alt(id, data, win) {
    if (win) {
        if (win.art_alt) {
            win.art_alt[id] = data;
            return;
        }
        for (var i = 0; i < win.frames.length; ++i) {
            if (win.frames[i].art_alt) {
                win.frames[i].art_alt[id] = data;
                return;
            }
        }
        return;
    }
    if (art_alt) {
        art_alt[id] = data;
        return;
    }
    if (_top().items_alt) {
        _top().items_alt[id] = data;
        return;
    }
    _top().frames['main_frame'].art_alt[id] = data;
}


function inArray(needle, haystack) {
    var length = haystack.length;
    for (var i = 0; i < length; i++) {
        if (haystack[i] == needle) return true;
    }
    return false;
}

function _html_money_gold_str(o, amount) {
    var str = '<a href="'+o.url+'" target="_blank">';
    str += '<span title="'+o.alt+'"><img src="/images/'+o.picture+'" border="0" width="11" height="11" align="absmiddle" /></span></a>';
    str += '&nbsp;'+amount+'&nbsp;';
    return str;
}

function html_money_str(count, price, price_gold, price_work, price_silver, hide_empty_silver) {
    var multi = price < 0 ? -1 : 1;
    multi *= price_gold < 0 ? -1 : 1;
    multi *= price_work < 0 ? -1 : 1;
    multi *= price_silver < 0 ? -1 : 1;
    var money_info = _top().money_type_info;
    var str = '';

    var amount = moneyRound(Math.abs(price) * count);
    var amount_gold = moneyRound(Math.abs(price_gold) * count);
    var amount_work = moneyRound(Math.abs(price_work) * count);
    var amount_silver = moneyRound(Math.abs(price_silver) * count);

    var gold = Math.floor(amount/100);
    var silver = Math.floor(amount - (gold * 100));

    //работаем с int чтобы избежать проблем типа 101.1-101 = 0.09999999999999432
    amount = parseInt(amount * 100, 10);
    var bronze = Math.floor(amount - gold * 10000 - silver * 100);

    if (gold) str += _html_money_str(money_info.gold, gold);
    if (!hide_empty_silver && gold && bronze || silver) str += _html_money_str(money_info.silver, silver);
    if (bronze) str += _html_money_str(money_info.bronze, bronze);

    if (amount_gold) str += _html_money_gold_str(money_info.brilliante, parseFloat(amount_gold) ? (Math.round(amount_gold) == amount_gold ? Math.round(amount_gold) : amount_gold) : 0.00);
    if (amount_work) str += _html_money_str(money_info.work, amount_work, true);
    if (amount_silver) str += _html_money_gold_str(money_info.ruby, parseFloat(amount_silver) ? amount_silver : 0.00);

    if (str == '') {
        //str += _html_money_str(money_info.bronze, 0);
    }

    return (multi == -1 ? ' - ' : '') + str;
}

function _html_money_str(o, amount, nospace) {
    var str = '<span title="'+o.alt+'"><img src="/images/'+o.picture+'" border=0 width=11 height=11 align=absmiddle>&nbsp;'+amount+(nospace ? '' : '&nbsp;')+'</span>';
    return str;
}

function moneyRound(num) {
    return parseFloat(num) ? num.toFixed(2) : num;
}

function artifact_calc_sell_price(e) {
    e = $(e);

    var price = parseFloat(e.attr('sell_price'));

    return {money: price};
}

function artifact_calc_repair_price(e) {
    e = $(e);

    return {money: parseFloat(e.attr('repair_price'))}
}

function quality_color(quality){
    var quality_info = {
        0:'#666666',
        1:'#339900',
        2:'#3300ff',
        3:'#990099',
        4:'#ff0000',
        5:'#016e71',
        6:'#ff4500',
        7:'#b55e00',
    };
    return quality_info[quality];
}

function popupDialog(html, title, width, height, modal) {
    var options = {};
    if (width) options.width = width;
    if (height) options.height = height;
    if (modal) options.modal = modal;
    var cont = $('#popup_global .popup_global_container', _top().document);
    options.closeContent = '';
    if (_top().popupDialogObj) _top().popupDialogObj.close();
    var name = _top().$.Popup;
    _top().popupDialogObj = new name(options);
    var popup = _top().popupDialogObj;
    html = $(html);
    cont.find('.popup_global_content').html(html.html());
    cont.find('.popup_global_title').html(title);
    var el = cont.get(0);
    if (!el || !el.outerHTML) return;
    popup.open(el.outerHTML, 'html');
    _top().$('input').chStyler();
}

function html_period_str_lite(time){
    ostd = time % 86400;
    days = (time - ostd) / 86400;
    osth = ostd % 3600;
    hours = (ostd - osth) / 3600;
    ostm = osth % 60;
    minutes = (osth - ostm) / 60;
    seconds = ostm % 60;
    seconds = ( seconds < 10 ? '0'+seconds : seconds );
    return ( days ? days + 'д ' : '') + ( hours ? hours + 'ч ' : '') + ( minutes ? minutes + 'мин ' : '') + ( seconds ? seconds + 'сек ' : '');
}

function html_period_str(v, with_seconds, with_minutes, glue, extended) {
    if(!with_seconds || with_seconds == undefined) with_seconds = false;
    if(!with_minutes || with_minutes == undefined) with_minutes = true;
    if(!glue || glue == undefined) glue = ' ';
    if(!extended || extended == undefined) extended = true;
    v = Math.max(v,0);
    ss = parseInt(v) % 60;
    mm = parseInt(v/60) % 60;
    hh = parseInt(v/3600) % 24;
    dd = parseInt(v/86400);
    t = Array();
    if(extended) {
        if (dd) t.push(dd.toString().concat('д'));
        if (hh || (dd && mm)) t.push(hh.toString().concat('ч'));
        if (with_minutes && mm) t.push(mm.toString().concat('мин'));
        if ((with_seconds && ss) || (v < 60)) t.push(ss.toString().concat('сек'));
    }else{
        t.push(sprintf("%02d", hh));
        if (with_minutes) t.push(sprintf("%02d", mm));
        if (with_seconds) t.push(sprintf("%02d", ss));
    }
    return t.join(glue); //Implode With Glue
}

//Analog PHP
function sprintf () {
    var regex = /%%|%(\d+\$)?([-+'#0 ]*)(\*\d+\$|\*|\d+)?(?:\.(\*\d+\$|\*|\d+))?([scboxXuideEfFgG])/g
    var a = arguments
    var i = 0
    var format = a[i++]
    var _pad = function (str, len, chr, leftJustify) {
        if (!chr) {
            chr = ' '
        }
        var padding = (str.length >= len) ? '' : new Array(1 + len - str.length >>> 0).join(chr)
        return leftJustify ? str + padding : padding + str
    }
    var justify = function (value, prefix, leftJustify, minWidth, zeroPad, customPadChar) {
        var diff = minWidth - value.length
        if (diff > 0) {
            if (leftJustify || !zeroPad) {
                value = _pad(value, minWidth, customPadChar, leftJustify)
            } else {
                value = [
                    value.slice(0, prefix.length),
                    _pad('', diff, '0', true),
                    value.slice(prefix.length)
                ].join('')
            }
        }
        return value
    }
    var _formatBaseX = function (value, base, prefix, leftJustify, minWidth, precision, zeroPad) {
        // Note: casts negative numbers to positive ones
        var number = value >>> 0
        prefix = (prefix && number && {
            '2': '0b',
            '8': '0',
            '16': '0x'
        }[base]) || ''
        value = prefix + _pad(number.toString(base), precision || 0, '0', false)
        return justify(value, prefix, leftJustify, minWidth, zeroPad)
    }
    // _formatString()
    var _formatString = function (value, leftJustify, minWidth, precision, zeroPad, customPadChar) {
        if (precision !== null && precision !== undefined) {
            value = value.slice(0, precision)
        }
        return justify(value, '', leftJustify, minWidth, zeroPad, customPadChar)
    }
    // doFormat()
    var doFormat = function (substring, valueIndex, flags, minWidth, precision, type) {
        var number, prefix, method, textTransform, value
        if (substring === '%%') {
            return '%'
        }
        // parse flags
        var leftJustify = false
        var positivePrefix = ''
        var zeroPad = false
        var prefixBaseX = false
        var customPadChar = ' '
        var flagsl = flags.length
        var j
        for (j = 0; j < flagsl; j++) {
            switch (flags.charAt(j)) {
                case ' ':
                    positivePrefix = ' '
                    break
                case '+':
                    positivePrefix = '+'
                    break
                case '-':
                    leftJustify = true
                    break
                case "'":
                    customPadChar = flags.charAt(j + 1)
                    break
                case '0':
                    zeroPad = true
                    customPadChar = '0'
                    break
                case '#':
                    prefixBaseX = true
                    break
            }
        }
        // parameters may be null, undefined, empty-string or real valued
        // we want to ignore null, undefined and empty-string values
        if (!minWidth) {
            minWidth = 0
        } else if (minWidth === '*') {
            minWidth = +a[i++]
        } else if (minWidth.charAt(0) === '*') {
            minWidth = +a[minWidth.slice(1, -1)]
        } else {
            minWidth = +minWidth
        }
        // Note: undocumented perl feature:
        if (minWidth < 0) {
            minWidth = -minWidth
            leftJustify = true
        }
        if (!isFinite(minWidth)) {
            throw new Error('sprintf: (minimum-)width must be finite')
        }
        if (!precision) {
            precision = 'fFeE'.indexOf(type) > -1 ? 6 : (type === 'd') ? 0 : undefined
        } else if (precision === '*') {
            precision = +a[i++]
        } else if (precision.charAt(0) === '*') {
            precision = +a[precision.slice(1, -1)]
        } else {
            precision = +precision
        }
        // grab value using valueIndex if required?
        value = valueIndex ? a[valueIndex.slice(0, -1)] : a[i++]
        switch (type) {
            case 's':
                return _formatString(value + '', leftJustify, minWidth, precision, zeroPad, customPadChar)
            case 'c':
                return _formatString(String.fromCharCode(+value), leftJustify, minWidth, precision, zeroPad)
            case 'b':
                return _formatBaseX(value, 2, prefixBaseX, leftJustify, minWidth, precision, zeroPad)
            case 'o':
                return _formatBaseX(value, 8, prefixBaseX, leftJustify, minWidth, precision, zeroPad)
            case 'x':
                return _formatBaseX(value, 16, prefixBaseX, leftJustify, minWidth, precision, zeroPad)
            case 'X':
                return _formatBaseX(value, 16, prefixBaseX, leftJustify, minWidth, precision, zeroPad)
                    .toUpperCase()
            case 'u':
                return _formatBaseX(value, 10, prefixBaseX, leftJustify, minWidth, precision, zeroPad)
            case 'i':
            case 'd':
                number = +value || 0
                // Plain Math.round doesn't just truncate
                number = Math.round(number - number % 1)
                prefix = number < 0 ? '-' : positivePrefix
                value = prefix + _pad(String(Math.abs(number)), precision, '0', false)
                return justify(value, prefix, leftJustify, minWidth, zeroPad)
            case 'e':
            case 'E':
            case 'f': // @todo: Should handle locales (as per setlocale)
            case 'F':
            case 'g':
            case 'G':
                number = +value
                prefix = number < 0 ? '-' : positivePrefix
                method = ['toExponential', 'toFixed', 'toPrecision']['efg'.indexOf(type.toLowerCase())]
                textTransform = ['toString', 'toUpperCase']['eEfFgG'.indexOf(type) % 2]
                value = prefix + Math.abs(number)[method](precision)
                return justify(value, prefix, leftJustify, minWidth, zeroPad)[textTransform]()
            default:
                return substring
        }
    }
    return format.replace(regex, doFormat)
}

function getIframeWin(name){
    return document.getElementById(name).contentWindow;
}

function stable_stage_iframe(h, id){
    $("#stage_iframe_" + id).height(h);
    $("#stage_iframe_" + id).show();
}

function main_frame_set_url(url) {
    _top().frames["main_frame"].frames["main"].location.href = url;
}

function time_bonus_update(){
    _top().frames['main_frame']._time_bonus_update();
}

function randomInteger(min, max) {
    var rand = min - 0.5 + Math.random() * (max - min + 1)
    rand = Math.round(rand);
    return rand;
}

var roll_list = {};
function roll_item_timer(data, roll_item){
    var _this = this;
    this.started = false;
    this.parent = roll_item;
    this.sel_item = $('.roll_item[data-id=\''+data.id+'\']');
    this.sel_item_t1 = this.sel_item.find('.progress-bar__red:first');
    this.sel_item_t2 = this.sel_item.find('.progress-bar__marker:first');
    this.sel_item_txt = this.sel_item.find('.progress-bar__txt:first');

    this.tick = function(){
        this.data.ttl--;
        if(!this.interval){
            try{clearInterval(this.interval);}catch (e) {}
            return false;
        }
        p = 97 - this.data.ttl / this.data.period * 100;
        this.sel_item_t1.css('width', p + '%');
        this.sel_item_t2.css('right', p + '%');
        this.sel_item_txt.html(html_period_str_lite(this.data.ttl));
        if(this.data.ttl <= 0) {
            console.log('stop ' + this.parent.data.id);
            this.stop();
            this.parent_delete();
        }
    };
    this.data = data;
    this.interval = false;

    this.start = function () {
        if(this.data.ttl <= 0){return false;}
        this.interval = setInterval(function () {
            _this.tick();
        }, 1000);
        this.started = true;
    };
    this.stop = function () {
        clearInterval(this.interval);
        this.started = false;
    };
    this.parent_delete = function () {
        console.log('parent delete ' + this.parent.data.id);
        this.parent.delete();
    };
    this.start();
}
function roll_item(data){
    var _this = this;
    this.sel_item = false;
    this.roll_deleted = false;
    this.data = data;
    this.timer = false;
    this.sel = $('.roll_item[data-id=\''+this.data['id']+'\']');

    this.data_construct_html = function(data){
        txt = '';
        Object.keys(data).forEach(function(k,i){
            txt += ' data-'+k+'="'+data[k]+'" ';
        });
        return txt;
    };

    this.generate_html = function(data) {

        p = parseInt(97 - this.data.ttl / this.data.period * 100);

        html = '';
        html += '<div class="roll_item"'+this.data_construct_html(data)+'>';
        html += '    <div class="roll_cont">';
        html += '        <div class="roll_bg"></div>';
        html += '        <div class="roll_title"><p>'+data['title']+'</p></div>';
        html += '        <div class="roll_art">';
        html += '            <div onclick="showArtifactInfo(false,'+data['artikul_id']+');" class="b-special-slot" style="margin: 0;">';
        if(data['nogive'] == 1) html +='<div class="roll_nogive">без передачи</div>';
        if(data['artikul_cnt'] !== undefined && parseInt(data['artikul_cnt']) > 1) html +='<div class="roll_cnts">'+parseInt(data['artikul_cnt'])+'</div>';
        html += '                <div class="b-special-slot__img"><img src="'+data['picture']+'"></div>';
        html += '            </div>';
        html += '        </div>';
        html += '        <div class="roll_buttons">';
        html += '            <div class="roll_button_1" style="top:0px; left:0px;"><div data-action="1" class="hover_elem"></div><div class="roll_b_bg"></div><div class="rollb_icon need"></div><div class="rollb_text">Мне это нужно!</div></div>';
        html += '            <div class="roll_button_2" style="top: 23px;left: 33px;"><div data-action="2" class="hover_elem"></div><div class="roll_b_bg"></div><div class="rollb_icon maybe"></div><div class="rollb_text">Не откажусь.</div></div>';
        html += '            <div class="roll_button_1" style="top:46px; left:0px;"><div data-action="3" class="hover_elem"></div><div class="roll_b_bg"></div><div class="rollb_icon no_need"></div><div class="rollb_text">Отказаться.</div></div>';
        html += '            <div class="roll_stage">';
        html += '                <div class="progress-bar">';
        html += '                    <div class="progress-bar__bg"></div>';
        html += '                    <div class="progress-bar__red" style="width: '+p+'%;"></div>';
        html += '                    <div class="progress-bar__cover"></div>';
        html += '                    <div class="progress-bar__left"></div>';
        html += '                    <div class="progress-bar__right"></div>';
        html += '                    <div class="progress-bar__marker" style="right: '+p+'%;"></div>';
        html += '                    <div class="progress-bar__txt">'+html_period_str_lite(this.data.ttl)+'</span></div>';
        html += '                </div>';
        html += '            </div>';
        html += '        </div>';
        html += '    </div>';
        html += '</div>';
        return html;
    };

    this.construct = function(data){
        $('#rolling_div').append(this.generate_html(data));
        this.timer = new roll_item_timer(this.data, this);
        this.sel_item = this.timer.sel_item;
        this.sel_item.find('.hover_elem').click(function () {
            $.ajax('/rollact.php', {
                dataType : 'json',
                data : {
                    'ajax' : true,
                    'action' : 'select',
                    'sel' : $(this).data('action'),
                    'id' : _this.data.id,
                },
                complete : function(data, statusCode){
                    if(data.responseText){
                        var err = JSON.parse(data.responseText);
                        if(err['error']){
                            _top().showError(err['error']);
                        }
                        if(err['no_delete'] === undefined || err['no_delete'] == false) _this.delete();
                    }
                },
                async: true,
            });
        });
    };
    this.delete = function () {
        console.log('deleted ' + this.data.id);
        this.timer.stop();
        this.sel_item.remove();
        this.roll_deleted = true;
        //самовыпил
        delete roll_list[this.data.id];
        _mf().RollShowDiv();
    };
    this.construct(this.data);
}
function RollShowDiv(){
    if(Object.keys(roll_list).length > 0){
        $('#rolling_div').css('left', ((document.body.clientWidth - 870)/2) + 'px');
        $('#rolling_div').show();
    }else{
        $('#rolling_div').hide();
    }
}
function RollShowing(id) {
    if(roll_list[id] !== undefined) return false;
    $.ajax('/rollact.php', {
        dataType : 'json',
        data : {
            'ajax' : true,
            'action' : 'check',
            'id' : id,
        },
        complete : function(data, statusCode){
            if(data.responseText){
                var err = JSON.parse(data.responseText);
                if(err['error']){
                    _top().showError(err['error']);
                }else{
                    cnf = err['data'];
                    if(cnf.ttl > 0) {
                        roll_list[cnf['id']] = new roll_item(cnf);
                        RollShowDiv();
                    }
                }
            }
        },
        async: true,
    });
}

function closePuzzle() {
    confirmCenterDivClose();
}

function confirmCenterDivClose() {
    if (_top().popup != null && _top().popup != undefined) {
        _top().popup.close();
    }

    if (typeof (showNextPopupWindow) == "function") {
        showNextPopupWindow();
    }
}


function isChatLoaded() {
    var chat_frame = _top().frames['chat'];
    return chat_frame && chat_frame.LMTS && chat_frame.LMTS.isAuthorized();
}
function _mf(fr){
    if(fr !== undefined) _top().frames['main_frame'].frames[fr];
    return _top().frames['main_frame'];
}

function jq_tooltip(selector, title, type){
    if(type === undefined) type = 1;
    switch (type) {
        case 1:
            $(selector).attr('data-tooltip', '').attr('tooltip', '<div class="tooltip_ul">'+title+'</div>');
            break;
    }
}

function js_tooltip(title, type){
    if(type === undefined) type = 1;
    str = '';
    switch (type) {
        case 1:
            str += 'data-tooltip data-tooltip-content="<div class=\'tooltip_ul\'>'+title.replace('"', '\"')+'</div>"';
            break;
    }
    return str;
}

function ui_reload_tooltip() {
    try{
        $('[data-tooltip]').tooltipster({
            contentCloning: false,
            delay: 0,
            delayTouch: 0,
            trackerInterval: 0,
            animationDuration: 0,
            plugins: ['follower'],
        });
    }catch (e) {}
}

function formatByCount(number, form1, form2, form3) {
    var count = Math.abs(number) % 100;
    var lCount = number % 10;

    if (count >= 11 && count <= 19) {
        return form3;
    }

    if (lCount >= 2 && lCount <= 4) {
        return form2;
    }

    if (lCount == 1) {
        return form1;
    }

    return form3;
}


function stopAction() {
    $.ajax({
        url: '/entry_point.php',
        data: {
            json_mode_on: true,
            object: 'common',
            action: 'stopaction'
        },
        dataType: 'JSON',
        success: function (objects) {
            _top().chat.chatReceiveObject(objects);
        }
    });
}
function check_notify_cnt(){
    if(!window.jQuery) return false;
    $.ajax('/entry_point.php', {
        dataType : 'json',
        data : {
            'object' : 'user',
            'action' : 'check_notify_cnt',
        },
        complete : function(data, statusCode){
            if(data.responseText){
                var object = JSON.parse(data.responseText);
                if(object['status'] == 100 && object['count'] > 0) {
                    _top().sFrMe('notify_login');
                }
            }
        },
        async: true,
    });
}

function fixEvent(e, _this) {
    e = e || window.event;

    var docElem = document.documentElement,
        body = document.body;

    if (!e.currentTarget) e.currentTarget = _this;
    if (!e.target) e.target = e.srcElement;

    if (!e.relatedTarget) {
        if (e.type === 'mouseover') e.relatedTarget = e.fromElement;
        if (e.type === 'mouseout') e.relatedTarget = e.toElement;
    }

    if (!e.pageX && e.clientX) {
        e.pageX = e.clientX + (docElem.scrollLeft || body && body.scrollLeft || 0);
        e.pageX -= docElem.clientLeft || 0;

        e.pageY = e.clientY + (docElem.scrollTop || body && body.scrollTop || 0);
        e.pageY -= docElem.clientTop || 0;
    }

    if (!e.which && e.button) {
        e.which = e.button & 1 ? 1 : (e.button & 2 ? 3 : (e.button & 4 ? 2 : 0));
    }

    return e;
}
function guideShow(gid, link) {
    if(gid === undefined) gid = 0;
    _xfr = _top();
    let lnk = 'user_guide.php';
    if(lnk !== undefined && typeof link == 'string') lnk = link;
    _xfr.need_frames['user_guide'].src = lnk + '?guide_id='+gid;
    _xfr.sFrMe('user_guide');
}
function refresh_dice_stage(st) {
    try{_top().frames["dice_game_adv"].update_dice_game(st);}catch (e) {}
}

function showMagicMirror(){
    _top().sFrMe('magic_mirror');
}

function closeMagicMirror(){
    _top().hFrMe('magic_mirror');
}

function PopupCenter(url, title, w, h) {
    var userAgent = navigator.userAgent,
        mobile = function() {
            return /\b(iPhone|iP[ao]d)/.test(userAgent) ||
                /\b(iP[ao]d)/.test(userAgent) ||
                /Android/i.test(userAgent) ||
                /Mobile/i.test(userAgent);
        },
        screenX = typeof window.screenX != 'undefined' ? window.screenX : window.screenLeft,
        screenY = typeof window.screenY != 'undefined' ? window.screenY : window.screenTop,
        outerWidth = typeof window.outerWidth != 'undefined' ? window.outerWidth : document.documentElement.clientWidth,
        outerHeight = typeof window.outerHeight != 'undefined' ? window.outerHeight : document.documentElement.clientHeight - 22,
        targetWidth = mobile() ? null : w,
        targetHeight = mobile() ? null : h,
        V = screenX < 0 ? window.screen.width + screenX : screenX,
        left = parseInt(V + (outerWidth - targetWidth) / 2, 10),
        right = parseInt(screenY + (outerHeight - targetHeight) / 2.5, 10),
        features = [];
    if (targetWidth !== null) {
        features.push('width=' + targetWidth);
    }
    if (targetHeight !== null) {
        features.push('height=' + targetHeight);
    }
    features.push('left=' + left);
    features.push('top=' + right);
    features.push('scrollbars=1');

    var newWindow = window.open(url, title, features.join(','));

    if (window.focus) {
        newWindow.focus();
    }

    return newWindow;
}

function lockScroll(){
    $html = $('html');
    $body = $('body');
    var initWidth = $body.outerWidth();
    var initHeight = $body.outerHeight();

    var scrollPosition = [
        self.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft,
        self.pageYOffset || document.documentElement.scrollTop  || document.body.scrollTop
    ];
    $html.data('scroll-position', scrollPosition);
    $html.data('previous-overflow', $html.css('overflow'));
    $html.css('overflow', 'hidden');
    window.scrollTo(scrollPosition[0], scrollPosition[1]);

    var marginR = $body.outerWidth()-initWidth;
    var marginB = $body.outerHeight()-initHeight;
    $body.css({'margin-right': marginR,'margin-bottom': marginB});
}

function unlockScroll(){
    $html = $('html');
    $body = $('body');
    $html.css('overflow', $html.data('previous-overflow'));
    var scrollPosition = $html.data('scroll-position');
    window.scrollTo(scrollPosition[0], scrollPosition[1]);

    $body.css({'margin-right': 0, 'margin-bottom': 0});
}

function time_current() {
    return Math.round(((new Date()).getTime()) / 1000);
}

function current_server_time() {
    var chat = _top().chat;
    if (!chat) return false;
    var timestamp = parseInt(chat.chat_clock.getTime() / 1000);
    timestamp += chat.CHAT.timezone_diff;
    return timestamp;
}
function single_top_redirect(url) {
    if (!_top().already_redirected) {
        _top().already_redirected = true;
        _top().location.href = url;
    }
}

function is_touch_device() {
    return (('ontouchstart' in window)
        || (navigator.MaxTouchPoints > 0)
        || (navigator.msMaxTouchPoints > 0));
}
function getScrollMaxY(win) {
    var innerh;
    if (win.innerHeight){
        innerh = win.innerHeight;
    } else {
        innerh = win.document.body.clientHeight;
    }

    if (win.innerHeight && win.scrollMaxY){
        // Firefox
        yWithScroll = win.innerHeight + win.scrollMaxY;
    } else if (win.document.body.scrollHeight > win.document.body.offsetHeight){
        // all but Explorer Mac
        yWithScroll = win.document.body.scrollHeight;
    } else {
        // works in Explorer 6 Strict, Mozilla (not FF) and Safari
        yWithScroll = win.document.body.offsetHeight;
    }
    return yWithScroll - innerh;
}

function timezone_list(timezone) {
    $.ajax('/entry_point.php', {
        dataType: 'json',
        data: {
            'object': 'user',
            'action': 'zone_list',
            'sig': timezone,
            'json_mode_on': 1,
        },
        complete: function (data, statusCode) {
            if (data.responseText) {
                var err = JSON.parse(data.responseText);
                if (err['error']) {
                    _top().showError(err['error']);
                }
            }
        },
        async: false,
    });
}

function openMap() {
    var top = _top();
    if (top.js_popup) {
        tProcessMenu('b06', {url: 'world_map.php', force: true});
        tUnsetFrame('main');
    } else {
        top.opened_windows = top.opened_windows || {};
        top.opened_windows['world_map'] = window.open("world_map.php", "world_map", "location=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");
    }
}

if (!Object.keys) {
    Object.keys = (function () {
        'use strict';
        var hasOwnProperty = Object.prototype.hasOwnProperty,
            hasDontEnumBug = !({toString: null}).propertyIsEnumerable('toString'),
            dontEnums = [
                'toString',
                'toLocaleString',
                'valueOf',
                'hasOwnProperty',
                'isPrototypeOf',
                'propertyIsEnumerable',
                'constructor'
            ],
            dontEnumsLength = dontEnums.length;

        return function (obj) {
            if (typeof obj !== 'object' && (typeof obj !== 'function' || obj === null)) {
                throw new TypeError('Object.keys called on non-object');
            }

            var result = [], prop, i;

            for (prop in obj) {
                if (hasOwnProperty.call(obj, prop)) {
                    result.push(prop);
                }
            }

            if (hasDontEnumBug) {
                for (i = 0; i < dontEnumsLength; i++) {
                    if (hasOwnProperty.call(obj, dontEnums[i])) {
                        result.push(dontEnums[i]);
                    }
                }
            }
            return result;
        };
    }());
}
function setTransformImage(select) {
    var selectedOption = select.options[select.selectedIndex],

        slotBefore = $('span[data-slot="transformBefore"]')[0],
        slotAfter = $('span[data-slot="transformAfter"]')[0],

        innerSlotBefore = slotBefore.firstChild,
        innerSlotAfter = slotAfter.firstChild,

        slotEnchantFrame = selectedOption.getAttribute('data-enchant3'),
        slotEnchant = selectedOption.getAttribute('data-enchant-class'),

        itemBefore,
        itemAfter;

    innerSlotBefore.innerHTML = '';
    innerSlotBefore.setAttribute('div_id', '');
    innerSlotBefore.style.backgroundImage = 'none';
    innerSlotBefore.onmouseover = function(e) {};
    innerSlotBefore.onmouseout = function(e) {};

    innerSlotAfter.setAttribute('div_id', '');
    innerSlotAfter.style.backgroundImage = 'none';
    innerSlotAfter.onmouseover = function(e) {};
    innerSlotAfter.onmouseout = function(e) {};

    if (art_alt) {
        itemBefore = art_alt['AA_' + selectedOption.value];
        itemAfter = art_alt['AA_' + selectedOption.getAttribute('data-transform-artikul')];

        if (itemBefore) {
            innerSlotBefore.setAttribute('div_id', itemBefore.artifact_alt_id);
            innerSlotBefore.style.backgroundImage = 'url(' + itemBefore.image + ')';
            innerSlotBefore.onmouseover = function(e) {
                artifactAlt(this, e, 2);
            };
            innerSlotBefore.onmouseout = function(e) {
                artifactAlt(this, e, 0);
            };

            if (slotEnchantFrame) {
                innerSlotBefore.innerHTML += '<span class="artifact-slot__enchant enchant-oprava"></span>';
            }

            if (slotEnchant) {
                innerSlotBefore.innerHTML += '<span class="artifact-slot__enchant ' + slotEnchant + '"></span>';
            }
        }

        if (itemAfter) {
            innerSlotAfter.setAttribute('div_id', itemAfter.artifact_alt_id);
            innerSlotAfter.style.backgroundImage = 'url(' + itemAfter.image + ')';
            innerSlotAfter.onmouseover = function(e) {
                artifactAlt(this, e, 2);
            };
            innerSlotAfter.onmouseout = function(e) {
                artifactAlt(this, e, 0);
            };
        }
    }
}

function clan_talents_alt(talent_id, num, m_event, evnt){
    var alt_div = _top().$('#clan_talents_alt');
    var cur_id = alt_div.attr("data-id");
    var cur_num = alt_div.attr("data-num");

    if(evnt == 2){
        document.onmousemove=function(e){ clan_talents_alt(0, 0, e||event, 1); }
        alt_div.show();
    }else if(evnt == 0){
        document.onmousemove=function(){};
        alt_div.hide(); return false;
    }

    if(evnt == 1){
        var coor = getIframeShift();
        var ex = m_event.clientX+coor.left;
        var ey = m_event.clientY+coor.top;
        var x = 0;
        var y = 0;

        alt_div.offsetWidth = alt_div.width();
        alt_div.offsetHeight = alt_div.height();

        if(top.noIframeCoords !== undefined && top.noIframeCoords) {
            ex = m_event.clientX;
            ey = m_event.clientY;

            x = ex + 10 + alt_div.offsetWidth > top.document.body.clientWidth ? (ex) - (ex + 10 + alt_div.offsetWidth - top.document.body.clientWidth) : ex + 10;
            y = ey + alt_div.offsetHeight - top.document.body.scrollTop > top.document.body.clientHeight - 20 ? ey - alt_div.offsetHeight - 10 : ey + 10;
            alt_div.css('left', x);
            alt_div.css('top', y);
        }else{
            ex = m_event.clientX + top.document.body.scrollLeft;
            ey = m_event.clientY + top.document.body.scrollTop;
            x = ex + alt_div.offsetWidth > top.document.body.clientWidth - 20 ? ex - alt_div.offsetWidth - 10 : ex + 10;
            y = ey + alt_div.offsetHeight - top.document.body.scrollTop > top.document.body.clientHeight - 20 ? ey - alt_div.offsetHeight - 10 : ey + 10;
            if (x < 0 ) {
                x = ex - alt_div.offsetWidth/2;
            }
            if (x < 7 ) {
                x = 7;
            }
            if (x > top.document.body.clientWidth - alt_div.offsetWidth - 20) {
                x= top.document.body.clientWidth - alt_div.offsetWidth - 20;
            }
            alt_div.css('left', x + 100);
            alt_div.css('top', y + 175);
        }
        return false;
    }

    if(cur_id == talent_id && cur_num == num) return false;

    alt_div.attr("data-id", talent_id);
    alt_div.attr("data-num", num);

    var content = '';

    content += '<table width="300" border="0" cellspacing="0" cellpadding="0" class="aa-table">';
    content += '<tr><td width="14" class="aa-tl"><img src="images/d.gif" width="14" height="24"><br></td>';
    content += '<td class="aa-t aa-table-t" align="center" style="vertical-align:middle"><b style="color:' + quality_color((talents_hash[talent_id][num]['level'] - 1)) + '">' + talents_hash[talent_id][num]['title'] + '</b></td>';
    content += '<td width="14" class="aa-tr"><img src="images/d.gif" width="14" height="24"><br></td></tr>';
    content += '<tr class="bg_alt2"><td class="aa-l" style="padding:0;"></td><td  class="bg_alt3" style="padding:0;">';
    content += '<table width="275" style=" margin: 3px" border="0" cellspacing="0" cellpadding="0" class="aa-table-t"><tr>';
    content += '<td align="center" valign="top" width="60">';

    try{
        content += talents_hash[talent_id][num]['description'];
    }catch(e){ content += ' --- '; }

    content += '</td></tr></table></td><td class="aa-r" style="padding:0px"></td></tr>';
    content += '<tr><td class="aa-bl"></td><td class="aa-b"><img src="images/d.gif" width="1" height="5"></td><td class="aa-br"></td></tr>';
    content += '</table>';

    alt_div.html(content);
}

function clanGiftsOpen(){
    _top().frames['main_frame'].frames['main'].location.href='/clan_management.php?f=1&mode=gift'; return false;
}

var time_sel = 0;
var select_check_before = false;
var select_check_after = false;
var sel_check = function(obj,id) {
    if(typeof select_check_before == 'function'){
        select_check_before();
    }
    if (time_sel > 0) {
        clearTimeout(time_sel);
        time_sel = 0;
    }
    object = obj;
    time_sel = setTimeout('select_check(object,\''+id+'\')',200);
};
var select_check = function(obj,id) {
    var txt = obj.value;
    var objsel = gebi(id);
    while (objsel.type != "select-one" && objsel.nextSibling != null && !objsel.mass) {
        objsel = objsel.nextSibling;
    }
    var mas = objsel.mass;
    objsel.innerHTML = "";
    txt = txt.toLowerCase();
    for (var i in mas) {
        if (mas[i] == null) continue;
        var text = mas[i].toLowerCase();
        if (text.indexOf(txt) !== false && text.indexOf(txt) != -1) {
            var option = document.createElement('OPTION');
            option.text = mas[i];
            option.value = i;
            try {objsel.add(option,null) } catch(e) {objsel.add(option,-1)}
        }
    }
    if(typeof select_check_after == 'function'){
        select_check_after();
    }
};

function html_page_list(action, page, page_count, param={}) {
    size = param['size'] !== undefined ? param['size']: 10;
    format = param['format'] !== undefined ? param['format']: '&nbsp;%d&nbsp;';

    if (page_count < 2) return '';
    html = param['prefix'] ? param['prefix']: '<nobr>'+('Страницы: ')+'</nobr>';
    page_start = Math.floor(page/size)*size;
    page_end = Math.min(page_start+size,page_count);
    if (page_start > 0) {
        html += '<a href="#" onclick="'+action+'(0); return false;">'+sprintf(format,1)+'</a>...';
        html += '<a href="#" onclick="'+action+'('+(page_start-1)+'); return false;">&nbsp;&laquo;&nbsp;</a>';
    }
    for (i=page_start; i<page_end; i++) {
        t = sprintf(format,i+1);
        if (page === i) t = '<b>'+t+'</b>';
        html += '<a href="#" onclick="'+action+'('+i+'); return false;">'+t+'</a>';
    }
    if (page_end < page_count) {
        html += '<a href="#" onclick="'+action+'('+(page_end)+'); return false;">&nbsp;&raquo;&nbsp;</a>';
        html += '...<a href="#" onclick="'+action+'('+(page_count-1)+'); return false;">'+sprintf(format,page_count)+'</a>';
    }
    return html;
}

function html_page_list2(action, page, page_count, param={}){
    size = param['size'] !== undefined ? param['size']: 10;
    if(param['page_name'] === undefined) param['page_name'] = 'page';

    if (page_count < 2) return '';
    html = '<table border=0 cellpadding=0 cellspacing=0 class="pg w100" '+(param['add_table'] !== undefined ? param['add_table'] : '')+'><tr>';
    html += '<td class="b" width=10><nobr>'+('Страницы:')+'&nbsp;</nobr></td>';
    page_start = Math.floor(page/size)*size;
    page_end = Math.min(page_start+size,page_count);

    if (page_start > 0) {
        html += '<td class="pg-inact"><a href="#" onclick="'+action+'(0); return false;" class="pg-inact_lnk">1</a></td><td width=17>...</td>';
        html += '<td class="pg-inact"><a href="#" onclick="'+action+'('+(page_start-1)+'); return false;" class="pg-inact_lnk">&laquo;</a></td>';
    }
    for (i=page_start; i<page_end; i++) {
        cl_pfx = (page === i ? 'pg-act': 'pg-inact');
        html += '<td class="'+cl_pfx+'"><a href="#" onclick="'+action+'('+(i)+'); return false;" class="'+cl_pfx+'_lnk">'+(i+1)+'</a></td>';
    }
    if (page_end < page_count) {
        html += '<td class="pg-inact"><a href="#" onclick="'+action+'('+(page_end)+'); return false;" class="pg-inact_lnk">&raquo;</a></td>';
        html += '<td width=17>...</td><td class="pg-inact"><a href="#" onclick="'+action+'('+(page_count-1)+'); return false;" class="pg-inact_lnk">'+page_count+'</a></td>';
    }
    html += '<td style="text-align: left;">'+(param['add'] !== undefined ? param['add'] : '')+'</td>';
    t = [];
    html += '<td width="1%" style="text-align:right" nowrap>';
    if (page > 0) {
        html += '<a href="#" onclick="'+action+'('+(page-1)+'); return false;"><img src="/images/p-left-red.gif" border=0 width=29 height=17 title="'+('Предыдущая')+'"></a>';
    } else {
        html += '<img src="/images/p-left-gray.gif" border=0 width=29 height=17 title="'+('Предыдущая')+'">';
    }
    html += '<img src="/images/pg-act.gif" border=0 width=17 height=17>';
    if (page < page_count-1) {
        html += '<a href="#" onclick="'+action+'('+(page+1)+'); return false;"><img src="/images/p-right-red.gif" border=0 width=29 height=17 title="'+('Следующая')+'"></a>';
    } else {
        html += '<img src="/images/p-right-gray.gif" border=0 width=29 height=17 title="'+('Следующая')+'">';
    }
    html += '</td>';
    html += '</tr></table>';
    return  html;
}
function startPuzzle(params) {
    var useCanvas = (params.useCanvas == undefined) ? false : params.useCanvas >= 1;
    var swf_params = [];
    swf_params.push("pictureURI=" + params["pictureURI"]);
    swf_params.push("segmentsOnSide=" + params["segmentsOnSide"]);
    swf_params.push("quickStart=" + params["quickStart"]);
    swf_params.push("locale_file=" + _top().locale_file);

    var html;
    if (useCanvas) {
        html = document.createElement("div");
        if (document.puzzle) {
            document.puzzle.destroy();
        }
        document.puzzle = new canvas.app.CanvasPuzzle(params,html);
    } else {
        html = AC_FL_RunContent(
            'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,60,0',
            'width', params['width'],
            'height', params['height'],
            'src', 'images/swf/puzzle.swf',
            'quality', 'high',
            'pluginspage', 'http://www.macromedia.com/go/getflashplayer',
            'align', 'middle',
            'play', 'true',
            'loop', 'true',
            'scale', 'showall',
            'wmode', 'transparent',
            'devicefont', 'false',
            'id', 'WohPlayer',
            'bgcolor', '#ffffff',
            'name', 'WohPlayer',
            'menu', 'true',
            'cancelwrite','true',
            'allowScriptAccess','sameDomain',
            'allowFullScreen','true',
            'movie', 'images/swf/puzzle.swf',
            'salign', '',
            'flashVars', swf_params.join('&')
        );
    }

    var options = {width:params['width'], height:params['height']};

    confirmCenterDiv(html, options);
}

document.addEventListener("keyup",itemsRight);
document.addEventListener("keydown",keyDownHandler);

function itemsRight(e) {
    var input = $(e.target).attr('contentEditable');
    switch (e.target.tagName) {
        case 'INPUT':
        case 'TEXTAREA':
            input = true;
            break;
    }
    if (!input) {
        if (e.shiftKey || e.ctrlKey || e.altKey) {
            var swf = getSWF('items_right');
            if (swf && swf.externalKey) swf.externalKey({shiftKey : e.shiftKey, ctrlKey : e.ctrlKey, altKey : e.altKey, keyCode : e.keyCode, code : e.code});
        }
    }
}

function keyDownHandler(e) {
    var input = $(e.target).attr('contentEditable');
    switch (e.target.tagName) {
        case 'INPUT':
        case 'TEXTAREA':
            input = true;
            break;
    }
    if (!input) {
        var swf = getSWF('game');
        if (swf && swf.externalKey) swf.externalKey(e);
    }
}

function canvasIsSupported() {
    var result = /Chrome\/(\d+)/.exec(navigator.userAgent);
    if (result && result[1] && parseInt(result[1]) < 30) {
        return false;
    }
    return true;
}

function jailExit() {
    if (window.jailExitStarted) return;
    window.jailExitStarted = true;
    entry_point_request('common', 'jailExit', {}, function(response){
        if (response['status'] != DATA_OK && response['error']) {
            showError(response['error']);
            window.jailExitStarted = false;
        } else {
            location.reload();
        }
    });
}

function copyToClipboard(str) {
    const el = document.createElement('textarea');
    el.value = str;
    el.setAttribute('readonly', '');
    el.style.position = 'absolute';
    el.style.left = '-9999px';
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
}

function openPremium() {
    tProcessMenu("b36",  {url: '/area_banks.php?mode=premium'});
}

function openLocator() {
    window.open('/friend_list.php?mode=located', "", "width=920,height=700,location=yes,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no");

}


function confirm_front(area_id) {
    entry_point_request('front', 'fight_join', {area_id: area_id}, function(response) { if (response['status'] != 100) showError(response['error'],response['error_crc']); })
}

function front_conf(front_id) {
    var params = {};
    if (front_id) params = {'front_id': front_id};
    entry_point_request('front', 'conf', params, function(response) {
        if (response['status'] == DATA_OK) swfObject('area', response);
    });
}

function front_fight_start() {
    entry_point_request('front', 'fight_start', {}, function(response){
        if (response['status'] != DATA_OK && response['error']) {
            showError(response['error'],response['error_crc']);
        } else {
            swfObject('area', response);
        }
    });
}

function front_locations() {
    entry_point_request('front', 'locations', {}, function(response){
        if (response['status'] == DATA_OK) {
            swfObject('area', response);
            swfObject('world', response);
        }
    });
}


function isWebGLSupported() {
    var contextOptions = { stencil: true, failIfMajorPerformanceCaveat: true };
    try {
        if (!window.WebGLRenderingContext) {
            return false;
        }
        var canvas = document.createElement('canvas');
        var gl = canvas.getContext('webgl', contextOptions) || canvas.getContext('experimental-webgl', contextOptions);
        var success = !!(gl && gl.getContextAttributes().stencil);
        if (gl) {
            var loseContext = gl.getExtension('WEBGL_lose_context');
            if (loseContext) {
                loseContext.loseContext();
            }
        }
        gl = null;
        return success;
    } catch (e) {
        return false;
    }
}

function topDwar(data) {
    if (_top().dwar) {
        for (var i in data) _top().dwar[i] = data[i];
    } else if (_top().dialogArguments && _top().dialogArguments.win && _top().dialogArguments.win.dwar) {
        for (var i in data) _top().dialogArguments.win.dwar[i] = data[i];
    }
}

function main_counter_init(cnt, img, msg) {
    var html = '';
    html += '<div class="main_counter"'+(msg !== undefined ? ' title="'+msg+'"' : '')+'><div class="main_counter_cont">';

    var str = cnt.toString();
    for(i = 0; i < str.length; i++) {
        html += '<img src="/images/counter/'+str[i]+'.png">';
    }
    if(img !== undefined) html += '<img src="/images/counter/'+img+'.png">';
    html += '</div></div>';
    document.write(html);
}

var logitems_enabled = false;
function logitems(s){
    if(!logitems_enabled) return;
    console.log(s);
}

var trig_swf_data = function(tar, name, data){
    arr = data.split("@");
    switch (tar) {
        case 'items':
            logitems(arr);
            if(arr.length === 2) {
                switch(arr[0]) {
                    case "from_small":
                        _top().frames['main_frame'].items_ex.init_from_small(arr[1]);
                        break;
                    case "update_cnt":
                        _top().frames['main_frame'].items_ex.update_cnt(arr[1]);
                        try{
                            _top().frames['main_frame'].frames['main']._NPsymagic.update_cnt(arr[1]);
                        }catch (e) {}
                        break;
                    case "set_cooldown":
                        _top().frames['main_frame'].items_ex.set_colldown(arr[1]);
                        break;
                    case "EffList":
                        _top().frames['main_frame'].items_ex.getEffList(arr[1]);
                        break;
                    case "HotKey":
                        var key = (parseInt(arr[1]) - 1);
                        //Просто в боевке нажали на кнопку юз, не работает (Древняя боевка флешки не посылает такой хуиты!)
                        if(!isNaN(key) && key >= 0) _top().frames['main_frame'].items_ex.release(key);
                        break;
                    case 'NextPage':
                        _top().frames['main_frame'].items_ex.nextPage();
                        break;
                    case 'PrevPage':
                        _top().frames['main_frame'].items_ex.prevPage();
                        break;
                    default:
                        logitems("Nothink " + arr[0]);
                }
            }
            break;
        case 'items_right':
            if(arr.length === 2) {
                switch(arr[0]) {
                    case 'OpenBag':
                        _top().frames['main_frame'].right_menu_vars['2']['function']();
                        break;
                    case 'OpenMount':
                        _top().frames['main_frame'].right_menu_vars['3']['function']();
                        break;
                    default:
                        break;
                }
            }
            break;
    }
}

function trig_js_data(name, data){
    arr = data.split("@");
    logitems(arr);
    if(arr.length === 2) {
        switch(arr[0]) {
            case "updateFlags":
                _top().frames['main_frame'].frames['main'].updatePersFlags(parseInt(arr[1]));
                break;
        }
    }
}

/*Функции
* THE DOLBAEB SCRIPT PRODUCTION
* */

var getCurrentTime = function(){
    return new Date().getTime();
};

function CdTimer(_eff_item){
    var self = this;
    this.visible = false;

    this._eff_item = _eff_item;
    this._eff_idx = _eff_item.idx;

    this._sel_eff_cd = false;
    this._sel_cdtime = false;

    this.timeBegin = 0;
    this.timeFinish = 0;
    this.timeSegment = 0;

    this.timerId = false;

    this.startCooldown = function(timerVal, leftVal){
        if(parseInt(timerVal) <= 0) {
            return undefined;
        }
        if(this._eff_item.notEff) return undefined;
        this.timeBegin = getCurrentTime() - (timerVal - leftVal);
        this.timeSegment = timerVal;
        this.timeFinish = this.timeBegin + this.timeSegment; //-1 для интервала
        this.visible = true;

        this._sel_eff_cd.show();
        logitems(this._sel_eff_cd);
        this._sel_cdtime.html(Math.round((this.timeFinish - this.timeBegin) / 1000)); //Задаем сразу времечко
        this.timerId = setInterval(function(){ self.timer_tick(); }, 1000);
    };
    this.getSecondsRemains = function(nowTime) {
        return this.timeFinish - nowTime;
    };
    this.remCooldown = function() {
        if(this._eff_item.notEff) return undefined;
        this._sel_eff_cd.hide();
        this.visible = false;
        try{clearInterval(this.timerId);}catch (e) {}
    };

    this.timer_tick = function(){
        now_time = getCurrentTime();
        //logitems(now_time);
        //logitems(this.timeFinish);
        if(now_time >= this.timeFinish) {
            this.remCooldown();
            return undefined;
        }
        this._sel_cdtime.html(Math.round((this.timeFinish - now_time) / 1000));
    };

    this.init = function(){
        if(this._eff_item.notEff) return undefined;
        if(!this._sel_eff_cd) this._sel_eff_cd = $('#eff_index_' + this._eff_idx + ' .eff_cd');
        if(!this._sel_cdtime) this._sel_cdtime = $('#eff_index_' + this._eff_idx + ' .cdtime');
    };
}

function Eff_item(_eff, index){
    this.hide = false;
    this.notEff = false;
    try{
        this.idx = index;
        this.disabled = false;
        this.cdTimer = new CdTimer(this);
        if(_eff !== undefined){
            this.EffId = parseInt(_eff.EffId);
            this.slotNum = parseInt(_eff._slot_num);
            this.cnt = parseInt(_eff.EffCnt);
            this.cdTime = parseInt(_eff.cdTime);
            this.cdGripId = parseInt(_eff.cdGripId);
            this.picture = _eff.EffPic;
        }else{
            this.notEff = true;
        }
    }catch (e) {}
}

//Класс items
function Items_class(){
    this.myPicPath = '';
    this.asInventory = false;
    this.myInvFocus = false;
    this.myPageCur = 1;
    this.myPageMax = 1;
    this.myPages = [];
    this.myClipsAll = [];
    this.myDatLen = 0;
    this.myDatArr = [];
    this.mySlotsTotal = 4; //Кол-во слотов у игрока
    this.mySlotsPage = 6; //Кол-во слотов на странице
    this.flg1 = false;
    this.myClipsForId = [];
    this.eff_item_list = [];
    this.myMinCooldown = 5000;

    this.alt_active = true;

    //colldown controller
    this.grips_cfg = {};
    this.new_cd_flag = false;
    this.new_cd_time = 0;
    this.new_cd_time_real = 0;

    //selectors Jquery
    this._item_eff_list = false;
    this._b_page_text = false;
    this._items_add = false;

    this.pg_prev = { enabled: true };
    this.pg_next = { enabled: true }; //В будущем привязать функции сюда

    this.prevPage = function() {
        if(this.myPageCur > 1) {
            this.myPageCur--;
            this.showCurPage();
        }
    };
    this.nextPage = function() {
        if(this.myPageCur < this.myPageMax) {
            this.myPageCur++;
            this.showCurPage();
        }
    };

    this.init_selectors = function(){
        if(!this._item_eff_list) this._item_eff_list = $('.item_eff_list');
        if(!this._b_page_tex) this._b_page_tex = $('.items_cont .b_page_text');
        //if(!this._items_add) this._items_add = $('.items_cont .items_add');
    };

    this.init = function(vars){
        //Jquery

        this.asInventory = true;
        this.flg1 = false;
        this.myPicPath = vars['PicPath'] !== undefined ? vars['PicPath'] : "/images/data/artifacts/";
        if(vars['slotsTotal'] !== undefined) this.mySlotsTotal = parseInt(vars['slotsTotal']);
        this.mySlotsPage = (parseInt(vars['pageSlots']) ? parseInt(vars['pageSlots']) : 6);
        if(vars['curSlot'] !== undefined) this.myPageCur = Math.ceil(parseInt(vars['curSlot']) / this.mySlotsPage);
        logitems(" mySlotsTotal=" + this.mySlotsTotal);
        logitems(" curSlot=" + this.myPageCur);
        logitems(" myPageCur=" + this.myPageCur);


        if(this.mySlotsPage > 10) this.mySlotsPage = 10;
        if(this.mySlotsPage < 4) this.mySlotsPage = 4;
        add_slots_cnt = this.mySlotsPage - 4; //4 это 4 слота в неподходящем месте, а не что-то другое

        /*отключаем подсказки если надо*/
        try{ this.alt_active = (parseInt(vars['altHide']) === 1 ? false : true); }catch (e) {}

        //Контролируем кол-во дополнительных слотов
        /*
        _html_add_slots = '';
        for(i = 0; i < add_slots_cnt; i++) _html_add_slots += '<div class="item_bg_cent"></div>';
        if(_html_add_slots !== '') this._items_add.html(_html_add_slots);
        else this._items_add.html('');
        */

        /* Подстраиваем нижную фигню где страницы */
        /*
        _height = Math.floor(59 * this.mySlotsPage * 1.038);
        $('.items_cont .item_eff_pages').css('top', _height + 2);
        */

        if(vars['EffList'] !== undefined) {
            this.myDatLen = 0;
            this.myDatArr = [];
            eff_list = vars['EffList'].split(",");
            i = 0;
            while(i < eff_list.length) {
                _eff = eff_list[i].split(":",4);
                slot_id = parseInt(_eff[0]);
                this.myDatArr[slot_id] = {EffId:_eff[1],EffCnt:_eff[2],EffPic:_eff[3]};
                this.myDatLen = Math.max(this.myDatLen,slot_id);
                logitems(" slot[" + slot_id + "] id[" + _eff[1] + "] cnt[" + _eff[2] + "] pic[" + _eff[3] + "]");
                i++;
            }
            this.createList();
        }
    };

    //Нажатие на эффект
    this.release = function(idx){
        _eff_item = this.eff_item_list[idx];
        if(_eff_item === undefined) return false;
        if(_eff_item.notEff) return false;
        if(_eff_item.cdTimer.getSecondsRemains(getCurrentTime()) > 0) return false; //Похуй это разрешить, вдруг забагается таймер ( обидно будет )
        this.alt(idx, _eff_item.EffId, 0);
        if(swfTransfer("item", "game","ping@null") === true) {
            swfTransfer("item", "game","useEffect@" + _eff_item.EffId);
        } else {
            unsetEffect(_eff_item.EffId, _eff_item.slot_num);
        }
    };

    this.alt = function(idx, eff_id, act) {
        return 0;
        if(act == 2) {
            var has_shown = false;
            try {if (top.frames['main_frame'].frames['main'].frames['user_iframe'].__user_php__) {has_shown = true;}} catch (e) {}
            try {if (top.frames['main_frame'].frames['main'].__fight_php__) {has_shown = true;}} catch (e) {}
            if(!has_shown) return false;
        }
        if(this.eff_item_list[idx].hide || this.eff_item_list[idx].notEff) return false;
        if(!(this.eff_item_list[idx].EffId !== undefined && this.eff_item_list[idx].EffId !== 'undefined')) return false;
        return artifactAltSimple(eff_id, act);
    };

    this.createList = function(){
        this.myClipsAll = [];
        this.myClipsForId = [];
        this.myPages = [];
        this.myPageMax = Math.ceil(this.mySlotsTotal / this.mySlotsPage);
        this.pg_prev.enabled = this.myPageMax > 1;
        this.pg_next.enabled = this.myPageMax > 1;
        this.myDatLen = Math.ceil(this.mySlotsTotal / this.mySlotsPage) * this.mySlotsPage;

        _slot_num = 0;
        _cur_slot = 1;

        for(i = 0; i < this.eff_item_list.length; i++){
            try{ this.eff_item_list[i].cdTimer.remCooldown(); /*free space IMPORTANT! */ }catch (e) {}
        }
        delete this.eff_item_list; //free space
        this.eff_item_list = [];
        i = 0;
        while(_cur_slot <= this.mySlotsTotal) {
            _slot_num++;
            _eff = this.myDatArr[_cur_slot];
            if(_eff !== undefined){
                _eff._slot_num = _slot_num;
            }
            item = new Eff_item(_eff, i);
            this.eff_item_list.push(item);
            _cur_slot++; i++;
        }

        if(this.eff_item_list.length <= 0) return false;

        //Генерация списка HTML
        this._item_eff_list.html('');
        html = '';
        _total = Math.max(this.eff_item_list.length, Math.ceil(this.eff_item_list.length / this.mySlotsPage) * this.mySlotsPage);
        s_index = (this.myPageCur * this.mySlotsPage) - this.mySlotsPage;
        s_index_max = s_index + this.mySlotsPage;
        for(i = 0; i < _total; i++){
            displayed = false;
            if(i >= s_index && i < s_index_max) displayed = true;

            _eff_item = this.eff_item_list[i];
            if(_eff_item !== undefined) {
                this.eff_item_list[i].idx = i;

                html_alt = (this.alt_active ? 'onmousemove="items_ex.alt('+_eff_item.idx+', '+_eff_item.EffId+', 2);" onmouseleave="items_ex.alt('+_eff_item.idx+', '+_eff_item.EffId+', 0);"' : '');

                html += '<div '+html_alt+' class="eff_item '+(_eff_item.hide ? 'eff_hidden' : '')+'" onclick="items_ex.release('+i+'); return false;" style="'+(!displayed ? 'display:none;' : '')+'" id="eff_index_' + _eff_item.idx + '">' +
                    '<div class="eff_hover"></div>' +
                    '<div class="eff_bg_top"></div><div class="eff_bg"></div>' +
                    '<div class="eff_cd" style="display: none;"><div class="cdtime"></div></div><div class="art_bg">';
                if (_eff_item.picture !== undefined) html += '<img src="' + this.myPicPath + _eff_item.picture + '">';
                html += '</div>';

                //if (_eff_item.cnt !== undefined)
                html += '<div class="eff_cnt">' + (_eff_item.cnt !== undefined ? _eff_item.cnt : '') + '</div>';

                html +=
                    '</div>';
            }else{
                html += '<div class="eff_item disabled" style="display: none;" id="eff_index_' + i + '"><div class="eff_bg_top"></div><div class="eff_bg"></div><div class="eff_cnt"></div></div>';
            }
        }

        var bg_top = '';
        var bg_bottom = '';

        html = bg_top + html + bg_bottom;

        html += ' <div class="item_eff_pages"><div class="butt_bg left"><div class="left_butt" onclick="items_ex.prevPage();"></div></div><div class="butt_bg right"><div class="right_butt" onclick="items_ex.nextPage();"></div></div><div class="b_page_text">1</div></div>';

        this._item_eff_list.html(html);

        //init cdtimers
        logitems(this.eff_item_list);

        for(i = 0; i < this.eff_item_list.length; i++){
            if(this.eff_item_list[i].cdTimer !== undefined) this.eff_item_list[i].cdTimer.init();
        }
        this.showCurPage();

        /*for debug only*/
        /*for(i = 0; i < this.eff_item_list.length; i++){
            if(this.eff_item_list[i].cdTimer !== undefined) this.eff_item_list[i].cdTimer.startCooldown(this.myMinCooldown,this.myMinCooldown);
        }*/
    };

    this.showCurPage = function(){
        _total = Math.max(this.eff_item_list.length, Math.ceil(this.eff_item_list.length / this.mySlotsPage) * this.mySlotsPage);
        s_index = (this.myPageCur * this.mySlotsPage) - this.mySlotsPage;
        s_index_max = s_index + this.mySlotsPage;
        for(i = 0; i < _total; i++) {
            if (i >= s_index && i < s_index_max) $('#eff_index_' + i).show();
            else $('#eff_index_' + i).hide();
        }
        this._b_page_tex = $('.items_cont .b_page_text');
        this._b_page_tex.html(this.myPageCur);
    };

    this.getEffList = function(dat){
        eff_list = dat.split("\x02");
        logitems(eff_list);
        this.myDatLen = 0;
        this.myDatArr = [];
        this.myMinCooldown = 1000 * parseInt(eff_list.pop());
        i = s_max = 0;
        while(i < eff_list.length) {
            _eff = eff_list[i].split("\x01");
            logitems(_eff);
            slot_id = parseInt(_eff[0]);
            this.myDatArr[slot_id] = {EffId:_eff[1],EffCnt:_eff[2],EffPic:_eff[3],cdTime:1000 * parseInt(_eff[4]),cdGripId:_eff[5],cdLeft:1000 * parseInt(_eff[6])};
            this.myDatLen = Math.max(this.myDatLen,slot_id);
            this.max_slots = Math.max(this.max_slots,slot_id);
            s_max = Math.max(s_max,slot_id);
            i++;
        }
        if(this.mySlotsTotal < s_max) this.mySlotsTotal = s_max;
        logitems(this.myDatArr);
        logitems(this.myDatLen);
        this.createList();

        logitems('calc colldown start');
        this.grips_cfg = {};
        minCooldown = this.myMinCooldown;
        _cdLeft = 0;
        for(_idx in this.myDatArr) {
            _eff_i = this.myDatArr[_idx];
            _cdGripId = _eff_i.cdGripId;
            _cdTime = _eff_i.cdTime - _eff_i.cdLeft;
            logitems(" cd_expired=" + _cdTime + " (" + _eff_i.cdTime + "-" + _eff_i.cdLeft + ")");
            if(_eff_i.cdTime !== 0) {
                minCooldown = Math.min(minCooldown,_cdTime);
            }
            _cdLeft = Math.max(_cdLeft,_eff_i.cdLeft);
            if(this.grips_cfg[_cdGripId] === undefined) {
                this.grips_cfg[_cdGripId] = {max_cooldown:_eff_i.cdTime,cdLeft:_eff_i.cdLeft};
            } else if(this.grips_cfg[_cdGripId].max_cooldown < _eff_i.cdTime) {
                this.grips_cfg[_cdGripId].max_cooldown = _eff_i.cdTime;
                this.grips_cfg[_cdGripId].cdLeft = _eff_i.cdLeft;
            }
        }
        logitems(" min_cd_expired=" + minCooldown);
        logitems(" max_cd_left=" + _cdLeft);
        this.new_cd_flag = false;
        if(minCooldown < this.myMinCooldown) {
            if(_cdLeft > 0) {
                logitems(" apply myMinCooldown(" + this.myMinCooldown + " ms) to all items where cdLeft < " + this.myMinCooldown);
                this.new_cd_flag = true;
                this.new_cd_time =  this.myMinCooldown - minCooldown;
                this.new_cd_time_real = getCurrentTime();
            }
        }
        this.apply_new_cd_time();
        logitems('calc colldown stop');
    };

    this.apply_new_cd_time = function() {
        logitems("apply_new_cd_time begin");
        if(this.new_cd_flag === true) {
            cdTimeReal = this.new_cd_time_real;
            for(_idx in this.eff_item_list) {
                _eff = this.eff_item_list[_idx];
                logitems(" cItem=" + _eff);
                if(_eff.hide !== false && _eff.cdTimer !== undefined) {
                    if(_eff.cdTimer.visible === true) {
                        logitems(" " + _eff.cdTimer + " " + _eff.cdTimer.getSecondsRemains + " " + _eff.cdTimer.getSecondsRemains(cdTimeReal) + " ? " + this.new_cd_time);
                        if(_eff.cdTimer.getSecondsRemains(cdTimeReal) < this.new_cd_time) {
                            _eff.cdTimer.startCooldown(Math.max(this.myMinCooldown,this.new_cd_time),this.new_cd_time);
                            logitems(" restart cooldown " + this.new_cd_time);
                        }else{
                            logitems(" cooldown time more or equal needed");
                        }
                    } else {
                        _eff.cdTimer.startCooldown(this.new_cd_time,this.new_cd_time);
                        logitems("  start cooldown " + this.new_cd_time);
                    }
                }
            }
            logitems("---");
        }
        for(_idx in this.grips_cfg) {
            logitems(" grip_id=" + _idx);
            for(i in this.eff_item_list) {
                _eff = this.eff_item_list[i];
                if(_eff.cdGripId == _idx) {
                    logitems("  " + _eff + " startCooldown(" + this.grips_cfg[_idx].max_cooldown + ", " + this.grips_cfg[_idx].cdLeft + ")");
                    if(this.grips_cfg[_idx].cdLeft !== 0) {
                        _eff.cdTimer.startCooldown(this.grips_cfg[_idx].max_cooldown,this.grips_cfg[_idx].cdLeft);
                    }
                }
            }
        }
        logitems("apply_new_cd_time end");
    };

    this.update_cnt = function(dat){
        logitems("UPD_CNT " + dat);
        info = dat.split(",");
        _e_id = parseInt(info[0]);
        _e_cnt = parseInt(info[1]);
        _indx = this.get_index_item_list(_e_id);
        _eff_item = this.eff_item_list[_indx];
        if(_eff_item !== undefined) {
            curCdTime = _eff_item.cdTime !== 0 ? _eff_item.cdTime : this.myMinCooldown;
            currentTime = getCurrentTime();

            n_number = parseInt(_eff_item.cnt) + _e_cnt;
            if(n_number <= 0) {
                _eff_item.cdTimer.visible = false;
                _eff_item.hide = true;
                _eff_item.notEff = true;
                _eff_item.cnt = 0;
                $('#eff_index_' + _eff_item.idx).addClass('eff_hidden');
                this.alt(_eff_item.idx, _eff_item.EffId, 0);
            }else{
                _eff_item.cnt = n_number;
                $('#eff_index_' + _eff_item.idx + ' .eff_cnt').html(_eff_item.cnt);
            }

            for(id in this.eff_item_list) {
                _eitem = this.eff_item_list[id];
                if(_eitem.hide !== true) {
                    if(_eitem.cdGripId === _eff_item.cdGripId) {
                        if(_eitem.cdTimer.visible === true) {
                            if(_eitem.cdTimer.getSecondsRemains(currentTime) < curCdTime) {
                                _eitem.cdTimer.startCooldown(curCdTime,curCdTime);
                            }else{
                                logitems("cooldown time more or equal needed // in_grip");
                            }
                        }else{
                            _eitem.cdTimer.startCooldown(curCdTime,curCdTime);
                        }
                    }
                    else if(_eitem.cdTimer.visible == true) {
                        if(_eitem.cdTimer.getSecondsRemains(currentTime) < thie.myMinCooldown) {
                            _eitem.cdTimer.startCooldown(thie.myMinCooldown,thie.myMinCooldown);
                        }else{
                            logitems("cooldown time more or equal needed // no grip");
                        }
                    }
                    else {
                        _eitem.cdTimer.startCooldown(this.myMinCooldown,this.myMinCooldown);
                    }
                }
            }
        }else{
            logitems("WARN: mc for EffId=" + _e_id + " is null");
        }
    };

    this.set_colldown = function(dat) {
        logitems("SET_COOLDOWN " + dat);
        cdSeconds = Math.max(1000,int(dat));
        tCurrent = new Date().getTime();
        for(idx in this.eff_item_list) {
            _eitem = this.eff_item_list[idx];
            if(_eitem.hide !== true) {
                if(_eitem.cdTimer.visible === true) {
                    if(_eitem.cdTimer.getSecondsRemains(tCurrent) < cdSeconds) {
                        _eitem.cdTimer.startCooldown(cdSeconds,cdSeconds);
                    } else {
                        logitems("cooldown time more or equal needed // no grip");
                    }
                } else {
                    _eitem.cdTimer.startCooldown(cdSeconds,cdSeconds);
                }
            }
        }
    };

    this.init_from_small = function(dat) {
        logitems("init_from_small " + dat);
        i_data = dat.split("&");
        i = 0;
        _vars = {};
        while(i < i_data.length) {
            d_item = i_data[i].split("=");
            if(d_item.length === 2) {
                _vars[d_item[0]] = d_item[1];
                logitems("pair: " + d_item[0] + "=" + d_item[1]);
            }
            i++;
        }
        this.init(_vars);
    };

    this.get_index_item_list = function(eff_id){
        for(i = 0; i < this.eff_item_list.length; i++) {
            if(this.eff_item_list[i].EffId === eff_id){
                return i;
            }
        }
    };
}


var Base64 = {

    // private property
    _keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

    // public method for encoding
    encode : function (input) {
        var output = "";
        var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
        var i = 0;

        input = Base64._utf8_encode(input);

        while (i < input.length) {

            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);

            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;

            if (isNaN(chr2)) {
                enc3 = enc4 = 64;
            } else if (isNaN(chr3)) {
                enc4 = 64;
            }

            output = output +
                this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
                this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);

        }

        return output;
    },

    // public method for decoding
    decode : function (input) {
        var output = "";
        var chr1, chr2, chr3;
        var enc1, enc2, enc3, enc4;
        var i = 0;

        input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

        while (i < input.length) {

            enc1 = this._keyStr.indexOf(input.charAt(i++));
            enc2 = this._keyStr.indexOf(input.charAt(i++));
            enc3 = this._keyStr.indexOf(input.charAt(i++));
            enc4 = this._keyStr.indexOf(input.charAt(i++));

            chr1 = (enc1 << 2) | (enc2 >> 4);
            chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
            chr3 = ((enc3 & 3) << 6) | enc4;

            output = output + String.fromCharCode(chr1);

            if (enc3 != 64) {
                output = output + String.fromCharCode(chr2);
            }
            if (enc4 != 64) {
                output = output + String.fromCharCode(chr3);
            }

        }

        output = Base64._utf8_decode(output);

        return output;

    },

    // private method for UTF-8 encoding
    _utf8_encode : function (string) {
        string = string.replace(/\r\n/g,"\n");
        var utftext = "";

        for (var n = 0; n < string.length; n++) {

            var c = string.charCodeAt(n);

            if (c < 128) {
                utftext += String.fromCharCode(c);
            }
            else if((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            }
            else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }

        }

        return utftext;
    },

    // private method for UTF-8 decoding
    _utf8_decode : function (utftext) {
        var string = "";
        var i = 0;
        var c = c1 = c2 = 0;

        while ( i < utftext.length ) {

            c = utftext.charCodeAt(i);

            if (c < 128) {
                string += String.fromCharCode(c);
                i++;
            }
            else if((c > 191) && (c < 224)) {
                c2 = utftext.charCodeAt(i+1);
                string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                i += 2;
            }
            else {
                c2 = utftext.charCodeAt(i+1);
                c3 = utftext.charCodeAt(i+2);
                string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                i += 3;
            }

        }

        return string;
    }

}