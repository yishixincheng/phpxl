<?php

namespace xl;
use xl\base\XlException;
use xl\util\XlUMeta;

/**
 * Class XlInjector
 * @package xl
 * 注入依赖工厂
 */

class XlInjector{

    private $_bindParam=[];
    private $_insStack=[];
    private $_singletons=[];

    private $_cachesec=TIMEOUT_METACACHE; //缓存时间
    public static $cache=null;

    public function binds($binds){

        if(!$binds){
            return $this;
        }
        if(!is_array($binds)){
            return $this;
        }
        $keys=['properties','construct_args','injector','singleton','conf'];
        foreach ($binds as $key=>$value){

            if(in_array($key,$keys)){
                $this->bind($key,$value); //绑定参数
            }
        }

        return $this;
    }

    public function bind($name,$value){
        $this->_bindParam[$name]=$value?:null;
        return $this;
    }
    public function getBinParam($name){

        return $this->_bindParam[$name]??null;

    }
    public function unbind($name=null){

        if($name&&isset($this->_bindParam[$name])){
            unset($this->_bindParam[$name]);
        }else if($name==null){
            $this->_bindParam=[];
        }

    }
    public function getInstance($id,$init=null){

        $properties=$this->getBinParam("properties")?:[]; //要注入的属性
        $construct_args=$this->getBinParam("construct_args")?:[]; //注入的构造函数参数
        $injector=$this->getBinParam("injector");
        $issingleton=$this->getBinParam("singleton")?:false; //是否是单例
        $conf=$this->getBinParam("conf")?:[];

        $ins=null; //要返回的实例
        $className=$this->getClassName($id);
        $reflClass=new \ReflectionClass($className); //反射类

        if(isset($conf['id'])){
            $class=$conf['id']; //通过配置文件读取

            if(isset($class['properties'])){
                $properties = array_merge($class['properties'], $properties);
            }
            if (isset($class['pass_by_construct']) && $class['pass_by_construct']){ //属性在构造时传入
                if(count($construct_args) ===0){
                    throw  new XlException("construct must pass params".$className);
                }
                //组合构造参数
                $construct_args = $this->buildConstructArgs($reflClass, $properties);
                $properties=array();
            }
        }
        if(!$issingleton){

            //循环依赖只能使用单例
            if(array_search($id,$this->_insStack)!==false){

                throw  new XlException("circular rely must use singleton".print_r($this->_insStack,true));
            }
            $this->_insStack[]=$id;

        }
        try{
            if(isset($class)&&$class['pass_by_construct']){
                $ins=$reflClass->newInstanceArgs($construct_args);
                $meta=$this->getMeta($reflClass); //获取注释信息

                if($issingleton){
                    $this->_singletons[$id]=$ins;
                }
                $this->inject($reflClass, $ins, $meta, $properties, $injector);
            }else{
                $ins=$reflClass->newInstanceWithoutConstructor();
                $meta=$this->getMeta($reflClass); //获取注释信息

                if($issingleton){
                    $this->_singletons[$id]=$ins;
                }
                $this->inject($reflClass, $ins, $meta, $properties, $injector);

                if($init !==null&&is_callable($init)){
                    $init($ins);
                }
                $cnst = $reflClass->getConstructor();
                if($cnst){
                    $cnst->invokeArgs($ins,$construct_args);
                }
            }


        }catch (XlException $e){
            array_pop($this->_insStack);
            throw $e;
        }
        array_pop($this->_insStack);
        $this->unbind(); //清空临时参数

        return $ins;

    }

