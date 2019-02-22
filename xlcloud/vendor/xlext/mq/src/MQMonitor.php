<?php

namespace Xl_MQ;
use Xl_MQ\Lib\Queue;

class MQMonitor{


    /**
     * 显示redis内存数据
     */
    public static function showHtml($unlockurl=''){

        $queuenamelist=Queue::getQueueNameList()?:[];

        $html="<!DOCTYPE HTML>
                <html>
               <head>
                   <meta charset=\"utf-8\">
                   <title></title>
                   <style type='text/css'>
                      html,body,ul,li,dl,dt,dd{
                         margin: 0;
                         padding: 0;
                         font-size: 14px;
                         list-style: none;
                      }
                      .wrap{
                           width: 650px;
                           margin: auto;
                           padding-top: 50px;
                      }
                      dl{
                          width: 100%;
                      }
                      dl ul{
                          height: 30px;
                          line-height: 30px;
                          clear: both;
                      }
                      dl ul li{
                         float: left;
                         text-align: center;
                         margin: -1px 0 0 -1px;
                         border: 1px solid #999;
                      }
                      dl dt{
                         font-weight: bold;
                      }
                      .c0{
                          width: 80px;
                      }
                      .c1{
                         width: 120px;
                      }
                      .c2{
                         width: 50px;
                      }
                      .c3{
                         width: 150px;
                      }
                      .c4{
                         width: 100px;
                      }
                      .c5{
                         width: 100px;
                      }
                   </style>
               </head>
               <body>
               
               <div class='wrap'>
               <dl>
                  <dt>
                      <ul><li class='c0'>序号</li><li class='c1'>队列名</li><li class='c2'>性质</li><li class='c3'>锁定</li><li class='c4'>任务剩余个数</li><li class='c5'>操作</li></ul>
                  </dt>
                        
               ";

         $qNPSet=MQConfig::getQNPSet()?:[];  //赋值

         $i=0;
         foreach($queuenamelist as $quname){

             $i++;
             $html.="<dd><ul><li class='c0'>".$i."</li>";
             $html.="<li class='c1'>".$quname."</li>";
             $typename='随机';
             if(isset($qNPSet[$quname])&&is_array($qNPSet[$quname])){
                 if($qNPSet[$quname]['type']==1){
                     $typename="顺序";
                 }
             }
             $html.="<li class='c2'>".$typename."</li>";
             $expireTime=Queue::isLockedByQueue($quname);
             $html.="<li class='c3'>";
             if($expireTime){
                 $html.="锁定[".date("ymd H:i:s",$expireTime)."]";
             }else{
                 $html.="否";
             }
             $html.="</li>";

             $html.="<li class='c4'>".Queue::getQueueSize($quname)."</li>";

             $html.="<li class='c5'><a href='".$unlockurl."?queuename=".$quname."'>释放锁</a></li>";


             $html.="</ul></dd>";

         }

         $html.="</dl></div></body></html>";


        return $html;

    }

    /**
     * 显示redis内存数据
     */
    public static function showTable($unlockurl=''){

        $queuenamelist=Queue::getQueueNameList()?:[];

        $html="
                   <style type='text/css'>
                      .xlext_mq_wrap{
                           width: 650px;
                           margin: auto;
                           padding-top: 50px;
                      }
                      .xlext_mq_wrap dl{
                          width: 100%;
                      }
                      .xlext_mq_wrap dl ul{
                          height: 30px;
                          line-height: 30px;
                          clear: both;
                      }
                      .xlext_mq_wrap dl ul li{
                         float: left;
                         text-align: center;
                         margin: -1px 0 0 -1px;
                         border: 1px solid #999;
                      }
                      .xlext_mq_wrap dl dt{
                         font-weight: bold;
                      }
                      .xlext_mq_wrap .c0{
                          width: 80px;
                      }
                      .xlext_mq_wrap .c1{
                         width: 120px;
                      }
                      .xlext_mq_wrap .c2{
                         width: 50px;
                      }
                      .xlext_mq_wrap .c3{
                         width: 150px;
                      }
                      .xlext_mq_wrap .c4{
                         width: 100px;
                      }
                      .xlext_mq_wrap .c5{
                         width: 100px;
                      }
                   </style>
               
               <div class='xlext_mq_wrap'>
               <dl>
                  <dt>
                      <ul><li class='c0'>序号</li><li class='c1'>队列名</li><li class='c2'>性质</li><li class='c3'>锁定</li><li class='c4'>任务剩余个数</li><li class='c5'>操作</li></ul>
                  </dt>
                        
               ";

        $qNPSet=MQConfig::getQNPSet()?:[];  //赋值

        $i=0;
        foreach($queuenamelist as $quname){

            $i++;
            $html.="<dd><ul><li class='c0'>".$i."</li>";
            $html.="<li class='c1'>".$quname."</li>";
            $typename='随机';
            if(isset($qNPSet[$quname])&&is_array($qNPSet[$quname])){
                if($qNPSet[$quname]['type']==1){
                    $typename="顺序";
                }
            }
            $html.="<li class='c2'>".$typename."</li>";
            $expireTime=Queue::isLockedByQueue($quname);
            $html.="<li class='c3'>";
            if($expireTime){
                $html.="锁定[".date("ymd H:i:s",$expireTime)."]";
            }else{
                $html.="否";
            }
            $html.="</li>";

            $html.="<li class='c4'>".Queue::getQueueSize($quname)."</li>";

            $html.="<li class='c5'><a href='".$unlockurl."?queuename=".$quname."'>释放锁</a></li>";


            $html.="</ul></dd>";

        }

        $html.="</dl></div>";


        return $html;

    }

    /**
     * @param $queuename
     * 删除锁
     */
    public static function delLock($queuename){

        Queue::unLockByQueue($queuename); //释放锁

    }

}
