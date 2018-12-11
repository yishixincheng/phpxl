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

                //溢出回收机制，30分钟一次
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

            $havesetconfig=false;
            if(static::$qNPSet&&is_array(static::$qNPSet)&&static::$qNPSet[static::$currqueuename]){

                $havesetconfig=true;
                //定义了队列参数
                $type=static::$qNPSet[static::$currqueuename]['type']?:0;           //0默认类型，1顺序执行
                $interval=static::$qNPSet[static::$currqueuename]['interval']?:0;   //间隔执行
                $settime=static::$qNPSet[static::$currqueuename]['settime']?:null; //指定时间执行
                $locktimeout=static::$qNPSet[static::$currqueuename]['locktimeout']?:null; //超过多少时间则自动解锁

                $controlparam=Queue::getQueueNameControlParam(static::$currqueuename); //队列参数

                if($settime&&$settime-$currtime>0){
                    //未到指定的时间不执行
                    return;
                }
                $lasttime=$controlparam['lasttime'];
                //默认获取
                if($interval){
                    //有间隔的执行
                    if($currtime-$lasttime<=$interval){
                        //未到时间间隔不执行
                        return;
                    }
                }
                //排序执行的情况
                if($type==1){
                    if($controlparam['lock']){
                        //已经上锁则等待
                        if($locktimeout&&$currtime-$lasttime>=$locktimeout){
                            Queue::setQueueNameControlParam(static::$currqueuename,'lock',null); //解锁
                        }
                        return;
                    }
                    Queue::setQueueNameControlParam(static::$currqueuename,'lock',1); //上锁
                }
            }


            if($havesetconfig){
                Queue::setQueueNameControlParam(static::$currqueuename,'lasttime',$currtime); //设置执行时间
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
                            //直接返回
                            return;
                        }

                    }
                }

                if (static::$logger) {
                    static::$logger->write("进程".$worker_id."取任务执行".print_r($msgStruct,true).PHP_EOL, true);
                }

                (static::$taskCallBack)($msgStruct); //调用任务处理方法

            }

            if ($havesetconfig&&isset($type)&&$type==1){
                Queue::setQueueNameControlParam(static::$currqueuename,'lock',null); //释放锁
            }

        }catch(\Exception $e){

            if (static::$logger) {
                static::$logger->write($e->getMessage(), true);
            }

        }


    }


}