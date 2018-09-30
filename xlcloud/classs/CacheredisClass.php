<?php

namespace xl\classs;

use xl\base\XlClassBase;

class CacheredisClass extends XlClassBase {

    private $_pre='';

    // 是否使用 M/S 的读写集群方案
    private $_isUseCluster = false;
    // Slave 句柄标记
    private $_sn = 0;
    private $config=null;
    // 服务器连接句柄
    private $_linkHandle = array(
        'master'=>null,// 只支持一台 Master
        'slave'=>array(),// 可以有多台 Slave
    );
    public function __construct($config,$isMaster=true,$isconnect=true){
        $this->setConfig($config);
        if($isconnect){
            $this->connect($isMaster);
        }
        parent::__construct();
    }
    public function setting($type=''){

        return $this;
    }

    private function _svalue($value){


        $data=['___cachetimes___'=>time(),'___data___'=>$value];

        return serialize($data);

    }
    private function _gvalue($value){

        $data=unserialize($value);
        if(!is_array($data)){
            return $data;
        }
        if(!isset($data['___cachetimes___'])){
            return $data;
        }
        return $data['___data___'];

    }

    private function _getPre($pre){

        if(empty($pre)){
            $pre='@ns';
        }
        if($pre=="@ns"){
            $pre=md5(DOC_ROOT);
        }else{
            $pre=str_replace('@ns_',md5(DOC_ROOT)."_",$pre);
        }

        return $pre;

    }

    public function setConfig($config){

        $c=$config?$config:config("cache/redis");
        $this->config=$c;
        $this->config['host']=$c['hostname'];
        $this->_isUseCluster=$c['isusecluster'];
        $this->_pre=$this->_getPre($c['pre']);
        $this->hostname=$c['hostname'];
        $this->port=$c['port'];
        $this->pconnect=$c['pconnect'];
    }
    /**
     * 连接服务器,注意：这里使用长连接，提高效率，但不会自动关闭
     * @param array $config Redis服务器配置
     * @param boolean $isMaster 当前添加的服务器是否为 Master 服务器
     * @return boolean
     */
    public function connect($isMaster=true){
        // default port
        // 设置 Master 连接
        if($this->config['pconnect']){
            $connect='pconnect';
        }else{
            $connect='connect';
        }
        if($isMaster){
            $this->_linkHandle['master'] = new \Redis();
            $ret = $this->_linkHandle['master']->{$connect}($this->config['host'],$this->config['port']);

        }else{
            // 多个 Slave 连接
            $this->_linkHandle['slave'][$this->_sn] = new \Redis();
            $ret = $this->_linkHandle['slave'][$this->_sn]->{$connect}($this->config['host'],$this->config['port']);
            ++$this->_sn;
        }
        return $ret;

    }
    private function _key($key){
        if(is_array($key)){
            foreach($key as &$v){
                $flag=substr($v,0,1);
                if($flag=="/"){
                    $v=substr($v,1);
                }else if($flag=="@"){
                    $v=md5(DOC_ROOT).substr($v,1);
                }else{
                    $v=($this->_pre).$v;
                }
            }
            $k=$key;
        }else{
            $flag=substr($key,0,1);
            if($flag=="/"){
                $k=substr($key,1);
            }else if($flag=="@"){
                $k=md5(DOC_ROOT).substr($key,1);
            }else{
                $k=($this->_pre).$key;
            }
        }
        return $k;
    }
    /**
     * 关闭连接
     * @param int $flag 关闭选择 0:关闭 Master 1:关闭 Slave 2:关闭所有
     * @return boolean
     */
    public function close($flag=2){
        switch($flag){
            // 关闭 Master
            case 0:
                $this->getRedis()->close();
                break;
            // 关闭 Slave
            case 1:
                for($i=0; $i<$this->_sn; ++$i){
                    $this->_linkHandle['slave'][$i]->close();
                }
                break;
            // 关闭所有
            case 2:
                $this->getRedis()->close();
                for($i=0; $i<$this->_sn; ++$i){
                    $this->_linkHandle['slave'][$i]->close();
                }
                break;
        }
        return true;
    }
    /**
     * 得到 Redis 原始对象可以有更多的操作
     * @param boolean $isMaster 返回服务器的类型 true:返回Master false:返回Slave
     * @param boolean $slaveOne 返回的Slave选择 true:负载均衡随机返回一个Slave选择 false:返回所有的Slave选择
     * @return redis object
     */
    public function getRedis($isMaster=true,$slaveOne=true){
        // 只返回 Master
        if($isMaster){
            return $this->_linkHandle['master'];
        }else{
            return $slaveOne ? $this->_getSlaveRedis() : $this->_linkHandle['slave'];
        }
    }
    /**
     * 写缓存
     * @param string $key 组存KEY
     * @param string $value 缓存值
     * @param int $expire 过期时间， 0:表示无过期时间
     */
    public function set($key, $value='', $expire=0){
        // 永不超时
        $value=$this->_svalue($value);

        $this->_set($key,$value,$expire);

    }

    /**
     * 不加算法设置
     */
    public function sSet($key,$value='',$expire=0){
        $this->_set($key,$value,$expire);
    }

    private function _set($key,$value='',$expire=0){

        if(is_array($key)){
            $ret = $this->getRedis()->mset($this->_key($key),$value,$expire);
        }else{
            if($expire == 0){
                $ret = $this->getRedis()->set($this->_key($key), $value);
            }else{
                $ret = $this->getRedis()->setex($this->_key($key), $expire, $value);
            }
        }
        return $ret;

    }

