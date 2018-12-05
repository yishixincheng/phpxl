<?php

namespace Xl_MQ\Lib;

use Xl_MQ\MQConfig;

/**
 * Class Queue
 * @package Xl_MQ\Lib
 * 消息队列操作类
 */
class Queue{

    private static $redisObject=null;

    private $_queuename=null;
    private $_msgStructStr=null;

    public function setQueueName($queuename=null){

        $this->_queuename=$queuename?:"default"; //如果没有指明，则用默认队列

    }

    public function setMsgStruct($msgStruct){

        if(is_array($msgStruct)){
            $this->_msgStructStr=json_encode($msgStruct); //json编码
        }else{
            $this->_msgStructStr=$msgStruct;
        }

    }

    public function getQueueName(){
        return $this->_queuename;
    }

    public function getMsgStruct(){

        return $this->_msgStructStr;

    }

    //获取redis对象
    public static function getRedisObject(){

        if(static::$redisObject){
            return static::$redisObject;
        }
        static::$redisObject=new Redis(MQConfig::getRedisHost(),MQConfig::getRedisPort(),MQConfig::getRedisPre(),MQConfig::getRedisPconnect());

        return static::$redisObject;

    }

    /**
     * @param null $queuename
     * 队列名称添加到缓存中
     */
    public static function addToQueueNameList($queuename=null){

        if(empty($queuename)){
            $queuename="default";
        }

        $redis=static::getRedisObject();
        $key="xl_mqnl";
        $queuenamelist=$redis->get($key);

        if(!is_array($queuename)){
            $queuenamelist=[];
        }
        if(!in_array($queuename,$queuenamelist)){

            array_push($queuenamelist,$queuename);

            $redis->set($key,$queuenamelist); //设置到队列中
        }

    }

    /**
     * @param null $queuename
     * 队列名称从队列列表中移除
     */
    public static function removeFromQueueNameList($queuename=null){

        if(empty($queuename)){
            $queuename="default";
        }
        $redis=static::getRedisObject();
        $key="xl_mqnl";
        $queuenamelist=$redis->get($key);
        if(!is_array($queuename)){
            return;
        }
        if(in_array($queuename,$queuenamelist)){
            $index=array_search($queuename,$queuenamelist);
            unset($queuenamelist[$index]);
            $queuenamelist=array_values($queuenamelist);
            $redis->set($key,$queuenamelist); //移除
        }

    }

    public static function getQueueNameList(){

        $redis=static::getRedisObject();

        $queuenamelist=$redis->get("xl_mqnl");

        return $queuenamelist?:[];

    }

        //添加到队列列表中
    public static function addToList(Queue $queque){

        $_queuename=$queque->getQueueName();    //队列名称
        $_msgStruct=$queque->getMsgStruct();    //队列消息

        $redis=static::getRedisObject();

        $key="xl_mq_".$_queuename;

        $redis->lpush($key,$_msgStruct); //设置到队列中

        static::addToQueueNameList($_queuename); //队列名称

        return true;
    }

    public static function clearList($queuename=null){

        if(empty($queuename)){
            $queuename="default";
        }

        $redis=static::getRedisObject();

        $key="xl_mq_".$queuename;

        $redis->delete($key);

        static::removeFromQueueNameList($queuename);

        return true;

    }

    //获取任务节点
    public static function getQueueNode($queuename=null){

        if(empty($queuename)){
            $queuename="default";
        }
        $redis=static::getRedisObject();
        $key="xl_mq_".$queuename;
        $msgStructStr=$redis->lpop($key);

        if($msgStructStr){
            $msgStruct=json_decode($msgStructStr,true);
            if(!is_array($msgStruct)){
                return $msgStructStr;
            }
            return $msgStruct;

        }else{
            //队列数据取完
            return null;
        }

    }

}