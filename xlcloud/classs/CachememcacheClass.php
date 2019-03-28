<?php

namespace xl\classs;

use xl\base\XlClassBase;

class CachememcacheClass extends XlClassBase
{

    private $memcache = null;
    private $_pre='';

    public function __construct($config=null)
    {

        if(!$config) {

            $config = ['hostname' => 'localhost',
                'port' => 11211,         //6379
                'type' => 'memcache',
                'debug' => true,
                'pconnect' => 0,
                'autoconnect' => 0,
                'pre' => '@ns_ls_',
                'ismaster' => true, //是否是单机
            ];

        }
        $this->memcache = new \Memcache;
        if($config['pconnect']){
            $this->memcache->pconnect($config['hostname'], $config['port']);
        }else{
            $this->memcache->connect($config['hostname'], $config['port']);
        }
        $this->_pre=$this->_getPre($config['pre']);

        parent::__construct();
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

    public function getMemcache(){

        return $this->memcache;

    }

    public function setting($type = '')
    {

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

    public function set($key,$value='',$expire=0){

        $value=$this->_svalue($value);

        $this->_set($key,$value,$expire);

    }

    private function _set($key,$value,$expire=0){


        $rt=$this->memcache->set($this->_key($key),$value,MEMCACHE_COMPRESSED, $expire);

        return $rt;
    }

    public function get($key){

        return $this->_gvalue($this->_get($key));

    }

    public function _get($key){

        return $this->memcache->get($this->_key($key));

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

    public function delete($key){

        return $this->memcache->delete($this->_key($key));

    }

    /**
     * 不加算法设置
     */

    public function sGet($key){

        return $this->_get($key);

    }

    public function sSet($key,$value='',$expire=0){
        $this->_set($key,$value,$expire);
    }

}