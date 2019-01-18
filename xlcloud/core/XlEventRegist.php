<?php

namespace xl\core;
use xl\{XlLead, XlInjector};
use xl\util\{XlUAnnotationReader,XlUVerify};

/**
 * Class XlHookEntry
 * @package xl\core
 * 钩子事件手柄注册映射
 */
final class XlEventRegist{


    //"lftsoft"=>["*"=>[
    //0=>[
    //0=>["handler"=>func]
    //]
    //]
    //]

    protected static $___eventHooks=[];   //事件钩子
    protected static $___handlerHooks=[];

    private static $___tmpEventMethods=[];
    private static $___tmpHandlerMethods=[];

    /**
     * @param $eventtype
     * @param $eventname
     * @param $handler
     * @param int $order
     *
     */

    public static function registEvent($eventtype,$eventname,$handler,$order=0,$isplugin=false,$ns=null){

        XlUVerify::isTrue($handler,"parameter[handler] not empty!");

        $staticName="___".$eventtype."Hooks";
        if(!isset(static::${$staticName}[$ns])){
            static::${$staticName}[$ns]=[];
        }
        if(!isset(static::${$staticName}[$ns][$eventname])){
            static::${$staticName}[$ns][$eventname]=[];
        }
        if(!is_numeric($order)){
            $order=0;
        }
        if(!isset(static::${$staticName}[$ns][$eventname][$order])){
            static::${$staticName}[$ns][$eventname][$order]=[];
        }
        $eventnode=[];
        $eventnode['sourcehandler']=$handler;
        if(is_array($handler)){
            XlUVerify::isTrue(count($handler), "handler parameter invalid");
            if(is_object($handler[0])){
                //如果是对象,判断是否是当前的插件
                if($handler[0] instanceof \xl\base\XlHookBase){
                    XlUVerify::isTrue($handler[0]->get_Isplugin()==$isplugin,"处理函数，不能跨插件注册！");
                    XlUVerify::isTrue($handler[0]->get_Ns()==$ns,"处理函数，不能跨插件注册！！");
                }
            }
            $eventnode['class']=$handler[0];
            $eventnode['method']=$handler[1];
        }else{
            $eventnode['handler']=$handler;
        }
        $eventnode['isplugin']=$isplugin; //不允许夸插件调用
        $eventnode['ns']=$ns;

        //过滤掉同排序下相同的事件响应函数
        static::${$staticName}[$ns][$eventname][$order]=array_filter(static::${$staticName}[$ns][$eventname][$order],function($v) use($eventnode){
            if(isset($v['class'])&&isset($eventnode['class'])){
                if($v['class']==$eventnode['class']&&$v['method']==$eventnode['method']){
                    return false;
                }
            }else if(isset($v['handler'])&&isset($eventnode['handler'])){

                if(is_string($v['handler'])&&$eventnode['handler']){
                    if($v['handler']==$eventnode['handler']){
                        return false;
                    }
                }else{
                    //匿名函数也只能绑定一个，防止调用者非单例模式重复绑定和调用问题
                    if(is_callable($v['handler'])&&is_callable($eventnode['handler'])){
                        return false;
                    }
                }
            }
            return true;
        });

        static::${$staticName}[$ns][$eventname][$order][]=$eventnode;

    }

    public static function removeEvent($eventtype,$eventname,$handler,$isplugin=false,$ns=null){


        $staticName="___".$eventtype."Hooks";
        if(!isset(static::${$staticName}[$ns])){
            return;
        }
        if(!isset(static::${$staticName}[$ns][$eventname])){
            return;
        }

        if($handler==null){
            //移除所有临时事件
            unset(static::${$staticName}[$ns][$eventname]);
            return;
        }

        foreach (static::${$staticName}[$ns][$eventname] as $order=>&$methods){
            if(is_array($methods)){
                foreach ($methods as $k=>&$eventnode){
                    if(!is_array($eventnode)){
                        continue;
                    }
                    if($eventnode['sourcehandler']==$handler){
                        unset($methods[$k]);
                    }
                }
            }
        }

    }

