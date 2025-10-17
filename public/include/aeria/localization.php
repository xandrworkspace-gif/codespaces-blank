<?php

$default_lang = "en-US";
$lang_dir = dirname(__FILE__) ."/../locale/";
$avail_langs = array('en-US' => 'en-us.yaml');

if (is_null($_SESSION) or !array_key_exists("locale", $_SESSION)){
    if (array_key_exists("l", $_GET)) $locale = $_GET["l"];
    else $locale=$default_lang;
    if (array_key_exists($locale, $avail_langs)) $locale = $default_lang;
    $_SESSION["locale"] = $locale;
}else $locale = $_SESSION["locale"];

load_lang($avail_langs[$locale]);

function load_lang($localization_file){
    global $__lang__, $lang_dir;
    $fullpath = $lang_dir.$localization_file;
    if (file_exists($fullpath)){
        $__lang__ = Spyc::YAMLLoad($fullpath);
    }else{
        throw new appException("Language file error.");
    }
}

function __($translation, $type = "errors"){
    global $__lang__;
    if (array_key_exists($translation, $__lang__[$type])) return $__lang__[$type][$translation];
    else throw new appException(sprintf("Language error key='%s' not found in translations.", $translation));
}