    /**
     * 标签注入
     * @param $refl
     * @param $ins
     * @param $meta
     * @param $properties
     * @param null $injector
     */
    public function inject($reflClass, $ins, $meta, $properties, $injector=null){

        $defaults=[];
        $className=$reflClass->getName();
        $classDefaults=$reflClass->getDefaultProperties();
        if(isset($meta['property']) ){

            foreach ($meta['property'] as $property => $value) {
                //参数是否可选
                if(isset($value['value']) && isset($value['value']['optional']) && $value['value']['optional']){
                    continue;
                }
                if(isset($value['value']) && isset($value['value']['default'])){
                    $defaults[$property] = $value['value']['default'];
                    continue;
                }
                if (array_key_exists($property, $classDefaults)) {
                    continue;
                }
                if(!array_key_exists($property, $properties)){
                    throw new XlException($className."::".$property." is required");
                }

            }
        }
        if ($properties !== null) {
            foreach ($properties as $name => $value) {
                unset($defaults[$name]);
                $v = $this->getProperty($value);
                static::setPropertyValue($reflClass, $ins, $name, $v);
            }
        }
        //解析依赖标签注入
        if(isset($meta['inject'])){

            foreach ($meta['inject'] as $property => $value) {
                //先设置必须的属性
                if(is_array($value['value'])){
                    $src = $value['value']['src'];
                    //参数是否可选
                    if(isset($value['value']) && isset($value['value']['optional']) && $value['value']['optional']){
                        continue;
                    }
                    //设置了默认值
                    if(isset($value['value']) && isset($value['value']['default'])){
                        $defaults[$property] = $value['value']['default'];
                        continue;
                    }
                }else{
                    $src = $value['value'];
                }
                //是否设置了默认值
                if(array_key_exists($property, $classDefaults)){
                    continue;
                }
                if ($src === "xlinjector" || $src == "factory"){
                    continue;
                }else{
                    $got = false;
                    if($injector==null){
                        throw new XlException($className."::".$property."is required");
                    }
                    $val=null;
                    if(is_callable($injector)){
                        $val = $injector($src, $got);
                        if(!$got){
                            throw new XlException($className."::".$property."is required");
                        }
                        static::setPropertyValue($reflClass, $ins, $property, $val);
                        unset($meta['inject'][$property]);
                    }

                }
            }
            //在设置可选的
            foreach ($meta['inject'] as $property => $value) {
                if(is_array($value['value'])){
                    $src = $value['value']['src'];
                }else{
                    $src = $value['value'];
                }
                if ( $src == "xlinjector" || $src == "factory") {
                    self::setPropertyValue($reflClass, $ins, $property, $this);
                    unset($defaults[$property]);
                }else if($injector){
                    if(is_callable($injector)){
                        $got=false;
                        $val = $injector($src, $got);
                        if($got){
                            self::setPropertyValue($reflClass, $ins, $property, $val);
                            unset($defaults[$property]);
                        }
                    }

                }
            }
        }

        foreach ($defaults as $name => $value ){
            unset($defaults[$name]);
            $v = $this->getProperty($value);
            static::setPropertyValue($reflClass, $ins, $name, $v);
        }

    }
    private function getProperty($value){
        if (is_string($value) && substr($value, 0, 1) == '@') {
            return $this->getInstance(substr($value, 1));
        } else {
            return $value;
        }
    }
    public function getClassName($id){

        $conf=$this->getBinParam("conf")?:[];
        if(isset($conf[$id])){
            $class=$conf[$id];
            if(is_array($class)&&$class['class']){
                return $class['class'];
            }
        }
        return $id;
    }
    public function getMeta($reflClass){

        //获取注释信息
        if(is_string($reflClass)){
            $reflClass=new \ReflectionClass($reflClass);
        }
        $name=$reflClass->getName(); //类名
        if(!IS_DEBUG) {
            if(!static::$cache){
                $cls = sysclass("cachefactory", 0);
                $cache = $cls::priority(['apc','xcache','eaccelerator','memcache','file']);
                static::$cache=$cache;
            }else{
                $cache=static::$cache;
            }
            $cache_key = '@meta_' . md5($reflClass->getFileName() . '_' . $name);
            $data = $cache->get($cache_key);
            if ($data) {
                return $data;
            }
        }
        //从文件中获取meta信息
        $data = XlUMeta::get($name);
        if(isset($cache)&&$cache) {

            //缓存时间60秒
            $cache->set($cache_key, $data,$this->_cachesec);
        }

        return $data;
    }
    private function buildConstructArgs($reflClass, $properties){

        if($properties===null) {
            return [];
        }
        if(count($properties)==0){
            return [];
        }
        $refMethod = $reflClass->getConstructor();
        $params = $refMethod->getParameters();
        $args = [];
        foreach ($params as $key => $param) {
            $param_name = $param->getName();
            if(isset($properties[$param_name])){
                $args[$key] = $this->getProperty($properties[$param_name]);
            }else{
                if(!$param->isOptional()){
                    throw new XlException("{$reflClass->getName()}::__construct lose paramer $param_name"); //抛出异常
                }
                break;
            }
        }
        return $args;

    }
    public static function setPropertyValue($refl, $ins, $name, $value)
    {
        //设置属性值,如果没有不抛出异常
        if($refl->hasProperty($name)) {
            if ($m = $refl->getProperty($name)) {
                $m->setAccessible(true);
                $m->setValue($ins, $value);
            }
        }
    }

}