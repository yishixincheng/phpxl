<?php

namespace xl\util;

/**
 * Class XlUComm
 * 常用功能类
 */

class XlUComm{

    public static function toAbsUrl($url){

        $url=trim($url);
        if(filter_var($url,FILTER_VALIDATE_URL)){
            return $url;
        }

        if(strcmp($url{0},"/")===0){
            return config("system/site_url").$url;
        }

        return '';

    }
    public static function dealLackTelphone($telphone){

        $telphone=trim($telphone);
        if(is_telphone($telphone)){
            return $telphone;
        }
        if(preg_match("/^\d{6,7}$/",$telphone)){
            if(strlen($telphone)==6){
                return substr($telphone,0,3)."*****".substr($telphone,3);
            }else{
                return substr($telphone,0,3)."****".substr($telphone,3);
            }
        }

        return $telphone;

    }

}