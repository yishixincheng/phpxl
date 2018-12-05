<?php

namespace xl\util;

class XlUVerify{

    public static function isTrue($bool,$msg=null,$hook=null){

        if(!$bool){

            if(is_callable($hook)){
                //钩子
                $hook();
            }

            if($msg==null){
                return false;
            }
            if(is_numeric($msg)){
                throw new XlUException("",$msg);
            }else if(is_string($msg)){

                if(preg_match("/^[A-Za-z]{1,18}$/",$msg)){
                    throw new XlUException($msg,$msg);
                }else{
                    throw new XlUException($msg);
                }

            }else if($msg instanceof \Exception ){
                throw new $msg;
            }

        }

        return true;

    }

}