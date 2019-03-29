<?php

namespace xl\core;
use xl\base\XlException;

/**
 * Class XlLock
 * @package xl\core
 */
final class XlLock{

    public static $__EXPIRETIME=10; //默认阻塞10秒
    private static function getLockObj(){

        if(extension_loaded('redis')){
            return "\\xl\\core\\RedisLock";
        }else if(extension_loaded('memcache')){
            return "\\xl\\core\\MemcacheLock";
        }else{
            return "\\xl\\core\\FileLock";
        }
    }

    public static function lock($key,$expireTime=0,$autoReleaseLock=false){

        $lockObj=static::getLockObj();
        $key=md5(DOC_ROOT)."__syslock__".$key;

        return $lockObj::lock($key,$expireTime?:static::$__EXPIRETIME,$autoReleaseLock);

    }
    public static function unlock($key){

        $lockObj=static::getLockObj();
        $key=md5(DOC_ROOT)."__syslock__".$key;
        $lockObj::unlock($key);

    }

}

trait LockTrait{

    public static function throwException($key){

        $keyarr=explode("__syslock__",$key);
        $skey=array_pop($keyarr);
        static::unlock($key);
        throw new XlException("lock:".$skey." is timeout throw exception");

    }

}

class RedisLock{

    use LockTrait;

    public static function lock($key,$expireTime=0,$autoReleaseLock=false){

        $cls = sysclass("cachefactory", 0);
        $redis = $cls::priority(['redis'])->getRedis();

        while($redis->setnx($key,time()+$expireTime)==0){

            if(time()>$redis->get($key)&&time()>$redis->getSet($key,time()+$expireTime)){

                if($autoReleaseLock){
                    //超时了
                    break;
                }
                //超时后自动抛出异常并解锁
                static::throwException($key);

            }else{
                usleep(20);
            }
        }
        //获得锁
        return true;
    }
    public static function unlock($key){

        $cls = sysclass("cachefactory", 0);
        $redis = $cls::priority(['redis'])->getRedis();
        $redis->delete($key); //释放锁

    }
}


class MemcacheLock{

    use LockTrait;

    public static function lock($key,$expireTime=0,$autoReleaseLock=false){

        $cls = sysclass("cachefactory", 0);
        $memcache = $cls::priority(['memcache'])->getMemcache();

        $timeout=$expireTime+time();

        while (time() <= $timeout && false == $memcache->add($key,time()+$expireTime, false))
        {
            if(!$autoReleaseLock&&time()>=$memcache->get($key)){
                static::throwException($key);
            }
            usleep(20);
        }

        return true;

    }
    public static function unlock($key){

        $cls = sysclass("cachefactory", 0);
        $memcache = $cls::priority(['memcache'])->getMemcache();
        $memcache->delete($key); //释放锁

    }
}

class FileLock{

    use LockTrait;

    private static $files=[];

    public static function getFilePath($key){

        if(!is_dir(CACHE_PATH."lock")){
            mkdir(CACHE_PATH."lock",0777,true);
        }

        return CACHE_PATH."lock".D_S.$key.".lock";

    }

    public static function lock($key,$expireTime=0,$autoReleaseLock=false){

        $filepath=static::getFilePath($key);
        $fp = fopen($filepath, "w+");
        static::$files[$key]=$fp;
        if(flock($fp, LOCK_EX))
        {
            //上锁成功
            return true;
        }
        $timeout=$expireTime+time();
        while(time()<$timeout){
            if(flock($fp,LOCK_EX)){
                //上锁成功释放
                return true;
            }
            usleep(20);
        }

        if(!$autoReleaseLock){
            static::throwException($key); //抛出异常
        }

        return true;

    }
    public static function unlock($key){

        if(static::$files[$key]){
            flock(static::$files[$key],LOCK_UN);
            fclose(static::$files[$key]);
            $filepath=static::getFilePath($key); //移除文件
            GDelFile($filepath);
        }
    }

}




