<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-09-08
 * Time: 13:43
 */
namespace xl;
use xl\base\XlHookBase;
use xl\util\{XlUException,XlULogger};

final class  XlLead{

    public static $factroy=null;
    public static $hook=null;
    public static $routercache=null;

    public static function checkPhpVersion(){
        if (version_compare(PHP_VERSION, '7.0.0', '<') ) exit("Sorry, Xl will only run on PHP version 7.0.0 or greater!\n");
    }
    /**
     * @param $file
     * @param array $conf
     * cli模式运行
     */
    public static function cli(){

    }
    /**
     * @param $file
     * @param array $conf
     * 工具引入接口
     */
    public static function nude($conf=[]){

        $file=$_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'index.php';
        static::entInit($file,$conf);
        $ioc = new XlInjector();
        static::$factroy=$ioc; //赋值供全局使用

    }
    public static function run($file,$conf=[]){

        static::entInit($file,$conf);

        //IOC工厂依赖注入,注册模块启动路由
        $ioc = new XlInjector();
        static::$factroy=$ioc; //赋值供全局使用
        //注册钩子
        if(defined("ISCLIPURE")&&ISCLIPURE){
            //cli模式或者回调模式
            static::cli();
            return;
        }else{
            define("ISCLIPURE",false);
        }

        try {
            $ins = $ioc->getInstance("xl\\XlRouter");
            if (is_callable($ins)) {
                $ins();
            }
        }catch (XlUException $e){

            //异常
            if($e->isHttpError()){
                $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
                header($protocol . " ".$e->getMessage());
            }else{
                echo $e->getMessage();
            }

        }


    }
    public static function entInit($file='',$conf=[]){

        static::checkPhpVersion();
        if(defined("TIME_LIMIT")){
            set_time_limit(TIME_LIMIT);
        }else{
            set_time_limit(30);
        }
        ini_set("arg_seperator.output", "&amp;");
        ini_set("magic_quotes_runtime", 0);
        //程序统一入口，初始化到分支处理
        if($conf&&$conf['namespace']){
            define("ROOT_NS",$conf['namespace']);
        }
        define('IN_XL', true);
        define("DOC_ROOT",dirname($file)); //项目路径
        define("PRO_ROOT",dirname(DOC_ROOT)); //项目地址
        define("D_S",DIRECTORY_SEPARATOR);
        $projectarr=explode(D_S,PRO_ROOT);
        define("PROR_NAME",array_pop($projectarr));
        unset($projectarr);
        define("ROOT_PATH",DOC_ROOT.D_S);  //根目录
        define("PROROOT_PATH",PRO_ROOT.D_S); //项目根
        define("XL_ROOT",dirname(__FILE__)); //xl框架路径

        define("XLROOT_PATH",XL_ROOT.D_S);
        define("XLFUNC_PATH",XLROOT_PATH.'func'.D_S);

        if(!defined("RPC_PATH")){
            define("RPC_PATH",PROROOT_PATH.'rpc'.D_S);
        }
        if(!defined("FUNC_PATH")){
            define("FUNC_PATH",PROROOT_PATH.'func'.D_S);
        }
        if(!defined("IS_DEBUG")){
            define("IS_DEBUG",true); //调试状态
        }
        if(!defined("CHARSET")){
            define("CHARSET","utf-8");
        }
        header('Content-type: text/html; charset='.CHARSET);

        //读取插件配置文件，并将插件文件载入到自动加载类中
        $plugins_conf_file=PROROOT_PATH.'config'.D_S."plugins.php";
        $plugins_conf=null;
        if(is_file($plugins_conf_file)){
            $plugins_conf=include($plugins_conf_file); //读取配置文件
            if(is_array($plugins_conf)){
                //处理插件
                $plugins_conf=array_filter($plugins_conf,function ($v){
                    if(isset($v['isclose'])&&$v['isclose']){
                        return false; //过滤掉
                    }
                    return true;
                });
            }
        }

        include(XLROOT_PATH.'AutoLoad.php');
        AutoLoad::run($conf['nss'],$plugins_conf);
        XlStatic::init();


        if(function_exists('ob_gzhandler')) {
            ob_end_clean();
            ob_start('ob_gzhandler');
        } else {
            ob_start();
        }

        config('system/errorlog') ? set_error_handler(["\\xl\\base\\XlException","errorHandlerCallback"]) : error_reporting(E_ERROR | E_WARNING | E_PARSE );


    }
    public static function AtoRArray($a_path){

        //绝对路径转换为相对路径

        $ns='';
        $isplugin=false;
        if(defined("PLUGIN_PATH")&&strpos($a_path,PLUGIN_PATH)===0){
            $r_path=str_replace(PLUGIN_PATH,'',$a_path);
            $r_path_arr=explode(D_S,$r_path);
            $ns=array_shift($r_path_arr);
            $r_path=implode(D_S,$r_path_arr);
            $isplugin=true;

        }else if(strpos($a_path,PRO_ROOT)===0){
            $r_path=str_replace(PRO_ROOT,'',$a_path);
            $ns=defined("ROOT_NS")?ROOT_NS:'';

        }else if(strpos($a_path,XL_ROOT)===0){
            $r_path=str_replace(XL_ROOT,'',$a_path);
            $ns="xl";
        }
        if(!isset($r_path)){
            return null;
        }
        $r_path=ltrim($r_path,D_S);
        $class_name=null;
        $offset=strlen($r_path)-4;
        if(substr_compare ($r_path, '.php', $offset,4,true) ==0){
            $class_name = substr($r_path, 0, $offset);
            $class_name = str_replace(D_S,'\\',$class_name);
            $class_name=$ns.'\\'.$class_name;
        }

        return ['class'=>$class_name,'a_path'=>$a_path,'r_path'=>$r_path,'ns'=>$ns,'isplugin'=>$isplugin];

    }
    public static function logger($logname='',$filemaxsize=null){

        return XlULogger::getInstance($logname,$filemaxsize);

    }
    public static function hook(){

        if(static::$hook){
            return static::$hook;
        }
        static::$hook=new XlHookBase();

        return static::$hook;

    }

    public static function getRouterCache(){

        if(!static::$routercache){
            $cls = sysclass("cachefactory", 0);
            static::$routercache = $cls::priority(['apc','xcache','eaccelerator','memcache','file']);
        }
        return static::$routercache;

    }

    public static function routerCacheGet($key){

          $cache=static::getRouterCache();

          return $cache->get($key);


    }
    public static function routerCacheSet($key,$val,$expire=0){

        $cache=static::getRouterCache();
        $cache->set($key,$val,$expire);

        $keycaches=static::routerCacheGet("@xl_router_keys");

        if(!$keycaches||!is_array($keycaches)){
            $keycaches=[];
        }
        if(!in_array($key,$keycaches)){
            $keycaches[]=$key;
            $cache->set("@xl_router_keys",$keycaches);
        }

        return true;

    }
    public static function routerCacheDel($key){

        $cache=static::getRouterCache();

        $cache->delete($key);

        $keycaches=static::routerCacheGet("@xl_router_keys");

        if(!$keycaches||!is_array($keycaches)){
            $keycaches=[];
        }
        if(in_array($key,$keycaches)){

            $offset=array_search($key,$keycaches);
            unset($keycaches[$offset]);
            $cache->set("@xl_router_keys",$keycaches);
        }

        return true;


    }


}