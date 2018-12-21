<?php

namespace xl\base;

class XlClassBase extends XlBase{
    public $__params=[];
    final public static function LoadClass(){
        //加载
        return self;
    }
    final public function setParam($key,$value){
        $this->__params[$key]=$value;
    }
    final public function getParam($key){
        return $this->__params[$key];
    }

}