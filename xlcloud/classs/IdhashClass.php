<?php

namespace xl\classs;

use xl\base\XlClassBase;

class IdhashClass extends XlClassBase {

    public static $hashmap=array(
        1=>array('a','z',1),
        2=>array('v','X',2),
        3=>array('D','A',3,'y'),
        4=>array('b','Z',4,'p'),
        5=>array(5,'x','H'),
        6=>array('M','n',6,'i'),
        7=>array('Y',7,'f','O'),
        8=>array('G','k',8),
        9=>array('q','Q',9,'r','R'),
        0=>array('C','c','I',0,'B'),
        '_'=>array('T','t')
    );
    public static $shopcitycache=array();


    public static function getImgCodeByPicPath($path){
        if(strpos($path,'/')!==false){
            $imgarr=explode('/',$path);
            $path=array_pop($imgarr);
        }
        if(strpos($path,'.')!==false){
            $imgarr=explode('.',$path);
            $path=array_shift($imgarr);
        }
        $imgcode=trim($path);
        return $imgcode;
    }
    public static function getIndexByImgCode($imgcode){

        $imgcode=static::getImgCodeByPicPath($imgcode);
        $fzm=substr($imgcode,0,1); //首字母
        $lzm= substr($imgcode,-1,1); //最后字母
        $maps=static::$hashmap;
        $index=1;
        $lastindex=0;
        foreach($maps as $i=>$v){
            if(is_array($v)){
                if(in_array($fzm,$v)){
                    $index=$i;
                    break;
                }
            }
        }
        foreach ($maps as $i=>$v){
            if(is_array($v)){
                if(in_array($lzm,$v)){
                    $lastindex=$i;
                    break;
                }
            }
        }
        $index=(int)$index;
        if($index==0){
            $index=100;
        }else{
            $index.=$lastindex;
        }

        return $index;

    }
    public static function createImgCode(){

        $rand=mt_rand(0,9).'_'.time().'_'.mt_rand(0,999);
        $maps=static::$hashmap;
        $len=strlen($rand);
        $str='';
        for($i=0;$i<$len;$i++){
            $ca=$maps[substr($rand,$i,1)];
            $str.=$ca[array_rand($ca,1)];
        }

        return $str;

    }
    public static function createIdByCitycode($citycode){

        $ct=substr($citycode,0,4);
        $rand=$ct.''.mt_rand(0,999).''.time().''.mt_rand(0,999); //最多20位

        $maps=static::$hashmap;
        $len=strlen($rand);
        $str='';
        for($i=0;$i<$len;$i++){
            $ca=$maps[substr($rand,$i,1)];
            $str.=$ca[array_rand($ca,1)];
        }
        return $str;
    }
    public static function getCitycodeById($id){

        //id，是hashid
        $id=trim($id);
        $citycode=substr($id,0,4); //根据前四位判断

        $nums='';
        for($i=0;$i<4;$i++){
            $nums.=static::codeToNum(substr($citycode,$i,1));
        }

        return $nums.'00'; //城市码6位补零

    }
    public static function codeToNum($code){

        $maps=static::$hashmap;
        foreach($maps as $k=>$v){
            if($code==$k){
                return $k;
            }else{
                if(in_array($code,$v)){
                    return $k;
                }
            }

        }
    }
    public static function getCitycodeByShopuid($uid,$iscache=true){
        if($iscache){
            if($citycode=static::$shopcitycache[$uid]){
                return $citycode;
            }
        }
        $user=model("member")->getMember($uid);
        $citycode=$user['shopcitycode'];
        static::$shopcitycache[$uid]=$citycode;
        return $citycode;
    }
    public static function getuuid($workid=1){

        //创建全局唯一id,便于以后分表
        $iid=new curr_inner_idwork($workid);
        return $iid->nextId();
    }

}

class curr_inner_idwork
{
    static $workerId;
    static $twepoch = 1361775855078;
    static $sequence = 0;
    static $maxWorkerId = 1024;
    static $workerIdShift = 10;
    static $timestampLeftShift = 14;
    static $sequenceMask = 1023;
    private  static $lastTimestamp = -1;

    function __construct($workId=1){

        if($workId > self::$maxWorkerId || $workId< 0 )
        {
            throw new Exception("worker Id can't be greater than 1024 or less than 0");
        }
        self::$workerId=$workId;


    }
    public function timeGen(){
        //获得当前时间戳
        $time = explode(' ', microtime());
        $time2= substr($time[0], 2, 3);
        return  $time[1].$time2;
    }
    public function  tilNextMillis($lastTimestamp) {
        $timestamp = $this->timeGen();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = $this->timeGen();
        }

        return $timestamp;
    }
    public function  nextId()
    {
        $timestamp=$this->timeGen();
        if(self::$lastTimestamp == $timestamp) {
            self::$sequence = (self::$sequence + 1) & self::$sequenceMask;
            if (self::$sequence == 0) {
                $timestamp = $this->tilNextMillis(self::$lastTimestamp);
            }
        } else {
            self::$sequence  = 0;
        }
        if ($timestamp < self::$lastTimestamp) {
            throw new Exception("Clock moved backwards.  Refusing to generate id for ".(self::$lastTimestamp-$timestamp)." milliseconds");
        }
        self::$lastTimestamp  = $timestamp;
        $nextId = ((sprintf('%.0f', $timestamp) - sprintf('%.0f', self::$twepoch)  )<< self::$timestampLeftShift ) | ( self::$workerId << self::$workerIdShift ) | self::$sequence;

        return $nextId;
    }

}
