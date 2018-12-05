<?php

namespace xl\api;

import("@xl.vendor.autoload");

/**
 * Class OpenMQServer
 * @package xl\api
 * 开启MQ服务
 */
class OpenMQServer extends XlApiBase{


    protected $config=null;
    protected $logger=null;


    public function run()
    {

        if($this->config&&empty($this->config['redisPre'])){
            $this->config['redisPre']=md5(DOC_ROOT);
        }

        print_r($this->config);

        \Xl_MQ\MQConfig::setup($this->config);

        \Xl_MQ\MQServer::setCallback(function($msgStruct){

            $task=$msgStruct['task'];       //task 结构plugin:folder/taskname
            $params=$msgStruct['params'];   //参数

            $ns=null;
            if(($pos=strpos($task,":"))===false){
                //全局方法
                $ns=defined("ROOT_NS")?ROOT_NS:'';
                $methodname=$task;
                $isplugin=false;
            }else{
                //插件
                $ns=substr($task,0,$pos);
                $methodname=substr($task,$pos+1);
                $isplugin=true;
            }

            TS("消息队列任务",$params,$isplugin,$ns)->task($methodname)->done(); //调用task任务

        });

        \Xl_MQ\MQServer::setLogger($this->logger);

        \Xl_MQ\MQServer::run(); //执行

    }

}