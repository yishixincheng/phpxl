<?php

namespace xl\classs;
use xl\base\XlClassBase;

class OpfileClass extends XlClassBase {

    private $filepath;
    private $filedir; //文件目录
    private $propcache=[];

    function __construct(){

        parent::__construct();
    }
    public function getFilePath(){

        return $this->filepath;

    }
    public function setParam($filepath,$islocal=true){
        $this->filepath=$filepath;
        if($islocal==true){$this->filepath=$_SERVER['DOCUMENT_ROOT'].$this->filepath;}
        $farr=explode(D_S,$this->filepath);
        array_pop($farr);
        $this->filedir=implode(D_S,$farr);
    }
    public function C($filepath)
    {
        //改变文件路径
        $this->filepath=$filepath;
        $farr=explode(D_S,$this->filepath);
        array_pop($farr);
        $this->filedir=implode(D_S,$farr);
    }
    public function Read($one=false,$sharelock=false)
    {
        //读取文件
        $filepath=$this->filepath;
        if(!$one)
        {
            if(file_exists($filepath))
            {
                $file=fopen($filepath,'r');
                if($sharelock){
                    flock($file,LOCK_SH);
                }
                $buff='';
                while (!feof($file)) {
                    $buff.=fgets($file);
                }
                if($sharelock){
                    flock($file,LOCK_UN);
                }
                @fclose($file);
                return $buff;
            }
        }
        else
        {

            if(file_exists($filepath)){
                return file_get_contents($filepath);
            }

        }
        return false;
    }
    public function Write($buff,$add=false,$ismutex=false)
    {
        //写文件
        $this->mkdirm($this->filedir);
        $filepath=$this->filepath;
        if(!$add)
        {
            $file = fopen($filepath,"w");
        }
        else
        {
            $file= fopen($filepath,"a");
        }
        if($ismutex){
            flock($file,LOCK_EX);
            $b=@fwrite($file,$buff);
            flock($file,LOCK_UN);
        }else{
            $b=@fwrite($file,$buff);
        }
        @fclose($file);
        return $b;
    }
    public function mkdirm($path)
    {
        if(!file_exists($path))
        {
            $this->mkdirm(dirname($path));;
            @mkdir($path,0777);
        }
    }
    public function delFile($path=''){

        if(!$path){
            $path=$this->getFilePath();
        }

        if(file_exists($path)){
            if(@chmod($path,0777)){
                @unlink($path);
            }else{
                @unlink($path);
            }
        }

    }
    public function readProp($key=null,$iscache=true,$sharelock=false){

        if($key===null){
            $prop_key=md5($this->filepath);
            if($iscache){
                if($this->propcache[$prop_key]){
                    return $this->propcache[$prop_key];
                }
            }
            $propsstr=$this->Read(false,$sharelock);
            $props=[];
            if($propsstr){
                $propsarr=explode("\n",$propsstr);

                $props=[];

                foreach ($propsarr as $line){

                    if(substr($line,0,1)=="#"){
                        continue;
                    }
                    if(preg_match("/([A-Za-z_]+)\s*=(.+)/",$line,$match)){
                        $props[$match[1]]=trim($match[2]);
                    }
                }

            }
            $this->propcache[$prop_key]=$props;

            return $props;
        }else{

            $props=$this->readProp(null,$iscache);
            if(isset($props[$key])){
                return $props[$key];
            }
            return null;
        }

    }
    public function writeProp($props,$value=null,$ismutex=false){

        $propstr='';
        if($props&&is_array($props)){



            foreach ($props as $k=>$v){
                if(is_array($v)||is_object($v)||is_callable($v)){
                    continue;
                }
                $propstr.=$k."=".$v."\n";
            }

        }else if(preg_match("/[A-Za-z_]+/",$props)){

            $propsarr=$this->readProp();
            $propsarr[$props]=$value;

            return $this->writeProp($propsarr);

        }
        $rt=$this->Write($propstr,false,$ismutex);

        if($rt){
            $this->propcache=[]; //清空缓存数据
        }

        return $rt;
    }


}