    public function getcachetime($key){

        $data=$this->_get($key);
        $data=unserialize($data);
        if(!is_array($data)){
            return 0;
        }
        if(isset($data['___cachetimes___'])){
            return $data['___cachetimes___'];
        }
        return 0;

    }

    /**
     * 读缓存
     * @param string $key 缓存KEY,支持一次取多个 $key = array('key1','key2')
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function get($key){
        // 是否一次取多个值

        return $this->_gvalue($this->_get($key));
    }

    /**
     * @param $key
     *
     */
    public function sGet($key){

        return $this->_get($key);

    }

    public function _get($key){

        $func = is_array($key) ? 'mget' : 'get';
        // 没有使用M/S
        if(! $this->_isUseCluster){
            return $this->getRedis()->{$func}($this->_key($key));
        }
        // 使用了 M/S
        return $this->_getSlaveRedis()->{$func}($this->_key($key));

    }


    /**
     * 条件形式设置缓存，如果 key 不存时就设置，存在时设置失败
     * @param string $key 缓存KEY
     * @param string $value 缓存值
     * @return boolean
     */
    public function setnx($key, $value){
        return $this->getRedis()->setnx($key, $value);
    }

    /**
     * 删除缓存
     * @param string || array $key 缓存KEY，支持单个健:"key1" 或多个健:array('key1','key2')
     * @return int 删除的健的数量
     */
    public function remove($key){
        // $key => "key1" || array('key1','key2')

        if(preg_match("/\*/",$key)){

            $keys=$this->getRedis()->keys($this->_key($key));
            return $this->getRedis()->delete($keys);

        }else{
            return $this->getRedis()->delete($this->_key($key));
        }

    }
    public function delete($key){

        if($key=="*"){
           return $this->clear(); //清空内存
        }

        return $this->remove($key);
    }

    /**
     * 值加加操作,类似 ++$i ,如果 key 不存在时自动设置为 0 后进行加加操作
     * @param string $key 缓存KEY
     * @param int $default 操作时的默认值
     * @return int　操作后的值
     */
    public function incr($key,$default=1){
        if($default == 1){
            return $this->getRedis()->incr($this->_key($key));
        }else{
            return $this->getRedis()->incrBy($this->_key($key), $default);
        }
    }
    /**
     * 值减减操作,类似 --$i ,如果 key 不存在时自动设置为 0 后进行减减操作
     * @param string $key 缓存KEY
     * @param int $default 操作时的默认值
     * @return int　操作后的值
     */
    public function decr($key,$default=1){
        if($default == 1){
            return $this->getRedis()->decr($this->_key($key));
        }else{
            return $this->getRedis()->decrBy($this->_key($key), $default);
        }
    }
    /**
     * 添空当前数据库
     * @return boolean
     */
    public function clear(){
        return $this->getRedis()->flushDB();
    }

    /* =================== 以下私有方法 =================== */
    /**
     * 随机 HASH 得到 Redis Slave 服务器句柄
     * @return redis object
     */
    private function _getSlaveRedis(){
        // 就一台 Slave 机直接返回
        if($this->_sn <= 1){
            return $this->_linkHandle['slave'][0];
        }
        // 随机 Hash 得到 Slave 的句柄
        $hash = $this->_hashId(mt_rand(), $this->_sn);
        return $this->_linkHandle['slave'][$hash];
    }

    /**
     * 根据ID得到 hash 后 0～m-1 之间的值
     * @param string $id
     * @param int $m
     * @return int
     */
    private function _hashId($id,$m=10)
    {
        //把字符串K转换为 0～m-1 之间的一个值作为对应记录的散列地址
        $k = md5($id);
        $l = strlen($k);
        $b = bin2hex($k);
        $h = 0;
        for($i=0;$i<$l;$i++)
        {
            //相加模式HASH
            $h += substr($b,$i*2,2);
        }
        $hash = ($h*1)%$m;
        return $hash;
    }

    /**
     *    lpush
     */
    public function lpush($key,$value){
        return $this->getRedis()->lpush($this->_key($key),$value);
    }

    /**
     *    add lpop
     */
    public function lpop($key){
        return $this->getRedis()->lpop($this->_key($key));
    }
    /**
     * lrange
     */
    public function lrange($key,$start,$end){
        return $this->getRedis()->lrange($this->_key($key),$start,$end);
    }

    /**
     *    set hash opeation
     */
    public function hset($name,$key,$value){
        if(is_array($value)){
            return $this->getRedis()->hset($name,$this->_key($key),serialize($value));
        }
        return $this->getRedis()->hset($name,$this->_key($key),$value);
    }
    /**
     *    get hash opeation
     */
    public function hget($name,$key = null,$serialize=true){
        if($key){
            $row = $this->getRedis()->hget($name,$this->_key($key));
            if($row && $serialize){
                unserialize($row);
            }
            return $row;
        }
        return $this->getRedis()->hgetAll($name);
    }

    /**
     *    delete hash opeation
     */
    public function hdel($name,$key = null){
        if($key){
            return $this->getRedis()->hdel($name,$this->_key($key));
        }
        return $this->getRedis()->hdel($name);
    }
    /**
     * Transaction start
     */
    public function multi(){
        return $this->getRedis()->multi();
    }

    public function exec(){
        return $this->getRedis()->exec();
    }
    public function clearmemory(){

        //清空内存
        $this->clear();
    }


}