<?php


function tpl_um_menu() { global $session_user;?>
 
 <!--
     
-->
    <?
}
function tpl_slmm_menu() {
    return;
    global $session_user; ?>
<link rel="stylesheet" href="<?=static_get('../style/fight_menu.css');?>" type="text/css">
<script>
function flag_red_click(){
	var red = document.getElementById('flag_red');
	var green = document.getElementById('flag_green');
	var combo = document.getElementById('combo_list_hidden');
	red.style.display = 'none';
	green.style.display = '';
	combo.style.display = '';
}
function flag_green_click(){
	var red = document.getElementById('flag_red');
	var green = document.getElementById('flag_green');
	var combo = document.getElementById('combo_list_hidden');
	red.style.display = '';
	green.style.display = 'none';
	combo.style.display = 'none';
}
</script>
<style>
#combo_list_hidden {
    height: 200px;
    width: 50px;
    padding: 2px;
}
</style>

<!--<div onClick="flag_red_click();" id="flag_red" style="z-index: 1000000; position: absolute; top: 125px; left: 45px;cursor:pointer;"><a  href="#" class="nlm_close" data-tooltip tooltip="<table border=0 cellspacing=0 cellpadding=0><tr><td background=/images/des/5_left_act.png width=50 height=40></td><td background=/images/des/5_center_act2.png><b style=color:#FFE4AA>Меню быстрого доступа</b> </td><td background=/images/des/5_right_act.png  width=50 height=40></td></tr></table>"></a></div>
<div onClick="flag_green_click();" id="flag_green" style="z-index: 1000000; position: absolute; top: 125px; left: 45px;cursor:pointer;display:none;"><a  href="#" class="nlm_open" data-tooltip tooltip="<table border=0 cellspacing=0 cellpadding=0><tr><td background=/images/des/5_left_act.png width=50 height=40></td><td background=/images/des/5_center_act2.png><b style=color:#FFE4AA>Закрыть</b> </td><td background=/images/des/5_right_act.png  width=50 height=40></td></tr></table>"></a></div>
-->

<div id="combo_list_hidden" style="z-index: 999999; position: absolute; top: 117px; left: 50px;display:none;">
    
    <!--Комплекты--><div style="position: relative; top: 0px; left: 0px;cursor:pointer;">
    <a  href="#" onclick="showMsg2('save_complects.php','',800,450); return false;" class="nlm_complects" data-tooltip tooltip="<table border=0 cellspacing=0 cellpadding=0><tr><td background=/images/des/5_left_act.png width=50 height=40></td><td background=/images/des/5_center_act2.png><b style=color:#FFE4AA>Сохранение комплектов</b> </td><td background=/images/des/5_right_act.png  width=50 height=40></td></tr></table>"></a>
    </div>
    <!--Карманы--><div style="position: absolute; top: 44px; left: 0px;cursor:pointer;">
    <a  href="#" onclick="showMsg2('save_slots.php','',800,450); return false;" class="nlm_slots" data-tooltip tooltip="<table border=0 cellspacing=0 cellpadding=0><tr><td background=/images/des/5_left_act.png width=50 height=40></td><td background=/images/des/5_center_act2.png><b style=color:#FFE4AA>Сохранение карманов</b> </td><td background=/images/des/5_right_act.png  width=50 height=40></td></tr></table>"></a>
    </div>
	<!--Жрачка--><div style="position: absolute; top: 86px; left: 0;cursor:pointer;">
    <a  href="#" onclick="showMsg2('save_eat.php','',800,450); return false;" class="nlm_food" data-tooltip tooltip="<table border=0 cellspacing=0 cellpadding=0><tr><td background=/images/des/5_left_act.png width=50 height=40></td><td background=/images/des/5_center_act2.png><b style=color:#FFE4AA>Настройка автопоедания пищи</b> </td><td background=/images/des/5_right_act.png  width=50 height=40></td></tr></table>"></a>
    </div>
    <!--Авто-юзы--><!--<div style="position: absolute; top: 86px; left: 0;cursor:pointer;">
        <a  href="#" onclick="showMsg2('save_eat.php','',800,450); return false;" class="nlm_food" data-tooltip tooltip="<table border=0 cellspacing=0 cellpadding=0><tr><td background=/images/des/5_left_act.png width=50 height=40></td><td background=/images/des/5_center_act2.png><b style=color:#FFE4AA>Настройка автопоедания пищи</b> </td><td background=/images/des/5_right_act.png  width=50 height=40></td></tr></table>"></a>
    </div>-->
	<!--Очистка--><div style="position: absolute; top: 128px; left: 0px;cursor:pointer;">
    <a  href="#" onclick="empty_slots_all(); return false;" class="nlm_clear" data-tooltip tooltip="<table border=0 cellspacing=0 cellpadding=0><tr><td background=/images/des/5_left_act.png width=50 height=40></td><td background=/images/des/5_center_act2.png><b style=color:#FFE4AA>Очистка карманов</b> </td><td background=/images/des/5_right_act.png  width=50 height=40></td></tr></table>"></a>
    </div>
    <script>
        function empty_slots_all() {
            $.ajax({
                type: 'POST',
                url: 'entry_point.php?action=empty_all_slots&object=user',
                async: true,
                dataType: 'json',
                complete: function (XMLHttpRequest, textStatus) {
                    _top().frames['main_frame'].updateSwf({'items': ''});
                }
            });
        }
    </script>

