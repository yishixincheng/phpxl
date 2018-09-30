<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-09-08
 * Time: 13:13
 */

namespace  xl\base;


class XlBase{

    private $___params=[];

    public function __construct(){

    }
    public function setParam($key,$value){
        if(!is_array($this->___params)) {
            $this->___params = [];
        }
        $this->___params[$key]=$value;
    }
    public function getParam($key){
        if(!is_array($this->___params)) {
            $this->___params = [];
        }
        return $this->___params[$key];
    }
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

}