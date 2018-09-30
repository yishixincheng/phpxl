<?php

namespace xl\api;

import("@xl.vendor.autoload");

/**
 * Class Timingplan
 * @package xl\api
 * 定时器接口，需要cli模式调用
 */

class Timingplan extends XlApiBase{

    protected $logger;        //日志操作对象
    protected $processnum=1; //进程数量
    protected $plans;         //计划列表

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
                        $callback = $plan['callback'];
                        affair($callback[0])->{$callback[1]}();
                    }
                } else if ($plan['startrun']) {
                    $this->setLogContent($log, $todaytime);
                    $this->plans[$k]['exectime'] = $currtime;
                    $callback = $plan['callback'];
                    affair($callback[0])->{$callback[1]}();
                } else if (empty($v['exectime'])) {
                    $this->plans[$k]['exectime'] = $currtime;
                }

            } else if ($plan['settime']) {
                $settime = $plan['settime'];
                $settime = self::getParseTime($settime);
                if ($currtime == strtotime($settime)) {
                    $log = $k . "|" . date("Y-m-d H:i:s", $plan['settime']) . "|" . date("Y-m-d H:i:s", $currtime) . "|执行时间" . date("Y-m-d H:i:s") . PHP_EOL;
                    $this->setLogContent($log, $todaytime);
                    $callback = $plan['callback'];
                    affair($callback[0])->{$callback[1]}();

                }
            }
        }catch (\Exception $e){
            $this->setLogContent($e->getMessage(),"error");
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
        }else if(preg_match("\d{2}\s+\d{2}:\d{2}:\d{2}",$time)){
            $time=date("Y-m-").$time;
        }else if(preg_match("\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}",$time)){
            $time=date("Y-").$time;
        }
        return $time;

    }



}