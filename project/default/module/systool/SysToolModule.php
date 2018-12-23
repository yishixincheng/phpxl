<?php

namespace lftsoft\module\systool;
use xl\base\XlModuleBase;

import("@xl.vendor.autoload");

/**
 * Class SysToolModule
 * @package lftsoft\module
 * @path("/systool")
 */
class SysToolModule extends XlModuleBase
{


    /**
     * @path({"mqmonitor","GET"})
     */
    public function mqMonitor(){

        $html="<!DOCTYPE HTML>
                <html>
               <head>
               <meta charset=\"utf-8\">
                   <title></title>
               </head>
               <body>
               ";

        $config=config("mq");
        if(empty($config['redisPre'])) {
            $config['redisPre'] = md5(DOC_ROOT);
        }

        \Xl_MQ\MQConfig::setup($config);

        $html.=\Xl_MQ\MQMonitor::showHtml("/systool/mqunlock");

        $html.="</body></html>";

        GW($html);

    }

    /**
     * @path({"/systool/mqunlock","GET"})
     */
    public function mqDelLock($getParam){

        $queuename=$getParam['queuename'];
        if($queuename){

            $config=config("mq");
            if(empty($config['redisPre'])) {
                $config['redisPre'] = md5(DOC_ROOT);
            }

            \Xl_MQ\MQConfig::setup($config);

            \Xl_MQ\MQMonitor::delLock($queuename);

            GW("OK");

        }else{
            GW("fail");
        }

    }

}