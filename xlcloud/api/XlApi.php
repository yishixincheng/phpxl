<?php

namespace xl\api;

use xl\XlLead;

final class XlApi{

    public static $classobjcache=[];

    /**
     * XlApi constructor.
     * $method 方法名
     * $propertys 注入的属性
     * bool $issingle 是否是单例
     */
    public static function exec($method,$propertys,$issingle=false)
    {

        $routerfunc="run";
        if($pos=strpos($method,".")){
            $routerfunc=substr($method,$pos+1);
            $method=substr($method,0,$pos);
        }

        $obj=static::getInterface($method,$propertys,$issingle);

        return $obj->$routerfunc();

    }
    public static function getInterface($method,$propertys,$issingle=false)
    {

        if(strpos($method,'.')){
            $class=str_replace(".","\\",$method);
        }else{
            $class=$method;
        }
        $class="xl\\api\\".$class;

        $factory=XlLead::$factroy->bind("properties", $propertys);
        if($issingle){
            $factory->bind("singleton",true);
        }

        $obj=$factory->getInstance($class);

        static::$classobjcache[$method]=$obj;

        return $obj;

    }

    public static function getObjectFromMethod($method){

        if(static::$classobjcache[$method]){
            return static::$classobjcache[$method];
        }
        return null;
    }

}
