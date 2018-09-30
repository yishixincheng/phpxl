<?php

namespace rpc\client;


class Request{

    public $apiParas=[];

    function __construct($methodname=''){
        $this->methodname=$methodname;
    }
    public function promiseParam(){
        return array();
    }

    public function checkPromiseParam($pms){

        foreach($this->apiParas as $k=>$v){
            if(in_array($k,$pms)){
                $apiParas[$k]=$v;
            }else{
                $isfileupload=false;
                foreach($pms as $p){
                    if($p&&substr($p,0,1)=="@"){
                        if(substr($p,1)==$k){
                            $this->apiParas[$k]="@".$v; //文件上传
                            $isfileupload=true;
                        }
                    }
                }
                if(!$isfileupload){

                    static::halt("api方法名：".$this->methodname.'中参数'.$k.'无效');
                }
            }
        }

    }

    public function setApiParas($apiParas){

        $this->apiParas=$apiParas;

    }

    public function check(){
    }
    public function checkNotNull($key,$name=''){
        $value=$this->apiParas[$key];
        $keyname=$name?:$key;
        if($value===null){
            static::halt("参数：".$keyname." 不能为空");
        }
    }
    public function checkNotAllNull($keys){

        if(!is_array($keys)){
            $keys=explode(',',$keys);
        }

        if(!is_array($keys)){
            return;
        }

        $isallempty=true;
        $keyarrs=array();
        foreach($keys as $key){
            $value=$this->apiParas[$key];
            if($value!=null){
                $isallempty=false;
            }
            array_push($keyarrs,$key);
        }
        if($isallempty){
            static::halt("参数：".implode(',',$keyarrs)." 不能都为空");
        }

    }
    public function checkNotIn($key,$inarr,$name=''){
        $value=$this->apiParas[$key];
        $keyname=$name?:$key;
        if(!in_array($value,$inarr)){
            static::halt("参数：".$keyname." 值无效");
        }
    }

    public static function halt($msg,$isprint=true){
        if($isprint){
            echo $msg;
            exit;
        }else{
            return array('status'=>'fail','msg'=>$msg,'code'=>0);
        }
    }

}