<? # $Id: stat_register_builder.php,v 1.2 2009-07-28 16:16:13 n.alekseev Exp $ %TRANS_SKIP%
exit;
chdir("..");
require_once("include/common.inc");
require_once("include/cPHPezMail.inc");
set_time_limit(0);

$STAT_FILENAME_DATE = "Y_m_d";
$DAY_AGO = 2;
$STAT_LINKS = array(
	"terraq" => "http://w2.dragons.terrhq.ru/shared/tmp/stat_register", 
);
//'somebody@it-territory.ru' => 'ФИО',
//'n.alekseev@it-territory.ru' => 'Николай Алексеев'
$EMAIL_LIST = array(
);
function main($stat_mng) {
	GLOBAL $STAT_LINKS, $DAY_AGO, $EMAIL_LIST;
	$stat_date = mktime(0, 0, 0, date("m"), date("d")-$DAY_AGO, date("Y"));
	foreach ($STAT_LINKS as $title => $path) {
		try {
			$data = load_xml($path, $stat_date);
			$data = parse_xml($data);
			$stat_mng->add($title, $data );
		}
		catch (Exception $e) {
			$date_str = date("Y m d", $stat_date);
			$stat_mng->add_error($title, $e->getMessage());
			  
		}
	}
	// Render page
	$html = $stat_mng->make();
	$date_str = date("d-m-Y", $stat_date);
	$title = sprintf("Статистика по эффективним регистрациям за %s", $date_str);
	$email_subject = $title;  
	$title = htmlspecialchars($title);
	ob_start();
	?>
	<html>
		<head>
			<title><?=$title;?></title>
			<style type="text/css">
				.stat_table				{background-color: black;}
				.stat_table tr			{background-color: black;}
				.stat_table td			{background-color: white; text-align:center;}
				.stat_table .odd td		{background-color: #DBEAF1; }  
				.stat_table .stat_title	{text-align:left; width:300px; }
				.caption				{font-weight:bold;}
				.error p 				{font-weight:bold; color:red;}
				.version				{color:gray;margin-top:10px;}
			</style>
		</head>
	<body>
		<h1><?=$title;?></h1>
		<?=$html;?>
	</body>
	</html>
	<?
	$content = ob_get_contents();
	ob_end_clean();
	send_mail($email_subject, $content, $EMAIL_LIST);
	//echo $content;
}
function send_mail($subject, $text, $emails) {
	if (empty($emails))
		return false;
	$cMail = new cPHPezMail();
	$cMail->SetCharset('Windows-1251');
	$cMail->SetEncodingBit(8);
	$cMail->SetFrom('n.alekseev@it-territory.ru', 'Николай Алексеев');
	foreach ($emails as $k=>$v) 
		$cMail->AddTo($k, $v);
	$cMail->SetSubject($subject);
	$cMail->SetBodyHTML($text);
	$cMail->Send();
	return true;
}

function load_xml($fold_path, $stat_date) {
	global $STAT_FILENAME_DATE;
	$fname = "statdistr_".date("Y_m_d", $stat_date);
	$path = $fold_path."/".$fname;
	$content = @file_get_contents($path);
	if ($content === false)
		throw new Exception("Fail to load xml `$path`");
	return $content; 
}

function parse_xml($xml_str) {
	$p = new stat_xml_parser();
	return $p->parse($xml_str);
}

class xml_node  {
	private $name;
	private $attrs;
	private $childs;
	private $text;
	
	function __construct($name, $attrs, $text = "", $childs = null) {
		$this->name = $name;
		$this->attrs = $attrs;
		$this->text = htmlspecialchars_decode($text);
		$this->childs = $childs ? $childs : array();
		foreach($this->attrs as $key => &$val)
			$val = htmlspecialchars_decode($val);
	} 
		
	// childs 
	function add_child(xml_node &$n) {$this->childs[] = $n; }
	function &get_childs() { return $this->childs; }
	function &get_child_chain($n) {
		$nd = &$this;
		foreach(func_get_args() as $name)
			$nd = &$nd->get_child_byname($name);
		return $nd;
	}
	function &get_child_byname($name, $ind = 0) {
		$r = &$this->get_child_byname_nothrow($name, $ind);
		if ($r==null)
			throw new Exception("Failed to get child `$name` index [$ind]");
		return $r;
	}
	function &get_child_byname_nothrow($name, $ind = 0) {
		$count = 0;
		$name = strtoupper($name);
		foreach ($this->childs as $i => &$c) {
			if (strtoupper($c->get_name() ) == $name ) {
				if ($count == $ind ) 
					return $c;
				++$count; 
			}
		}
		return null;	
	}
	// Attributes
	function get_attrs() { return $this->attrs; }
	function get_attr($name) {
		$r = &$this->get_attr_nothrow($name);
		if ($r==null) throw new Exception("Failled to get attr `$name`");
		return $r;
	}
	function get_attr_nothrow($name) {
		$name = strtoupper($name);
		if (isset($this->attrs[$name]))
			return $this->attrs[$name];
		return null;
	}
	//Name
	function get_name() { return strtoupper($this->name); }
	//Text
	function set_text($val) {$this->text = htmlspecialchars_decode($val);}
	function get_text() {return $this->text;}
} 
// Parse xml to array struct
class stat_xml_parser {
	public $root = null;
	public $history = array();
	public $parser;
	function parse($data) {
		$this->parser = xml_parser_create("UTF-8"); 
        xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser,'saxStartElement', 'saxEndElement');
        xml_set_character_data_handler($this->parser, 'saxCharacterData');
		xml_parser_set_option($this->parser,XML_OPTION_CASE_FOLDING, true);
		
		$res = xml_parse($this->parser, $data, true);
		
		xml_parser_free($this->parser);
		if (!$res)  
			throw new Exception("Failed to parse xml");
		return $this->root;
	}

	private function &history_last() {
		$hcount = count($this->history);
		if ( $hcount )
			return $this->history[$hcount-1];
		return $this->root;
	}
		
	function saxStartElement($parser, $name, $attrs) {
		if($this->root === null) {
			$this->root = new xml_node($name, $attrs);
			return;
		}
		$parent = &$this->history_last();
		$node = new xml_node($name, $attrs);
		$parent->add_child( $node );
		$this->history[] = &$node;
	}
	
	function saxEndElement($parser, $name){
		if ( count($this->history) )
			array_pop($this->history);
	}
	
	function saxCharacterData($parser,$data) {
	    $last = &$this->history_last();
	    if ($last)
	    	$last->set_text(trim($data));
	}
}

