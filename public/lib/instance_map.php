<? # $Id: instance_map.php,v 1.19 2010-01-15 09:50:09 p.knoblokh Exp $

require_once("include/common.inc");
require_once("lib/session.lib");
require_once("lib/instance.lib");


common_init();
session_init();
common_redirector();

$instance_id = $session_user['instance_id'];
$root = instance_get_root($instance_id);

if ($_GET['conf']) {
	//if ($root['artikul_id'] != intval($_REQUEST['inst_id'])) $instance_id = 0;
	$xml = '<users>';
	if ($instance_id) {
		$instance_hash = make_hash(instance_list(false,' AND (id='.$root['id'].' OR root_id='.$root['id'].')'));
		$instance_ids = array_keys($instance_hash);
		$user_ids = array();
		if ($instance_ids) {
			$sessions = array();
			foreach($NODE_NUMS as $nn) {
				if ($nn == FRIDGE_NN) continue;
				NODE_PUSH($nn);
				$tmp_sessions = session_list(null,null,true,sql_pholder(" AND instance_id IN (?@)",$instance_ids),'uid',true);
				foreach($tmp_sessions as $tmp_session) {
					$sessions[] = $tmp_session;
				}
				NODE_POP($nn);
			}
			$user_ids = get_hash($sessions,'uid','uid');
		}
		$user_list_grp = array();
		if ($user_ids) {
		    if($root['dun_active']) {
                $user_list_grp = make_hash(user_list(array('id' => $user_ids)), 'instance_id', true);
            }else {
                $user_list_grp = $session_user['raid_id'] ? make_hash(user_list(array('id' => $user_ids, 'raid_id' => $session_user['raid_id'])), 'instance_id', true) : make_hash(user_list(array('id' => $user_ids, 'kind' => $session_user['kind'])), 'instance_id', true);
            }
		}
		//$instance_artikul_root = instance_artikul_get($root['artikul_id']);
		$xml .= '<my_loc id="'.$instance_hash[$instance_id]['artikul_id'].'" root_id="'.$root['artikul_id'].'" />';
		foreach ($user_list_grp as $instance_id=>$user_list) {
			$xml .= '<loc id="'.$instance_hash[$instance_id]['artikul_id'].'">';
			foreach ($user_list as $user) $xml .= '<user><![CDATA['.$user['nick'].' ['.$user['level'].']]]></user>';
			$xml .= '</loc>';
		}
	}
	$xml .= '</users>';

	if ($_REQUEST['debug']) {
		print htmlspecialchars($xml);
		exit;
	}
	header('Content-Type: text/xml;charset=utf-8');
	print '<'.'?xml version="1.0" encoding="utf-8"?'.'>';
	print $xml;
	return;
}

$swf_ins = file_url(PATH_SWF.'ins.swf');
$swf_ins_vars = array(
	'GrPack' => file_url(PATH_SWF.'world_graph.swf'),
	'ListLink' => file_url(locale_path().'ins_list.xml'),
	'ResPath' => PATH_SWF.'ins_res/',
	'ActiveIns' => intval($root['artikul_id']),
	'UpdLink' => 'instance_map.php?conf=1',
	'UpdInterval' => 10,

    'locale_file' => locale_data_path().'flash_translate.xml',
    'lang' => translate_default_language(),
    'ux_conf' => 'images/data/canvas/ux.cfg',
);
if (defined('NO_TEXT_DECORATION') && NO_TEXT_DECORATION) $swf_ins_vars['nobold'] = 1;
?>
<html>
<head>
<title><?=translate('Карты инстансов');?></title>
<meta http-equiv="Content-Type" content="text/html; charset=<?=charset_code_html()?>">
<link href="<?=static_get('style/main.css');?>" rel="stylesheet" type="text/css">
<link rel=stylesheet type="text/css" href="<?=static_get(locale_path().'alt.css');?>">
<script language="javaScript" src="<?=static_get('js/common.js');?>"></script>
<script language="javaScript" src="<?=static_get('js/jquery.js');?>"></script>
<script language="javaScript" src="<?=static_get('js/ac_runactivecontent.js');?>"></script>
<? canvas_all_js(); ?>
</head>

<body class="bg regcolor" leftmargin="0" rightmargin="0" topmargin="0" bottommargin="0">
<table class="coll w100 h100">
<tr><td id="instCanvas" valign="top" height="100%">
		<script>
            if(canvasIsSupported()) {
                var par = '<?=common_build_request($swf_ins_vars);?>';
                this.document.ins_map = new canvas.app.CanvasInst(par,document.getElementById("instCanvas"));
            }else{
                AC_FL_RunContent(
                    'id','ins_map',
                    'name','ins_map',
                    'codebase','http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0',
                    'width','100%',
                    'height','100%',
                    'src','<?=$swf_ins;?>',
                    'movie','<?=$swf_ins;?>',
                    'wmode','transparent',
                    'flashvars','<?=common_build_request($swf_ins_vars);?>',
                    'quality','<?=($_COOKIE['flash_low'] ? 'low': 'high');?>',
                    'pluginspage','http://www.macromedia.com/go/getflashplayer'
                );
            }
		window.focus();
		</script>
</td></tr>
</table>

<script type="text/javascript">
window.onload = function() {
	var obj=document.getElementsByTagName('body')[0];
	if(obj.addEventListener) {
		obj.addEventListener('DOMMouseScroll',mouseWheel,false);
		obj.addEventListener("mousewheel",mouseWheel,false);
	}
	else obj.onmousewheel=mouseWheel;

	function mouseWheel(e) {
		e=e?e:window.event;
		if(e.ctrlKey) {
			if(e.preventDefault) e.preventDefault();
			else e.returnValue=false;
			return false;
		}
	}
}

		function attachOnmousewheel(obj, func){
			if(!obj || !func) {
				return
			}
			obj.onmousewheel = function(event){
				event = event|| window.event
				var d = event.wheelDelta ? event.wheelDelta / 120 * (window.opera ?
					-1 : 1 ) : (event.detail ? -event.detail / 3 : 0)
				if(d) {
					func(d, "y")
				}
			}
			if (obj.addEventListener) {
				obj.addEventListener('DOMMouseScroll', obj.onmousewheel, false)
			}
		}

		var gecko = navigator.userAgent.match(/Gecko/i);
		if (gecko) {
			attachOnmousewheel(document.body, function (d, a){window.document['ins_map'].MouseWheel(d, a)})
		}

</script>

</body>
</html>

<noindex>
    <?// Счетчики
    require_once('lib/counters.lib');
    echo print_counters(COUNTER_FLAG_EVERYWHERE, strval($_REQUEST['partner_counter']), intval($_REQUEST['site_id']));
    ?>
</noindex>