//lvl
//put fonts canvas.Const.FONT_TAHOMA_11_BOLD,
canvas.app.CanvasAvatar.prototype.init = function() {
    this.model = new canvas.app.avatar.Model(this.par);
    canvas.app.avatar.model = this.model;
    this.model.width = this.par.width;
    this.model.height = this.par.height;
    var fonts = [canvas.Const.FONT_IFLASH, canvas.Const.FONT_TAHOMA_11_BOLD, canvas.Const.FONT_TAHOMA_10_BOLD_SHARP, canvas.Const.FONT_TAHOMA_11_BOLD_STROKE_BEVEL_SHARP, canvas.Const.FONT_TAHOMA_10_STROKE];
    var resources = [
        ["ui", canvas.Config.ui + "main.json"]
    ];
    for (var i = 0; i < fonts.length; i++) resources.push(canvas.Config.fontsPath + fonts[i] + ".fnt");
    canvas.app.CanvasApp.prototype.init.call(this, resources)
};
//location
//redeclare navigateUrl
canvas.Functions.navigateToURLExtend = function(lnk, callback) {
    console.log('okan');
    if(callback === undefined) callback = function() {};
    var Conf = canvas.app.location.model;
    _top().frames['main_frame'].frames['main_hidden'].location.href = lnk;
    Conf.inWaitingAnswer = false;
    /*
    $.ajax(lnk, {
        complete : function(data, statusCode){
            callback();
            Conf.inWaitingAnswer = false;
        },
        async: true,
    });
     */
};
canvas.app.location.View.prototype.navigateToUrl = function() {
    var curTime = Date.now();
    var Conf = canvas.app.location.model;
    if (curTime - Conf.reqTime > 1e4 || !Conf.inWaitingAnswer) {
        var target = Conf.waitRefresh ? Conf.MAIN_HIDDEN : "_self";
        if (!Conf.waitRefresh) {
            console.log('not okan');
            canvas.Functions.navigateToURL(Conf.transitionLnk, target)
        } else {
            canvas.Functions.navigateToURLExtend(Conf.transitionLnk, function () {
                //_top().frames['main_frame'].updateSwf({'area':'', 'event':'', 'area_fight':''});
            });
        }
        this.main.hintManager.hide();
        Conf.reqTime = Date.now();
        Conf.inWaitingAnswer = true;
    }
};
//fight
//http to https!
canvas.app.battle.engine.Connection.prototype.initProxy = function() {
    this.proxyAddr = "https://" + canvas.app.battle.model.proxyHost + "/";
    this.proxyLoader = new canvas.utils.URLRequest;
    canvas.EventManager.addEventListener(canvas.utils.URLRequestEvent.EVENT_COMPLETE, this.proxyLoader, this.proxyCompleteHandler, this);
    canvas.EventManager.addEventListener(canvas.utils.URLRequestEvent.EVENT_ERROR, this.proxyLoader, this.proxyErrorHandler, this);
    this.proxyReady = true;
    this.proxyQueue = [];
    this.startProxy()
};
//common
//only 13 px
canvas.app.firstBattle.Model.prototype.parse = function(par) {
    this.persParams = {};
    this.persParams[canvas.Const.GENDER.MALE] = {
        nick: this.ok(par.fight_user_male_nick) ? par.fight_user_male_nick : "Warrior",
        sk: this.ok(par.fight_user_male_skeleton) ? par.fight_user_male_skeleton : 1,
        parts: this.ok(par.fight_user_male_parts) ? par.fight_user_male_parts : "590330;;;,0,0;;;,0;;;,0;;;,404;;;,590325;;;,275;;;,271;;;,590325;;;,275;;;,271;;;,590325;;;,275;;;,271;;;,590325;;;,275;;;,271;;;,590325;;;,0;;;,271;;;,590325;;;,275;;;,271;;;,590325;;;,275;;;,271;;;,590325;;;,0;;;,271;;;,590325;;;,275;;;,271;;;,590325;;;,275;;;,271;;;,590325;;;,0;;;,271;;;,590325;;;,275;;;,271;;;,590325;;;,275;;;,271;;;,590325;;;,0;;;,271;;;,0;;;,0;;;,1186;;;,0;;;,0;;;,1368;;;,0;;;,0;;;,0;;;,0;;;,0;;;,0;;;,0;;;,0;;;,0;;;",
        level: 5,
        curHp: 200,
        maxHp: 200,
        curMp: 100,
        maxMp: 100
    };
    this.persParams[canvas.Const.GENDER.FEMALE] = {
        nick: this.ok(par.fight_user_female_nick) ? par.fight_user_female_nick : "Warrior",
        sk: this.ok(par.fight_user_female_skeleton) ? par.fight_user_female_skeleton : 1,
        parts: this.ok(par.fight_user_female_parts) ? par.fight_user_female_parts : "262159;;;,0,0;;;,0;;;,0;;;,407;;;,262145;;;,274;;;,270;;;,262145;;;,274;;;,270;;;,262145;;;,274;;;,270;;;,262145;;;,274;;;,270;;;,262145;;;,0;;;,270;;;,262145;;;,274;;;,270;;;,262145;;;,274;;;,270;;;,262145;;;,0;;;,270;;;,262145;;;,274;;;,270;;;,262145;;;,274;;;,270;;;,262145;;;,0;;;,270;;;,262145;;;,274;;;,270;;;,262145;;;,274;;;,270;;;,262145;;;,0;;;,270;;;,0;;;,0;;;,1187;;;,0;;;,0;;;,1369;;;,0;;;,0;;;,0;;;,0;;;,0;;;,0;;;,0;;;,0;;;,0;;;",
        level: 5,
        curHp: 200,
        maxHp: 200,
        curMp: 100,
        maxMp: 100
    };
    this.enemyParams = {
        nick: this.ok(par.fight_enemy_nick) ? par.fight_enemy_nick : "Enemy",
        sk: this.ok(par.fight_enemy_skeleton) ? par.fight_enemy_skeleton : 1160,
        parts: this.ok(par.fight_enemy_parts) ? par.fight_enemy_parts : "",
        level: 6,
        curHp: 250,
        maxHp: 250,
        curMp: 150,
        maxMp: 150
    };
    this.link = this.ok(par.link) ? par.link : "https://" + location.host + "/"
};
//clock
canvas.modules.ChatClock = function(parent, ts, tm) {
    parent.style.backgroundImage = "url('images/tbl-main_chat-clock-bg.gif')";
    var img = document.createElement("img");
    parent.appendChild(img);
    img.src = "images/tbl-main_chat-clock-btn.gif";
    img.style.cursor = "pointer";
    img.style.position = "relative";
    img.style.top = 12;
    img.onclick = this.clickHandler.bind(this);
    var span = document.createElement("span");
    parent.appendChild(span);
    span.style.position = "relative";
    span.style.color = "#f9dfa1";
    span.style.fontSize = 13;
    span.style.fontWeight = "bold";
    span.style.top = 13;
    span.style.left = 2;
    this.span = span;
    var d1 = new Date;
    if (tm == undefined) {
        tm = d1.getHours() + ":" + d1.getMinutes() + ":" + d1.getSeconds()
    }
    var ar = tm.split(":");
    var d2 = new Date;
    d2.setHours(parseInt(ar[0]));
    d2.setMinutes(parseInt(ar[1]));
    d2.setSeconds(parseInt(ar[2]));
    this.delta = d2.getTime() - d1.getTime();
    setInterval(this.timerHandler.bind(this), 1e4);
    this.timerHandler()
};
canvas.modules.ChatClock.prototype.clickHandler = function() {
    chat_change_time_zone()
};
canvas.modules.ChatClock.prototype.timerHandler = function() {
    var d = new Date(Date.now() + this.delta);
    this.span.innerText = canvas.Functions.setNumberLen(d.getHours()) + ":" + canvas.Functions.setNumberLen(d.getMinutes())
};
canvas.modules.ChatClock.prototype.getTime = function() {
    return Date.now() + this.delta
};
canvas.modules.ChatClock.prototype.time_shift = function(minutes) {
    this.delta += parseInt(minutes) * 6e4
};
//magic
canvas.app.CanvasMagic.prototype.init = function() {
    this.model = new canvas.app.magic.Model(this.par);
    this.model.width = this.par.width;
    this.model.height = this.par.height;
    this.model.type = this.par.type;
    canvas.app.magic.model = this.model;
    switch (this.model.type) {
        case canvas.app.magic.Const.TYPE_AURA:
            canvas.app.magic.modelAura = this.model;
            break;
        case canvas.app.magic.Const.TYPE_SHADOW:
            canvas.app.magic.modelShadow = this.model;
            break;
        default:
            canvas.app.magic.modelMagic = this.model
    }
    var fonts = [canvas.Const.FONT_IFLASH, canvas.Const.FONT_TAHOMA_9_STROKE];
    var resources = [
        ["ui", canvas.Config.ui + "magic.json"]
    ];
    for (var i = 0; i < fonts.length; i++) resources.push(canvas.Config.fontsPath + fonts[i] + ".fnt");
    canvas.app.CanvasApp.prototype.init.call(this, resources)
};
//path
canvas.app.location.Finder.prototype.find = function(start_loc_id, target_loc_id) {
    canvas.app.location.log("Finder: start = " + start_loc_id + ", target = " + target_loc_id, 10551296);
    var Conf = canvas.app.location.model;
    this.p1 = start_loc_id;
    this.p2 = target_loc_id;
    if (String(this.p1) == String(this.p2)) {
        return [0]
    }
    this.p2_tmp = this.p2;
    this.p1_tmp = this.p2_tmp;
    this.arr_tmp = [];
    this.arr_trash = [];
    this.cache_cont = {};
    this.arr_loc = [];
    this.arr_tmp.push(this.p1);
    this.arr_trash = this.arr_trash.concat(this.arr_tmp);
    var k = 0;
    var len = Conf.NUM_LOCATIONS;
    if (len <= 0) return [0];
    link: while (k < len) {
        k++;
        var arr2 = [];
        for (var i = this.arr_tmp.length; i--;) {
            var arr1 = [];
            arr1 = this.getNearLoc(this.arr_tmp[i]);
            this.clearArr(arr1, this.arr_trash);
            this.cache_cont[this.arr_tmp[i]] = arr1;
            if (this.seach(arr1, this.p2)) {
                break link
            }
            this.arr_trash = this.arr_trash.concat(arr1);
            arr2 = arr2.concat(arr1)
        }
        this.arr_tmp = [];
        this.arr_tmp = this.arr_tmp.concat(arr2);
        if (this.arr_tmp.length == 0) {
            return [-1]
        }
    }
    this.arr_loc.push(this.p1_tmp);
    var repeat_cnt_over = 30;
    while (this.p1_tmp != this.p1) {
        console.log(this.p1_tmp + " = " + this.p1);
        this.p2_tmp = this.getNextCacheLoc(this.p2_tmp);
        this.p1_tmp = this.p2_tmp;
        this.arr_loc.push(this.p1_tmp);
        repeat_cnt_over--;
        if(repeat_cnt_over <= 0) break;
    }
    return this.arr_loc
};



