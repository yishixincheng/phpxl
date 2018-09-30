<?php

namespace xl\classs;

use xl\base\XlClassBase;


class OppicfactoryClass extends XlClassBase{

    private static $oppic_factory;

    function __construct(){
    }

    public static function get_instance() {
        if(self::$oppic_factory == '') {
            self::$oppic_factory = new OppicfactoryClass();
        }
        return self::$oppic_factory;
    }
    public function getinterface(){

        //获得操作对象的接口
        if(class_exists('\Imagick')){
            //加载操作
            return sysclass('imagick');
        }else{
            return sysclass("gd");
        }

    }


}