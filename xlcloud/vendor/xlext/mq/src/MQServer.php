<?php

namespace Xl_MQ;

use Xl_MQ\Lib\Queue;
use Xl_MQ\Lib\SpillQueue;
/**
 * Class MQServer
 * @package Xl_MQ
 * 服务端,在cli模式下启动
 * 依赖workman扩展
 */

class MQServer{

    public static $queuenamelist=null;
    public static $currqueuename=null;
    public static $fetchquequelisttime=null;

    public static $taskCallBack=null; //注入回调
    public static $logger=null;       //日志对象
    public static $runTaskCountPointer=0;
    public static $maxMemory=null; //超过30m自动重启

    public static $qNPSet=[];      //queuename参数设置

    public static function setCallback($callback){
        static::$taskCallBack=$callback;
    }
    public static function setLogger($logger){
        static::$logger=$logger;
    }

    public static function run(){

        //启动
        static::$qNPSet=MQConfig::getQNPSet()?:[];  //赋值
        static::$maxMemory=MQConfig::getMaxMemory()?:null;

        $worker=new \Workerman\Worker();
        $worker->count=MQConfig::getMaxProcessesNum(); //最多进程数
        $beatsec=MQConfig::getBeatSec();

        $worker->onWorkerStart=function($worker) use($beatsec){

            $worker_id = $worker->id; //进程编号
            \Workerman\Lib\Timer::add($beatsec, function() use($worker_id){
                static::openTask($worker_id);
            });
        };

        \Workerman\Worker::$daemonize=true; //守护进程方式
        \Workerman\Worker::runAll(); //运行

    }
    public static function openTask($worker_id){


        try {

            if($worker_id==0){

                //溢出回收机制，10分钟一次
                if(!Queue::getSpillLock()){
                    //没有上锁，则执行还原机制
                    $spills=SpillQueue::fetchlines();
                    if($spills&&is_array($spills)){
                        foreach ($spills as $node){
                            $queue=new Queue();
                            $queue->setQueueName($node['queuename']); //要加入的队列名称
                            $queue->setMsgStruct($node['msgStruct']); //消息结构体
                            Queue::addToList($queue); //添加到消息队列中
                            unset($queue);
                        }
                    }
                    unset($spills);
                }

            }
            //读取任务列表
            $currtime = time();
            if (static::$fetchquequelisttime) {
                if ($currtime - static::$fetchquequelisttime > 1) {
                    //缓存1秒
                    static::$queuenamelist = Queue::getQueueNameList();
                    static::$fetchquequelisttime = $currtime;
                }
            } else {
                static::$queuenamelist = Queue::getQueueNameList();
                static::$fetchquequelisttime = $currtime;
            }
            if (empty(static::$queuenamelist)) {
                //没有任务
                unset($currtime);
                unset($worker_id);
                return;
            }

            $len = count(static::$queuenamelist);
            $isfind=false;
            for ($i = 0; $i < $len; $i++) {
                if (static::$queuenamelist[$i] == static::$currqueuename) {
                    if ($i == $len - 1) {
                        static::$currqueuename = static::$queuenamelist[0];
                    } else {
                        static::$currqueuename = static::$queuenamelist[$i + 1];
                    }
                    $isfind=true;
                    break;
                }
            }
            if (empty(static::$currqueuename)||!$isfind){
                static::$currqueuename = static::$queuenamelist[0];
            }
            unset($i);
            unset($len);
            unset($isfind);

            $havesetconfig=false;
            if(static::$qNPSet&&is_array(static::$qNPSet)&&is_array(static::$qNPSet[static::$currqueuename])){

                $havesetconfig=true;
                //定义了队列参数

                if(isset(static::$qNPSet[static::$currqueuename]['type'])){
                    $type=static::$qNPSet[static::$currqueuename]['type'];
                }else{
                    $type=0;
                }

                if(isset(static::$qNPSet[static::$currqueuename]['interval'])){
                    $interval=static::$qNPSet[static::$currqueuename]['interval']; //间隔执行
                }else{
                    $interval=0;
                }
                if(isset(static::$qNPSet[static::$currqueuename]['settime'])){
                    $settime=static::$qNPSet[static::$currqueuename]['settime']; //指定时间执行
                }else{
                    $settime=0;
                }
                if(isset(static::$qNPSet[static::$currqueuename]['locktimeout'])){
                    $locktimeout=static::$qNPSet[static::$currqueuename]['locktimeout']; //超过多少时间则自动解锁
                }else{
                    $locktimeout=null;
                }

                if($settime&&$settime-$currtime>0){
                    //未到指定的时间不执行
                    unset($settime);
                    unset($type);
                    unset($locktimeout);
                    unset($currtime);
                    unset($havesetconfig);
                    unset($interval);
                    return;
                }
                //默认获取
                if($interval){
                    $controlparam=Queue::getQueueNameControlParam(static::$currqueuename); //队列参数
                    if($controlparam&&isset($controlparam['lasttime'])){
                        $lasttime=$controlparam['lasttime'];
                        unset($controlparam);
                    }else{
                        $lasttime=null;
                    }
                    //有间隔的执行
                    if($lasttime&&($currtime-$lasttime<=$interval)){
                        //未到时间间隔不执行
                        unset($settime);
                        unset($type);
                        unset($locktimeout);
                        unset($lasttime);
                        unset($currtime);
                        unset($havesetconfig);
                        unset($interval);
                        return;
                    }
                    Queue::setQueueNameControlParam(static::$currqueuename,'lasttime',$currtime);
                }
                //排序执行的情况
                if($type==1){
                    if(!Queue::lockByQueue(static::$currqueuename,$locktimeout)){
                        unset($settime);
                        unset($type);
                        unset($locktimeout);
                        unset($currtime);
                        unset($havesetconfig);
                        unset($interval);
                        return;
                    }

                }
                unset($settime);
                unset($locktimeout);
                unset($interval);
            }

            $msgStruct = Queue::getQueueNode(static::$currqueuename); //取队列，结构体

            if($msgStruct&&is_callable(static::$taskCallBack)){

                if(is_array($msgStruct)){

                    if(isset($msgStruct['settime'])&&$msgStruct['settime']>0){
                        //设置了定时时间
                        if($msgStruct['settime']-$currtime>0){
                            //加入到定时计划中，颗粒度是1分钟
                            $queue=new Queue();
                            $queue->setQueueName("inner_timing_".date("YmdHi",$msgStruct['settime'])); //要加入的队列名称
                            $queue->setMsgStruct($msgStruct); //消息结构体
                            Queue::addToList($queue); //添加到消息队列中
                            unset($queue);
                            unset($msgStruct);
                            unset($currtime);
                            //直接返回
                            return;
                        }

                    }
                }

                if (static::$logger) {
                    static::$logger->reName("mqlog_".date("Y-m-d"))->write(date("Y-m-d H:i:s")."进程".$worker_id."取任务执行".print_r($msgStruct,true).PHP_EOL, true);
                }

                static::$runTaskCountPointer++; //正在执行的任务

                (static::$taskCallBack)($msgStruct); //调用任务处理方法

                unset($msgStruct);

                static::$runTaskCountPointer--; //执行完

                if ($havesetconfig&&isset($type)&&$type==1){
                    Queue::unLockByQueue(static::$currqueuename); //释放锁
                }

            }else{

                //内存超过一定阶段则重启进程
                if(static::$maxMemory){
                    $mb=memory_get_usage();
                    if(static::$runTaskCountPointer==0&&$mb>static::$maxMemory){
                        if (static::$logger) {
                            static::$logger->reName("mqlog")->write(date("Y-m-d H:i:s")."进程" . $worker_id ."在内存".$mb."B退出" . PHP_EOL, true);
                        }
                        if ($havesetconfig&&isset($type)&&$type==1){
                            Queue::unLockByQueue(static::$currqueuename); //释放锁
                        }
                        \Workerman\Worker::stopAll();
                    }
                    unset($mb);
                }
                if ($havesetconfig&&isset($type)&&$type==1){
                    Queue::unLockByQueue(static::$currqueuename); //释放锁
                }
            }

            unset($havesetconfig);
            unset($worker_id);
            unset($currtime);


        }catch(\Exception $e){

            if (static::$logger) {
                static::$logger->reName("mqlog")->write($e->getMessage(), true,true);
            }

        }


    }


}