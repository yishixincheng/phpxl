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
    protected $plans;         //计划列表
    protected $maxMemory=31457280; //超过30M自动重启
    private $execSetTimeCache=[];
    protected $tsdriver='auto'; // workerman|swoole|auto
    private $timerserver=null;

    public function run(){

        if(is_string($this->logger)){
            $this->logger=\xl\XlLead::logger($this->logger);
        }
        if($this->logger){
            $this->logger->write("启动时间".date("Y-m-d H:i:s").PHP_EOL,false);
        }

        if($this->tsdriver=="auto"||$this->tsdriver=="swoole"){
            if(extension_loaded("swoole")){
                $this->timerserver=new SwooleTimerServer([
                    'maxMemory'=>$this->maxMemory,
                    'logger'=>$this->logger
                ]);
            }else{
                $this->timerserver=new WorkermanTimerServer([
                    'maxMemory'=>$this->maxMemory,
                    'logger'=>$this->logger
                ]);
            }
        }else{

            $this->timerserver=new WorkermanTimerServer([
                'maxMemory'=>$this->maxMemory,
                'logger'=>$this->logger
            ]);

        }


        foreach ($this->plans as $k=>&$plan){
            //执行计划
            if(isset($v['interval'])){
                $plan['interval']*=10; //微秒数
            }
            if(!isset($plan['key'])){
                $plan['key']=$k;
            }

            $this->openPlanNode($plan);

        }

        $this->timerserver->start();



    }
    public function openPlanNode(&$plan){


        //时间驱动
        $this->timerserver->timerTick(function () use(&$plan){

             $this->execPlan($plan);

        });


    }

    public function execPlan(&$plan){


        try {
            $name = $plan['name'];
            $currtime = static::getCurrMsec(); //毫秒时间戳
            $logname = $plan['key'];
            if ($plan['interval']) {
                if (isset($plan['starttime'])) {
                    $starttime = static::secToMsec($plan['starttime']);
                    if ($currtime < $starttime) {
                        //未到开始时间
                        echo '定时计划执行时间未到' . PHP_EOL;
                        return;
                    }
                }
                if (isset($plan['endtime'])) {
                    $endtime = static::secToMsec($plan['endtime']);
                    if ($currtime > $endtime) {
                        //计划结束执行
                        echo '定时计划执行时间已结束' . PHP_EOL;
                        return;
                    }
                }
                if (!empty($plan['exectime'])) {
                    if ((static::getCurrMsec() - $plan['exectime']) > $plan['interval']) {
                        $log = $name . "|执行时间" . static::mSecTimeToDate(static::getCurrMsec()) . PHP_EOL;
                        $this->setLogContent($log, $logname);
                        $plan['exectime'] = static::getCurrMsec();
                        $this->callTask($plan);
                    }
                } else if (!empty($plan['startrun'])) {
                    $log = $name . "|执行时间" . static::mSecTimeToDate($currtime) . PHP_EOL;
                    $this->setLogContent($log, $logname);
                    $plan['exectime'] = $currtime;
                    $this->callTask($plan);
                } else if (empty($v['exectime'])) {
                    $plan['exectime'] = $currtime;
                }
            } else if ($plan['settime']) {

                $this->cleanTimeCache();
                $settime = static::secToMsec($plan['settime']);
                if (isset($this->execSetTimeCache[$settime])) {
                    //已经执行过了
                    return;
                }
                if ($currtime >= $settime) {
                    $log = $name . "|执行时间" . static::mSecTimeToDate($currtime) . PHP_EOL;
                    $this->setLogContent($log, $logname);
                    $this->callTask($plan);
                    $this->execSetTimeCache[$settime] = 1; //设置
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

    public function setLogContent($logcontent,$logname){

        if(!$this->logger){
            return null;
        }

        $logname=$this->logger->getName()."_".$logname;

        \xl\XlLead::logger($logname)->write($logcontent,true);

    }

    private static function secToMsec($time){

        if(is_numeric($time)){

            if(strlen($time)>10){
                return intval($time);
            }

            return intval($time*10000);

        }else{

            return intval(strtotime(self::getParseTime($time))*10000);

        }

    }

    private static function getCurrMsec(){

        //微秒
        return intval(microtime(true)*10000);

    }

    private static function mSecTimeToDate($time){

        //毫秒时间戳转日期格式
        if(strstr($time,'.')){
            list($usec, $sec) = explode(".",$time);
            $sec = str_pad($sec,4,"0",STR_PAD_RIGHT);
        }else{


            if(strlen($time)>10){
                $usec=substr($time,0,10);
                $sec=substr($time,11);
                $sec = str_pad($sec,4,"0",STR_PAD_RIGHT);

            }else{
                $usec=$time;
                $sec="0000";
            }

        }
        $date = date("Y-m-d H:i:s.x",$usec);
        return str_replace('x', $sec, $date);


    }

    private static function getParseTime($time){
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

/**
 * Interface TimerServer
 * @package xl\api
 * 提供定时器服务接口
 */
interface TimerServer{


    public function start(); //开启服务

    public function stop();  //开始

    public function timerTick($callback);


}

class WorkermanTimerServer implements TimerServer{

    private $maxMemory=null;
    private $logger=null;

    public function __construct($params)
    {
        $this->maxMemory=$params['maxMemory'];
        $this->logger=$params['logger'];

    }

    public function start()
    {
        // TODO: Implement start() method.

        global $argv;

        if (!isset($argv[1])) {
            return;
        }
        if($argv[1]=="start") {

            if (isset($argv[2]) && $argv[2] == "-d") {

                \Workerman\Worker::$daemonize=true; //守护进程方式

            }
        }

        \Workerman\Worker::runAll(); //运行

    }

    public function stop()
    {
        // TODO: Implement stop() method.

        \Workerman\Lib\Timer::delAll(); //删除定时器

    }

    public function timerTick($callback)
    {
        // TODO: Implement timerTick() method.

        $worker=new \Workerman\Worker();
        $worker->count=1;

        $worker->onWorkerStart=function($worker) use($callback){

            //心跳10毫秒

            \Workerman\Lib\Timer::add(0.01, function() use($callback){

                if(is_callable($callback)){
                    $callback();
                }

                $this->checkMemAndRestart();

            });

        };

        $worker->onWorkerStop=function (){
            \Workerman\Lib\Timer::delAll(); //删除定时器
        };

    }

    private function checkMemAndRestart(){

        $mb=memory_get_usage();

        if($mb>$this->maxMemory){

            $this->stop();

        }

    }

}


class PidManager
{
    /** @var string */
    protected $file;

    public function __construct(string $file = null)
    {
        $this->file = $file ?? ( CACHE_PATH. '/swoole.pid');
    }

    public function create(int $masterPid, int $managerPid)
    {
        if (!is_writable($this->file)
            && !is_writable(dirname($this->file))
        ) {
            throw new \RuntimeException(
                sprintf('Pid file "%s" is not writable', $this->file)
            );
        }

        file_put_contents($this->file, $masterPid . ',' . $managerPid);
    }

    public function getMasterPid()
    {
        return $this->getPids()['masterPid'];
    }

    public function getManagerPid()
    {
        return $this->getPids()['managerPid'];
    }

    public function getPids(): array
    {
        $pids = [];

        if (is_readable($this->file)) {
            $content = file_get_contents($this->file);
            $pids    = explode(',', $content);
        }

        return [
            'masterPid'  => $pids[0] ?? null,
            'managerPid' => $pids[1] ?? null,
        ];
    }

    /**
     * 是否运行中
     * @return bool
     */
    public function isRunning()
    {
        $pids = $this->getPids();

        if (!count($pids)) {
            return false;
        }

        $masterPid  = $pids['masterPid'] ?? null;
        $managerPid = $pids['managerPid'] ?? null;

        if ($managerPid) {
            // Swoole process mode
            return $masterPid && $managerPid && \Swoole\Process::kill((int) $managerPid, 0);
        }

        // Swoole base mode, no manager process
        return $masterPid && \Swoole\Process::kill((int) $masterPid, 0);
    }

    /**
     * Kill process.
     *
     * @param int $sig
     * @param int $wait
     *
     * @return bool
     */

    public function killProcess($sig, $wait = 0)
    {

        $pids = $this->getPids();

        if (!count($pids)) {
            return false;
        }
        \Swoole\Process::kill(
            $pids['masterPid'],
            $sig
        );

        \Swoole\Process::kill(
            $pids['managerPid'],
            $sig
        );


        if ($wait) {
            $start = time();

            do {
                if (!$this->isRunning()) {
                    break;
                }

                usleep(100000);
            } while (time() < $start + $wait);
        }

        return $this->isRunning();
    }

    public function remove(): bool
    {
        if (is_writable($this->file)) {
            return unlink($this->file);
        }

        return false;
    }
}



class SwooleTimerServer implements TimerServer{


    private $maxMemory=null;
    private $logger=null;
    private $pidM=null;
    private $serv=null;
    private $ticks=[];
    private $timeids=[];

    protected $host='127.0.0.1';
    protected $options=[
        'worker_num'=>1,
        'daemonize'=>false
    ];

    public function __construct($params)
    {
        $this->maxMemory=$params['maxMemory'];
        $this->logger=$params['logger'];
        $this->pidM=new PidManager();

    }

    public function start()
    {
        // TODO: Implement start() method.
        //解析命令
        global $argv;
        $available_commands = [
            'start',
            'stop',
            'restart',
            'reload'];

        if (!isset($argv[1]) || !in_array($argv[1], $available_commands)) {
            if (isset($argv[1])) {
                echo 'Unknown command: ' . $argv[1] . PHP_EOL;
            }else{

                echo '命令无效：请使用 php file start|stop|reload|restart命令'.PHP_EOL;
            }

            return;
        }

        if($argv[1]=="start"){

            if(isset($argv[2])&&$argv[2]=="-d"){

                $this->options['daemonize']=true; //守护进程模式
            }

            $this->_start(); //启动服务

        }else if($argv[1]=="stop"){

            //结束服务
            $this->_stop();

        }else if($argv[1]=="restart"){

            $this->_restart();

        }else if($argv[1]=="reload"){

            $this->_reload();

        }

    }

    public function stop()
    {
        $this->_stop();

    }

    public function timerTick($callback)
    {
        // TODO: Implement timerTick() method.

        $this->ticks[]=$callback;

    }

    private function _start(){

        if($this->pidM->isRunning()){
            echo '服务已经运行'.PHP_EOL;
            return;
        }
        echo "定时任务服务启动成功".PHP_EOL;

        $this->serv = new \Swoole\Server($this->host);
        $this->serv->set($this->options);
        $this->serv->on('Start', function($serv){

            cli_set_process_title("xlswooletimerplan");
            //记录进程id,脚本实现自动重启
            $this->pidM->create($serv->master_pid,$serv->manager_pid);

        });

        $this->serv->on('WorkerStart', function ($serv,$worker_id){

            echo $worker_id ." onWorkerStart \n";

            if( $worker_id == 0 ) {


                foreach ($this->ticks as $callback){

                    \Swoole\Timer::tick(10,function ($timer_id) use($callback){

                        $this->timeids[]=$timer_id;

                        $callback();

                    });

                }

            }

        });
        $this->serv->on("Receive", function ($serv, $fd, $from_id, $data){

        });
        $this->serv->on('Close', function ($serv, $fd, $from_id){

            echo "Client {$fd} close connection\n";

        });
        $this->serv->on("WorkerStop",function ($serv,$worker_id){

            if($worker_id==0){

                foreach ($this->timeids as $timeid){

                    \Swoole\Timer::clear($timeid);

                }

                $this->timeids=[];

            }

            echo "定时任务停止";

        });

        $this->serv->start(); //启动服务

    }

    private function _stop(){

        // TODO: Implement stop() method.

        if (!$this->pidM->isRunning()) {
            echo '服务没有启动'.PHP_EOL;
            return;
        }

        $isRunning = $this->pidM->killProcess(SIGTERM, 15);
        if ($isRunning) {
            echo 'Unable to stop the swoole_http_server process.'.PHP_EOL;
            return;
        }

        echo "停止成功".PHP_EOL;

    }

    private function _restart(){

        if ($this->pidM->isRunning()) {
            $this->_stop();
        }
        $this->_start();

    }

    private function _reload(){


        if (!$this->pidM->isRunning()) {
            echo 'no swoole http server process running.';
            return;
        }

        if (!$this->pidM->killProcess(SIGUSR1)) {

            echo "重新加载失败";
            return;
        }

        echo "重新加载成功";

    }

}




