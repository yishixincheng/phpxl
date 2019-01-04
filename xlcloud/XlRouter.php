<?php

namespace xl;
use xl\base\XlBase;
use xl\util\{XlURequest,XlURouterEntries,XlUVerify};

/**
 * Class XlRouter
 * @author("xincheng")
 */
class XlRouter extends XlBase{

    /**
     * @inject("xlinjector")
     */
    private $factory;

    private $_cachesec=TIMEOUT_ROUTETIME; //缓存时间
    /**
     * @property
     */
    private $projectname="default";
    private $default_strict_matching=true;
    private $routes=null;

    public function __construct($projectname='') {

        parent::__construct();

        //注册所有Moudle模块并解析参数，找到对应的方法调用
        if($projectname){
            $projectarr=explode('/',dirname(dirname($projectname)));
            $projectname=array_pop($projectarr);
            $this->projectname=$projectname;
        }

        if(!$this->cache=XlInjector::$cache){
            $cls = sysclass("cachefactory", 0);
            $this->cache = $cls::priority(['apc','xcache','eaccelerator','memcache','file']);
        }

        $this->routes=$this->getRouterFromCache();
        if( $this->routes==null){
            $this->routes=[];
            $this->parseModuleRouter( $this->routes);
        }

    }

    /**
     * 解析路由规则，调用对用的模块
     */

    public function __invoke(){

        $routes=$this->getRoutes();

        //解析路由匹配规则
        $requst=new XlURequest();

        $requstdata=$requst->getData();

        XlUVerify::isTrue($this->invokeRoute($routes,$requstdata),"NotFound",function() use($requstdata){

            if(XlLead::$hook){
                //钩子存在
                XlLead::$hook->triggerRequestEvent("_notfoundroute",['path'=>$requstdata['path'],'request'=>$requstdata]); //触发钩子事件
            }

        });

    }
    private function invokeRoute($routes,$request){

        $method = $request['_SERVER']['REQUEST_METHOD'];
        $path = $request['path'];
        $uri = $request['_SERVER']['REQUEST_URI'];
        list(,$params) = explode('?', $uri)+array( null,null );
        $params = is_null($params)?null:explode('&', $params);

        $match_path = array();
        if(isset($routes[$method])){

            if(($cam= $routes[$method]->findByArray($path,$params,$match_path)) !== null){

                $request['_Ns']=$cam['ns'];
                $request['_Isplugin']=$cam['isplugin'];
                //找到路由
                $this->callModule($cam['class'],$cam['method'],$cam['param']??null,$request,$cam['route']);
                return true;
            }
        }

        if(!isset($routes['*'])){
            return false;
        }
        if(($cam = $routes['*']->find($uri, $match_path)) === null){
            return false;
        }

        $request['_Ns']=$cam['ns'];
        $request['_Isplugin']=$cam['isplugin'];

        $this->callModule($cam['class'],$cam['method'],$cam['param']??null,$request,$cam['route']);

        return true;
    }

    private function callModule($class,$method,$regParam=null,$request,$route=null){

        $properties=[
           '_Post'=>$request['_POST']??null,
           '_Get'=>$request['_GET']??null,
           '_Cookie'=>$request['COOKIE']??null,
           '_Session'=>$request['Session']??null,
           '_Files'=>$request['FILES']??null,
           '_Genv'=>$request,
           '_Ns'=>$request['_Ns']??null,
           '_Isplugin'=>$request['_Isplugin']??null
        ];

        if(XlLead::$hook){

            //钩子存在
            XlLead::$hook->triggerRequestEvent($route,['request'=>$request,'regparam'=>$regParam,'route'=>$route]); //触发钩子事件

        }

        $ins=$this->factory->bind("properties",$properties)->getInstance($class);

        $http_method=$request['_SERVER']['REQUEST_METHOD'];

        if(in_array($http_method,['GET','POST'])){
            $param=$request['_'.$http_method];
        }else if(in_array($http_method,['PUT','DELETE'])){
            $param=$regParam['_REQUEST'];
        }else{
            $param=null;
        }


        ob_start();


        call_user_func_array([$ins,$method],[$param,$regParam]);

        //响应钩子
        if(XlLead::$hook){

            XlLead::$hook->triggerResponseEvent($route,['request'=>$request,'regparam'=>$regParam,'route'=>$route,'response'=>ob_get_contents()]); //触发钩子事件

        }

        ob_end_flush();

        // ob_flush()
        //flush();

    }

