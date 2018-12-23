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

        if(!is_array($queuenamelist)){
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

        $len=$redis->lPush($key,$_msgStruct); //设置到队列中

        $qnpset=MQConfig::getQNPSet();

        if($qnpset&&$qnpset[$_queuename]&&is_array($qnpset[$_queuename])&&$qnpset[$_queuename]['type']==1){

            //顺序执行则不启动回收机制

        }else{

            if($len>MQConfig::getMaxQuequeTaskNum()){
                $_spillMsgStruct=$redis->lPop($key); //移除头部队列
                if($_spillMsgStruct){
                    //保存到文件中
                    SpillQueue::add($_queuename,$_spillMsgStruct);
                }
            }

        }

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
        $msgStructStr=$redis->rPop($key);

        if($msgStructStr){
            $msgStruct=json_decode($msgStructStr,true);
            if(!is_array($msgStruct)){
                return $msgStructStr;
            }
            return $msgStruct;

        }else{
            //队列数据取完,移除名称节点,如果是定时计划则移除
            if($queuename!="default"){
                if(preg_match("/inner_timing/",$queuename)){
                    static::clearList($queuename);
                }
            }
            return null;
        }

    }

    //获取队列控制节点
    public static function getQueueNameControlParam($queuename=null){

        if(empty($queuename)){
            $queuename="default";
        }
        $redis=static::getRedisObject();
        $key="xl_mq_controlparam_".$queuename;
        $controlparam=$redis->get($key);

        if(!$controlparam){
            return null;
        }
        if(!is_array($controlparam)){
            return null;
        }

        return $controlparam;

    }

    //设置控制节点参数
    public static function setQueueNameControlParam($queuename=null,$key,$value=null){

        $controlparam=static::getQueueNameControlParam($queuename);

        if(!$controlparam){
            $controlparam=[];
        }
        if($value==null){
            unset($controlparam[$key]);
        }else{
            $controlparam[$key]=$value;
        }

        $redis=static::getRedisObject();
        $key="xl_mq_controlparam_".$queuename;

        return $redis->set($key,$controlparam);

    }

    public static function getSpillLock(){

        $key="xl_mq_spilllock";
        $redis=static::getRedisObject();
        $time=$redis->get($key);
        $currtime=time();
        if($time){

            if($currtime-$time<600){
                return true; //10分钟自动解锁
            }

        }
        $redis->set($key,$currtime);

        return false;
    }



}