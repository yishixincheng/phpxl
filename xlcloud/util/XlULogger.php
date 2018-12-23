<?php

namespace xl\util;

/**
 * 日志
 */

class XlULogger{

    public static $logger_instances=[]; //实例

    private $_logname='';
    private $_ext='txt'; //后缀名
    private $_logpath=LOG_PATH;  //绝对路径
    private $_opfileobj=null;
    private $_maxsize=1;   //文件大小超过此值则自动删除单位是M

    public static function getInstance($logname=''){

         if(empty($logname)){
             $logname=date("ymd"); //每天生成一个日志
         }
         if(static::$logger_instances[$logname]){
             return static::$logger_instances[$logname];
         }else{

             $obj=new XlULogger();

             $obj->setName($logname);

             static::$logger_instances[$logname]=$obj;

             return $obj;
         }

    }
    public function setExt($ext){
        $this->_ext=$ext;
        return $this;
    }

    /**
     * @param $logname
     * 设置日志名称
     */
    public function setName($logname){

        $this->_logname=$logname;
        $this->_opfileobj=null; //重新获取操作文件对象

        $this->getOpfileObj();

    }

    public function getName(){

        return $this->_logname;
    }

    private function getOpfileObj(){

        if($this->_opfileobj){
            return;
        }
        $cls=sysclass("opfile",0);

        $obj=new $cls();

        $path=$this->_logpath.$this->_logname;
        if($this->_ext) {
            $path.='.'.$this->_ext;
        }
        if(file_exists($path)){
            $filesize=filesize($path)/1000000; //M
            if($filesize>=$this->_maxsize){
                $obj->delFile($path); //自动删除文件
            }
        }
        $obj->setParam($path,false); //传入的是绝对路径

        $this->_opfileobj=$obj;

    }
    public function write($buff,$add=false,$ismutex=false){


        $this->_opfileobj->write($buff,$add,$ismutex);

    }
    public function read($one=false,$sharelock=false){

        return $this->_opfileobj->read($one,$sharelock);

    }
    public function delete(){

        $this->_opfileobj->delFile(); //删除日志文件

    }

}