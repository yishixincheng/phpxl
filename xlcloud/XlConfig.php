<?php

namespace xl;

/*
config(array(key=>'',value=>'','file'=>'system','op'=>'','iswrite'=>false),$op="",$iswrite=false);

$key=>'file::key/key2/key3/key4/key5/key6/key7/key8/key9/key10';

$op='set,insert,get,delete', //支持set,insert,get,delete

config("system/opp",1);
*/


class XlConfig{

    private $file;
    private $key;
    private $value;
    private $op;
    private $iswrite;
    public static $configs=[];
    private $cachetime=1; //缓存时间
    private $isopenmemcache=30;//是否开启内存缓存
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
        if(!XlConfig::$configs[$this->file]){
            if($this->isopenmemcache){

                $cachekey="config_file_".$this->file;

                $imem=$this->_getMemCacheObj();
                $fc=null;
                if($imem){
                    $fc=$imem->get($cachekey);
                }
                if($fc){
                    XlConfig::$configs[$this->file] = $fc;
                }else{
                    $path=CONFIG_PATH.$this->file.'.php';
                    if(file_exists($path)){
                        XlConfig::$configs[$this->file] = include $path;
                        if($imem){
                            $imem->set($cachekey,XlConfig::$configs[$this->file],$this->cachetime);
                        }
                    }
                }
            }else{
                $path=CONFIG_PATH.$this->file.'.php';
                if(file_exists($path)){
                    XlConfig::$configs[$this->file] = include $path;
                }
            }
        }
        if($this->op=="get"){
            return $this->_getvalue();
        }else if($this->op=="set"){
            return $this->_setvalue();
        }else if($this->op=="insert"){
            return $this->_insertvalue();
        }else if($this->op=="delete"){
            return $this->_deletevalue();
        }
    }
    private function _getvalue(){


        if(empty($this->key)){
            return XlConfig::$configs[$this->file];  //返回整个数组
        }
        $data=XlConfig::$configs[$this->file];

        $keyarr=explode('/',$this->key);
        $keylen=count($keyarr);
        $d=array();
        switch($keylen){
            case 1:
                $d=$data[$keyarr[0]];
                break;
            case 2:
                $d=$data[$keyarr[0]][$keyarr[1]];
                break;
            case 3:
                $d=$data[$keyarr[0]][$keyarr[1]][$keyarr[2]];
                break;
            case 4:
                $d=$data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]];
                break;
            case 5:
                $d=$data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]];
                break;
            case 6:
                $d=$data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]];
                break;
            case 7:
                $d=$data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]];
                break;
            case 8:
                $d=$data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]][$keyarr[7]];
                break;
            case 9:
                $d=$data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]][$keyarr[7]][$keyarr[8]];
                break;
            case 10:
                $d=$data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]][$keyarr[7]][$keyarr[8]][$keyarr[9]];
                break;
        }
        return $d;

    }
    public function _setvalue(){

        //设置数据
        $data=XlConfig::$configs[$this->file];
        $keyarr=$this->key?explode('/',$this->key):array();
        $keylen=count($keyarr);
        $data=$data?$data:array();
        $value=$this->value;
        //最多支持10级
        switch($keylen){
            case 0:
                $data=is_array($this->value)?$this->value:array();
                break;
            case 1:
                $data[$keyarr[0]]=$value;
                break;
            case 2:
                $data[$keyarr[0]][$keyarr[1]]=$value;
                break;
            case 3:
                $data[$keyarr[0]][$keyarr[1]][$keyarr[2]]=$value;
                break;
            case 4:
                $data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]]=$value;
                break;
            case 5:
                $data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]]=$value;
                break;
            case 6:
                $data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]]=$value;
                break;
            case 7:
                $data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]]=$value;
                break;
            case 8:
                $data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]][$keyarr[7]]=$value;
                break;
            case 9:
                $data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]][$keyarr[7]][$keyarr[8]]=$value;
                break;
            case 10:
                $data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]][$keyarr[7]][$keyarr[8]][$keyarr[10]]=$value;
                break;
        }
        XlConfig::$configs[$this->file]=$data; //重新赋值
        $this->_writeToFile($data);

    }
    public function _insertvalue(){

        //插入数组项目
        $data=XlConfig::$configs[$this->file];

        $keyarr=explode('/',$this->key);
        $keylen=count($keyarr);
        $data=$data?$data:array();
        $value=$this->value;
        $d=array();
        switch($keylen){
            case 1:
                $d=$data[$keyarr[0]];
                break;
            case 2:
                $d=$data[$keyarr[0]][$keyarr[1]];
                break;
            case 3:
                $d=$data[$keyarr[0]][$keyarr[1]][$keyarr[2]];
                break;
            case 4:
                $d=$data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]];
                break;
            case 5:
                $d=$data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]];
                break;
            case 6:
                $d=$data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]];
                break;
            case 7:
                $d=$data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]];
                break;
            case 8:
                $d=$data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]][$keyarr[7]];
                break;
            case 9:
                $d=$data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]][$keyarr[7]][$keyarr[8]];
                break;

        }
        $d=$d?$d:array();
        $max=0;
        foreach($d as $k=>$v){
            $max=max((int)$k,$max);
        }
        $max++;
        $index=strval($max);
        switch($keylen){
            case 1:
                $data[$keyarr[0]][$index]=$value;
                break;
            case 2:
                $data[$keyarr[0]][$keyarr[1]][$index]=$value;
                break;
            case 3:
                $data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$index]=$value;
                break;
            case 4:
                $data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$index]=$value;
                break;
            case 5:
                $data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$index]=$value;
                break;
            case 6:
                $data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$index]=$value;
                break;
            case 7:
                $data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]][$index]=$value;
                break;
            case 8:
                $data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]][$keyarr[7]][$index]=$value;
                break;
            case 9:
                $data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]][$keyarr[7]][$keyarr[8]][$index]=$value;
                break;
        }

        XlConfig::$configs[$this->file]=$data; //重新赋值
        $this->_writeToFile($data);


    }
    public function _deletevalue(){

        $data=XlConfig::$configs[$this->file];
        $keyarr=explode('/',$this->key);
        $keylen=count($keyarr);
        $data=$data?$data:array();

        switch($keylen){
            case 1:
                unset($data[$keyarr[0]]);
                break;
            case 2:
                unset($data[$keyarr[0]][$keyarr[1]]);
                break;
            case 3:
                unset($data[$keyarr[0]][$keyarr[1]][$keyarr[2]]);
                break;
            case 4:
                unset($data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]]);
                break;
            case 5:
                unset($data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]]);
                break;
            case 6:
                unset($data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]]);
                break;
            case 7:
                unset($data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]]);
                break;
            case 8:
                unset($data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]][$keyarr[7]]);
                break;
            case 9:
                unset($data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]][$keyarr[7]][$keyarr[8]]);
                break;
            case 10:
                unset($data[$keyarr[0]][$keyarr[1]][$keyarr[2]][$keyarr[3]][$keyarr[4]][$keyarr[5]][$keyarr[6]][$keyarr[7]][$keyarr[8]][$keyarr[9]]);
                break;
        }
        XlConfig::$configs[$this->file]=$data; //重新赋值

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

    }

}