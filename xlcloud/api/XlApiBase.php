<?php

namespace xl\api;

abstract class XlApiBase implements XlApiInterface {


    public function run()
    {
        // TODO: Implement run() method.
    }

    private function _unifiedSuccErrorInf($error,$text,$attach=0){

        $code=$attach;
        $rt=array();
        if(is_array($attach)){
            foreach($attach as $k=>$v){
                if(is_numeric($k)){
                    continue;
                }
                $rt[$k]=$v;
            }
        }else{
            $rt['code']=$code;
        }
        if($error){
            $rt['status']="fail";
        }else{
            $rt['status']="success";
        }
        $rt['msg']=$text;

        $rt['__']=1;

        return $rt;
    }
    final public function SuccInf($text,$attach=0)
    {
        return $this->_unifiedSuccErrorInf(false,$text,$attach);
    }
    final public function ErrorInf($text,$attach=0)
    {
        return $this->_unifiedSuccErrorInf(true,$text,$attach);

    }
    final public function superIsOK($rt){

        if($rt['status']=="success"){
            return true;
        }
        return false;
    }

}