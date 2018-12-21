<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-09-08
 * Time: 13:13
 */

namespace  xl\base;


class XlBase{

    public static $__staticCache=null;
    private $__staticCacheLen=null;

    public function __construct(){}

    public function hasProperty($name, $checkVars = true)
    {
        return $this->canGetProperty($name, $checkVars) || $this->canSetProperty($name, false);
    }
    public function canGetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'get' . $name) || $checkVars && property_exists($this, $name);
    }
    public function canSetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'set' . $name) || $checkVars && property_exists($this, $name);
    }
    public function hasMethod($name)
    {
        return method_exists($this, $name);
    }
    public static function getClassName(){
        return get_called_class();
    }

    public function staticCacheLenSet($len=100){

        $this->staticCacheLen=$len;

    }

    private function _staticCacheCheck(&$staticname){

        $staticname=static::getClassName()."_".$staticname;

        if(!isset(static::$__staticCache)){
            static::$__staticCache=[];
        }
        if(!isset(static::$__staticCache[$staticname])){
            static::$__staticCache[$staticname]=[];
        }else{
            $len=count(static::$__staticCache[$staticname]);
            if($len>($this->__staticCacheLen?:100)){
                array_shift(static::$__staticCache[$staticname]);
            }
        }
    }

    /**
     * 静态缓存设置
     */
    public function staticCacheSet($staticname,$key,$value=null){

        //静态缓存
        $this->_staticCacheCheck($staticname);
        if($value===null){
            unset(static::$__staticCache[$staticname][$key]);
        }else{
            static::$__staticCache[$staticname][$key]=$value;
        }
        unset($staticname);

    }
    /**
     * 静态缓存获取
     */
    public function staticCacheGet($staticname,$key){

        //静态缓存
        $this->_staticCacheCheck($staticname);
        $data=static::$__staticCache[$staticname][$key];

        unset($staticname);

        return $data;

    }

    /**
     * 静态缓存是否设置
     */
    public function staticCacheIsSet($staticname,$key){

        //静态缓存
        $this->_staticCacheCheck($staticname);

        $data=isset(static::$__staticCache[$staticname][$key]);

        unset($staticname);

        return $data;

    }


}