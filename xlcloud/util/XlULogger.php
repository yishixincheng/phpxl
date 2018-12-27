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
    private $_filepath=null;

    public static function getInstance($logname='',$filemaxsize=null){

        if(empty($logname)){
            $logname=date("ymd"); //每天生成一个日志
        }

        if(defined("ISCLI")&&ISCLI) {
            if (count(static::$logger_instances) > 10) {
                array_shift(self::$logger_instances);
            }
        }
        if(isset(static::$logger_instances[$logname])){
            //移除
            static::$logger_instances[$logname]->autoCleanFile();
            return static::$logger_instances[$logname];

        }else{
            $obj=new XlULogger();
            $obj->setMaxFileSize($filemaxsize)->setName($logname);
            static::$logger_instances[$logname]=$obj;
            return $obj;
        }

    }

    public function setMaxFileSize($size=1){
        //默认是M
        if($size&&$size>0){
            $this->_maxsize=$size;
        }
        return $this;
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

        return $this;

    }

    public function reName($logname){

        if(empty($logname)){
            return $this;
        }
        if($logname==$this->getName()){
            return $this;
        }

        if(!$this->_opfileobj){

            $this->setName($logname);

        }else{

            $this->_logname=$logname;
            $this->_filepath=$this->_logpath.$this->_logname;
            if($this->_ext) {
                $this->_filepath.='.'.$this->_ext;
            }
            $this->_opfileobj->setParam($this->_filepath,false); //传入的是绝对路径
        }

        return $this;

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

        $this->_filepath=$this->_logpath.$this->_logname;
        if($this->_ext) {
            $this->_filepath.='.'.$this->_ext;
        }

        $obj->setParam($this->_filepath,false); //传入的是绝对路径

        $this->_opfileobj=$obj;

        $this->autoCleanFile();

    }

    public function autoCleanFile(){

        if(file_exists($this->_filepath)){
            $filesize=filesize($this->_filepath)/1000000; //M
            if($filesize>=$this->_maxsize){
                $this->delete();
            }
        }
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