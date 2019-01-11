<?php

namespace xl\core;
use xl\{XlLead,XlInjector};
use xl\util\{XlUAnnotationReader,XlUVerify};


/**
 * Class XlPointEntry
 * @package xl\core
 * 埋点调用入口
 */
class XlPointEntry{

    private static $_isopenmq=null;

    /**
     * @param $pointname
     * @param null $params
     * @param bool $async
     * @param array|null $filter 插件过滤
     * @return array|null
     */
    public static function buryPointAndCall($pointname,$params=null,$async=false,$filter=null){

        $findfolders=[['path'=>PROROOT_PATH."point".D_S,'isplugin'=>false,'ns'=>null]];

        if (is_array($filter) && $filter) {
            $whitelist =$filter['whitelist']??null;
            $blacklist=$filter['blacklist']??null;
        }else{
            $whitelist =null;
            $blacklist=null;
        }

        if(defined("PLUGINS_PATH")&&is_array(PLUGINS_PATH)) {

            foreach (PLUGINS_PATH as $ns=>$path){

                if($whitelist&&is_array($whitelist)){
                    if(!in_array($ns,$whitelist)){
                        //不在白名单里，则过滤
                        continue;
                    }
                }else if($blacklist&&is_array($blacklist)){
                    //白名单和黑名单不共存，白名单优先
                    if(in_array($ns,$blacklist)){
                        continue;
                    }
                }
                $findfolders[]=['path'=>$path.D_S.'point'.D_S,'isplugin'=>true,'ns'=>$ns];
            }
        }
        $methods=[];

        if(!(defined("IS_DEBUG")&&IS_DEBUG)){
            //非研发状态,尝试从缓存中获取
            $key="@xl_point_".PROR_NAME."_".$pointname;
            if(!$cache=XlInjector::$cache){
                $cls = sysclass("cachefactory", 0);
                $cache = $cls::priority(['apc','xcache','eaccelerator','memcache','file']);
            }
            $methods=$cache->get($key);
            if(empty($methods)){
                foreach ($findfolders as $v){
                    if($whitelist&&is_array($whitelist)){
                        if(!in_array($v['ns'],$whitelist)){
                            //不在白名单里，则过滤
                            continue;
                        }
                    }else if($blacklist&&is_array($blacklist)){
                        //白名单和黑名单不共存，白名单优先
                        if(in_array($v['ns'],$blacklist)){
                            continue;
                        }
                    }
                    static::findMethodsFromFiles($methods,$v['path'],$pointname,$v['isplugin'],$v['ns']);
                }
                $cache->set($key,$methods,TIMEOUT_METACACHE);
            }

        }else{
            foreach ($findfolders as $v){
                static::findMethodsFromFiles($methods,$v['path'],$pointname,$v['isplugin'],$v['ns']);
            }
        }

        if(empty($methods)) {
            return null;
        }
        ksort($methods); //排序，升序

        if(!$async){
            //同步执行
            $call_results=[];
            foreach ($methods as $_order=>$_methods){
                if(is_array($_methods)&&$_methods){
                    foreach ($_methods as $method){
                        if(!is_array($method)||empty($method)){
                            continue;
                        }
                        $ins=XlLead::$factroy->bind("properties",['_Isplugin'=>$method['isplugin'],'_Ns'=>$method['ns']])->getInstance($method['class']);
                        $call_result=call_user_func([$ins,$method['method']],$params);
                        $call_results[]=['result'=>$call_result,'order'=>$_order,'class'=>$method['class'],'method'=>$method['method']];
                    }
                }

            }
            return $call_results;

        }else{

            //异步执行加入消息队列
            foreach ($methods as $_order=>$_methods){
                if(is_array($_methods)&&$_methods){
                    foreach ($_methods as $method){
                        if(!is_array($method)||empty($method)){
                            continue;
                        }
                        //加入到消息队列中异步执行
                        static::addToMQ($method['class'],$method['method'],$params,$method['isplugin'],$method['ns'],"point:".$pointname);
                    }
                }

            }


        }

        return null;

    }

    /**
     * 添加到消息队列
     */
    private static function addToMQ($class,$method,$params=null,$isplugin=false,$ns=null,$queuename=null){

        $config=null;
        if(!static::$_isopenmq){
            static::$_isopenmq=true;
            $config=config("mq");
        }
        //添加到消息队列中
        return \xl\api\XlApi::exec("AddMQMessage", ['queuename'=>$queuename,
            'class'=>$class,
            'method'=>$method,
            'isplugin'=>$isplugin,
            'ns'=>$ns,
            'params'=>$params,
            'settime'=>null,
            'config'=>$config]);

    }

    private static function findMethodsFromFiles(&$methods,$mdl_dir,$pointname,$isplugin=false,$ns=null){

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

                static::parseFileToMethods($path,$methods,$pointname);

            }elseif(is_dir($path)){
                 static::findMethodsFromFiles($methods,$path.D_S,$pointname,$isplugin,$ns);
            }
        }
        if($dir !== null){
            $dir->close();
        }

    }

    /**
     * 解析文件
     */
    private static function parseFileToMethods($filepath,&$methods,$pointname){

        //读缓存
        $key="@xl_point_".PROR_NAME."_".md5($filepath)."_".$pointname;

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

        $matchArr=static::matchPointMethodFromMeta($ara['class'],$pointname,$ara['isplugin'],$ara['ns']);

        goto_stowmethods:

        if($matchArr){

            foreach ($matchArr as $order=>$item){

                if(!isset($methods[$order])){
                    $methods[$order]=[];
                }
                if(is_array($item)){
                    foreach ($item as $node){
                        $methods[$order][]=$node;
                    }
                }

            }

        }

        //设置缓存
        $cache->set($key,['filemtime'=>filemtime($filepath),'matchArr'=>$matchArr],TIMEOUT_METACACHE); //设置缓存

    }

    private static function matchPointMethodFromMeta($class,$pointname,$isplugin,$ns){

        $reflClass=new \ReflectionClass($class);
        $reader= new XlUAnnotationReader($reflClass);
        $matchPoints=[];

        foreach ($reflClass->getMethods() as $method){

            $methodName=$method->getName();
            if($method->getDeclaringClass()->getName()!=$reflClass->getName()){
                continue;
            }
            $anns = $reader->getMethodAnnotations($method, false);

            if(!isset($anns['point'])){
                continue;
            }

            $pointArr=$anns['point'];

            foreach ($pointArr as $ann){

                $pointV=$ann['value'];
                if(!is_array($pointV)){
                    //自动过滤，不抛出异常
                    continue;
                }
                list($_pointname,$_order) = $pointV+[null,null];
                if($_pointname!=$pointname){
                    continue;
                }
                if(empty($_order)||!is_numeric($_order)){
                    $_order=0;
                }

                if(!isset($matchPoints[$_order])){
                    $matchPoints[$_order]=[];
                }
                $matchPoints[$_order][]=['class'=>$class,
                                         'method'=>$methodName,
                                         'isplugin'=>$isplugin,
                                         'ns'=>$ns];

            }

        }

        return $matchPoints;


    }



}
