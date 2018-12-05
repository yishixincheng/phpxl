<?php

namespace xl\classs;

use xl\base\XlClassBase;

class SmsfactoryClass extends XlClassBase {

    private static $_factory;

    public static function get_instance() {

        if(!SmsfactoryClass::$_factory){
            SmsfactoryClass::$_factory=new SmsfactoryClass();
        }
        return SmsfactoryClass::$_factory;

    }
    public function getinterface(){

        //获得操作对象的接口
        $smstype=config("sms/sms_type");
        $smsc=config("sms/".$smstype);
        if(!$smsc){return null;}

        import("@third.sms.".$smsc['classname']);

        $className='\\'.$smsc['classname'];

        $isms=new $className;

        return $isms;


    }



}