</div>
    <?
}

function js_mount_menu(){
    ?>
    <script>
        var _HyperMount = function() {
            this.mount_red = false;
            this.mount_green = false;
            this.hyperemount = false;
            this.mount_list = {};
            this.page = 0;
            this.type = 0;
            this.slot_cnt = 4; //По дефолту
            this.open = function(){
                this.init_obj();
                this.mount_red.show();
                this.mount_green.hide();
                this.hyperemount.show();
                if(Object.keys(this.mount_list).length == 0){
                    this.load_mounts();
                    this.hyperemount.html(this.generateHtml());
                }

            };
            this.close = function(){
                this.init_obj();
                this.mount_red.hide();
                this.mount_green.show();
                this.hyperemount.hide();
            };
            this.init_obj = function(){
                if(!this.mount_red) this.mount_red = $('#mount_red');
                if(!this.mount_green) this.mount_green = $('#mount_green');
                if(!this.hyperemount) this.hyperemount = $('#hypermount');
            };

            this.use = function(artifact_id,action_id){
                var obj_cl = "ARTIFACT";
                var act = 'action_run.php?action_id='+action_id+'&object_id='+artifact_id+'&artifact_id='+artifact_id+'&object_class='+obj_cl+'&in[object_class]='+obj_cl+'&in[object_id]='+artifact_id+'&in[action_id]='+action_id;
                act += '&url_success=error_parser.php&url_error=error_parser.php';
                //showMsg(act);
                //showMsg('action_form.php?' + Math.random() + '&artifact_id=' + id + '&in[param_success][window_reload]=0');

                $.ajax({
                    type: 'POST',
                    url: act,
                    async: false,
                    dataType: 'json',
                    data: false,
                    complete: function (XMLHttpRequest, textStatus) {
                        var data = XMLHttpRequest.responseText;
                        if(data.length > 0){
                            showError(data);
                        }
                    },
                    error: function (request, status, error) {
                        showError(error);
                    }
                });
            };

            this.load_mounts = function(){
                $.ajax('/entry_point.php', {
                    dataType : 'json',
                    data : {
                        'object' : 'user',
                        'action' : 'give_mounts',
                    },
                    complete : function(data, statusCode){
                        if(data.responseText){
                            var obj = JSON.parse(data.responseText);
                            if(obj['status'] == 100){
                                HyperMount.init_mounts(obj['mount_list']);
                            }else{
                                //Ошибка
                            }
                        }
                    },
                    async: false,
                });
            };

            this.generateHtml = function(){
                var html = '';
                var x = (this.page) * this.slot_cnt; // ОТ
                var y = x + this.slot_cnt; //До
                var ii = 0;
                Object.keys(HyperMount.mount_list).forEach(function (i, v) {
                    do{
                        if(ii >= x && ii < y) {
                            var mount = HyperMount.mount_list[i];
                            if (HyperMount.type == 1 && mount['no_fight']) break;
                            var h_item = '<div class="item_mount_x">' +
                                '<div class="pre_image_mount"><img class="image_mount" src="' + mount['picture'] + '"></div>' +
                                '<div class="pre_a_image_mount"><a title="' + mount['title'] + '" href="#" onclick="HyperMount.use(' + mount['id'] + ',' + mount['action_id'] + '); return false;" class="w_mounts_item a_image_mount"></a>' +
                                '</div></div>';
                            html += h_item;
                        }
                    }while(0);
                    ii++;
                });
                //Менюшка переключения страниц
                html += '<div class="item_mount_page">' +
                    '<div style="width: 0px; height: 0px;"><div onclick="_top().showMsg2(\'menu_settings.php?mode=mounts\',\'\',480,350); return false;" class="cmsn"></div>' +
                    '<div style="width: 0px; height: 0px;"><div class="pre_m_prev_br"><div onclick="HyperMount.back();" class="m_prev_br"></div></div>' +
                        '<div class="pre_m_next_br"><div onclick="HyperMount.next();" class="m_next_br"></div></div></div></div>' +
                    '<div class="pre_item_mount_page"><div class="page_counter_br_m"><div id="page_cnt_mount">' + (this.page + 1) +
                    '</div></div></div>' +
                    '</div></div>';
                return html;
            };

            this.check_page = function (page) {
                var x = (page) * this.slot_cnt;
                var y = x + this.slot_cnt;
                var ii = 0;
                var have = false;
                Object.keys(HyperMount.mount_list).forEach(function (i, v) {
                    if(have) return false;
                    if(ii >= x && ii < y){
                        have = true;
                    }
                    ii++;
                });
                return have;
            };

            this.next = function(){
                if(!this.check_page((this.page + 1))){
                    return false;
                }
                this.page++;
                this.hyperemount.html(this.generateHtml());
            };

            this.back = function(){
                if(!this.check_page((this.page - 1))){
                    return false;
                }
                this.page--;
                this.hyperemount.html(this.generateHtml());
            };

            this.init_mounts = function (mounts) {
                this.mount_list = mounts;
            };

            this.reinit_type = function(type){
                return false; //пока не знаю как сделать
                this.type = type;
                this.page = 0;
                this.hyperemount.html(this.generateHtml());
            };

            this.update_slots = function(cnt){
                this.slot_cnt = cnt;
                this.page = 0;
                this.hyperemount.html(this.generateHtml());
            };

            var c_hm_slot_cnt = getCookie('hm_mount_cnt');
            if(c_hm_slot_cnt != null && parseInt(c_hm_slot_cnt) > 0){
                this.slot_cnt = parseInt(c_hm_slot_cnt);
            }

        };
        var HyperMount = new _HyperMount();
    </script>
    <?
}

