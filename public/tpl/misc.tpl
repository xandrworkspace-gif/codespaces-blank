<?php

function tpl_micro_menu() {
global $session_user;	
	
	?>
<script>
function simple_action(action){
    $.ajax({
        type: 'POST',
        url: 'simple_action.php',
        async: true,
        dataType: 'json',
        data: {action:action},
        success: function(data){

        },
        complete: function(data) {
            var out = JSON.parse(data.responseText);
            if(out['error']){
                showError(out['error']);
            }
        },
    });
}

</script>
<link rel="stylesheet" href="../style/fight_menu.css?99" type="text/css">	
<!--Обменный шатер-->
<div style="position: absolute; top: 2px; left: 280px;cursor:pointer; z-index:1000;">
<a target="main" href="/area_shater.php"  class="mm_voin" <?=tpl_tooltip('Обменный шатер');?>></a>
</div>	
<!--Премиальный магазин-->
<div style="position: absolute; top: -2px; left: 262px;cursor:pointer; z-index:1000;">
<a  target="main" href="/area_store.php?source=premium&mode=store"   class="mm_prem" <?=tpl_tooltip('Премиальный магазин');?>></a>
</div>	

<!--Кости-->
<div style="position: absolute; top: 19px; left: 288px;cursor:pointer; z-index:1000;">
    <a href="#" onClick="_top().showMsg2('adv_minigame.php','',911,613); return false;" class="mm_kosti" <?=tpl_tooltip('Карты Судьбы');?>></a>
</div>
    <!--Подарок-->
<div style="position: absolute; top: 37px; left: 282px;cursor:pointer; z-index:1000;">
<a href="#" onClick="_top().sFrMe('roulete'); " class="mm_podarok" <?=tpl_tooltip('Рулетка Урчи');?>></a>
</div>	
<!--Скупка-->
<div style="position: absolute; top: -2px; left: 244px;cursor:pointer; z-index:1000;">
<a target="main" href="/area_huckster.php"  class="mm_huckster" <?=tpl_tooltip('Скупка вещей');?>></a>
</div>	

<?   if ($session_user['level'] > 15500) {?>
<link rel="stylesheet" href="../style/fight_menu.css?621" type="text/css">
<script>
function close_click(){
	var red = document.getElementById('close');
	var green = document.getElementById('open');
	var combo = document.getElementById('portal_list_hidden');
	red.style.display = 'none';
	green.style.display = '';
	combo.style.display = '';
}
function open_click(){
	var red = document.getElementById('close');
	var green = document.getElementById('open');
	var combo = document.getElementById('portal_list_hidden');
	red.style.display = '';
	green.style.display = 'none';
	combo.style.display = 'none';
}
</script>
<style>
#portal_list_hidden {
    height: 200px;
    width: 50px;
    padding: 2px;
}
</style>

<div onClick="close_click();" id="close" style="z-index: 1000000; position: absolute; top: 45px; left: 246px;cursor:pointer;"><a  href="#" class="mm_portal" data-tooltip tooltip="<table border=0 cellspacing=0 cellpadding=0><tr><td background=/images/des/5_left_act.png width=50 height=40></td><td background=/images/des/5_center_act2.png><b style=color:#FFE4AA>Портал</b> </td><td background=/images/des/5_right_act.png  width=50 height=40></td></tr></table>"></a></div>
<div onClick="open_click();" id="open" style="z-index: 1000000; position: absolute; top: 45px; left: 246px;cursor:pointer;display:none;"><a  href="#" class="mm_portal" data-tooltip tooltip="<table border=0 cellspacing=0 cellpadding=0><tr><td background=/images/des/5_left_act.png width=50 height=40></td><td background=/images/des/5_center_act2.png><b style=color:#FFE4AA>Скрыть</b> </td><td background=/images/des/5_right_act.png  width=50 height=40></td></tr></table>"></a></div>


<div id="portal_list_hidden" style="z-index: 999999; position: absolute; top: 0px; left: 0px;display:none;"> 


<div style="position: absolute; top: 60px; left: 234px;cursor:pointer; z-index:1000;">
<a href="#" onClick="simple_action(1); return false; " class="mm_portal2" data-tooltip tooltip="<table border=0 cellspacing=0 cellpadding=0><tr><td background=/images/des/5_left_act.png width=50 height=40></td><td background=/images/des/5_center_act2.png><b style=color:#FFE4AA>Портал в город</b></td><td background=/images/des/5_right_act.png  width=50 height=40></td></tr></table>"></a>
</div>

<div style="position: absolute; top: 60px; left: 257px;cursor:pointer; z-index:1000;">
<a href="#" onClick="simple_action(4); return false; " class="mm_portal3" data-tooltip tooltip="<table border=0 cellspacing=0 cellpadding=0><tr><td background=/images/des/5_left_act.png width=50 height=40></td><td background=/images/des/5_center_act2.png><b style=color:#FFE4AA>Портал в город Диких земель</b></td><td background=/images/des/5_right_act.png  width=50 height=40></td></tr></table>"></a>
</div>

</div>
<? } else {?>
<!--Портал-->
<div style="position: absolute; top: 48px; left: 265px;cursor:pointer; z-index:1000;">
<a href="#" onClick="simple_action(1); return false; " class="mm_portal" <?=tpl_tooltip('Портал в город');?>></a>
</div>
	<?
}

}
?>