<?php

namespace xl\api;

import("@xl.vendor.autoload");


class AddMQMessage extends XlApiBase{

    protected $queuename=null;  //队列名,null是默认队列
    protected $task;        //task名称，结构plugin:folder/taskname
    protected $params;      //参数名称

    protected $config=null;

    public static $issetup=false;

    public function run(){

        if(!self::$issetup&&$this->config){
            \Xl_MQ\MQConfig::setup($this->config);
            self::$issetup=true;
        }
        $msgStruct=['task'=>$this->task,'params'=>$this->params];

        //添加到消息队列中
        \Xl_MQ\MQClient::addToQueue($msgStruct,$this->queuename);

        return true;

    }


}