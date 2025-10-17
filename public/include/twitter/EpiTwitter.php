<?php // %TRANS_SKIP%
class EpiTwitter extends EpiOAuth
{
  const EPITWITTER_SIGNATURE_METHOD = 'HMAC-SHA1';
  protected $requestTokenUrl = 'http://twitter.com/oauth/request_token';
  protected $accessTokenUrl = 'http://twitter.com/oauth/access_token';
  protected $authorizeUrl = 'http://twitter.com/oauth/authorize';
  protected $apiUrl = 'http://twitter.com';
  protected $userLinkTpl = 'http://twitter.com/%s';

  public function __call($name, $params = null)
  {
    $parts  = explode('_', $name);
    $method = strtoupper(array_shift($parts));
    $parts  = implode('_', $parts);
    $url    = $this->apiUrl . '/' . preg_replace('/[A-Z]|[0-9]+/e', "'/'.strtolower('\\0')", $parts) . '.json';

    if(!empty($params))
      $args = array_shift($params);

    return new EpiTwitterJson(call_user_func(array($this, 'httpRequest'), $method, $url, $args));
  }
  
  public function getProfile ($params) {
  	$url = 'http://api.twitter.com/users/lookup.json';
  	
  	//$args = array_shift($params);
  	$method = 'GET';
  	return new EpiTwitterJson($this->httpRequest ($method, $url, $params, true));
  }
  
  public function getUserLink ($userName) {
  	return sprintf($this->userLinkTpl, $userName);
  }

  public function __construct($consumerKey = null, $consumerSecret = null, $oauthToken = null, $oauthTokenSecret = null)
  {
    parent::__construct($consumerKey, $consumerSecret, self::EPITWITTER_SIGNATURE_METHOD);
    $this->setToken($oauthToken, $oauthTokenSecret);
    $domain = $_REQUEST['callback_domain'];
    if (! empty($domain)) {
    	$this->oauthCallback = 'http://'.$domain.'/pub/twitter.php';
    }
  }
}

class EpiTwitterJson
{
  private $resp;

  public function __construct($resp)
  {
    $this->resp = $resp;
    $this->responseText = $this->resp->data;
    $this->response = (array)json_decode($this->responseText, 1);
    foreach($this->response as $k => $v)
    {
    	$this->$k = $v;
    }
  }

  public function __get($name)
  {
    return $this->$name;
  }
}
