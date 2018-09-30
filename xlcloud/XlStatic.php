<?php

namespace xl;

class XlStatic{

    public static function noDfSet($dfname,$value){

        if(!defined($dfname)){
            define($dfname,$value);
        }

    }

    public static function init(){

        define('IS_XLIFREAM',true);
        if(!defined("TIMERZONE")){
            define("TIMERZONE","Etc/GMT-8"); //默认设置东八区时间
        }
        date_default_timezone_set(TIMERZONE);
        define("SYS_TIME",time());
        define("SYS_CURR_TIME",date('Y-m-d H:i:s',SYS_TIME));
        define("SYS_CURR_DAY",date("Y-m-d",SYS_TIME));
        define("SYS_CURR_DAY_INT",strtotime(SYS_CURR_DAY));
        define('SYS_START_TIME', microtime(true));
        static::noDfSet("CACHE_PATH",ROOT_PATH.'cache'.D_S);
        static::noDfSet("TEMPLATE_PATH",ROOT_PATH.'view'.D_S);
        static::noDfSet("COMPILE_PATH",CACHE_PATH.'view'.D_S);
        static::noDfSet("PIC_PATH",ROOT_PATH.'pic'.D_S);
        static::noDfSet("DATA_PATH",PROROOT_PATH.'data'.D_S);
        static::noDfSet("STATIC_PATH",ROOT_PATH.'static'.D_S);
        static::noDfSet("CONFIG_PATH",PROROOT_PATH.'config'.D_S);
        static::noDfSet("MODULE_PATH",PROROOT_PATH.'module'.D_S);
        static::noDfSet("MODEL_PATH",PROROOT_PATH.'model'.D_S);
        static::noDfSet("DATASET_PATH",PROROOT_PATH.'dataset'.D_S);
        static::noDfSet("TASK_PATH",PROROOT_PATH.'task'.D_S);
        static::noDfSet("HOOK_PATH",PROROOT_PATH.'hook'.D_S);
        static::noDfSet("LOG_PATH",PROROOT_PATH.'log'.D_S); //日志路径
        static::noDfSet("TIMEOUT_METACACHE",60); //meta 解析时间
        static::noDfSet("TIMEOUT_ROUTETIME",60);//路由缓存时间
        static::noDfSet("SYS_UID",1);

        define('SITE_PROTOCOL', isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://');
        //当前访问的主机名
        define('SITE_URL', (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ''));
        //来源
        define('HTTP_REFERER', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');

        define("HOST",$_SERVER['HTTP_HOST']);
        define('NOW_TIME',      $_SERVER['REQUEST_TIME']);
        define('REQUEST_METHOD',$_SERVER['REQUEST_METHOD']);

        define('IS_GET',        REQUEST_METHOD =='GET' ? true : false);
        define('IS_POST',       REQUEST_METHOD =='POST' ? true : false);
        define('IS_PUT',        REQUEST_METHOD =='PUT' ? true : false);
        define('IS_DELETE',     REQUEST_METHOD =='DELETE' ? true : false);

        define('X_IS_AJAX', (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' || strtolower($_POST['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' || strtolower($_GET['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'));

        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        if(defined("WHITEWEBLIST")&&$origin){

            $whiteweblist=is_string(WHITEWEBLIST)?explode(',',WHITEWEBLIST):WHITEWEBLIST;
            if(is_array($whiteweblist)&&$whiteweblist) {

                $isfound=false;
                foreach ($whiteweblist as $weburi){
                    if(strpos($origin, $weburi)!==FALSE){
                        $isfound=true;
                        break;
                    }
                }
                if(!$isfound){
                    header('Access-Control-Allow-Origin:' . $origin);
                    header('Access-Control-Allow-Methods:PUT,POST,GET,DELETE,OPTIONS');
                    header('Access-Control-Allow-Headers:x-requested-with,content-type');
                }

            }

        }





    }

}