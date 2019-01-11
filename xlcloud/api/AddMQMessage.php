<?php

namespace xl\api;
use xl\base\XlException;

import("@xl.vendor.autoload");


class AddMQMessage extends XlApiBase{

    protected $queuename=null;  //队列名,null是默认队列
    protected $task=null;        //task名称，结构plugin:folder/taskname
    protected $params;      //参数名称
    protected $settime=null; //指定时间执行
    protected $config=null;

    /**
     * @var null
     * 第二种执行方式
     */
    protected $class=null;
    protected $method=null;
    protected $isplugin=null;
    protected $ns=null;

    public static $issetup=false;

    public function run(){

        if(!self::$issetup&&$this->config){

            if(empty($this->config['redisPre'])){
                $this->config['redisPre']=md5(DOC_ROOT);
            }

            \Xl_MQ\MQConfig::setup($this->config);
            self::$issetup=true;
        }
        if($this->task){
            $msgStruct=['task'=>$this->task,'params'=>$this->params,'settime'=>$this->settime];
        }else if($this->class){
            $msgStruct=['class'=>$this->class,'method'=>$this->method,'isplugin'=>$this->isplugin,'ns'=>$this->ns,'params'=>$this->params,'settime'=>$this->settime];
        }else{
            throw new XlException("未指明任务名");
        }

        //添加到消息队列中
        return \Xl_MQ\MQClient::addToQueue($msgStruct,$this->queuename);

    }


}