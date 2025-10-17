<?php

require_once("lib/artifact.lib");
require_once("tpl/artifact.tpl");

function tpl_chat_artifacts($text){
    //[[artifact_21694]]
    global $quality_info;
    if (!preg_match_all("/\[\[(.*?)_(.*?)\]\]/",$text,$matches)) return $text;
    $all = $matches[0];
    foreach ($all as $k=>$match){
        $tag = $matches[1][$k]; //Текущий тег
        $id_param = $matches[2][$k];
        switch ($tag){
            case 'artifact':
                $attr = '';
                $artifact = artifact_get($id_param);
                $artikul = artifact_artikul_get($artifact['artikul_id']);
                if(!$artifact || !$artikul){
                    $text = str_replace("[[".$tag."_".$id_param.']]','<span class="underline b" title="Или удален из игры.">#'.$id_param.' не найден.</span>',$text);
                    continue;
                }
                if($artifact['title']) $artikul['title'] = $artifact['title'];
                if($artifact['quality']) $artikul['quality'] = $artifact['quality'];
                $text = str_replace("[[".$tag."_".$id_param.']]','<span class="underline b"><a href="#" '.$attr.' style="color:'.$quality_info[$artikul['quality']]['color'].';" onClick="showArtifactInfo('.$id_param.');return false;">'.$artikul['title'].($artifact['toch'] ? ' +'.$artifact['toch'] : '').'</a></span>',$text);
                break;
            case 'artikul':
                $attr = '';
                $artikul = artifact_artikul_get($id_param);
                if(!$artikul){
                    $text = str_replace("[[".$tag."_".$id_param.']]','<span class="underline b" title="Или удален из игры.">#'.$id_param.' не найден.</span>',$text);
                    continue;
                }
                $text = str_replace("[[".$tag."_".$id_param.']]','<span class="underline b"><a href="#" '.$attr.' style="color:'.$quality_info[$artikul['quality']]['color'].';" onClick="showArtifactInfo(false,'.$id_param.');return false;">'.$artikul['title'].'</a></span>',$text);
                break;
        }
    }
    return $text;
}

//Кланы
function tpl_chat_clans($text){
    $text = htmlspecialchars_decode($text);
    preg_match_all("/http.*?\:\/\/".SERVER_DOMAIN."\/clan_info.php\?(clan_id=.*?\w*|&clan_id=\d*|&mode=\w*|mode=\w*|)(clan_id=.*?\w*|clan_id=\d*|&clan_id=\d*|&mode=\w*|mode=\w*|)/", $text, $mathes_ones);
    if($mathes_ones){
        require_once("lib/clan.lib");
        $clans = array();
        foreach($mathes_ones as $k => $mathes){
            if($k == 0) continue;

            $text_args = '';
            $clan_id = 0;
            foreach ($mathes as $i => $math){
                if (strpos($math, 'clan_id') !== false) {
                    $clan_id = intval(str_replace('clan_id=','',$math));
                    $clans[$i]['clan_id'] = $clan_id;
                }
                $text_args .= $math;
                $clans[$i]['text_args'] .= $math;
            }
        }

        foreach ($clans as $k=>$clan){
            $clan_get = clan_get($clans[$k]['clan_id']);
            $text_replace = '<a title="'.htmlspecialchars($clan['title']).'" target="_blank" href="../clan_info.php?'.$clans[$k]['text_args'].'"><img src="../'.PATH_IMAGE_CLANS.$clan_get['picture'].'" border=0 width=13 height=13 align="absmiddle">&nbsp;<b>'.htmlspecialchars($clan_get['title']).'</b> </a>';
            $clans[$k]['text_replace'] = $text_replace;
        }

        foreach($mathes_ones[0] as $k => $mathes){
            $text_plan = $text;
            $text = str_replace($mathes_ones[0][$k], $clans[$k]['text_replace'], $text_plan);
        }
    }
    return $text;
}

//Бои
function tpl_chat_fight($text){
    $text = htmlspecialchars_decode($text);
    preg_match_all("/http.*?\:\/\/".SERVER_DOMAIN."\/fight_info.php\?(fight_id=\d*|stat=\d*|server_id=\d*|page=\d*|&fight_id=\d*|&stat=\d*|&server_id=\d*|&page=\d*|)(fight_id=\d*|stat=\d*|server_id=\d*|page=\d*|&fight_id=\d*|&stat=\d*|&server_id=\d*|&page=\d*|)(fight_id=\d*|stat=\d*|server_id=\d*|page=\d*|&fight_id=\d*|&stat=\d*|&server_id=\d*|&page=\d*|)(fight_id=\d*|stat=\d*|server_id=\d*|page=\d*|&fight_id=\d*|&stat=\d*|&server_id=\d*|&page=\d*|)/", $text, $mathes_ones);
    if($mathes_ones){
        $fights = array();
        foreach($mathes_ones as $k => $mathes){
            if($k == 0) continue;

            $text_args = '';
            $fight_id = 0;
            foreach ($mathes as $i => $math){
                if (strpos($math, 'fight_id') !== false) {
                    $fight_id = intval(str_replace('fight_id=','',$math));
                    $fights[$i]['fight_id'] = $fight_id;
                }
                $text_args .= $math;
                $fights[$i]['text_args'] .= $math;
            }


        }

        foreach ($fights as $k=>$fight){
            $text_replace = '<a target="_blank" href="../fight_info.php?'.$fights[$k]['text_args'].'"><img src="/images/fighttype_pvp.gif" border=0 width=13 height=13 align="absmiddle">&nbsp;<b>Статистика боя <b style=color:darkred>[id: '.$fight['fight_id'].'] </b></b> </a>';
            $fights[$k]['text_replace'] = $text_replace;
        }

        foreach($mathes_ones[0] as $k => $mathes){
            $text_plan = $text;
            $text = str_replace($mathes_ones[0][$k], $fights[$k]['text_replace'], $text_plan);
        }
    }
    return $text;
}

//Эффекты
function tpl_chat_effectinfo($text){
    $text = htmlspecialchars_decode($text);
    preg_match_all("/http.*?\:\/\/".SERVER_DOMAIN."\/effect_info.php\?nick=(.*? |.*?\w*)/", $text, $mathes_ones);
    if($mathes_ones){
        $effects = array();
        foreach($mathes_ones as $k => $mathes){
            if($k == 0) continue;
            $text_args = '';
            $user_nick = '';
            foreach ($mathes as $i => $math){
                $user_nick = urldecode($math);
                $text_args = 'nick='.urldecode($math);
                $effects[$i]['nick'] = $user_nick;
                $effects[$i]['text_args'] .= $text_args;
            }
        }

        foreach ($effects as $k=>$effect){
            $text_replace = '<a target="_blank" href="../effect_info.php?'.$effects[$k]['text_args'].'"><b> Информация об эффектах "<b style=color:darkred>'.$effect['nick'].'</b>"</b> </a>';
            $effects[$k]['text_replace'] = $text_replace;
        }

        foreach($mathes_ones[0] as $k => $mathes){
            $text_plan = $text;
            $text = str_replace($mathes_ones[0][$k], $effects[$k]['text_replace'], $text_plan);
        }
    }
    return $text;
}