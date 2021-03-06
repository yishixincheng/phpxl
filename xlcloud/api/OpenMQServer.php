<?php

namespace xl\api;
use xl\XlLead;

import("@xl.vendor.autoload");

/**
 * Class OpenMQServer
 * @package xl\api
 * 开启MQ服务
 */
final class OpenMQServer extends XlApiBase{


    protected $config=null;
    protected $logger=null;


    public function run()
    {

        if($this->config&&empty($this->config['redisPre'])){
            $this->config['redisPre']=md5(DOC_ROOT);
        }

        \Xl_MQ\MQConfig::setup($this->config);

        \Xl_MQ\MQServer::setCallback(function($msgStruct){

            $task=$msgStruct['task'];       //task 结构plugin:folder/taskname
            $params=$msgStruct['params'];   //参数

            $ns=null;
            if($task){
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
            }else{

                $class=$msgStruct['class'];
                $method=$msgStruct['method'];
                $ns=$msgStruct['ns'];
                $isplugin=$msgStruct['isplugin'];

                if($class&&$method){
                    $ins=XlLead::$factroy->bind("properties",['_Isplugin'=>$isplugin,'_Ns'=>$ns])->getInstance($class);
                    call_user_func([$ins,$method],$params);
                }

            }

        });

        \Xl_MQ\MQServer::setLogger($this->logger);

        \Xl_MQ\MQServer::run(); //执行

    }

}