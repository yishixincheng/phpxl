<?php

namespace xl\base;

/**
 * Class XlIapiBase
 * @package xl\base
 * 内部接口api基类
 */
abstract class XlIapiBase extends XlMvcBase{

    private $_params=null;

    public function __construct(){
        parent::__construct();
    }
    public function setParams($params){

        $this->_params=$params;

    }
    public function getParams(){

        return $this->_params;
    }

    /**
     * @return mixed
     * 子类要实现的类，返回的结果
     */
    abstract public function getResult($params=null);

}