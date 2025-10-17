<?php
/** Simple layer for supporting most common deprecated mysql_* functions in PHP 7.
 *  It replaces their calls to MySQLi function calls.
 *  Just include this file in start of your index.php.
 *
 *  Written by 4X_Pro, http://4xpro.ru
 *  Distributed under MIT license terms.
 * **/

if(!defined('MYSQL_ASSOC')) define('MYSQL_ASSOC', MYSQLI_ASSOC);
if(!defined('MYSQL_NUM')) define('MYSQL_NUM', MYSQLI_NUM);
if(!defined('MYSQL_BOTH')) define('MYSQL_BOTH', MYSQLI_BOTH);
 
function mysql_connect($server,$username,$password,$new_link=false,$client_flags=0) {
    $GLOBALS['mysql_oldstyle_link']=mysqli_connect($server,$username,$password);
    return $GLOBALS['mysql_oldstyle_link'];
}

function mysql_pconnect($server,$username,$password,$new_link=false,$client_flags=0) {
    $GLOBALS['mysql_oldstyle_link']=mysqli_connect($server,$username,$password);
    return $GLOBALS['mysql_oldstyle_link'];
}

function mysql_query($sql,$link=NULL) {
    if ($link==NULL) $link=$GLOBALS['mysql_oldstyle_link'];
    return mysqli_query($link,$sql);
}

function mysql_fetch_row($res) {
    return mysqli_fetch_row($res);
}

function mysql_fetch_assoc($res) {
    return mysqli_fetch_assoc($res);
}

function mysql_fetch_array($res, $mode = MYSQLI_BOTH) {
    return mysqli_fetch_array($res, $mode);
}

function mysql_fetch_object($res,$classname='stdClass',$params=array()) {
    return mysqli_fetch_object($res,$classname,$params);
}

function mysql_affected_rows($link=NULL) {
    if ($link===NULL) $link=$GLOBALS['mysql_oldstyle_link'];
    return mysqli_affected_rows($link);
}

function mysql_insert_id($link=NULL) {
    if ($link===NULL) $link=$GLOBALS['mysql_oldstyle_link'];
    return mysqli_insert_id($link);
}

function mysql_select_db($database_name,$link=NULL) {
    if ($link==NULL) $link=$GLOBALS['mysql_oldstyle_link'];
    return mysqli_select_db($link,$database_name);
}

function mysql_errno($link=NULL) {
    if ($link===NULL) $link=$GLOBALS['mysql_oldstyle_link'];
    return mysqli_errno($link);
}

function mysql_error($link=NULL) {
    if ($link===NULL) $link=$GLOBALS['mysql_oldstyle_link'];
    return mysqli_error($link);
}

function mysql_num_rows($res) {
    return mysqli_num_rows($res);
}

function mysql_free_result($res) {
    return mysqli_free_result($res);
}

function mysql_close($link) {
    return mysqli_close($link);
}

function mysql_real_escape_string($sql,$link=NULL) {
    if ($link===NULL) $link=$GLOBALS['mysql_oldstyle_link'];
    return mysqli_real_escape_string($link,$sql);
}

function mysql_get_server_info($link=NULL) {
    if ($link===NULL) $link=$GLOBALS['mysql_oldstyle_link'];
    return mysqli_get_server_info($link);
}

function mysql_set_charset ($charset, $link_identifier = NULL) {
    if ($link===NULL) $link=$GLOBALS['mysql_oldstyle_link'];
    return mysqli_set_charset($link, $charset);
}

function mysql_db_query($db, $sql, $link) {
    mysqli_select_db($link, $db);
    return mysqli_query($link, $sql);
}

function mysql_ping($link) {
    return mysqli_ping($link);
}