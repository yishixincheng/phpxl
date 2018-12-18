<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-09-08
 * Time: 13:43
 */
namespace xl;
use xl\base\XlHookBase;
use xl\util\XlUException;
use xl\util\XlULogger;

class  XlLead{

    public static $factroy=null;
    public static $hook=null;

    public static function checkPhpVersion(){
        if (version_compare(PHP_VERSION, '5.6.0', '<') ) exit("Sorry, Xl will only run on PHP version 5.6.0 or greater!\n");
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
        try{
            $count=0;
            $rhook=str_replace(PROROOT_PATH,"",HOOK_PATH,$count);
            if($count>1){
                $rootpathlen=strlen(PROROOT_PATH);
                if(substr(HOOK_PATH,0,$rootpathlen)==PROROOT_PATH){
                    $rhook=substr(HOOK_PATH,$rootpathlen);
                }else{
                    $rhook=HOOK_PATH;
                }
            }
            $rhook=str_replace(D_S,"\\",$rhook);
            $ins=$ioc->getInstance(ROOT_NS."\\".$rhook."InitHook");
            if (is_callable($ins)) {
                $ins();
            }

            if(method_exists($ins,"init")){

                $ins->init();//注册事件
            }

            static::$hook=$ins;

        }catch (\Exception $e){
            //nothing to do
        }
        if(defined("ISCLIPURE")&&ISCLIPURE){
            //cli模式或者回调模式
            static::cli();
            return;
        }else{
            define("ISCLIPURE",false);
        }

        try {
            $ins = $ioc->bind("construct_args", [$file])->getInstance("xl\\XlRouter");

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
        define('IN_XL', true);
        define("DOC_ROOT",dirname($file)); //项目路径

        define("PRO_ROOT",dirname(DOC_ROOT)); //项目地址

        if($conf&&$conf['namespace']){
            define("ROOT_NS",$conf['namespace']);
        }
        define("D_S",DIRECTORY_SEPARATOR);
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
                    if($v['isclose']){
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

        config('system/errorlog') ? set_error_handler(array("\\xl\\base\\XlException","errorHandlerCallback")) : error_reporting(E_ERROR | E_WARNING | E_PARSE );


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
        if(substr_compare ($r_path, '.php', strlen($r_path)-4,4,true) ==0){
            $class_name = substr($r_path, 0, strlen($r_path)-4);
            $class_name = str_replace(D_S,'\\',$class_name);
            $class_name=$ns.'\\'.$class_name;
        }

        return ['class'=>$class_name,'a_path'=>$a_path,'r_path'=>$r_path,'ns'=>$ns,'isplugin'=>$isplugin];

    }
    public static function logger($logname=''){

        return XlULogger::getInstance($logname);

    }
    public static function hook(){

        if(static::$hook){
            return static::$hook;
        }
        static::$hook=new XlHookBase();

        return static::$hook;

    }

}