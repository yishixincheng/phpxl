<?php

namespace xl;
use xl\base\XlException;
use xl\util\XlUMeta;

/**
 * Class XlInjector
 * @package xl
 * 注入依赖工厂
 */

final class XlInjector{

    private $_bindParam=[];
    private $_insStack=[];
    private $_singletons=[];

    public function binds($binds){

        if(!$binds){
            return $this;
        }
        if(!is_array($binds)){
            return $this;
        }
        $keys=['properties','construct_args','injector','singleton'];
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

        $ins=null; //要返回的实例
        $className=$this->getClassName($id);
        $reflClass=new \ReflectionClass($className); //反射类

        if(!$issingleton){
            //循环依赖只能使用单例
            if(array_search($id,$this->_insStack)!==false){

                throw  new XlException("circular rely must use singleton".print_r($this->_insStack,true));
            }
            $this->_insStack[]=$id;

        }
        try{
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

            $cache_key = '@meta_' . md5($reflClass->getFileName() . '_' . $name);
            $data=XlLead::routerCacheGet($cache_key);
            if ($data) {
                return $data;
            }
        }
        //从文件中获取meta信息
        $data = XlUMeta::get($name);
        if(isset($cache_key)) {
            XlLead::routerCacheSet($cache_key,$data,TIMEOUT_METACACHE);
        }
        return $data;
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