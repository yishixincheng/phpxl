<?php

namespace xl\api;
use xl\XlLead;

import("@xl.vendor.autoload");

/**
 * Class Timingplan
 * @package xl\api
 * 定时器接口，需要cli模式调用
 */

final class Timingplan extends XlApiBase{

    protected $logger;        //日志操作对象
    protected $processnum=1; //进程数量
    protected $plans;         //计划列表
    protected $maxMemory=31457280; //超过30M自动重启

    private $execSetTimeCache=[];

    public function run(){

        if(is_string($this->logger)){
            $this->logger=\xl\XlLead::logger($this->logger);
        }
        if($this->logger){
            $this->logger->write("启动时间".date("Y-m-d H:i:s").PHP_EOL,false);
        }
        foreach ($this->plans as $k=>$v){
            //执行计划
            $this->openPlanNode($k);
        }
        \Workerman\Worker::$daemonize=true; //守护进程方式
        \Workerman\Worker::runAll(); //运行

    }
    public function openPlanNode($k){

        $worker=new \Workerman\Worker();
        $worker->count=$this->processnum;

        $worker->onWorkerStart=function($worker) use($k){

            \Workerman\Lib\Timer::add(1, function() use($k){

                $this->execPlan($k);
                $this->checkMemAndRestart();
            });

        };
        $worker->onWorkerStop=function ($worker){

            if($this->logger){
                $this->logger->write("停止时间".date("Y-m-d H:i:s").PHP_EOL,false);
            }
            \Workerman\Lib\Timer::delAll(); //删除定时器

        };

    }

    public function execPlan($k){

        try {
            $plan=$this->plans[$k];
            $currtime = time();
            $todaytime = date("Y-m-d");
            if ($plan['interval']) {
                if (isset($plan['starttime'])) {
                    $starttime = strtotime(self::getParseTime($plan['starttime']));
                    if ($currtime < $starttime) {
                        //未到开始时间
                        return;
                    }
                }
                if (isset($plan['endtime'])) {
                    $endtime = strtotime(self::getParseTime($plan['endtime']));
                    if ($currtime > $endtime) {
                        //计划结束执行
                        return;
                    }
                }
                $log = $k . "|" . date("Y-m-d H:i:s", $plan['exectime']) . "|" . date("Y-m-d H:i:s", $currtime) . "|执行时间" . date("Y-m-d H:i:s") . PHP_EOL;
                if ($plan['exectime']) {
                    if (($currtime - $plan['exectime']) >= $plan['interval']) {
                        $this->setLogContent($log, $todaytime);
                        $this->plans[$k]['exectime'] = $currtime;
                        $this->callTask($plan);
                    }
                } else if ($plan['startrun']) {
                    $this->setLogContent($log, $todaytime);
                    $this->plans[$k]['exectime'] = $currtime;
                    $this->callTask($plan);
                } else if (empty($v['exectime'])) {
                    $this->plans[$k]['exectime'] = $currtime;
                }

            } else if ($plan['settime']) {
                $settime = $plan['settime'];
                $settime = self::getParseTime($settime);

                $this->cleanTimeCache();
                $settime=strtotime($settime);
                if(isset($this->execSetTimeCache[$settime])){
                    //已经执行过了
                    return;
                }
                if ($currtime >= $settime) {
                    $log = $k . "|" . date("Y-m-d H:i:s", $plan['settime']) . "|" . date("Y-m-d H:i:s", $currtime) . "|执行时间" . date("Y-m-d H:i:s") . PHP_EOL;
                    $this->setLogContent($log, $todaytime);
                    $this->callTask($plan);
                    $this->execSetTimeCache[$settime]=1; //设置
                }

            }
        }catch (\Exception $e){
            $this->setLogContent($e->getMessage(),"error");
        }

    }
    /**
     * 清理时间缓存
     */
    private function cleanTimeCache(){
        $yesdaytime=time()-86400;
        foreach ($this->execSetTimeCache as $settime=>$v){
            if($settime<$yesdaytime){
                unset($this->execSetTimeCache[$settime]);
            }
        }
    }
    /**
     * @param $plan
     * 调用任务节点
     */
    private function callTask($plan){

        $task=$plan['task'];
        $parameter=$plan['parameter']??null;

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
            TS("定时器任务",$parameter,$isplugin,$ns)->task($methodname)->done(); //调用task任务

            return;

        }else if(isset($plan['callback'])&&$plan['callback']){

            affair($plan['callback'][0])->{$plan['callback'][1]}($parameter); //兼容以前的

            return;

        }else if(isset($plan['handler'])&&$plan['handler']){

            $class=$plan['handler'][0];
            $method=$plan['handler'][1];
            $isplugin=$plan['handler'][2]??false;
            $ns=$plan['handler'][3]??null;


        }else{

            $class=$plan['class'];
            $method=$plan['method'];
            $isplugin=$plan['isplugin']??false;
            $ns=$plan['ns']??null;

        }

        if($class&&$method){
            $ins=XlLead::$factroy->bind("properties",['_Isplugin'=>$isplugin,'_Ns'=>$ns])->getInstance($class);
            call_user_func([$ins,$method],$parameter);
        }

    }


    private function checkMemAndRestart(){

        $mb=memory_get_usage();

        if($mb>$this->maxMemory){

            \Workerman\Worker::stopAll(); //重启

        }

    }

    public function setLogContent($logcontent,$logname){

        if(!$this->logger){
            return null;
        }

        $logname=$this->logger->getName()."_".$logname;

        \xl\XlLead::logger($logname)->write($logcontent,true);

    }

    public static function getParseTime($time){

        if(preg_match("/\d{2}:\d{2}:\d{2}/",$time)){
            $time=date("Y-m-d")." ".$time;
        }else if(preg_match("/\d{2}\s+\d{2}:\d{2}:\d{2}/",$time)){
            $time=date("Y-m-").$time;
        }else if(preg_match("/\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}/",$time)){
            $time=date("Y-").$time;
        }
        return $time;

    }



}