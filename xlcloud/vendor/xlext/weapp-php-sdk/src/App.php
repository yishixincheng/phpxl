<?php

namespace Xl_WeApp_SDK;

class App{

    public static $serviceobjs=[];

    /**
     * @param $conf
     * 启动应用
     */
    public static function run($conf){

        Config::setup($conf); //注入配置

    }


    public static function getService($servicename,$iscache=true){

        if($iscache&&static::$serviceobjs[$servicename]){
            return static::$serviceobjs[$servicename];
        }

        $cls="Xl_WeApp_SDK\\Service\\".ucfirst($servicename)."Service";

        static::$serviceobjs[$servicename]=new $cls;

        return static::$serviceobjs[$servicename];

    }

}
