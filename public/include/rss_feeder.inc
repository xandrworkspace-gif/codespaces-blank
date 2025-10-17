<? # $Id: rss_feeder.inc,v 1.7 2008-09-19 15:29:36 s.panferov Exp $ %TRANS_SKIP%
/*
RSS feeder adapted for yandex
*/

define("ERR_OK",0);
define("ERR_NODATA",1);
define("ERR_FAIL",2);

class RssFeeder{

	var $rss      = "<?xml version=\"1.0\" encoding=\"~charset~\"?><rss version=\"2.0\" xmlns=\"http://backend.userland.com/rss2\">~rss~</rss>";
	var $channel    = "<channel>~title~~link~~description~~image~~channel~</channel>";
	var $title      = "<title>~title~</title>";
	var $link       = "<link>~link~</link>";
	var $description  = "<description>~description~</description>";
	var $image      = "<image><url>~logo~</url><title>~title~</title><link>~link~</link></image>";
	var $item       = "<item><title>~title~</title><link>~link~</link>~author~~description~~category~~enclosure~<pubDate>~date~</pubDate>~text~</item>";
	var $author     = "<author>~author~</author>";
	var $category     = "<category>~category~</category>";  
	var $pubDate    = "<pubDate>~pubDate~</pubDate>";
	var $enclosure    = "<enclosure url=\"~url~\" type=\"~type~\"/>";
	var $yandexFullText = "<yandex:full-text>~text~</yandex:full-text>";

	var $isYandexAdapted;

	var $rssHolder;

	var $error;

	var $data;
  
	function RssFeeder($forYandex=true){
		$this->isYandexAdapted = $forYandex;
		$this->rss = str_replace("~charset~",charset_code_html(),$this->rss);
		$this->rssHolder = str_replace("~rss~",$this->channel,$this->rss);
	}

	function rssPrepare(){
	}

	function rssClearText($text){
		if(!$text) return "";
		$text = trim($text);
		if(!$text) return "";
		return htmlspecialchars(strip_tags($text));
	}

	function setTitle($title,$link,$description=false){
		$title = $this->rssClearText($title);
		$link = $this->rssClearText($link);
		$description = $this->rssClearText($description);
		if(!$title || !$link){
			$this->error = ERR_FAIL;
			return $this->error;
		}
		$tmp = str_replace("~title~",$title,$this->title);
		$this->rssHolder = str_replace("~title~",$tmp,$this->rssHolder);
		$tmp = str_replace("~link~",$link,$this->link); 
		$this->rssHolder = str_replace("~link~",$tmp,$this->rssHolder);
		$tmp = "";
		if($description)
			$tmp = str_replace("~description~",$description,$this->description);  
		$this->rssHolder = str_replace("~description~",$tmp,$this->rssHolder);
	}

	function setLogo($title,$url,$link){
		$tmp = $this->image;
		$tmp = str_replace(array("~logo~","~title~","~link~"),array($url,$title,$link),$tmp);
		$this->rssHolder = str_replace("~image~",$tmp,$this->rssHolder);
	}

	function setItems($items=array()){
		if(empty($items)){ 
			$this->error = ERR_FAIL;
			$this->rssHolder = str_replace("~channel~","",$this->rssHolder);
			return $this->error;
		}

		$strItems = "";
		foreach($items as $item){
			$item["text"] = $this->rssClearText($item["text"]);
			if(!$item["date"] || !$item["title"] || !$item["link"] || !$item["text"]){
				$this->error = ERR_FAIL;
				continue;
			} 
			$item["date"] = date("r",$item["date"]);
			if($item["category"]) $item["category"] = str_replace("~category~",$item["category"],$this->category);
			if($item["description"]) $item["description"] = str_replace("~description~",$item["description"],$this->description);
			else $item["description"] = "";
			if($this->isYandexAdapted && $item["text"]) $item["text"] = str_replace("~text~",$item["text"],$this->yandexFullText);
			else $item["text"] = "";
			$pictures = $item["picture"];
			unset($item["picture"]);
			$tmp = str_replace(array("~title~","~link~","~author~","~description~","~category~","~date~","~text~"),array($item["title"],$item["link"],$item["author"],$item["description"],$item["category"],$item["date"],$item["text"]),$this->item);
			if($pictures && is_array($pictures) && !empty($pictures)){
				$pics = "";
				foreach($pictures as $pic){
					if(!$pic["url"] && !$pic["type"]) continue;
					$pics.= str_replace(array("~url~","~type~"),$pic,$this->enclosure);
				}
				$tmp = str_replace("~enclosure~",$pics,$tmp); 
			}else $tmp = str_replace("~enclosure~","",$tmp);
			$strItems.= $tmp;
		}
		$this->rssHolder = str_replace("~channel~",$strItems,$this->rssHolder);
	}

	function getRss($toFile){
		if($toFile){
			$file = fopen("cache/rss.xml","w+b");
			if($file){
				fwrite($file,$this->rssHolder);
				fclose($file);
				return ERR_OK;
			}else ERR_FAIL;
		}else return $this->rssHolder;
	}

	function getErrorState(){
		return $this->error;
	}
  
}
?>