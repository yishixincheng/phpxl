<?php

namespace lftsoft\event;
use xl\base\XlEventBase;


/**
 * Class RouterHook
 * @package lftsoft\event
 * 路由钩子捕获
 */

class RouterHook extends XlEventBase {


    /**
     * @request({"*"})
     * 请求钩子
     */
    public function allCallFromRequest($params){

        //拦截所有路由

        return true;

    }




    /**
     * @response({"*"})
     * 捕获所有响应钩子
     */
    public function allCallFromResponse($params){

        return true;

    }

    /**
     * @request({"_notfoundroute"})
     */
    public function rpcServerProxy($param){

        $path=$param['path'];
        $request=$param['request'];
        if($path[0]=="rpcgateway"){
            rpcserver($request['_POST']);
            return "__exit";
        }
        return true;

    }

}