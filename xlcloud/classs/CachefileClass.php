<?php

namespace xl\classs;

use xl\base\XlClassBase;

class CachefileClass extends XlClassBase{

    /*缓存默认配置*/
    protected $_setting = array(
        'suf' => '.cache.php',	/*缓存文件后缀*/
        'type' => null,		/*缓存格式：array数组，serialize序列化，null字符串*/
    );
    /*缓存路径*/
    protected $filepath = '';

    /**
     * 构造函数
     * @param	array	$setting	缓存配置
     * @return  void
     */
    public function __construct($config=null) {

        parent::__construct();
    }

    /**
     * 写入缓存
     */

    public function setting($type=null){

        $this->_setting['type']=$type;
        return $this;
    }

    public function set($key,$value='',$expire=0){

        $flag=substr($key,0,1);
        if($flag=="/"||$flag=="@"){
            $key=substr($key,1);
        }

        $filepath=CACHE_PATH.'caches'.D_S.$key;
        $expire=intval($expire);

        if(!is_dir($filepath)) {
            mkdir($filepath, 0777, true);
        }
        $filename='content'.$this->_setting['suf'];
        if(is_array($value)){
            $value['___cachetime___']=SYS_TIME;
            $value['___cacheexpire___']=$expire; //多少秒过期
            $value= "___type=array:".serialize($value);
        } elseif(is_object($value)) {
            $value= serialize($value);
            $value= "___type=serialize:___cachetime___:".SYS_TIME.";___cacheexpire___:".$expire."\r\n".$value;
        }else{
            $value= "___type=string:___cachetime___:".SYS_TIME.";___cacheexpire___:".$expire."\r\n".$value;
        }

        if(config('system/lock_ex')) {
            $file_size = file_put_contents($filepath.D_S.$filename, $value, LOCK_EX);
        } else {
            $file_size = file_put_contents($filepath.D_S.$filename, $value);
        }

        return $file_size ? $file_size : 'false';


    }

    public function get($key){

        $filepath=CACHE_PATH.'caches'.D_S.$key;

        if(!is_dir($filepath)) {
            return false;
        }

        $filename='content'.$this->_setting['suf'];

        $filepathall=$filepath.D_S.$filename;

        if (!file_exists($filepathall)) {
            return false;
        }

        $data=file_get_contents($filepathall);



        preg_match("/^___type=(.+?)\:(.+)/s",$data,$match);

        if(!$match){
            return null;
        }

        $type=$match[1];
        $data=$match[2];

        if($type == 'array') {

            $data=unserialize($data);

            $cachetime=intval($data['___cachetime___']);
            $cacheexpire=intval($data['___cacheexpire___']);

            if($cacheexpire){
                if($cacheexpire+$cachetime<SYS_TIME){
                    return null;//过期
                }
            }
            unset($data['___cachetime___']);
            unset($data['___cacheexpire___']);
            return $data;

        }
        preg_match("/^___cachetime___:(\d+?);___cacheexpire___:(\d+?)\r\n(.+)/s",$data,$match);
        if(!$match){
            return null;
        }
        $cachetime=intval($match[1]);
        $cacheexpire=intval($match[2]);
        if($cacheexpire){
            if($cacheexpire+$cachetime<SYS_TIME){
                return null;//过期
            }
        }
        $data=$match[3];
        if($type== 'serialize') {
            $data = unserialize($data);
        }
        return $data;

    }



    public function getcachetime($key){

        $filepath=CACHE_PATH.'caches'.D_S.$key;
        if(!is_dir($filepath)) {
            return 0;
        }

        $filename='content'.$this->_setting['suf'];

        $filepathall=$filepath.D_S.$filename;

        if(file_exists($filepathall)) {
            return filemtime($filepathall);
        } else {
            return 0; //缓存时间
        }

    }

    /**
     * 删除缓存
     * @param	string	$key		缓存名称
     */
    public function delete($key) {

        $filepath=CACHE_PATH.'caches'.D_S.$key;
        if(!is_dir($filepath)) {
            return false;
        }

        $filename='content'.$this->_setting['suf'];

        if(file_exists($filepath.D_S.$filename)) {
            return @unlink($filepath.$filename) ? true : false;
        } else {
            return false;
        }
    }


}