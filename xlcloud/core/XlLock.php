<?php

namespace xl\core;

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
            return "\\xl\\core\\ileLock";
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

class RedisLock{

    public static function lock($key,$expireTime=0,$autoReleaseLock=false){

        $cls = sysclass("cachefactory", 0);
        $redis = $cls::priority(['redis'])->getRedis();

        while($redis->setnx($key,time()+$expireTime)==0){

            if(time()>$redis->get($key)&&time()>$redis->getSet($key,time()+$expireTime)){

                if($autoReleaseLock){
                    //超时了
                    break;
                }
                return false; //未获得锁
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

    public static function lock($key,$expireTime=0,$autoReleaseLock=false){

        $cls = sysclass("cachefactory", 0);
        $memcache = $cls::priority(['memcache'])->getMemcache();

        $timeout=$expireTime+time();

        while (time() <= $timeout && false == $memcache->add($key,time()+$expireTime, false))
        {
            if(!$autoReleaseLock&&time()>=$memcache->get($key)){
                return false;
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

    public static function lock($key,$expireTime=0,$autoReleaseLock=false){





    }
    public static function unlock($key){

    }

}




