<?php
class appException extends Exception {
	public final function __construct($msg = "Application error!", $stop = true, $redirect = false, $redirectUrl = ""){
		echo $msg;
		if ($stop) die;
        if ($redirect) sprintf(header("location: %s"), $redirectUrl);
		return;
	}
}
