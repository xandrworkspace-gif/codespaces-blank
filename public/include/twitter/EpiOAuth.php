<?php
class EpiOAuth
{
  public $version = '1.0';
  
  protected $oauthCallback;
  protected $requestTokenUrl;
  protected $accessTokenUrl;
  protected $authorizeUrl;
  protected $consumerKey;
  protected $consumerSecret;
  protected $token;
  protected $tokenSecret;
  protected $signatureMethod;
  protected $headers;

  public function getAccessToken()
  {
    $resp = $this->httpRequest('GET', $this->accessTokenUrl);
    return new EpiOAuthResponse($resp);
  }

  public function getAuthorizationUrl()
  {  
    $token = $this->getRequestToken();
    return $this->authorizeUrl . '?oauth_token=' . $token->oauth_token;
  }

  public function getRequestToken()
  {
    $resp = $this->httpRequest('GET', $this->requestTokenUrl);
    return new EpiOAuthResponse($resp);
  }

  public function httpRequest($method = null, $url = null, $params = null, $no_oauth = false)
  {
  	$this->headers = array();
    if(empty($method) || empty($url))
      return false;

    if(empty($params['oauth_signature']))
      $params = $this->prepareParameters($method, $url, $params, $no_oauth);

    return $this->httpGet($url, $params);
  }

  public function setToken($token = null, $secret = null)
  {
    $params = func_get_args();
    $this->token = $token;
    $this->tokenSecret = $secret;
  } 

  public function encode($string)
  {
    return rawurlencode(utf8_encode($string));
  }

  protected function addOAuthHeaders($url, $oauthHeaders)
  {
   $this->headers[] = 'Expect:';
    $urlParts = parse_url($url);
    $oauth = 'Authorization: OAuth realm="' . $urlParts['path'] . '",';
    foreach($oauthHeaders as $name => $value)
    {
      $oauth .= "{$name}=\"{$value}\",";
    }
    $this->headers[] = substr($oauth, 0, -1);
    
  }

  protected function generateNonce()
  {
    if(isset($this->nonce)) // for unit testing
      return $this->nonce;

    return md5(uniqid(rand(), true));
  }

  protected function generateSignature($method = null, $url = null, $params = null)
  {
    if(empty($method) || empty($url))
      return false;


    // concatenating
    $concatenatedParams = '';
    foreach($params as $k => $v)
    {
      $v = $this->encode($v);
      $concatenatedParams .= "{$k}={$v}&";
    }
    $concatenatedParams = $this->encode(substr($concatenatedParams, 0, -1));

    // normalize url
    $normalizedUrl = $this->encode($this->normalizeUrl($url));
    $method = $this->encode($method); // don't need this but why not?

    $signatureBaseString = "{$method}&{$normalizedUrl}&{$concatenatedParams}";
    return $this->signString($signatureBaseString);
  }

  protected function httpGet($url, $params = null)
  {
    if(count($params['request']) > 0)
    {
      $url .= '?';
      foreach($params['request'] as $k => $v)
      {
        $url .= "{$k}={$v}&";
      }
      $url = substr($url, 0, -1);
    }
    if ($params['oauth']) {
    	$this->addOAuthHeaders($url, $params['oauth']);
    }
    
    $context = stream_context_create(array(
                      'http' => array(
                              'method'  => 'GET',
                              'header'  => implode("\r\n", $this->headers),
		    )
    ));
    $result = file_get_contents($url, false, $context);
    
    $res_obj = new stdClass();
    $res_obj->data = $result;
    
    if ($result) {
	    $res_obj->code = 200;
    } else {
    	$res_obj->code = 400;
    }
    
    return $res_obj;
  }

  protected function normalizeUrl($url = null)
  {
    $urlParts = parse_url($url);
    $scheme = strtolower($urlParts['scheme']);
    $host   = strtolower($urlParts['host']);
    $port = intval($urlParts['port']);

    $retval = "{$scheme}://{$host}";
    if($port > 0 && ($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443))
    {
      $retval .= ":{$port}";
    }
    $retval .= $urlParts['path'];
    if(!empty($urlParts['query']))
    {
      $retval .= "?{$urlParts['query']}";
    }

    return $retval;
  }

  protected function prepareParameters($method = null, $url = null, $params = null, $no_oauth = false)
  {
    if(empty($method) || empty($url))
      return false;
    
    if(is_array($params))
      array_walk($params, array($this, 'encode'));
    
    $ret_arr = array('request' => $params);
	
    if (! $no_oauth) {
	    $oauth['oauth_consumer_key'] = $this->consumerKey;
	    $oauth['oauth_token'] = $this->token;
	    $oauth['oauth_nonce'] = $this->generateNonce();
	    $oauth['oauth_timestamp'] = !isset($this->timestamp) ? time() : $this->timestamp; // for unit test
	    $oauth['oauth_signature_method'] = $this->signatureMethod;
	    $oauth['oauth_version'] = $this->version;
	    $oauth['oauth_callback'] = $this->oauthCallback;
	    // encoding
	    array_walk($oauth, array($this, 'encode'));
	    $encodedParams = array_merge($oauth, (array)$params);
	    // sorting
	    ksort($encodedParams);
	    // signing
	    $oauth['oauth_signature'] = $this->encode($this->generateSignature($method, $url, $encodedParams));
	    $ret_arr['oauth'] = $oauth;
    }
    return $ret_arr;
  }

  protected function signString($string = null)
  {
    $retval = false;
    switch($this->signatureMethod)
    {
      case 'HMAC-SHA1':
        $key = $this->encode($this->consumerSecret) . '&' . $this->encode($this->tokenSecret);
        $retval = base64_encode(hash_hmac('sha1', $string, $key, true));
        break;
    }

    return $retval;
  }

  public function __construct($consumerKey, $consumerSecret, $signatureMethod='HMAC-SHA1')
  {
    $this->consumerKey = $consumerKey;
    $this->consumerSecret = $consumerSecret;
    $this->signatureMethod = $signatureMethod;
  }
}

class EpiOAuthResponse
{
  private $__resp;

  public function __construct($resp)
  {
    $this->__resp = $resp;
    parse_str($this->__resp->data, $result);
    foreach($result as $k => $v)
    {
    	$this->$k = $v;
    }
  }

  public function __get($name)
  {
    if($this->__resp->code < 200 || $this->__resp->code > 299)
      return false;
    return $result[$name];
  }
}