interface stat_result_int {
	// Return true if node is valid for this action / false otherway 
	function set_node(xml_node &$n);
	function get_title();
	function get_result();
}

abstract class stat_result {
	protected $_name;
	protected $_node;
	function __construct($name) { $this->_name = strtoupper($name); } 

	// Return true if node is valid for this action / false otherway 
	function set_node(xml_node &$n) {
		if ($n->get_name() != $this->_name)
			return false; 
		$this->_node = $n;
		return true; 
	}
	// Helper for generate result
	function get_result() {
		$res = array();
		$r = $this->_node->get_child_byname("result");
		foreach ($r->get_childs() as $kind_res) {
			if ($kind_res->get_name() != "KIND" ) 
				continue;
			$res[ $kind_res->get_attr("ID") ] = $kind_res->get_text(); 
		}
		return $res;
	}

}
//Прохождение квеста
class stat_quest extends stat_result {
	function __construct() { parent::__construct("quest"); } 
	function get_title() {
		$nd = &$this->_node->get_child_chain("condition", "item");
		$is_done = strtoupper($nd->get_attr("is_done"));
		$status =  $is_done == "TRUE" ? "Cдали" : "Взяли";  
		return sprintf("%s квест [%s]", $status, $nd->get_attr("id"));
	}
	
}

