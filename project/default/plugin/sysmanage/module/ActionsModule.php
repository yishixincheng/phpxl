<?php

namespace sysmanage\module;

use xl\XlLead;

import("@xl.vendor.autoload");

/**
 * Class ActionsModule
 * @package sysmanage\module
 * @path("/sysmanage")
 */
class ActionsModule extends Base
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @path({"pluginmanage/opencloseplugin","POST"})
     */
    public function openClosePlugin($postParam){

        $plugintype=$postParam['plugintype'];
        $open=$postParam['open'];
        if(empty($plugintype)){
            AjaxPrint($this->ErrorInf("参数缺失！"));
            return;
        }
        $plugins=config("plugins");
        if(empty($plugins)){
            AjaxPrint($this->ErrorInf("插件配置文件不存在！"));
            return;
        }
        if(!isset($plugins[$plugintype])){
            AjaxPrint($this->ErrorInf("插件不存在！"));
            return;
        }
        if($open==1){
            config("plugins/".$plugintype."/isclose",0,true);
        }else{
            config("plugins/".$plugintype."/isclose",1,true);
        }

        AjaxPrint($this->SuccInf("操作成功！"));

    }

    /**
     * @path({"clearcache","POST"})
     */
    public function clearCache($postParam){

        $type=$postParam['type'];
        if($type==1){
            //清理路由缓存
            $this->_clearRouterCache();

        }else if($type==2){
            //清理模版缓存
            $this->_clearTplCache();
        }

    }
    private function _clearRouterCache(){


       $keys=XlLead::routerCacheGet("@xl_router_keys");
       if($keys&&is_array($keys)){
           foreach ($keys as $key){
               XlLead::routerCacheDel($key);
           }
       }

        AjaxPrint($this->SuccInf("删除成功！"));


    }
    private function _clearTplCache(){


        //清除模版缓存
        GDelFile(COMPILE_PATH);

        AjaxPrint($this->SuccInf("删除成功！"));

    }

    /**
     * @path({"mqunlock","GET"})
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

            AjaxPrint($this->SuccInf("解锁成功！"));

        }else{
            AjaxPrint($this->SuccInf("解锁失败！"));
        }

    }

    /**
     * @path({"setbasedata","POST"})
     */
    public function setBaseData($postParam){

        $data=fetchfromkeys($postParam,"seo_webname,site_url,site_domain,seo_keyword,seo_dis,closesite,closetip");

        if(is_array($data)){

            foreach ($data as $k=>$v){
                config("system/".$k,$v);
            }
            config("system/modifytime",SYS_TIME,true);
        }

        AjaxPrint($this->SuccInf("设置成功！"));

    }

    /**
     * @path({"getbasedata","POST"})
     */
    public function getBaseData($postParam){

        $data=config("system");

        if(!$data){
            AjaxPrint([]);
            return;
        }

        $data=fetchfromkeys($data,"seo_webname,site_url,site_domain,seo_keyword,seo_dis,closesite,closetip");

        AjaxPrint($data);


    }


}