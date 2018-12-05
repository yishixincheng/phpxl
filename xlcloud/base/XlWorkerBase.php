<?php

namespace xl\base;

use Workerman\Worker;

import("@xl.vendor.autoload");

/**
 * 使用Workerman框架
 */

class XlWorkerBase extends XlMvcBase{


    protected $worker;
    protected $commands=null;
    protected $name=null;
    protected $logFile=null; //日志目录
    protected $count = 4;     //开启子进程数
    protected $socket_name='http://0.0.0.0:2346';
    protected $context_option=[];
    protected $daemonize=true; //守护进程模式

    public static $onEvents=[
        'onWorkerStart',
        'onConnect',
        'onMessage',
        'onClose',
        'onError',
        'onBufferFull',
        'onBufferDrain',
        'onWorkerStop',
        'onWorkerReload'
    ];

    public $commandHandlers=[];

    public function __construct()
    {

        global $argv; //命令行输入
        $this->commands = $argv;

        if(!$this->name){
            $this->name=current(explode('.', $this->commands[0])); //执行脚本名
        }

        if(!$this->logFile){

            if(!file_exists(LOG_PATH.'worker')){
                @mkdir(LOG_PATH.'worker',0777,true);
            }
            $this->logFile=LOG_PATH.'worker'.D_S.$this->name.'.log'; //日志目录


        }

    }

    protected function createWorker($socket_name = null, $context_option = []){

        //创建workerman
        if($socket_name===null){
            $socket_name=$this->socket_name;
        }
        $this->worker = new \Workerman\Worker($socket_name,$context_option?:$this->context_option);
        $this->worker->count = $this->count;
        $this->worker->name = $this->name;
        \Workerman\Worker::$daemonize=$this->daemonize;
        \Workerman\Worker::$logFile=$this->logFile;
		
        $this->execCommand();
        $this->setWorkerParam($this->worker);

        foreach (static::$onEvents as $event) {
            if (method_exists($this, $event)) {
                $this->worker->$event = [$this, $event];
            }
        }
        return $this;
    }

    protected function start(){

        //运行Workerman
        \Workerman\Worker::runAll();

    }

    protected function setWorkerParam(&$worker)
    {


    }

    protected function execCommand(){

        if(!$this->commands){
            return null;
        }

        if(isset($this->commandHandlers[$this->commands[1]])){

            $handler=$this->commandHandlers[$this->commands[1]];

            if(is_callable($handler)){
                call_user_func_array($handler,[$this->worker,$this->commands[1]]); //执行解析命令
            }
        }

    }

    protected function setCommandHandler($command,$handler){

        $this->commandHandlers[$command]=$handler;

    }

    /**
     * @param $interval
     * @param $callback
     * @param array $args
     * @param bool $persistent
     * @return int
     * 启动定时器
     */

    public function timer($interval, $callback, $args = [], $persistent = true)
    {
        return \Workerman\Lib\Timer::add($interval, $callback, $args, $persistent);
    }

}