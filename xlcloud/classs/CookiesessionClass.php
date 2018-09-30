<?php

namespace xl\classs;

use xl\base\XlClassBase;


class CookiesessionClass extends XlClassBase{


    public function sgCookie($key,$val=null,$time=null){

        if(is_string($key)){
            if($val===null){
                $op="get";
            }else{
                $op="set";
            }
            if($time===null){
                $time=(int)config("system/cookie_expire")*86400+time(); //秒数
            }
        }else if(is_array($key)){
            $op=$key['op'];
            $val=$key['val'];
            $time=$key['time'];
            $key=$key['key'];
            if(!$op){
                $op="get";
            }
            if(!isset($time)||($time===null)){
                $time=(int)config("system/cookie_expire")*86400+time();
            }
        }
        if($op=="set"){
            $this->setCookie($key,$val,$time);
        }else if($op=="get"){
            return $this->getCookie($key,$val);
        }
    }
    public function sgSession($key,$val=null){

        session_start();
        $key=config('system/cookie_pre').$key;

        if($val===null){
            return unserialize(sys_auth($_SESSION[$key], 'DECODE'));
        }
        $val=sys_auth(serialize($val),"ENCODE");
        $_SESSION[$key]=$val;

        SetG("Session/Write",1);

        session_commit();

    }
    public function setCookie($var, $value = '', $time = 0){

        $var = config('system/cookie_pre').$var;
        $this->setCookieEx($var,$value,$time, config('system/cookie_path'),config('system/cookie_domain'));

    }
    public function setCookieEx($var,$value='',$time=0,$path="/",$domain=""){

        $time = $time > 0 ? $time : ($value == '' ? SYS_TIME - 3600 : 0);
        $s = $_SERVER['SERVER_PORT'] == '443' ? true : false;
        $value=serialize($value);
        $cv=sys_auth($value, 'ENCODE');
        $_COOKIE[$var] =$cv;

        setcookie($var,$cv,$time,$path,$domain,$s);

    }
    public function getCookie($var, $default = ''){

        $var = config('system/cookie_pre').$var;
        $r=isset($_COOKIE[$var]) ? unserialize(sys_auth($_COOKIE[$var], 'DECODE')) : $default;
        return $r;
    }
    public function setPureCookie($var,$value='',$time=0){

        //设置大的数据，存储在数据库中，突破cookie限制
        $time = $time > 0 ? $time : ($value == '' ? SYS_TIME - 3600 : 0);
        $s = $_SERVER['SERVER_PORT'] == '443' ? true : false;
        $_COOKIE[$var] =$value;

        setcookie($var, $value, $time, config('system/cookie_path'), config('system/cookie_domain'), $s);

    }
    public function getPureCookie($var,$default=''){


        return isset($_COOKIE[$var])?$_COOKIE[$var]:$default;

    }

}