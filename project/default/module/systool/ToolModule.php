<?php

namespace lftsoft\module;
use xl\base\XlModuleBase;

import("@xl.vendor.autoload");

/**
 * Class ToolModule
 * @package lftshuju\module
 * @path("/systool")
 */
class ToolModule extends XlModuleBase
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

            \Xl_MQ\MQMonitor::delLock($queuename);

            GW("OK");

        }else{
            GW("fail");
        }

    }

}