<?php

namespace Xl_MQ;

use Xl_MQ\Lib\Queue;

/**
 * Class MQClient
 * @package Xl_MQ
 * 消息队列客户端，将消息队列放入
 */
class MQClient{


    /**
     * @param $queuename
     * @param $msgstruct
     * 将消息添加到消息队列中
     *
     * msgstruct=[
     *     'task'=>[''],
     * ]
     *
     */
    public static function addToQueue($msgstruct,$queuename=null){


        $queue=new Queue();

        $queue->setQueueName($queuename); //要加入的队列名称
        $queue->setMsgStruct($msgstruct); //消息结构体

        return Queue::addToList($queue);


    }

    /**
     * @param null $queuename
     * @return bool
     * 清空队列
     */

    public static function clearQueue($queuename=null){

        return Queue::clearList($queuename);

    }




}