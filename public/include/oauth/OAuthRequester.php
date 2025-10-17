<?php

/**
 * Perform a signed OAuth request with a GET, POST, PUT or DELETE operation.
 *
 * Modified by Aeria
 * 
 * The MIT License
 * 
 * Copyright (c) 2007-2008 Mediamatic Lab
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

require_once dirname(__FILE__) . '/OAuthRequestSigner.php';
require_once dirname(__FILE__) . '/body/OAuthBodyContentDisposition.php';


class OAuthRequester extends OAuthRequestSigner
{
	protected $files;

	/**
	 * Construct a new request signer.  Perform the request with the doRequest() method below.
	 * 
	 * A request can have either one file or a body, not both. 
	 * 
	 * The files array consists of arrays:
	 * - file			the filename/path containing the data for the POST/PUT
	 * - data			data for the file, omit when you have a file
	 * - mime			content-type of the file
	 * - filename		filename for content disposition header
	 * 
	 * When OAuth (and PHP) can support multipart/form-data then we can handle more than one file.
	 * For now max one file, with all the params encoded in the query string.
	 * 
	 * @param string request
	 * @param string method		http method.  GET, PUT, POST etc.
	 * @param array params		name=>value array with request parameters
	 * @param string body		optional body to send
	 * @param array files		optional files to send (max 1 till OAuth support multipart/form-data posts)
	 */
	function __construct ( $request, $method = 'GET', $params = null, $body = null, $files = null )
	{
		parent::__construct($request, $method, $params, $body);

		// When there are files, then we can construct a POST with a single file
		if (!empty($files))
		{
			$empty = true;
			foreach ($files as $f)
			{
				$empty = $empty && empty($f['file']) && !isset($f['data']);
			}
			
			if (!$empty)
			{
				if (!is_null($body))
				{
					throw new OAuthException2('When sending files, you can\'t send a body as well.');
				}
				$this->files = $files;
			}
		}
	}


	/**
	 * Perform the request, returns the response code, headers and body.
	 * 
	 * @param int usr_id			optional user id for which we make the request
	 * @param array curl_options	optional extra options for curl request
	 * @param array options			options like name and token_ttl
	 * @exception OAuthException2 when authentication not accepted
	 * @exception OAuthException2 when signing was not possible
	 * @return array (code=>int, headers=>array(), body=>string)
	 */
	function doRequest ( $consumer_key, $consumer_secret, $user_token, $user_token_secret, $curl_options = array(), $options = array() )
	{
		$name = isset($options['name']) ? $options['name'] : '';
		if (isset($options['token_ttl']))
		{
			$this->setParam('xoauth_token_ttl', intval($options['token_ttl']));
		}

		if (!empty($this->files))
		{
			// At the moment OAuth does not support multipart/form-data, so try to encode
			// the supplied file (or data) as the request body and add a content-disposition header.
			list($extra_headers, $body) = OAuthBodyContentDisposition::encodeBody($this->files);
			$this->setBody($body);
			$curl_options = $this->prepareCurlOptions($curl_options, $extra_headers);
		}

                $secrets = array('consumer_key' => $consumer_key, 'consumer_secret' => $consumer_secret, "token" => $user_token, "token_secret" => $user_token_secret);
		$this->sign($secrets, $name);
		$text   = $this->curl_raw($curl_options);
		$result = $this->curl_parse($text);	
		if ($result['code'] >= 400)
		{
			throw new OAuthException2('Request failed with code ' . $result['code'] . ': ' . $result['body']);
		}

		// Record the token time to live for this server access token, immediate delete iff ttl <= 0
		// Only done on a succesful request.	
		$token_ttl = $this->getParam('xoauth_token_ttl', false);
		return $result;
	}


	/**
	 * Open and close a curl session passing all the options to the curl libs
	 * 
	 * @param string url the http address to fetch
	 * @exception OAuthException2 when temporary file for PUT operation could not be created
	 * @return string the result of the curl action
	 */
	protected function curl_raw ( $opts = array() )
	{
		if (isset($opts[CURLOPT_HTTPHEADER]))
		{
			$header = $opts[CURLOPT_HTTPHEADER];
		}
		else
		{
			$header = array();
		}

		$ch 		= curl_init();
		$method		= $this->getMethod();
		$url		= $this->getRequestUrl();
		$header[]	= $this->getAuthorizationHeader();
		$query		= $this->getQueryString();
		$body		= $this->getBody();

		$has_content_type = false;
		foreach ($header as $h)
		{
			if (strncasecmp($h, 'Content-Type:', 13) == 0)
			{
				$has_content_type = true;
			}
		}
		
		if (!is_null($body))
		{
			if ($method == 'TRACE')
			{
				throw new OAuthException2('A body can not be sent with a TRACE operation');
			}

			// PUT and POST allow a request body
			if (!empty($query))
			{
				$url .= '?'.$query;
			}

			// Make sure that the content type of the request is ok
			if (!$has_content_type)
			{
				$header[]         = 'Content-Type: application/octet-stream';
				$has_content_type = true;
			}
			
			// When PUTting, we need to use an intermediate file (because of the curl implementation)
			if ($method == 'PUT')
			{
				/*
				if (version_compare(phpversion(), '5.2.0') >= 0)
				{
					// Use the data wrapper to create the file expected by the put method
					$put_file = fopen('data://application/octet-stream;base64,'.base64_encode($body));
				}
				*/
				
				$put_file = @tmpfile();
				if (!$put_file)
				{
					throw new OAuthException2('Could not create tmpfile for PUT operation');
				}
				fwrite($put_file, $body);
				fseek($put_file, 0);

				curl_setopt($ch, CURLOPT_PUT, 		  true);
  				curl_setopt($ch, CURLOPT_INFILE, 	  $put_file);
  				curl_setopt($ch, CURLOPT_INFILESIZE,  strlen($body));
			}
			else
			{
				curl_setopt($ch, CURLOPT_POST,		  true);
				curl_setopt($ch, CURLOPT_POSTFIELDS,  $body);
  			}
		}
		else
		{
			// a 'normal' request, no body to be send
			if ($method == 'POST')
			{
				if (!$has_content_type)
				{
					$header[]         = 'Content-Type: application/x-www-form-urlencoded';
					$has_content_type = true;
				}

				curl_setopt($ch, CURLOPT_POST, 		  true);
				curl_setopt($ch, CURLOPT_POSTFIELDS,  $query);
			}
			else
			{
				if (!empty($query))
				{
					$url .= '?'.$query;
				}
				if ($method != 'GET')
				{
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
				}
			}
		}
		
		curl_setopt($ch, CURLOPT_HTTPHEADER,	 $header);
		curl_setopt($ch, CURLOPT_USERAGENT,		 'anyMeta/OAuth 1.0 - ($LastChangedRevision: 67 $)');
		curl_setopt($ch, CURLOPT_URL, 			 $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 		 true);
	
		foreach ($opts as $k => $v)
		{
			if ($k != CURLOPT_HTTPHEADER)
			{
				curl_setopt($ch, $k, $v);
			}
		}

		$txt = curl_exec($ch);
		curl_close($ch);
		
		if (!empty($put_file))
		{
			fclose($put_file);
		}

		return $txt;
	}
	
	
	/**
	 * Parse an http response
	 * 
	 * @param string response the http text to parse
	 * @return array (code=>http-code, headers=>http-headers, body=>body)
	 */
	protected function curl_parse ( $response )
	{
		if (empty($response))
		{
			return array();
		}
	
		@list($headers,$body) = explode("\r\n\r\n",$response,2);
		$lines = explode("\r\n",$headers);

		if (preg_match('@^HTTP/[0-9]\.[0-9] +100@', $lines[0]))
		{
			/* HTTP/1.x 100 Continue
			 * the real data is on the next line
			 */
			@list($headers,$body) = explode("\r\n\r\n",$body,2);
			$lines = explode("\r\n",$headers);
		}
	
		// first line of headers is the HTTP response code 
		$http_line = array_shift($lines);
		if (preg_match('@^HTTP/[0-9]\.[0-9] +([0-9]{3})@', $http_line, $matches))
		{
			$code = $matches[1];
		}
	
		// put the rest of the headers in an array
		$headers = array();
		foreach ($lines as $l)
		{
			list($k, $v) = explode(': ', $l, 2);
			$headers[strtolower($k)] = $v;
		}
	
		return array( 'code' => $code, 'headers' => $headers, 'body' => $body);
	}


	/**
	 * Mix the given headers into the headers that were given to curl
	 * 
	 * @param array curl_options
	 * @param array extra_headers
	 * @return array new curl options
	 */
	protected function prepareCurlOptions ( $curl_options, $extra_headers )
	{
		$hs = array();
		if (!empty($curl_options[CURLOPT_HTTPHEADER]) && is_array($curl_options[CURLOPT_HTTPHEADER]))
		{
			foreach ($curl_options[CURLOPT_HTTPHEADER] as $h)
			{
				list($opt, $val) = explode(':', $h, 2);
				$opt      = str_replace(' ', '-', ucwords(str_replace('-', ' ', $opt)));
				$hs[$opt] = $val;
			}
		}

		$curl_options[CURLOPT_HTTPHEADER] = array();
		$hs = array_merge($hs, $extra_headers);		
		foreach ($hs as $h => $v)
		{
			$curl_options[CURLOPT_HTTPHEADER][] = "$h: $v";
		}
		return $curl_options;
	}
}

/* vi:set ts=4 sts=4 sw=4 binary noeol: */

?>
