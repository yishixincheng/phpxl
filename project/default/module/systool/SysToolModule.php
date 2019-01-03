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


        $config=config("mq");
        if(empty($config['redisPre'])) {
            $config['redisPre'] = md5(DOC_ROOT);
        }

        \Xl_MQ\MQConfig::setup($config);

        $html=\Xl_MQ\MQMonitor::showHtml("/systool/mqunlock");


        GW($html);

    }

    /**
     * @path({"/mqunlock","GET"})
     */
    public function mqDelLock($getParam){

        $queuename=$getParam['queuename'];

        $username=$getParam['username'];
        $password=$getParam['password'];

        if(empty($username)||empty($password)){
            GW("抱歉，你没有权限操作！");
            exit;
        }

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