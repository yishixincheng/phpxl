<?php

namespace Xl_MQ;

/**
 * Class MQConfig
 * @package Xl_MQ
 * 初始配置通用文件
 */
class MQConfig{


    public static $RedisHost="localhost"; //redis主机
    public static $RedisPort=6379;         //redis监听端口
    public static $RedisPre="";            //前缀
    public static $RedisPconnect=false;    //是否是长链接


    public static $BeatSec=0.2;            //2秒检测一次有无新的队列
    public static $MaxProcessesNum=10;     //最多的进程数
    public static $MaxQuequeTaskNum=1000;  //每个队列里同时最多任务数
    

    public static function __callStatic($name, $arguemnts) {
        $class = get_class();
        if (strpos($name, 'get') === 0) {
            $key = preg_replace('/^get/', '', $name);
            if (property_exists($class, $key)) {
                $value = self::$$key;
                return $value;
            }
        }else if (strpos($name, 'set') === 0) {
            $key = preg_replace('/^set/', '', $name);
            $value = isset($arguemnts[0]) ? $arguemnts[0] : NULL;
            if (property_exists($class, $key)) {
                self::$$key = $value;
            }
        }
        return null;
    }

    public static function setup($config =NULL){
        if (!is_array($config)) {
            throw new \Exception("配置参数应该是数组");
        }

        $class = get_class();
        foreach ($config as $key => $value) {
            $key = ucfirst($key);
            if (property_exists($class, $key)) {
                if (gettype($value) === gettype(self::$$key)) {
                    if (gettype($value) === 'array') {
                        self::$$key = array_merge(self::$$key, $value);
                    } else {
                        self::$$key = $value;
                    }
                } else {
                    throw new \Exception('配置类型未指定: ' . $key);
                }
            }
        }
    }

}
