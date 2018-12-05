<?php

namespace xl;

class AutoLoad
{

    public static $classFiles = [];
    public static $aliases = [];

    public static function run($nss=null,$plugins=null)
    {
        static::$aliases['@xl'] = XL_ROOT;
        static::$aliases['@Doctrine']=XL_ROOT.'/third/Doctrine';
        static::$aliases['@third'] = DOC_ROOT.'/third';
        static::$aliases['@root']=DOC_ROOT;
        static::$aliases['@rpc'] = rtrim(RPC_PATH,D_S);
        if(defined("ROOT_NS")&&ROOT_NS){
            static::$aliases['@'.ROOT_NS]=PRO_ROOT;
        }

        $keepns=['xl','Doctrine','third','root','rpc',ROOT_NS];

        //插件存在，加入到自动加载中
        if($plugins&&is_array($plugins)){

            $pluginspathlist=[];
            foreach ($plugins as $_ns=>$v){
                $namespace=$v['namespace']?:$_ns;
                if(empty($namespace)){
                    continue;
                }
                if(in_array($namespace,$keepns)){
                    continue;
                }
                static::$aliases['@'.$namespace]=$pluginspathlist[$namespace]=PROROOT_PATH.'plugin'.D_S.$namespace;
            }

            if($pluginspathlist){

                define("PLUGIN_PATH",PROROOT_PATH."plugin".D_S);
                define("PLUGINS_PATH",serialize($pluginspathlist)); //定义插件路径常量
            }

        }

        if($nss&&is_array($nss)){
            foreach ($nss as $ns=>$rootpath){
                if(in_array($ns,$keepns)){
                    continue;
                }
                static::$aliases[$ns]=$rootpath;
            }
        }
        spl_autoload_register('\xl\AutoLoad::autoLoadClass', true, true);
        static::autoLoadFunc();
        register_shutdown_function('\xl\AutoLoad::shutdownCall');
        static::recodeErrorLog();

    }
    public static function autoLoadClass($className)
    {

        if (isset(static::$classFiles[$className])) {
            $classFile = static::$classFiles[$className];
            if ($classFile[0] == "@") {
                //别名
                $classFile = static::getAlias($classFile);
            }
        } elseif (strpos($className, '\\') !== false) {

            $classFile = static::getAlias('@' . str_replace('\\', '/', $className) . '.php');
            if ($classFile === false || !is_file($classFile)) {
                return;
            }
        } else {
            return;
        }
        include($classFile); //包含文件


        if (!class_exists($className, false) && !interface_exists($className, false) && !trait_exists($className, false)) {

            throw new \Exception("Unable to find '$className' in file: $classFile. Namespace missing?");
        }

    }

    public static function getAlias($alias)
    {

        if (strncmp($alias, '@', 1)) {
            return $alias;
        }
        $pos = strpos($alias, '/');
        $root = $pos === false ? $alias : substr($alias, 0, $pos);
        if (isset(static::$aliases[$root])) {
            if (is_string(static::$aliases[$root])) {
                return $pos === false ? static::$aliases[$root] : static::$aliases[$root] . substr($alias, $pos);
            } else {
                foreach (static::$aliases[$root] as $name => $path) {
                    if (strpos($alias . '/', $name . '/') === 0) {
                        return $path . substr($alias, strlen($name));
                    }
                }
            }
        }
        return null;
    }

    public static function autoLoadFunc()
    {
        //加载全局函数
        include XLFUNC_PATH . 'global.php';

        if(file_exists(FUNC_PATH . 'extention.php')){
            //扩展函数库
            include FUNC_PATH.'extention.php';
        }

    }
    public static function recodeErrorLog(){

        //注册钩子事件

        XlLead::hook()->registEventHook("system_shutdownpage",function(){

            $_error = error_get_last();

            if ($_error && in_array($_error['type'], [1, 4, 16, 64, 256, 4096, E_ALL])) {

                $str='-----------------------------------'."\r\n";
                $str.='错误:' . $_error['message'] . "\r\n";
                $str.='文件:' . $_error['file'] . "\r\n";
                $str.='在第' . $_error['line'] . "行\r\n";
                $str.='时间'.date("Y-m-d H:i:s",time())."\r\n";
                $str.='-----------------------------------'."\r\n";

                XlLead::logger("_shutdownerrorlog_".date("Y-m-d",time()))->write($str,true);
            }

        });

    }
    public static function shutdownCall(){

        /**
         * 触发事件
         */

        XlLead::hook()->triggerEvent("system_shutdownpage");

    }


}