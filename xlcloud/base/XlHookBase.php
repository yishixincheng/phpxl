<?php

namespace xl\base;

class XlHookBase extends XlBase {

    public static $___requestHooks=[];  //请求钩子，拦截路由

    public static $___responseHooks=[]; //响应钩子，拦截响应

    public static $___eventHooks=[];   //事件钩子



    /**
     * @param $route
     * @param $handler
     * @param int $appendtype
     * 请求钩子，应该在调用module之前注册，否则拦截不了
     */

    final public function registRequestHook($route,$handler,$param=null,$appendtype=0){

        $route=rtrim($route,"/");

        if(!isset(static::$___requestHooks[$route])){
            static::$___requestHooks[$route]=[];
        }
        if($appendtype==0){
            array_push(static::$___requestHooks[$route],['handler'=>$handler,'param'=>$param]);
        }else{
            array_unshift(static::$___requestHooks[$route],['handler'=>$handler,'param'=>$param]);
        }

    }

    final public function removeRequestHook($route=null,$handler=null){

        if($route===null){
            static::$___requestHooks=[];
            return;
        }
        $route=rtrim($route,"/");

        //删除单个事件
        foreach (static::$___requestHooks[$route] as $key=>$eventnode){

            if($handler===null){

                //解除所有匿名函数
                if(!(is_string($eventnode)||is_array($eventnode))){
                    unset(static::$___requestHooks[$route][$key]); //解绑
                }

            }else if($eventnode['handler']==$handler){
                unset(static::$___requestHooks[$route][$key]); //解绑
            }
        }

    }

    final public function triggerRequestEvent($route,$param=null){

        //触发事件
        $route=rtrim($route,"/");
        if($route!="*"&&$route!="_notfoundroute"){

            //先触发全局钩子
            $rt=$this->triggerRequestEvent("*",$param); //触发全局事件
            if($rt===false||$rt==="__break"){
                return;
            }

        }

        if(!isset(static::$___requestHooks[$route])){
            static::$___requestHooks[$route]=[];
        }

        foreach (static::$___requestHooks[$route] as $eventnode){

            if(!$eventnode){
                continue;
            }
            if(!$eventnode['handler']){
                continue;
            }
            $rt=call_user_func_array($eventnode['handler'],[$param,$eventnode['param']]);

            if($rt===false||$rt==="__break"){
                break;
            }

            if($rt==="__esc"||$rt==="__exit"){
                exit; //直接退出
            }

        }

    }

    /**
     * @param $route
     * @param $handler
     * @param int $appendtype
     * 响应钩子，作用于输出模板，或者输出json之前
     */

    final public function registResponseHook($route,$handler,$param=null,$appendtype=0){

        $route=rtrim($route,"/");
        if(!isset(static::$___responseHooks[$route])){
            static::$___responseHooks[$route]=[];
        }
        if($appendtype==0){
            array_push(static::$___responseHooks[$route],['handler'=>$handler,'param'=>$param]);
        }else{
            array_unshift(static::$___responseHooks[$route],['handler'=>$handler,'param'=>$param]);
        }

    }

    final public function removeResponseHook($route=null,$handler=null){

        if($route===null){
            static::$___responseHooks=[];
            return;
        }
        $route=rtrim($route,"/");
        //删除单个事件
        foreach (static::$___responseHooks[$route] as $key=>$eventnode){

            if($handler===null){

                //解除所有匿名函数
                if(!(is_string($eventnode)||is_array($eventnode))){
                    unset(static::$___responseHooks[$route][$key]); //解绑
                }

            }else if($eventnode['handler']==$handler){
                unset(static::$___responseHooks[$route][$key]); //解绑
            }
        }

    }

    final public function triggerResponseEvent($route,$param=null){

        //触发事件
        $route=rtrim($route,"/");
        if($route!="*"){

            //先触发全局钩子
            $rt=$this->triggerResponseEvent("*",$param); //触发全局事件
            if($rt===false||$rt==="__break"){
                return;
            }

        }

        if(!isset(static::$___responseHooks[$route])){
            static::$___responseHooks[$route]=[];
        }

        foreach (static::$___responseHooks[$route] as $eventnode){

            if(!$eventnode){
                continue;
            }
            if(!$eventnode['handler']){
                continue;
            }
            $rt=call_user_func_array($eventnode['handler'],[$param,$eventnode['param']]);

            if($rt===false||$rt==="__break"){
                break;
            }

            if($rt==="__esc"||$rt==="__exit"){
                exit; //直接退出
            }

        }

    }

    /**
     * @param $eventname
     * @param $handler
     * @param int $appendtype
     * 时间钩子
     */

    final public function registEventHook($eventname,$handler,$param=null,$appendtype=0){

        if(!isset(static::$___eventHooks[$eventname])){
            static::$___eventHooks[$eventname]=[];
        }
        if($appendtype==0){
            array_push(static::$___eventHooks[$eventname],['handler'=>$handler,'param'=>$param]);
        }else{
            array_unshift(static::$___eventHooks[$eventname],['handler'=>$handler,'param'=>$param]);
        }

    }

    final public function removeEventHook($eventname=null,$handler=null){

        if($eventname===null){
            static::$___eventHooks=[]; //移除所有
            return;
        }

        //删除单个事件
        foreach (static::$___eventHooks[$eventname] as $key=>$eventnode){

            if($handler===null){

                //解除所有匿名函数
                if(!(is_string($eventnode)||is_array($eventnode))){
                    unset(static::$___eventHooks[$eventname][$key]); //解绑
                }

            }else if($eventnode['handler']==$handler){
                unset(static::$___eventHooks[$eventname][$key]); //解绑
            }
        }

    }

    final public function triggerEvent($eventname,$param=null){

        //触发事件
        if($eventname!="*"){

            //先触发全局钩子
            $rt=$this->triggerEvent("*"); //触发全局事件
            if($rt===false||$rt==="__break"){
                return;
            }

        }

        if(!isset(static::$___eventHooks[$eventname])){
            static::$___eventHooks[$eventname]=[];
        }

        foreach (static::$___eventHooks[$eventname] as $eventnode){

            if(!$eventnode){
                continue;
            }
            if(!$eventnode['handler']){
                continue;
            }
            $rt=call_user_func_array($eventnode['handler'],[$param,$eventnode['param']]);

            if($rt===false||$rt==="__break"){
                break;
            }

            if($rt==="__esc"||$rt==="__exit"){
                exit; //直接退出
            }

        }

    }



}