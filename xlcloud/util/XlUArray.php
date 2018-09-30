<?php

namespace xl\util;

class XlUArray{

    public static function exChange($arr,$arg1,$arg2){

        $r = range(0,count($arr)-1);
        $res = $res_bak = array_combine($r,array_keys($arr));
        $change =[$arg1,$arg2];
        list($res[array_search($change[0],$res_bak)],$res[array_search($change[1],$res_bak)]) = array($change[1],$change[0]);
        $array=[];
        foreach ($res as $v){
            $array[$v] = $arr[$v];
        }
        return $array;

    }

    /**
     * 过滤截取数组
     */
    public static function filterCut($arr,$checkfun="",$count=-1,$ispromiseempty=false){
        if(is_string($arr)){
            $arr=multiexplode(array(',','-','|'),$arr); //支持3种分隔符
        }
        if(!is_array($arr)){return array();}
        if($checkfun){
            $checkfun=strtolower(trim($checkfun));
            if(!preg_match("/^is_.[a-z_0-9]+$/is",$checkfun)){
                $checkfun="is_".$checkfun;
            }
            if(!function_exists($checkfun)){
                //验证方法不存在
                $checkfun="is_string";//设置默认
            }
        }
        $arr=array_filter($arr,function($n) use($checkfun,$ispromiseempty){
            if(!$ispromiseempty){
                if($n===""){
                    return false;
                }
            }
            if(empty($checkfun)){
                return true;
            }
            if($checkfun($n)){
                return true;
            }
            return false;

        });
        if($count==-1){
            return $arr;
        }
        return array_slice($arr,0,$count);
    }

}