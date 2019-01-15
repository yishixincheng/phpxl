<?php

namespace lftsoft\event;
use xl\base\XlEventBase;

/**
 * Class TestEvent
 * @package lftsoft\event
 * 事件注册与响应注册
 */
class TestEvent extends XlEventBase{


    /**
     * @event({"test",1})
     * 事件注册（事件名，排序执行）
     */
    public function testRegistEvent($params){

        echo "事件-执行1";

        return true;
        //return "__break";
    }

    /**
     * @event({"test",2})
     */
    public function test2RegistEvent($params){

        echo "事件-执行2";
        return true;

    }

    /**
     * @handler({"sendmsg"})
     */
    public function sendMsgHandler($param){

        //事件回调注册

        echo "我要发送短信了";

        return true;

    }

    /**
     * @handler({"sendmsg"})
     */
    public function sendMsgHandler2($param){

        //事件回调注册

        echo "我要发送短信了2";

        return true;

    }


}