function tpl_mount_menu() { global $session_user; ?>
    <link rel="stylesheet" href="<?=static_get('../style/fight_menu.css');?>" type="text/css">
    <?js_mount_menu();?>
    <style>
        #hypermount {
            height: 200px;
            width: 50px;
            padding: 2px;
        }
        .image_mount{
            width: 30px;
            border-radius: 18px;
            margin-left: 12px;
            margin-top: 7px;
        }
        .pre_image_mount{
            width: 0px;height: 0px;z-index: -1;position: absolute;
        }
        .item_mount_x{
            cursor:pointer;height: 42px;width: 46px;
        }
        .pre_a_image_mount{
            width: 0px;height: 0px;z-index: 1;position: absolute;
        }
        .a_image_mount{
            position: absolute;z-index:1;
        }

        .page_counter_br_m{
            background: url('../images/menu/page_count.png');
            width: 62px;
            height: 33px;
        }

        .pre_m_prev_br{
            width: 0px;
            height: 0px;
            position: absolute;
            left: 1px;
            margin-top: 11px;
        }
        .m_prev_br{
            background: url('../images/menu/prev_r.png');
            width: 10px;
            height: 10px;
            cursor: pointer;
        }
        .m_prev_br:hover{
            background: url('../images/menu/prev_rh.png');
        }
        .pre_m_next_br{
            width: 0px;
            height: 0px;
            position: absolute;
            left: 1px;
            margin-top: 11px;

        }
        .m_next_br{
            background: url('../images/menu/next_r.png');
            width: 10px;
            height: 10px;
            position: absolute;
            left: 41px;
            cursor: pointer;
        }
        .m_next_br:hover{
            background: url('../images/menu/next_rh.png');
        }
        .pre_item_mount_page{
            width: 0px;
            height: 0px;
        }
        .item_mount_page{
            height: 33px;
            width: 63px;
            margin-left: -6px;
            margin-top: -5px;
            text-align: center;
        }
        #page_cnt_mount{
            padding-top: 9px;
            font-size: 11px;
            font-weight: bold;
            color: #fecf9d;
        }
        .cmsn{
            position: absolute;
            width: 20px;
            height: 20px;
            left: 17px;
            margin-top: 7px;
            border-radius: 25px;
            background: transparent;
            cursor: pointer;
        }
        .cmsn:hover{
            background: url(../images/btn-settings.png?r=1) no-repeat;
            left: 16px;
            margin-top: 5px;
            height: 23px;
            width: 23px;
        }
    </style>

    <div onClick="HyperMount.close();" id="mount_red" style="z-index: 1000000; position: absolute; top: 136px; right: 86px;cursor:pointer;display:none;"><a  href="#" class="nlm_close" data-tooltip tooltip="<table border=0 cellspacing=0 cellpadding=0><tr><td background=/images/des/5_left_act.png width=50 height=40></td><td background=/images/des/5_center_act2.png><b style=color:#FFE4AA>Закрыть</b> </td><td background=/images/des/5_right_act.png  width=50 height=40></td></tr></table>"></a></div>
    <div onClick="HyperMount.open();" id="mount_green" style="z-index: 1000000; position: absolute; top: 136px; right: 86px;cursor:pointer;"><a  href="#" class="nlm_open" data-tooltip tooltip="<table border=0 cellspacing=0 cellpadding=0><tr><td background=/images/des/5_left_act.png width=50 height=40></td><td background=/images/des/5_center_act2.png><b style=color:#FFE4AA>Ездовые животные</b> </td><td background=/images/des/5_right_act.png  width=50 height=40></td></tr></table>"></a></div>


    <div id="hypermount" style="z-index: 999999; position: absolute; top: 127px; right: 95px;display:none;">

    </div>
    <?
}
?>