//Прохождение этапа квеста
class stat_quest_point extends stat_result{
	function __construct() { parent::__construct("quest_point"); } 
	function get_title() {
		$nd = $this->_node->get_child_byname("condition");
		$quests_str = "";
		$sep_str = " или ";
		foreach ($nd->get_childs()as $quest) {
			if ($quests_str) $quests_str .= ". ";
			$quest_id = $quest->get_attr("id");
			$points_str = ""; 
			foreach ($quest->get_childs() as $c) {
				if ($c->get_name() != "POINT"  ) continue;
				if ($points_str) $points_str .= $sep_str;
				$points_str .= $c->get_attr("id");  
			}
			$quests_str .= sprintf("Прошли этапы [%s] квеста %s", $points_str, $quest_id);
		}
		return $quests_str;
	}
}
//Люди достигшие опредеоенного уровня ( <=level ) 
class stat_level extends stat_result {
	function __construct() { parent::__construct("level"); } 
	function get_title() {
		$nd = &$this->_node->get_child_chain("condition", "level");
		return sprintf("Получили [%s] уровень", $nd->get_text()); 
	}
}
//Люди владеющие артифактом
class stat_have_artivact extends stat_result {
	function __construct() { parent::__construct("have_artikul"); } 
	function get_title() {
		$nd = &$this->_node->get_child_byname("condition");
		$str = "";
		$sep_str = " и ";
		foreach ($nd->get_childs() as $c) {
			if ($c->get_name() != "ITEM") continue;
			if ($str) $str .= $sep_str;
			$str .= sprintf( "%s %s [%s]", $c->get_attr("COUNT"), "артефакт(ов) артикула", $c->get_attr("ID"));
		}
		return sprintf("Имеет %s", $str);
	}
}
//Люди владеющие артифактом + он надет
class stat_puton_artivac extends stat_result {
	function __construct() { parent::__construct("puton_artivact"); } 
	function get_title() {
		$nd = &$this->_node->get_child_byname("condition");
		$str = "";
		$sep_str = " и ";
		foreach ($nd->get_childs() as $c) {
			if ($c->get_name() != "ITEM") continue;
			if ($str) $str .= $sep_str;
			$str .= $c->get_attr("ID");
		}
		return sprintf("Есть одетые предметы артикулов [%s]", $str);
	}
}
//
class stat_registered extends stat_result {
	function __construct() { parent::__construct("registered"); } 
	function get_title() {
		return "Количество регистраций";
	}
}
//
class stat_fight extends stat_result {
	function __construct() { parent::__construct("fight"); } 
	function get_title() {
		$nd = &$this->_node->get_child_chain("condition", "only_win");
		$flag = strtoupper($nd->get_attr("value"));
		if ($flag == "YES")
			return "Провели бой, нет поражений";
		return "Провели бой";
	}
}
//
class stat_flash_load  extends stat_result {
	function __construct() { parent::__construct("flash_load"); } 
	function get_title() {
		$nd = &$this->_node->get_child_chain("condition", "status");
		return sprintf('Прошел %d этап загрузки флешки', $nd->get_text());
	}
}
//
class stat_have_damage extends stat_result {
	function __construct() { parent::__construct("have_damage"); } 
	function get_title() {
		return "Получили повреждения";
	}
}
class stat_lose_by_timeout extends stat_result {
	function __construct() { parent::__construct("lose_by_timeout"); } 
	function get_title() {
		return "Проиграл по тайм-ауту";
	}
}

class stat_manager {
	private $_stat_parsers;
	private $_stat;
	private $_errors = array();
	function __construct() {
		$this->_stat_parsers = array (
			new stat_quest(), new stat_quest_point(),
			new stat_level(), new stat_have_artivact(),
			new stat_puton_artivac(), new stat_registered(),
			new stat_fight(), new stat_flash_load(),
			new stat_have_damage(), new stat_lose_by_timeout()
 
		);
	}
	
