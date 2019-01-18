<?php

namespace xl\base;

use xl\core\XlEventRegist;

class XlHookBase extends XlBase {


    final public function registEvent($eventname,$handler,$order=0){
        XlEventRegist::registEvent("event",$eventname,$handler,$order,$this->_Isplugin,$this->_Ns);
    }

    final public function removeEvent($eventname=null,$handler=null){

        XlEventRegist::removeEvent("event",$eventname,$handler,$this->_Isplugin,$this->_Ns);
    }

    final public function triggerEvent($eventname,$param=null){

        //触发事件
        XlEventRegist::autoRegistAndTrigger("event",$eventname,$param,$this->_Isplugin,$this->_Ns);

    }

    final public function get_Isplugin(){

        return $this->_Isplugin?:false;
    }

    final public function get_Ns(){
        return $this->_Ns?:null;
    }

    /**
     * 注入的变量
     */
    protected $_Ns;
    protected $_Isplugin;



}