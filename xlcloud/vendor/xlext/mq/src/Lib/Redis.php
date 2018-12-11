<?php

namespace Xl_MQ\Lib;

/**
 * Class Redis
 * @package Xl_MQ\Lib
 * redis操作
 */
class Redis{

    private $_pre='';

    public function __construct($host='localhost',$port='6379',$pre='',$pconnect=false)
    {

           $this->_pre=$pre;
           $this->_redisObj=new \Redis();

           if($pconnect){
               $this->_redisObj->pconnect($host,$port);
           }else{
               $this->_redisObj->connect($host,$port);
           }

    }

    public function _key($key){


        return $this->_pre?$this->_pre."_".$key:$key;

    }

    //设置值
    public function set($key,$value){

        $value=serialize($value);

        $this->_redisObj->set($this->_key($key),$value);

    }

    //获取值
    public function get($key){

        $value=$this->_redisObj->get($this->_key($key));

        return unserialize($value);

    }

    /**
     *    lpush
     */
    public function lPush($key,$value){

        return $this->_redisObj->lPush($this->_key($key),$value);
    }

    public function rPush($key,$value){

        return $this->_redisObj->rPush($this->_key($key),$value);

    }

    /**
     *    add lpop
     */
    public function lPop($key){
        return $this->_redisObj->lPop($this->_key($key));
    }

    public function rPop($key){
        return $this->_redisObj->rPop($this->_key($key));
    }

    public function close(){

        $this->_redisObj->close();

    }

    public function delete($key){

        $this->_redisObj->delete($this->_key($key));
    }


}