<?php

namespace xl;

/*
config(array(key=>'',value=>'','file'=>'system','op'=>'','iswrite'=>false),$op="",$iswrite=false);

$key=>'file::key/key2/key3/key4/key5/key6/key7/key8/key9/key10';

$op='set,insert,get,delete', //支持set,insert,get,delete

config("system/opp",1);
*/


final class XlConfig{

    private $file;
    private $key;
    private $value;
    private $op;
    private $iswrite;
    public static $configs=[];
    private $cachetime=10; //缓存时间
    private $isopenmemcache=1;//是否开启内存缓存
    protected static $_imem=null;

    function __construct($key,$value='null',$iswrite=false){

        if($value===null){
            $this->op="get";
        }else{
            $this->op="set";
            $this->value=$value;
        }
        $this->iswrite=$iswrite;


        $this->_parseopstr($key);

    }
    private function _parseopstr($key){

        if(is_string($key)){
            $this->_parsekey($key);   //解析key
        }
        if(is_array($key)){
            $this->file=$key['file'];

            if(in_array($key['op'],array('get','set','insert','delete'))){
                $this->op=$key['op'];
            }
            $this->value=$key['value'];
            $this->iswrite=$key['iswrite'];
            if(!$this->file){
                $this->_parsekey($key['key']); //文件参数不存在，靠解析key来获得文件名称
            }else{
                $this->key=$key['key'];
            }

        }
        if($this->file=="cache"){
            $this->isopenmemcache=false;
        }

    }
    private function _parsekey($key){

        if(!is_string($key)){
            return;
        }
        if(preg_match("/^([^\/]+)::(.+)$/",$key,$mt)){
            $this->file=$mt[1];
            $this->key=$mt[2];
        }else if(preg_match("/^([^\/]+)\/(.+)$/",$key,$mt)){
            $this->file=$mt[1];
            $this->key=$mt[2];
        }else{
            $this->file=$key;
            $this->key='';
        }

    }
    private function _getMemCacheObj(){

        if(static::$_imem){
            return static::$_imem;
        }
        $cls = sysclass("cachefactory", 0);
        $hittype='';
        $imem = $cls::priority(['apc','xcache','eaccelerator'],$hittype);  //获得cache
        if($hittype=="file"){
            static::$_imem=null;
        }else{
            static::$_imem=$imem;
        }
        return static::$_imem;

    }
    public function exec(){
        //根据参数执行
        if(empty(static::$configs[$this->file])){
            if($this->isopenmemcache){

                $cachekey="config_file_".$this->file;

                $imem=$this->_getMemCacheObj();
                $fc=null;
                if($imem){
                    $fc=$imem->get($cachekey);
                }
                if($fc){
                    static::$configs[$this->file] = $fc;
                }else{
                    $path=CONFIG_PATH.$this->file.'.php';
                    if(file_exists($path)){
                        static::$configs[$this->file] = include $path;
                        if($imem){
                            $imem->set($cachekey,static::$configs[$this->file],$this->cachetime);
                        }
                    }
                }
            }else{
                $path=CONFIG_PATH.$this->file.'.php';
                if(file_exists($path)){
                    static::$configs[$this->file] = include $path;
                }
            }
        }
        if($this->op=="get"){
            return $this->_getvalue();
        }else if($this->op=="set"){
            $this->_setvalue();
        }else if($this->op=="insert"){
            $this->_insertvalue();
        }else if($this->op=="delete"){
            $this->_deletevalue();
        }
        return null;
    }
    private function _getvalue(){


        if(empty($this->key)){
            return static::$configs[$this->file];  //返回整个数组
        }
        $data=static::$configs[$this->file];

        $keyarr=explode('/',$this->key);

        return Xl_Recursion_Get_Array_Value($data,$keyarr);

    }
    public function _setvalue(){

        //设置数据
        $data=static::$configs[$this->file];
        $keyarr=$this->key?explode('/',$this->key):array();
        $data=$data?$data:array();
        $value=$this->value;

        Xl_Recursion_Set_Array_Value($data,$keyarr,$value);

        static::$configs[$this->file]=$data; //重新赋值
        $this->_writeToFile($data);

    }
    public function _insertvalue(){

        //插入数组项目
        $data=static::$configs[$this->file];
        $keyarr=explode('/',$this->key);
        $data=$data?$data:array();
        $value=$this->value;
        $d=Xl_Recursion_Get_Array_Value($data,$keyarr)?:[];
        $max=0;
        foreach($d as $k=>$v){
            $max=max((int)$k,$max);
        }
        $max++;
        $index=strval($max);
        array_push($keyarr,$index);
        Xl_Recursion_Set_Array_Value($data,$keyarr,$value);
        static::$configs[$this->file]=$data; //重新赋值
        $this->_writeToFile($data);


    }
    public function _deletevalue(){

        $data=static::$configs[$this->file];
        $keyarr=explode('/',$this->key);
        $data=$data?$data:array();
        Xl_Recursion_Del_Array_Value($data,$keyarr);
        static::$configs[$this->file]=$data; //重新赋值

        $this->_writeToFile($data);

    }
    private function _writeToFile($data){

        if($this->iswrite){
            if($this->isopenmemcache){
                $imem=$this->_getMemCacheObj();
                if($imem){
                    $imem->set("config_file_".$this->file,$data,$this->cachetime); //写到内存
                }
            }
            //写入文件
            $inputstr='<?php return '.var_export($data,true).'; ?>';

            $path=CONFIG_PATH.$this->file.'.php';

            if(file_exists($path)){
                @chmod($path,0777);
                return file_put_contents($path, $inputstr);
            }
        }
        return null;

    }

}