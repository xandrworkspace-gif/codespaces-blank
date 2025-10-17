<?php // %TRANS_SKIP%

require_once "include/oauth/OAuthRequester.php";

class Aeria {

//     const LIVE = LIVE_MODE;
    const LIVE = true;
    
    private $consumer_key;
    private $consumer_secret;
    private $user_token;
    private $user_token_secret;

    public function __construct($consumer_key, $consumer_secret, $user_token, $user_token_secret) {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        $this->user_token = $user_token;
        $this->user_token_secret = $user_token_secret;
    }

    /**
     * Purchase an in-game item with AP
     * @param string $txn_id Unique string to identify each transaction
     * @param int $price How much of user’s AP should be spent.
     * @param int $item_id Unique ID of item.
     * @param string $item_name Human-readable item name.
     * @param string $user_ip IP address of user whose AP is being spent.
     * @return stdClass Object with two numeric fields: "balance_remaining" and "txn_time_elapsed"
     */
    public function billing_purchase($txn_id, $price, $item_id, $item_name, $user_ip) {
        if (empty($txn_id) || empty($price) || empty($item_id) || empty($item_name) || empty($user_ip)) {
            throw new AeriaException("One or more arguments missing.", 0);
        }
        $txn_id_len = strlen($txn_id);
        $item_name_len = strlen($item_name);
        if (!ctype_alnum($txn_id) || $txn_id_len < 1 || $txn_id_len > 64 || !is_numeric($price) ||
                $price < 1 || !is_numeric($item_id) || $item_name_len < 3 || $item_name_len > 50) {
            throw new AeriaException("One or more arguments invalid.", 0);
        }
        $resource = "billing/purchase";
        $method = "POST";
        $params = array("txn_id" => $txn_id, "price" => $price, "item_id" => $item_id,
                        "item_name" => $item_name, "user_ip" => $user_ip);
        return $this->call_method($resource, $method, $params);
    }

    /**
     * Gets user's current AP balance
     * @return stdClass Object with single numeric field: "balance"
     */
    public function billing_balance() {
        $resource = "billing/balance";
        $method = "GET";
        return $this->call_method($resource, $method);
    }

    /**
     * Get list of friend UIDs (subject to privacy settings)
     * @param bool $show_usernames Whether to return usernames along with IDs
     * @return array Array of stdClass objects with 1 or 2 fields: "uid" and "username"
     */
    public function friends_ids($show_usernames = false) {
        $resource = "friends/ids";
        $method = "GET";
        $params = array("show_usernames" => $show_usernames ? 1 : 0);
        return $this->call_method($resource, $method, $params);
    }

    /**
     * Check if two users are friends (subject to privacy settings)
     * @param int $uid1
     * @param int $uid2
     * @return bool|null Boolean or null returned depending on both users' privacy
     */
    public function friends_exist($uid1, $uid2) {
        if (!is_numeric($uid1) || !is_numeric($uid2)) {
            throw new AeriaException("One or more arguments missing or invalid.", 0);
        }
        $resource = "friends/exists";
        $method = "GET";
        $params = array("uid1" => $uid1, "uid2" => $uid2);
        $result = $this->call_method($resource, $method, $params);
        if ($result == "true") {
            return true;
        } else if ($result == "false") {
            return false;
        } else {
            return null;
        }
    }

    /**
     * Send friend request to specified friend.
     * @param int $uid
     * @return bool True will be returned, or an exception will be thrown
     */
    public function friends_request($uid) {
        if (!is_numeric($uid)) {
            throw new AeriaException("One or more arguments missing or invalid.", 0);
        }
        $resource = "friends/request";
        $method = "POST";
        $params = array("uid" => $uid);
        $result = $this->call_method($resource, $method, $params);
        if ($result == "true") {
            return true;
        } else {
            throw new AeriaException("Unknown error.", 1);
        }
    }

    /**
     * Get profile information.
     * @param int $uid If this is null, information for the current user will be returned
     * @param array $fields Array of specific fields to select
     * @return stdClass Object with various user fields
     */
    public function user_profile($uid = null, $fields = array()) {
        if ((!is_null($uid) && !is_numeric($uid)) || !is_array($fields)) {
            throw new AeriaException("One or more arguments missing or invalid.", 0);
        }
        $fields = implode(",", $fields);
        $resource = "user/profile";
        $method = "GET";
        $params = array("uid" => $uid, "fields" => $fields);
        return $this->call_method($resource, $method, $params);
    }
    
    /**
     * Get profile information.
     * @param int $uid If this is null, information for the current user will be returned
     * @param array $fields Array of specific fields to select
     * @return stdClass Object with various user fields
     */
    public function test_uid() {
        $resource = "test/uid";
        $method = "GET";
        $params = array();
        return $this->call_method($resource, $method, $params);
    }

    /**
     * Get uid from username.
     * @param string $username
     * @return int UID of user
     */
    public function user_uid($username) {
        if(empty($username)) {
            throw new AeriaException("One or more arguments missing or invalid.", 0);
        }
        $resource = "user/uid";
        $method = "GET";
        $params = array("username" => $username);
        return (int) $this->call_method($resource, $method, $params);
    }

    
    private function call_method($resource, $method, $params = array()) {
        $url = $this->get_service_path() . $resource . $this->get_response_type();
        $request = new OAuthRequester($url, $method, $params);
        $result = $request->doRequest($this->consumer_key, $this->consumer_secret, $this->user_token,
                                      $this->user_token_secret);
		
		$response = $result["body"];
        if (empty($response)) {
            throw new AeriaException("No server response returned.", 0);
        } else {
            $result = $this->convert_result($response);
            if(is_object($result) && property_exists($result, "error_code")) {
                throw new AeriaException($result->error_msg, $result->error_code);
            } else {
                return $result;
            }
        }
    }

    private function get_service_path() {
        if (self::LIVE) {
            return "http://api.aeriagames.com/services/v1/";
        } else /* if(strstr($_SERVER['HTTP_REFERER'], 'test.aeriagames.com')) */{ 
        	return "http://api.test.aeriagames.com/services/v1/";
	}
	/*
		else {
		    exit;
		}
	*/
    }

    private function get_response_type() {
        return ".json";
    }

    private function convert_result($raw) {
        switch ($this->get_response_type()) {
            case ".json":
                $object = json_decode($raw);
                break;
        }
        return $object;
    }

}

class AeriaException extends Exception {
}