    private function _getRouterCacheKey(){

        return "@xl_router_".$this->projectname;

    }
    public function getRouterFromCache(){

        if(defined("IS_DEBUG")&&IS_DEBUG){

            //调试状态
            return null;
        }

        $key=$this->_getRouterCacheKey();
        $routerMap=$this->cache->get($key);

        if($routerMap&&is_array($routerMap)){
            return $routerMap;
        }
        return null;
    }
    public function parseModuleRouter(&$rMap){

        //解析路由
        $rMap=$rMap?:[];
        //检索模块
        $this->eachRoutes($rMap,MODULE_PATH,null,null);


        if(defined("PLUGINS_PATH")){

            //定义了插件
            $plugins_path=unserialize(PLUGINS_PATH);

            if($plugins_path&&is_array($plugins_path)){

                foreach ($plugins_path as $ns=>$path){

                    $this->eachRoutes($rMap,$path.D_S."module".D_S,null,null);

                }

            }

        }


        if(defined("IS_DEBUG")&&IS_DEBUG){
            //调试状态,不设置缓存
            return null;
        }

        $key=$this->_getRouterCacheKey();
        $this->cache->set($key,$rMap,$this->_cachesec); //设置到缓存中

    }

    public function getRoutes(){
        return $this->routes;
    }

    /**
     * 查找module模块下所有的文件
     *
     */
    private function eachRoutes(&$routes, $mdl_dir, $class, $method){

        $dir = null;
        if(is_dir($mdl_dir) && $class === null){
            XlUVerify::isTrue(is_dir($mdl_dir),$mdl_dir." not a dir");
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
                if(is_array($class)){
                    foreach ($class as &$v){
                        $v .= '.php';
                    }
                    $files = $class;
                }else{
                    $files = array($class.'.php');
                }
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
                $this->_fetchRoutesFromFile($routes, $path,$method);
            }elseif(is_dir($path)){
                $this->eachRoutes($routes,$path.D_S,$class,$method);
            }
        }
        if($dir !== null){
            $dir->close();
        }
        return $routes;
    }

    /**
     * @param $routes
     * @param $class_file
     * @param $class_name
     * @param null $method
     * 提取路由
     *
     */
    private function _fetchRoutesFromFile(&$routes, $class_file, $method=null){

        XlUVerify::isTrue(is_file($class_file), $class_file.' is not an exist file');

        $ara=XlLead::AtoRArray($class_file);
        if($ara==null||!$ara['class']){
            //类未找到
            return null;
        }
        $class_name=$ara['class'];
        $container = $this->factory->bind("construct_args",[$class_name,$method])->getInstance('xl\\XlContainer');

        foreach ($container->routes as $http_method=>$route){
            if(!isset($routes[$http_method])){
                $routes[$http_method] = new XlURouterEntries();
            }
            $cur = $routes[$http_method];
            foreach ($route as $entry){
                $path=$entry['path'];
                $class=$entry['class'];
                $method=$entry['method'];
                $strict=$entry['strict'];
                $realpath = preg_replace('/\/+/', '/', '/'.$path);
                $strict = ($strict===null)?$this->default_strict_matching:$strict;
                $cur->insert($realpath,['class'=>$class,'method'=>$method,'route'=>$realpath,'ns'=>$ara['ns'],'isplugin'=>$ara['isplugin']],$strict);
            }
        }

    }


}
