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
        $worker->onWorkerStart=function($worker){
            \Workerman\Lib\Timer::add(MQConfig::getBeatSec(), function() use($worker){
                static::openTask($worker);
            });
        };

    }
    public static function openTask($worker){

        try {
            $worker_id = $worker->id; //进程编号
            //读取任务列表
            $currtime = time();
            if (static::$fetchquequelisttime) {
                if ($currtime - static::$fetchquequelisttime > 1000) {
                    //缓存1秒
                    $queuenamelist = Queue::getQueueNameList();
                    static::$fetchquequelisttime = $currtime;
                }
            } else {
                $queuenamelist = Queue::getQueueNameList();
                static::$fetchquequelisttime = $currtime;
            }
            if (!(isset($queuenamelist) && $queuenamelist)) {
                //没有任务
                return;
            }

            if (empty(static::$currqueuename)) {
                static::$currqueuename = static::$queuenamelist[0];
            } else {
                $len = count(static::$queuenamelist);
                for ($i = 0; $i < $len; $i++) {
                    if (static::$queuenamelist[$i] == static::$currqueuename) {
                        if ($i == $len - 1) {
                            static::$currqueuename = static::$queuenamelist[0];
                        } else {
                            static::$currqueuename = static::$queuenamelist[$i + 1];
                        }
                        break;
                    }
                }
            }

            $msgStruct = Queue::getQueueNode(static::$currqueuename); //取队列，结构体

            if (static::$logger) {
                static::$logger->write("进程".$worker_id."取任务执行", true);
            }

            if(is_callable(static::$taskCallBack)){

                (static::$taskCallBack)($msgStruct); //调用任务处理方法
            }

        }catch(\Exception $e){

        }


    }


}