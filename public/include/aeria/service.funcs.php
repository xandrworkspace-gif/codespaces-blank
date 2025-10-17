<?php

function find_server_from_ip($ip){
    global $conn;
    $stmt = $conn->stmt_init();
    if ($stmt->prepare("SELECT id FROM servers WHERE server_ip = ? Limit 1")){
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $stmt->bind_result($id);
        $stmt->fetch();
        return  $id;
    }
    return false;
}

function get_server_info($id){
    global $conn;
    $stmt = $conn->stmt_init();
    if ($stmt->prepare("SELECT id,name,status,api_url,server_ip,auth_user,auth_pwd FROM servers WHERE id=?")){
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($id,$name,$status,$api_url,$server_ip,$auth_user,$auth_pwd);
        $stmt->fetch();
        return array("id" => $id, "name" => $name, "status" => $status, "server_ip" => $server_ip, "auth_user" => $auth_user, "auth_pwd" => $auth_pwd);
    }
    return false;
}

function get_user_info($username){
    global $conn;
    $stmt = $conn->stmt_init();
    // fgutacker@aeriagames.com: Query adapted to additionally get first charname for user, must be changed if more than one server are available
    if ($stmt->prepare("SELECT uid, aeria_id, username, users.join_date, last_login, charname FROM users left join user_servers on users.id=user_servers.uid WHERE username=? limit 0,1")){
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($id,$aeria_id, $username, $join_date, $last_login, $charname);
        $stmt->fetch();
        return array("id" => $id, "aeria_id" => $aeria_id, "username" => $username, "join_date" => $join_date, "last_login" => $last_login, "charname" => $charname);
    }
    return false;
}

// fgutacker@aeriagames.com: get_user_info() adapted into get_user_by_charname()
function get_user_by_charname($charname){
    global $conn;
    $stmt = $conn->stmt_init();
    if ($stmt->prepare("SELECT uid, aeria_id, username, users.join_date, last_login, charname FROM users left join user_servers on users.id=user_servers.uid WHERE charname=? limit 0,1")){
        $stmt->bind_param("s", $charname);
        $stmt->execute();
        $stmt->bind_result($id,$aeria_id, $username, $join_date, $last_login, $charname);
        $stmt->fetch();
        return array("id" => $id, "aeria_id" => $aeria_id, "username" => $username, "join_date" => $join_date, "last_login" => $last_login, "charname" => $charname);
    }
    return false;
}

function find_online($charname){
    global $conn;
    $stmt = $conn->stmt_init();
    // fgutacker@aeriagames.com: Query adapted to find charnames - not usernames
    if($stmt->prepare("SELECT token,secret,charname FROM users left join online on users.id = online.id left join user_servers on online.id = user_servers.uid where charname = ?")) {
    // if ($stmt->prepare("SELECT token,secret FROM users left join online on users.id = online.id WHERE username = ?")){
        $stmt->bind_param("s", $charname);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows != 1){
            return false;
        }
        $stmt->bind_result($token, $secret, $char);
        $stmt->fetch();
        return array("token" => $token, "secret" => $secret, "charname" => $char);
    }
    return false; 
}

function find_product($uname){
    global $conn;
    $stmt = $conn->stmt_init();
    if ($stmt->prepare("SELECT id,product_uname,item_name,price,last_updated FROM products WHERE product_uname = ?")){
        $stmt->bind_param("s", $uname);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows != 1){
            return false;
        }
        $stmt->bind_result($id, $product_uname, $item_name, $price, $last_updated);
        $stmt->fetch();
        return array("id" => $id, "product_uname" => $product_uname, "item_name" => $item_name, "price" => $price, "last_updated" => $last_updated);
    }
    return false;
}


function get_from_queee($txn_id){
    global $conn;
    $stmt = $conn->stmt_init();
    if ($stmt->prepare("SELECT id,txn_id,uid,price,is_success,order_date,product_id,complete_date FROM queee WHERE txn_id=?")){
        $stmt->bind_param("s", $txn_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows != 1){
            return false;
        }
        $stmt->bind_result($id,$txn_id,$uid,$price,$is_success,$order_date,$product_id,$complete_date);
        $stmt->fetch();
        return array("id" => $id, "txn_id" => $txn_id, "uid" => $uid, "price" => $price, "is_success" => $is_success, "order_date", "product_id" => $product_id, "complete_date" => $complete_date);
    }
    return false;
}

// fgutacker@aeriagames.com: $tnx_id changed into $txn_id
function purchase_success($txn_id){
    global $conn;
    $stmt = $conn->stmt_init();
    // fgutacker@aeriagames.com: statement corrected by exchanging %s with ?
    if ($stmt->prepare("UPDATE queee set is_success=1,complete_date=now() WHERE txn_id=?")){
    // if ($stmt->prepare("UPDATE queee set is_success=1,complete_date=now() WHERE txn_id=%s")){
        $stmt->bind_param("s", $txn_id);
        $stmt->execute();
        return true;
    }
    return false;
}
