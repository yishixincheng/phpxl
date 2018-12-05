<?php

return [
        'redisHost'=>"localhost", //redis主机
        'redisPort'=>6379,         //redis监听端口
        'redisPre'=>"",            //前缀
        'redisPconnect'=>false,    //是否是长链接
        'beatSec'=>0.2,            //2秒检测一次有无新的队列
        'maxProcessesNum'=>10,     //最多的进程数
        'maxQuequeTaskNum'=>1000
];