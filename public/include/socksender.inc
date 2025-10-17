<? # $Id: socksender.inc,v 1.2 2010/03/25 14:48:36 p.knoblokh Exp $

define('SOCKSENDER_SOCKET_TIMEOUT', 3);


class SockSender {
	var $user_agent = 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT)';
	var $accept_encoding = 'plain/text';
	var $method = 'POST';
	var $protocol = 'HTTP/1.0';
	var $port = 80;
	var $timeout = 30;
	
	function send($host, $send2url, $referer, $data, $cookies=array()) {
		$t = array();
		foreach ($data as $name => $value) {
			$t[] = urlencode($name).'='.urlencode($value);
		}
		$enc_data = implode('&', $t);
		$data_len = mb_strlen($enc_data);
		$cookie_str = '';
		if ($cookies) {
			$cookie_str = 'Cookie: ';
			foreach ($cookies as $k=>$v) {
				$cookie_str .= $k.'='.$v.';';
			}			
		}
		$http_query = $this->method.' '.$send2url." ".$this->protocol."\r\n".
			'Referer: '.$referer."\r\n".
			'Content-Type: application/x-www-form-urlencoded'."\r\n".
			'Content-Length: '.$data_len."\r\n".
			'Host: '.$host."\r\n".
			"Accept: */*\r\n".
			'Accept-Encoding: '.$this->accept_encoding."\r\n".
			'Connection: close'."\r\n".
			$cookie_str.
			'User-Agent: '.$this->user_agent."\r\n".
			"\r\n".
			$enc_data;

		$fs = fsockopen($host, $this->port, $errno, $errstr, $this->timeout);
		if (!$fs) return 'unable open socket: '.$errstr.' ('.$errno.')';
		stream_set_timeout($fs, $this->timeout);
		stream_set_write_buffer($fs, 1024);
		fputs($fs, $http_query);
		$result = '';
		while (!feof($fs)) {
			$result .= fread($fs, 1024);
		}
		fclose($fs);
		return $result;
	}

	function send_xml($host, $send2url, $referer, $xml_data, $cookies=array()) {
		$cookie_str = '';
		if ($cookies) {
			$cookie_str = 'Cookie: ';
			foreach ($cookies as $k=>$v) {
				$cookie_str .= $k.'='.$v.';';
			}			
		}
		$http_query = $this->method.' '.$send2url." ".$this->protocol."\r\n".
			'Referer: '.$referer."\r\n".
			'Content-Type: text/xml'."\r\n".
			'Content-Length: '.mb_strlen($xml_data)."\r\n".
			'Host: '.$host."\r\n".
			"Accept: */*\r\n".
			'Accept-Encoding: '.$this->accept_encoding."\r\n".
			'Connection: close'."\r\n".
			$cookie_str.
			'User-Agent: '.$this->user_agent."\r\n".
			"\r\n".
			$xml_data;
//		vardump($http_query);

		$fs = @fsockopen($host, $this->port, $errno, $errstr, $this->timeout);
		if (!$fs) return 'unable open socket: '.$errstr.' ('.$errno.')';
		stream_set_timeout($fs, $this->timeout);
		stream_set_write_buffer($fs, 1024);
		fputs($fs, $http_query);
		$result = '';
		while (!feof($fs)) {
			$result .= fread($fs, 1024);
		}
		fclose($fs);
		return $result;
	}

}

function get_answer($url, $data=array(), $referrer='', $cookies=array(), $timeout=SOCKSENDER_SOCKET_TIMEOUT, $auto_redirect=true) {
	$host_parts = parse_url($url);

	$sender = new SockSender();
	$host = @$host_parts['host'];
	$path = @$host_parts['path'];
	if (@$host_parts['port']) $sender->port = $host_parts['port'];
	$query = @$host_parts['query'];
	$url = ($path ? $path : '/').($query ? '?'.$query : '');

	$sender->timeout = $timeout;
	$response = $sender->send($host, $url, $referrer, $data, $cookies);
	$p = mb_strpos($response, "\r\n\r\n");
	$header = mb_substr($response, 0, $p);
	if ($auto_redirect) {
		preg_match('/Location: (.+)/i', $header, $matches);
		if (@$matches[1] && mb_strpos($matches[1], 'http://') === 0) {
			$referer = 'http://'.$host.$url;
			return get_answer(trim($matches[1]), $data, $referer, $cookies, $timeout, $auto_redirect);
		}	
	}
	return mb_substr($response, $p+4);
}

function get_answer_xml($url, $xml_data='', $referrer='', $cookies=array(), $timeout=SOCKSENDER_SOCKET_TIMEOUT) {
	$host_parts = parse_url($url);

	$sender = new SockSender();
	$host = @$host_parts['host'];
	$path = @$host_parts['path'];
	if (@$host_parts['port']) $sender->port = $host_parts['port'];
	$query = @$host_parts['query'];
	$url = ($path ? $path : '/').($query ? '?'.$query : '');

	$sender->timeout = $timeout;
	$response = $sender->send_xml($host, $url, $referrer, $xml_data, $cookies);
	$p = mb_strpos($response, "\r\n\r\n");
	$header = mb_substr($response, 0, $p);
	return mb_substr($response, $p+4);
}
