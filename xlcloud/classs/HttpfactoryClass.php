<?php

namespace xl\classs;

use xl\base\XlClassBase;

class HttpfactoryClass extends XlClassBase {

    private static $_factory;

    public static function get_instance() {

        if(!HttpfactoryClass::$_factory){
            HttpfactoryClass::$_factory=new HttpfactoryClass();
        }
        return HttpfactoryClass::$_factory;

    }
    public function get_http(){

        if(extension_loaded("curl")){
            return sysclass("httpcurl");
        }else{
            return sysclass("httpsocket");
        }

    }



}