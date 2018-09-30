<?php

namespace xl\base;

class XlClassBase extends XlBase{
    public $__param=[];
    final public static function LoadClass(){
        //加载
        return self;
    }
    final public function setAttach($key,$value){
        $this->__param[$key]=$value;
    }
    final public function getAttach($key){
        return $this->__param[$key];
    }

}