/*

canvas.app.battle.engine.MCmd.prototype.persActEffects = function() {
    var parser = canvas.app.battle.model.serverParser;
    var conf = canvas.app.battle.model;
    var baseLnk = this.baseLnk;
    var iPersId = parser.params[2].val;
    var cmdIndex = parser.params.pop().val;
    if (iPersId == conf.persId) {
        conf.persLastEffectsUpdateIndex = cmdIndex
    } else if (iPersId == conf.oppId) {
        conf.oppLastEffectsUpdateIndex = cmdIndex
    }
    var curArtId;
    var curPic;
    var curCnt;
    var curTitle;
    var curAnimData;
    var eetimeMax;
    var turnsLeft;
    var g;
    var increaser = 7;
    if (conf.persBafsFlag) {
        conf.persBafsFlag = false;
        var sp1 = "|";
        var sp2 = ";";
        var bafs_delta_time = conf.serverTimestamp - conf.clientTimestamp;
        var pers_bafs = iPersId.toString() + sp1 + bafs_delta_time.toString() + sp1;
        for (g = 3; g < parser.params.length; g += increaser) {
            curArtId = parser.params[g].val;
            curPic = canvas.Config.artifactsPath + parser.params[g + 3].val;
            curCnt = parser.params[g + 1].val;
            curTitle = parser.params[g + 2].val;
            eetimeMax = parser.params[g + 5].val;
            turnsLeft = parser.params[g + 6].val;
            canvas.app.battle.log(" // curArtId  " + curArtId, 10066329);
            canvas.app.battle.log(" // curPic    " + curPic, 10066329);
            canvas.app.battle.log(" // curCnt    " + curCnt, 10066329);
            canvas.app.battle.log(" // curTitle  " + curTitle, 10066329);
            canvas.app.battle.log(" // eetimeMax " + eetimeMax, 10066329);
            pers_bafs += curArtId.toString() + sp2 + curPic + sp2 + curCnt.toString() + sp2 + curTitle + sp2 + eetimeMax.toString() + sp2 + turnsLeft.toString() + sp1
        }
        baseLnk.sendData("mem", "pers_bafs@" + pers_bafs.substr(0))
    }
    var curList = [];
    var curListHash = {};
    var auraUpdate = false;
    for (g = 3; g < parser.params.length; g += increaser) {
        curArtId = parser.params[g].val;
        curPic = canvas.Config.artifactsPath + parser.params[g + 3].val;
        curCnt = parser.params[g + 1].val;
        curTitle = parser.params[g + 2].val;
        curAnimData = parser.params[g + 4].val;
        eetimeMax = parser.params[g + 5].val;
        turnsLeft = parser.params[g + 6].val;
        if (!auraUpdate) auraUpdate = conf.testCurrentAura(curArtId, iPersId);
        if (curListHash[curPic] != undefined) {
            curList[curListHash[curPic]].cnt += curCnt
        } else {
            var new_eff = {};
            new_eff.id = curArtId;
            new_eff.pic = curPic;
            new_eff.title = curTitle;
            new_eff.cnt = curCnt;
            new_eff.lnk = null;
            new_eff.animData = curAnimData;
            new_eff.eetimeMax = eetimeMax;
            new_eff.turnsLeft = turnsLeft;
            curListHash[curPic] = curList.push(new_eff) - 1
        }
    }
    console.log(curListHash);
    console.log(curList);
    if (auraUpdate) {
        baseLnk.updateAuras(iPersId)
    }
    var cur_eff_list;
    if (iPersId == conf.persId) {
        cur_eff_list = baseLnk.view.effectsP1
    } else if (iPersId == conf.oppId) {
        cur_eff_list = baseLnk.view.effectsP2
    } else {
        canvas.app.battle.log("       WARN: pers_id=" + iPersId.toString() + " not active", 16711680)
    }
    if (cur_eff_list) {
        cur_eff_list.initEffects(curList);
        var curPers = baseLnk.players[iPersId];
        if (curPers) {
            curPers.clearAdditionalEffects();
            var ss;
            var additional_effect;
            for (var k = 0; k < curList.length; k++) {
                ss = curList[k].animData.split("/");
                if (ss.length > 2) {
                    if (ss[2] != "null") {
                        additional_effect = ss[2];
                        curPers.showAdditionalEffects(conf.parser.parseAdditionalEffectsData(canvas.app.battle.Const.AEFF_ABSORB, additional_effect))
                    }
                }
            }
        } else {
            canvas.app.battle.log("       WARN: pers iPersId=" + iPersId + " is null", 10027008)
        }
    }
};

canvas.app.battle.engine.MCmd.prototype.useEffect = function() {
    var parser = canvas.app.battle.model.serverParser;
    var conf = canvas.app.battle.model;
    var baseLnk = this.baseLnk;
    canvas.app.battle.log("mCmd useEffect: paramsCount= " + parser.params.length);
    var effId = parser.params[2].val;
    var targetId = parser.params[3].val;
    var usageStatus = parser.params[4].val;
    console.log(parser.params);
    canvas.app.battle.log("        effId=" + String(effId) + " targetId=" + String(targetId) + " usageStatus=" + String(usageStatus), 10066329);
    var g;
    var slot_id = null;
    for (g in conf.spells) {
        if (conf.spells[g]["effId"] == effId) {
            slot_id = g;
            break
        }
    }
    if (!slot_id) {
        if (!conf.spellsBow[effId]) {
            canvas.app.battle.log("        send sig to items_left.swf", 10066329);
            baseLnk.sendData("items", "update_cnt@" + String(effId) + ",-1")
        } else {
            canvas.app.battle.log("        apply bow spells cooldown eff_id=" + effId, 10066329);
            baseLnk.view.bowPanel.confirmUseEffect(effId)
        }
    } else {
        canvas.app.battle.log("        apply spells cooldown slot_id=" + slot_id, 10066329);
        baseLnk.view.centerView.useSlotConfirmed(slot_id)
    }
    conf.useFlag(effId);
    baseLnk.updateAuras(conf.persId)
};

 */