	function add($title, xml_node $stat_node) {
		$this->_stat[$title] = $stat_node;
	}
	function add_error($title, $details) {
		$this->_errors[$title] = $details;
	}
	function make() {
		$res = array(); 
		// Интерпретируем (парсим все статистики) <statistic> .....
		foreach ($this->_stat as $title => $stat_node ) {
			try {
				$status = $stat_node->get_child_byname("status");
				$code = strtoupper($status->get_attr("code")); 
				if ( $code != "OK") {
					$this->_errors[$title] = sprintf(
						"В xml файле с сервера содержится отчет об ошибке: [%s] %s",
					     $title, $code, $status->get_text()
					);
					continue; 
				}
				//statistic->data
				$data_node = $stat_node->get_child_byname("data");
				list($node_result, $node_reg_stat) = $this->_make_node($data_node); 
				$res[$title] = array(
					"reg_stat" => $node_reg_stat,
					"statistic" => $node_result,
					"info" => $stat_node->get_attrs()
				);
			}
			catch (Exception $e) {
				// Не получилось интерпритировать стаистику
				$this->add_error($title, $e->getMessage());
			} 
		}
		
		// Проверяем что полученная статистика может быть синхронизирована
		// Выкидываем ошибочную  
		$ok_stat = array();
		// Check that all statistic are the same
		$check_tpl = array("statistic_date" => "", "version" => "");
		$md5_key = 0; 
		foreach ($res as $title => $data) {
			$have_err = false;
			//Проверяем что все поля в информации совпадают 
			//<statistic statistic_date="07 26 2009" ... version="$Id $">
			foreach ($check_tpl as $key=>&$tpl) {
				$key = strtoupper($key);
				if (!$tpl ) $tpl =$data["info"][$key];
				if ($tpl != $data["info"][$key]) {
					$have_err = true;
					$this->_errors[$title] = sprintf(
						"Заголовок статистики отличается от эталонного,	эталонный ключ `%s`=`%s`, текущий `%s`",
						$key, $tpl, $data["info"][$key]
					);
				}
			} 	
			//Есть статистика по регистрациям
			if ($data["reg_stat"] === null ) {
				$have_err = true;
				$this->_errors[$title] = "Нет информации о количестве регистраций";
			}
			//Поля совпадают
			$md5 = md5( serialize(array_keys($data["statistic"])) );
			if (!$md5_key && !$have_err) 
				$md5_key = $md5; 
			if ( $md5_key && $md5_key != $md5) {
				$have_err = true;
				$this->_errors[$title] = "Список полей(позиций) статистики не совпадает с эталонной";
			}
			if (!$have_err) 
				$ok_stat[$title] = $data;
		}
		// Рендерим html
		return $this->_render_stat($ok_stat );
	}
	// Рисуем уже отпаршенную, померженую статистику
	private function _render_stat($statistic_data) {
		global $kind_info;
		$res = array();
		$title_html = "<td></td>";//Столбец с название статистики (он пустой)
		$kind_html = "";
		$version_html = "";
		foreach ($kind_info as $kind_id => $kinf_tiltle)
			$kind_html .= "<td>".$kinf_tiltle['title']."</td>";
		//Humans | Magmars | SUM 1 | SUM 2
		$lower_title_html = "<td></td>";//Столбец с название статистики (он пустой)
		$colspan_count = count($kind_info) + 2; // Humans | Magmars | SUM 1 | SUM 2
		foreach($statistic_data as $title => $data) {
			if (!$version_html) $version_html = $data["info"]["VERSION"];
			$title_html .= "<td colspan=$colspan_count>".htmlspecialchars($title)."</td>";
			$lower_title_html .= $kind_html. "<td colspan=2 width=\"70px\">%</td>";
			$reg_count = 0;
			foreach($data["reg_stat"] as $kind => $count)
				$reg_count += $count;
			$statistic = &$data["statistic"];
			$prev_count = false; 
			foreach ($statistic as $stat_name => $result) {
				$kind_total = 0;
				$html = "";
				foreach ($kind_info as $kind_id => $kind_title) {
					$kind_count = isset($result[$kind_id]) ? intval($result[$kind_id]) : 0;
					$kind_total += $kind_count;
					$html .= "<td>$kind_count</td>";
				}	
				// Потери относительно предыдущего этапа
				$lost_prc = $prev_count ? ($kind_total/$prev_count) * 100 : 0;
				$lost_prc = 100 - $lost_prc; // инверсия			
				// Относительно зареганых
				$total_prc = $reg_count ? ($kind_total / $reg_count) * 100 : 0;
				$html .= $prev_count === false ? "<td>---</td>" : $this->_render_percents($lost_prc);
				$html .= $this->_render_percents($total_prc, true);
				// Нам нужно срендерить все в строчку - поэтому пока собираем в массив 
				$res[$stat_name] .= $html;
				$prev_count = $kind_total;
			}
		}
		
		$html = "";
		// Рисуем ошибки
		if (count($this->_errors) ) {
			$html .="<div class=\"error\">";
			foreach ($this->_errors as $srv => $details)
				$html .= sprintf("<p>Невозможно отобразить статистику с сервера `%s`, причина: %s</p>", $srv, $details);
			$html .="</div>";	
		}
		// Рисуем результат
		$html .= "<table class=\"stat_table\" cellpadding=\"3\" cellspacing=\"1\">";
		// | Англия | Турция | Германия
		$html .= "<tr class=\"caption\">".$title_html."</tr>";
		$html .= "<tr class=\"caption\">".$lower_title_html."</tr>";
		$i = 0;
		foreach ($res as $stat_name => $row) {
			$tr_name = ++$i % 2 == 0 ? "odd" : "";
			$html .= "<tr class=\"$tr_name\"><td class=\"stat_title\">".htmlspecialchars($stat_name)."</td>".$row."</tr>";
		}
		$html .= "</table>";
		$html .= sprintf("<div class=\"version\">Distributed verion: %s</div>", $version_html);
		return $html;
	}
	// Рендер процентов с цветом
	private function  _render_percents($val, $invert=false) {
		static $palette = array("53FF00", "83FF00", "B3FF00", "E3FF00", "FFE700",
			"FFBE00", "FF8F00", "FF6700", "FF3E00", "FF1500");
		$val = intval($val);
		if ($val > 99) $color = count($palette)-1;
		else if ($val < 0) $color = 0;
		else $color = $val % count($palette);
		if ($invert) $color = count($palette)-1 - $color;  
		return '<td style="background-color:#'.$palette[$color].'">'.$val.'</td>';
	}
	
	// Пытаемся отпарсить ноды в статистике
	private function _make_node(xml_node  $data_node) {
		$reg_parser = new stat_registered();
		$reg_stat = null;
		$data = array();
		foreach($data_node->get_childs() as $nd) {
			list($title, $res) = $this->_parse_choise($nd);
			if ($reg_parser->set_node($nd) )
				$reg_stat = $res;
			$data[$title] = $res; 
		}
		return array($data, $reg_stat); 
	}
	
	// Пытаемся отпарсить ноду парсерами, которые зарегали в конструкторе
	private function _parse_choise(xml_node &$stat_node) {
		foreach ($this->_stat_parsers as $p) {
			if ($p->set_node($stat_node)) {
				return array( $p->get_title(), $p->get_result());
			}
		}
		throw new Exception("Failed to find parser for node `".$stat_node->get_name()."`");
	}
}

$mng = new stat_manager();
main($mng);
exit(0);
?>