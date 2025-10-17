<? # $Id: mobile.inc,v 1.5 2007-11-15 11:25:14 kastet Exp $

define('PATH_MOBILE_PREFIX', 'mobile/');
define('PATH_MOBILE_POSTFIX', '.mobile');
define('MOBILE_USER_AGENT', 'dwarmobileserver');
define('MOBILE_FAKE_DOMAIN', 'dwarmobile.ru');

global $mobile, $send_response;

if ($_SERVER["HTTP_USER_AGENT"] == MOBILE_USER_AGENT) {
	$mobile = 1;
}
?>