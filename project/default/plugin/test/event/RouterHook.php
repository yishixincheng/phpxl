<?php

namespace test\event;

use xl\base\XlEventBase;

class RouterHook extends XlEventBase{

    /**
     * @request({"*"})
     * 请求钩子
     */
    public function allCallFromRequest($params){

        //拦截所有路由

        echo "嘿嘿";


        return true;

    }



}