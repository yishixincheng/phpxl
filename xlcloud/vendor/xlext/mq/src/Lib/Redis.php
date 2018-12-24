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
    public function get($key,$noserialize=false){

        $value=$this->_redisObj->get($this->_key($key));

        if($noserialize){
            return $value;
        }

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

    public function setnx($key,$value){

        return $this->_redisObj->setnx($this->_key($key),$value);

    }
    public function getSet($key,$value){

        return $this->_redisObj->getSet($this->_key($key),$value);

    }

    /**
     * @param $key
     * @param $expireTime
     * 上锁
     */
    public function lock($key,$expireTime=0){

        if(!$expireTime){
            $expireTime=600;//默认阻塞10分钟
        }
        $i=0;
        while($this->setnx($key,time()+$expireTime?:0)==0){

            if(time()>$this->get($key,true)&&time()>$this->getSet($key,time()+$expireTime?:0)){
                break;
            }else{
                usleep(20);
                if($i>1){
                    return false; //释放，防止阻塞整个进程
                }
                $i++;
            }

        }

        //获得锁
        return true;

    }

    /**
     * 释放锁
     */
    public function unlock($key){

        $this->delete($key); //释放锁

    }

}