    public static function autoRegistAndTrigger($eventtype,$eventname,$params,$isplugin=false,$ns=null){

        $maintiermethods=[];
        $currmethods=[];
        $iseventtype=true;
        if($eventtype=="request"||$eventtype=="response"){
            $eventname=trim($eventname,"/");
            $iseventtype=false;
        }
        if($isplugin){
            //是插件
            if(!$iseventtype){
                //非事件型，需要从核心向插件流向控制
                $maintiermethods=static::getMainTierMethods($eventtype);
            }
            $findfolder=['path'=>PLUGIN_PATH.$ns.D_S."event".D_S,'isplugin'=>true,'ns'=>$ns];
        }else{
            $ns=ROOT_NS?:'___';
            $findfolder=['path'=>PROROOT_PATH.'event'.D_S,'isplugin'=>false,'ns'=>$ns];
        }

        if(!(defined("IS_DEBUG")&&IS_DEBUG)){
            //非开发环境，从缓存中读取
            $key="@xl_event_".PROR_NAME."_".$ns; //插件隔离
            if(!$cache=XlInjector::$cache){
                $cls = sysclass("cachefactory", 0);
                $cache = $cls::priority(['apc','xcache','eaccelerator','memcache','file']);
            }
            $currmethods=$cache->get($key);
            if(empty($currmethods)){
                static::findMethodsFromFiles($currmethods,$findfolder['path'],$findfolder['isplugin'],$findfolder['ns']);
                $cache->set($key,$currmethods,TIMEOUT_METACACHE); //设置到缓存中
            }
            if(empty($maintiermethods)){
                $methods=$currmethods;
            }else{
                //合并
                $methods=static::mergerMethods($maintiermethods,$currmethods);
            }

        }else{
            //同一个进程无须多次获取
            if($eventtype=="event"){
                if(isset(static::$___tmpEventMethods[$ns])){
                    $currmethods['event']=static::$___tmpEventMethods[$ns];
                }
            }
            if(empty($currmethods)){
                $methods=$maintiermethods;
                static::findMethodsFromFiles($methods,$findfolder['path'],$findfolder['isplugin'],$findfolder['ns']);
            }else{
                $methods=$currmethods;
            }

        }

        unset($maintiermethods);
        unset($currmethods);

        $staticName="___".$eventtype."Hooks";
        if(!isset(static::$___tmpEventMethods[$ns])){
            static::$___tmpEventMethods[$ns]=$methods["event"]??null;
        }
        if(!isset(static::$___tmpHandlerMethods[$ns])){
            static::$___tmpHandlerMethods[$ns]=$methods['handler']??null;
        }
        $methods=$methods[$eventtype]??[]; //过滤掉所需要的
        $m0=static::fetchMethodsByEventName($methods["*"]??[],$eventtype,"*",$staticName,$ns);
        if($eventname!='*'){
            $m1=static::fetchMethodsByEventName($methods[$eventname]??[],$eventtype,$eventname,$staticName,$ns);
        }

        unset($methods); //释放

        //自动开始调用

        if($m0){
            $rt=static::triggerEvent($m0,$params,$eventtype);
            if($rt==="__breakall"){
                //跳出
                return;
            }
        }
        if(isset($m1)&&$m1){
            static::triggerEvent($m1,$params,$eventtype);
        }

    }
    private static function fetchMethodsByEventName($methods,$eventtype,$eventname,$staticName,$ns){


        if($eventtype=="event"){

            if(isset(static::${$staticName}[$ns])&&static::${$staticName}[$ns]){

                if(isset(static::${$staticName}[$ns][$eventname])){
                    //设置了临时hook
                    foreach(static::${$staticName}[$ns][$eventname] as $_order=>$item){
                        if(is_array($item)&&$item){
                            if(!isset($methods[$_order])){
                                $methods[$_order]=[];
                            }
                            foreach ($item as $node){
                                $methods[$_order][]=$node;
                            }
                        }
                    }

                }
            }

        }

        if(empty($methods)){
            //找不到方法，自动过滤
            return null;
        }

        ksort($methods);

        return $methods;

    }

    private static function getMainTierMethods($eventtype){

        $ns=ROOT_NS?:'___';
        $findfolder=['path'=>PROROOT_PATH.'event'.D_S,'isplugin'=>false,'ns'=>$ns];

        $methods=[];
        if(!(defined("IS_DEBUG")&&IS_DEBUG)){
            //非开发环境，从缓存中读取
            $key="@xl_event_".PROR_NAME."_".$ns; //插件隔离
            if(!$cache=XlInjector::$cache){
                $cls = sysclass("cachefactory", 0);
                $cache = $cls::priority(['apc','xcache','eaccelerator','memcache','file']);
            }
            $methods=$cache->get($key);
            if(empty($methods)){
                static::findMethodsFromFiles($methods,$findfolder['path'],$findfolder['isplugin'],$findfolder['ns']);
                $cache->set($key,$methods,TIMEOUT_METACACHE); //设置到缓存中
            }

        }else{
            //同一个进程无须多次获取
            static::findMethodsFromFiles($methods,$findfolder['path'],$findfolder['isplugin'],$findfolder['ns']);

        }
        $methods=array_filter($methods,function ($k) use($eventtype){
            if($k!=$eventtype){
                return false;
            }
            return true;
        },ARRAY_FILTER_USE_KEY);


        return $methods;

    }

