<?php

namespace Xl_MQ;

use Xl_MQ\Lib\Queue;
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

    public static function setCallback($callback){
        static::$taskCallBack=$callback;
    }
    public static function setLogger($logger){
        static::$logger=$logger;
    }

    public static function run(){

        //启动
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
            if (!(isset(static::$queuenamelist) &&static::$queuenamelist)) {
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

            $msgStruct = Queue::getQueueNode(static::$currqueuename); //取队列，结构体

            if($msgStruct&&is_callable(static::$taskCallBack)){

                if (static::$logger) {
                    static::$logger->write("进程".$worker_id."取任务执行".print_r($msgStruct,true).PHP_EOL, true);
                }

                (static::$taskCallBack)($msgStruct); //调用任务处理方法
            }

        }catch(\Exception $e){
            if (static::$logger) {
                static::$logger->write($e->getMessage(), true);
            }

        }


    }


}