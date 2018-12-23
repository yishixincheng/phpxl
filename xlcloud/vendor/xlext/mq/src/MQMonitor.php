<?php

namespace Xl_MQ;
use Xl_MQ\Lib\Queue;

class MQMonitor{


    /**
     * 显示redis内存数据
     */
    public static function showHtml($unlockurl=''){

         $queuenamelist=Queue::getQueueNameList()?:[];

         $html='<ul>';

         foreach($queuenamelist as $quname){

             $html.="<li><div><span>队列名：</span><span>".$quname."</span></div>";

             $ctrlparam=Queue::getQueueNameControlParam($quname);

             if(isset($ctrlparam)&&is_array($ctrlparam)){

                 foreach ($ctrlparam as $k=>$v){

                     if($k=="lasttime"){

                         $html.="<div><span>最后执行时间：</span><span>".date("Y-m-d H:i:s",$v)."</span></div>";

                     }else if($k=="lock"){

                         $html.="<div><span>是否上锁：</span><span>".($v==1?'是':'否')."</span>";
                         if($v==1){
                             $html.="<a href=\"".$unlockurl."?queuename=".$quname."\">释放锁</a>";
                         }
                         $html.="</div>";
                     }

                 }
             }

             $html.="</li>";


         }

         $html.="</ul>";


        return $html;

    }

    /**
     * @param $queuename
     * 删除锁
     */
    public static function delLock($queuename){

        Queue::setQueueNameControlParam($queuename,'lock',null); //释放锁

    }

}
