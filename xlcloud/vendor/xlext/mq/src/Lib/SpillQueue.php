<?php

namespace Xl_MQ\Lib;

use Xl_MQ\MQConfig;

class SpillQueue{


    public static function add($queuename,$msgstructstr){

        $filepath=__DIR__.DIRECTORY_SEPARATOR."spillqueue.ini"; //队列缓存文件

        $file=fopen($filepath,"a");

        flock($file,LOCK_EX); //上锁

        @fwrite($file,time().":".$queuename."=".$msgstructstr."\n"); //追加的方式写入文件

        flock($file,LOCK_UN); //释放锁

        fclose($file);

    }

    public static function fetchlines($linenum=null){

        if($linenum==null){
            $linenum=MQConfig::getMaxQuequeTaskNum(); //每次获取的行数
        }

        $filepath=__DIR__.DIRECTORY_SEPARATOR."spillqueue.ini"; //队列缓存文件

        $file=fopen($filepath,"rw");

        flock($file,LOCK_SH); //独占锁

        $result=[];
        $remain="";

        $count=0;
        while(feof($file)){

            $line=fgets($file);
            if($line) {
                if ($count < $linenum) {

                    $pos = strpos($line, "=");
                    if ($pos) {
                        $f1 = substr($line, 0, $pos);
                        $f2 = substr($line, $pos + 1);

                        if ($f1) {

                            if ($pos1 = strpos($f1, ":")) {
                                $time = substr($f1, 0, $pos1);
                                $queuename = substr($f1, $pos1 + 1);
                                $result[] = ['time' => $time, 'queuename' => $queuename, 'msgStruct' => json_decode($f2, "true")]; //获取参数
                                $count++;
                            }
                            unset($f1);
                            unset($pos1);
                            unset($pos);
                        }
                    }

                } else {

                    $remain.=$line."\n";

                }
            }
            unset($line);
        }

        flock($file,LOCK_UN); //独占锁

        flock($file,LOCK_EX);

        @fwrite($file,$remain);

        flock($file,LOCK_UN);

        fclose($file);

        return $result;

    }


}