//PersView
if(canvas.app.persView === undefined) canvas.app.persView = {};
canvas.app.persView.Const = {
    WIDTH: 60,
    HEIGHT: 60,
};
canvas.app.persView.Event = {
    ENTER_FRAME: "PersView.ENTER_FRAME",
};
canvas.app.persView.Model = function(par) {
    this.width = canvas.app.persView.Const.WIDTH;
    this.height = canvas.app.persView.Const.HEIGHT;
    this.parse(par)
};
canvas.app.persView.Model.prototype.parse = function(data) {
    if (this.ok(data.gender)) this.gender = data.gender == "1" ? "M" : "F";
    if (this.ok(data.kind)) this.race = data.kind == "1" ? "hum" : "mag";
    if (this.ok(data.inv_time)) this.invTime = parseInt(data.inv_time) * 1e3;
    if (this.ok(data.parts)) this.parts = data.parts;
    if (this.ok(data.dead)) this.ghost = data.dead == "1";
};
canvas.app.persView.Model.prototype.ok = function(value) {
    return value != undefined
};
canvas.app.CanvasPersView = function(par, parent) {
    canvas.app.CanvasApp.call(this, par, parent, true, 0, 0)
};
canvas.app.CanvasPersView.prototype = Object.create(canvas.app.CanvasApp.prototype);
canvas.app.CanvasPersView.prototype.init = function() {
    this.model = new canvas.app.persView.Model(this.par);
    canvas.app.persView.model = this.model;
    this.model.width = this.par.width;
    this.model.height = this.par.height;

    if(this.preloader) this.preloader.alpha = 1;

    var fonts = [canvas.Const.FONT_IFLASH, canvas.Const.FONT_TAHOMA_9_STROKE, canvas.Const.FONT_TAHOMA_9, canvas.Const.FONT_TAHOMA_9_BOLD, canvas.Const.FONT_TAHOMA_11, canvas.Const.FONT_TAHOMA_11_BOLD, canvas.Const.FONT_TAHOMA_12_BOLD, canvas.Const.FONT_TAHOMA_12, canvas.Const.FONT_TAHOMA_12_BOLD_STROKE_RED_WHITE, canvas.Const.FONT_TAHOMA_20_BOLD_STROKE];
    var resources = [
        ["locale", "{canvas.Config.langPath}locale.json"],
    ];
    for (var i = 0; i < fonts.length; i++) resources.push(canvas.Config.fontsPath + fonts[i] + ".fnt");
    canvas.app.CanvasApp.prototype.init.call(this, resources)
};
canvas.app.CanvasPersView.prototype.ready = function() {
    canvas.app.CanvasApp.prototype.ready.call(this);
    var conf = this.model;

    this.playerContainer = this.root.addChild(new canvas.px.Container());
    var msk = new canvas.px.Graphics();
    msk.beginFill(0, 1);
    msk.drawCircle(44, 44, 34);
    msk.endFill();
    //this.playerContainer.addChild(msk);
    this.player = this.playerContainer.addChild(new canvas.animation.SkeletonAvatar(conf.gender));
    //this.player.frameEvent = canvas.app.persView.Event.ENTER_FRAME;
    this.player.position.set(-60, 5);
    //this.player.mask = msk;

    this.player.skeletonData = new canvas.data.battle.SkeletonData(conf.parts, conf.gender);

    this.fps = 10;
};
canvas.app.CanvasPersView.prototype.addEnemy = function (sk) {
    if(this.enemy !== undefined) this.root.removeIfExist(this.enemy);
    this.enemy = new canvas.animation.Bot(canvas.Config.botsPath + "img" + sk + "/img" + sk);
    this.root.addChild(this.enemy);
    this.enemy.scale.x = 0.65;
    this.enemy.scale.y = 0.65;
    this.enemy.position.set((canvas.app.persView.Const.WIDTH / 2) - 50, 0);
    this.enemy.frameEvent = canvas.app.persView.Event.ENTER_FRAME;
}
canvas.app.CanvasPersView.prototype.handlerEnterFrame = function() {
    canvas.EventManager.dispatchEvent(canvas.app.persView.Event.ENTER_FRAME);
    canvas.app.CanvasApp.prototype.handlerEnterFrame.call(this)
};
canvas.app.CanvasPersView.prototype.resize = function() {
    canvas.app.CanvasApp.prototype.resize.call(this)
};
