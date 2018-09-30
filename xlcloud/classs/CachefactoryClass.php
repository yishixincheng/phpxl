<?php

namespace xl\classs;

use xl\base\XlClassBase;

class CachefactoryClass extends XlClassBase{

    /**
     * 当前缓存工厂类静态实例
     */
    private static $Cachefactory;

    /**
     * 缓存配置列表
     */
    protected $cache_config = array();

    /**
     * 缓存操作实例化列表
     */
    protected $cache_list = array();

    /**
     * 构造函数
     */

    /**
     * 优先缓存顺序
     */

    public static function priority($cachetype=null,&$hittype=null){

        if($cachetype==null){
            $cachetypes=['redis','memcache','apc','xcache','eaccelerator','file'];
        }else{
            if(is_string($cachetype)){
                $cachetypes=explode(',',$cachetype);
            }else if(is_array($cachetype)){
                $cachetypes=$cachetype;
            }
        }
        if(empty($cachetypes)){
            $cachetypes=['file'];
        }

        $cacheconfig=config("cache")?:[];

        $_p_memtype='file';
        foreach ($cachetypes as $memtype){

            $_iss=static::issupport($memtype);

            if($_iss){
                //支持
                $_p_memtype=$memtype;
                break;
            }

        }

        if(!$cacheconfig[$_p_memtype]){
            $cacheconfig[$_p_memtype]=['type'=>$_p_memtype];
        }

        if(isset($hittype)){
            $hittype=$_p_memtype;
        }

        return static::get_instance($cacheconfig)->get_cache($_p_memtype);

    }

    public static function issupport($memtype="file"){

        $_iss=false;
         switch ($memtype){

             case "redis":
                 $_iss=extension_loaded('redis');
                 break;
             case "memcache":
                 $_iss=extension_loaded("memcache");
                 break;
             case "apc":
                 $_iss=function_exists("apc_cache_info") && @apc_cache_info();
                 break;
             case "xcache";
                 $_iss=function_exists('xcache_get');
                 break;
             case "eaccelerator";
                 $_iss=function_exists('eaccelerator_get');
                 break;
             case "file":
                 $_iss=true;
                 break;

         }

         return $_iss;

    }

    public static function get_instance($cache_config = '') {

        if(CachefactoryClass::$Cachefactory == '' || $cache_config !='') {
            CachefactoryClass::$Cachefactory = new CachefactoryClass();
            if(!empty($cache_config)) {
                CachefactoryClass::$Cachefactory->cache_config = $cache_config;
            }
        }
        return CachefactoryClass::$Cachefactory;
    }

    /**
     * 获取缓存操作实例
     * @param $cache_name 缓存配置名称
     */
    public function get_cache($cache_name='file') {

        if(!isset($this->cache_list[$cache_name]) || !is_object($this->cache_list[$cache_name])) {
            $this->cache_list[$cache_name] = $this->load($cache_name);
        }
        return $this->cache_list[$cache_name];
    }

    /**
     *  加载缓存驱动
     * @param $cache_name 	缓存配置名称
     * @return object
     */
    public function load($cache_name='file') {
        $object = null;
        if(isset($this->cache_config[$cache_name]['type'])) {
            switch($this->cache_config[$cache_name]['type']) {
                case 'file':
                    $cls=sysclass("cachefile",0);
                    break;
                case 'memcache':
                    $cls = sysclass('cachememcache',0);
                    break;
                case 'apc' :
                    $cls= sysclass('cacheapc',0);
                    break;
                case 'redis':
                    $cls=sysclass('cacheredis',0);
                    break;
                case 'xcache':
                    $cls=sysclass('cachexcache',0);
                    break;
                default :
                    $cls = sysclass('cachefile',0);
            }
        } else {
            $cls=sysclass("cachefile",0);
        }

        $config=$this->cache_config[$cache_name?:'file'];
        $__pre=$this->cache_config['__pre']?:"";
        if($__pre){
            $config['pre']=$__pre."_".$config['pre'];
        }

        $object=new $cls($config);

        return $object;
    }

}