    private static function mergerMethods($m1,$m2){

        foreach ($m1 as $k=>&$v){
            if(isset($m2[$k])&&$m2[$k]){
                if(is_array($v)){
                    foreach ($v as $kk=>&$vv) {
                        if (isset($m2[$k][$kk]) && $m2[$k][$kk]) {
                            if (is_array($vv)) {
                                foreach ($vv as $k3 => &$v3) {
                                    if (isset($m2[$k][$kk][$k3]) && $m2[$k][$kk][$k3]) {
                                        foreach ($m2[$k][$kk][$k3] as $node) {
                                            //附加
                                            $v3[] = $node;
                                        }
                                    }
                                }
                                foreach ($m2[$k][$kk] as $f_k => $f_v) {
                                    if (!isset($vv[$f_k])) {
                                        $vv[$f_k] = $f_v;
                                        unset($m2[$k][$kk][$f_k]);
                                    }
                                }
                                ksort($vv);
                            }
                        }
                        foreach ($m2[$k] as $ff_kk=>$ff_v){
                            if(!isset($v[$ff_kk])){
                                $v[$ff_kk]=$ff_v;
                                unset($m2[$k][$ff_kk]);
                            }
                        }
                    }
                }
                unset($m2[$k]);
            }
        }
        foreach ($m2 as $m2k=>$m2v){
            if(!isset($m1[$m2k])){
                $m1[$m2k]=$m2v;
            }
        }

        return $m1;


    }

    private static function triggerEvent($methods,$params,$eventtype){

        //开始调用

        $rt=true;
        foreach ($methods as $datalist){

            foreach ($datalist as $k=>$eventnode){

                if(!$eventnode){
                    continue;
                }
                if(!$eventnode['handler']){
                    if($eventnode['class']&&$eventnode['method']){
                        if(is_object($eventnode['class'])){
                            $eventnode['handler']=[$eventnode['class'],$eventnode['method']];
                        }else{
                            $ins=XlLead::$factroy->bind("properties",['_Isplugin'=>$eventnode['isplugin'],'_Ns'=>$eventnode['ns']])->getInstance($eventnode['class']);
                            $eventnode['handler']=[$ins,$eventnode['method']];
                        }
                    }
                }else{

                    if($eventtype=="event"){
                        if(is_string($eventnode['handler'])&&strncmp($eventnode['handler'],"@",1)===0){
                            $errormsg=$eventnode['handler']." map methods no found!";
                            XlUVerify::isTrue(static::$___tmpHandlerMethods,$errormsg);
                            XlUVerify::isTrue(static::$___tmpHandlerMethods[$eventnode['ns']],$errormsg);
                            $handlername=substr($eventnode['handler'],1);
                            XlUVerify::isTrue($handlers=static::$___tmpHandlerMethods[$eventnode['ns']][$handlername],$errormsg);

                            static::triggerEvent($handlers,$params,$eventtype); //继续执行handler

                            continue;
                        }
                    }
                }
                if(!$eventnode['handler']){
                    continue;
                }

                $rt=call_user_func($eventnode['handler'],$params);

                if($rt===false||$rt==="__break"||$rt==="__breakall"){
                    break 2;
                }

                if($rt==="__esc"||$rt==="__exit"){
                    exit; //直接退出
                }

            }

        }

        return $rt;

    }
    private static function findMethodsFromFiles(&$methods,$mdl_dir,$isplugin=false,$ns=null){

        $dir = null;
        if(is_dir($mdl_dir)){
            $dir = @dir($mdl_dir);
            XlUVerify::isTrue($dir !== null, "open dir $mdl_dir failed");
            $geteach = function ()use($dir){
                $name = $dir->read();
                if(!$name){
                    return $name;
                }
                return $name;
            };

        }else{
            if(is_file($mdl_dir)){
                $files = [$mdl_dir];
                $mdl_dir = '';
            }else{
                return;
            }
            $geteach = function ()use(&$files){
                $item =  fun_adm_each($files);
                if($item){
                    return $item[1];
                }else{
                    return false;
                }
            };
        }
        while( !!($entry = $geteach()) ){

            if($entry=="."||$entry==".."){
                continue;
            }
            $path = $mdl_dir. str_replace('\\', D_S, $entry);
            if(is_file($path)){

                static::parseFileToMethods($path,$methods);

            }elseif(is_dir($path)){
                static::findMethodsFromFiles($methods,$path.D_S,$isplugin,$ns);
            }
        }
        if($dir !== null){
            $dir->close();
        }

    }

    /**
     * 解析文件
     */
    private static function parseFileToMethods($filepath,&$methods){

        //读缓存
        $key="@xl_event_".PROR_NAME."_".md5($filepath);

        if(!$cache=XlInjector::$cache){
            $cls = sysclass("cachefactory", 0);
            $cache = $cls::priority(['apc','xcache','eaccelerator','memcache','file']);
        }

        $cacheArr=$cache->get($key);

        if($cacheArr&&is_array($cacheArr)){

            $cachelasttime=$cacheArr['filemtime'];

            if($cachelasttime&&$cachelasttime==filemtime($filepath)){

                //文件没有改变需要从缓存中获取
                $matchArr=$cacheArr['matchArr']??null;

                goto goto_stowmethods;

            }

        }


        $ara=XlLead::AtoRArray($filepath);
        if($ara==null||!$ara['class']){
            //类未找到
            return;
        }

        $matchArr=static::matchMethodFromMeta($ara['class'],$ara['isplugin'],$ara['ns']);

        goto_stowmethods:

        if($matchArr){

            foreach ($matchArr as $eventtype=>$item){

                if(!isset($methods[$eventtype])){
                    $methods[$eventtype]=[];
                }
                if(is_array($item)){
                    foreach ($item as $eventname=>$node){
                        if(!isset($methods[$eventtype][$eventname])){
                            $methods[$eventtype][$eventname]=[];
                        }
                        foreach ($node as $k=>$v){
                            if(isset($methods[$eventtype][$eventname][$k])){
                                foreach ($v as $kk=>$vv){
                                    $methods[$eventtype][$eventname][$k][]=$vv;
                                }
                            }else{
                                $methods[$eventtype][$eventname][$k]=$v;
                            }
                        }
                    }
                }
            }
        }
        //设置缓存
        $cache->set($key,['filemtime'=>filemtime($filepath),'matchArr'=>$matchArr],TIMEOUT_METACACHE); //设置缓存

    }

    private static function matchMethodFromMeta($class,$isplugin,$ns){

        $reflClass=new \ReflectionClass($class);
        $reader= new XlUAnnotationReader($reflClass);
        $matchResults=[];

        foreach ($reflClass->getMethods() as $method){

            $methodName=$method->getName();

            if($method->getDeclaringClass()->getName()!=$reflClass->getName()){
                continue;
            }
            $anns = $reader->getMethodAnnotations($method, false);

            if(!isset($anns['event'])&&!isset($anns['request'])&&!isset($anns['response'])&&!isset($anns['handler'])){
                continue;
            }

            /**
             * @event({"事件名","排序"})
             * @request({"路由名","排序"})
             * @response({"路由名","排序"})
             * @handler({"事件句柄名称"})
             */

            foreach($anns as $annName=>$annArr){

                if(!is_array($annArr)){
                    continue;
                }

                if(!isset($matchResults[$annName])){
                    $matchResults[$annName]=[];
                }

                foreach($annArr as $ann){

                    $annV=$ann['value'];
                    if(!is_array($annV)){
                        continue;
                    }
                    list($_eventname,$_order)=$annV+[null,null];

                    if(empty($_order)||!is_numeric($_order)){
                        $_order=0;
                    }
                    if(!isset($matchResults[$annName][$_eventname])){
                        $matchResults[$annName][$_eventname]=[];
                    }
                    if(!isset($matchResults[$annName][$_eventname][$_order])){
                        $matchResults[$annName][$_eventname][$_order]=[];
                    }

                    $matchResults[$annName][$_eventname][$_order][]=['class'=>$class,
                        'method'=>$methodName,
                        'isplugin'=>$isplugin,
                        'ns'=>$ns];

                }


            }

        }

        return $matchResults;


    }


}