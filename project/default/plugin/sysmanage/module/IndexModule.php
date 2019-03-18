<?php

namespace sysmanage\module;

import("@xl.vendor.autoload");

/**
 * Class IndexModule
 * @package sysmanage\module
 * @path("/sysmanage")
 */
class IndexModule extends Base{

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 初始化导航
     */
    private function initNav(){


        $navs=[
            ['name'=>'基本设置','page'=>''],
            ['name'=>'清除缓存','page'=>'cleancache'],
            ['name'=>'消息队列','page'=>'mqmonitor'],
            ['name'=>'插件管理','page'=>'pluginmanage'],
            ['name'=>'在线升级','page'=>'upgrade']
        ];

        foreach ($navs as &$nav){

            $nav['url']="/sysmanage/".$nav['page'];
        }

        $this->setAttach("navs",$navs);

    }

    /**
     * @path({"","GET"})
     */
    public function index(){

        $this->setHtmlTitle("基本设置");
        $this->initNav();

        $this->setAttach("currnav","");

        $this->Display("index");

    }

    /**
     * 清除缓存
     * @path({"cleancache","GET"})
     */
    public function cleanCachePage(){

        $this->setHtmlTitle("清除缓存");
        $this->initNav();

        $this->setAttach("currnav","cleancache");

        $this->Display("cleancache");


    }

    /**
     * 消息队列
     * @path({"mqmonitor","GET"})
     */
    public function mqMonitorPage(){

        $this->setHtmlTitle("消息队列");
        $this->initNav();

        $this->setAttach("currnav","mqmonitor");

        $config=config("mq");
        if(empty($config['redisPre'])) {
            $config['redisPre'] = md5(DOC_ROOT);
        }

        \Xl_MQ\MQConfig::setup($config);

        $html=\Xl_MQ\MQMonitor::showTable("/sysmanage/mqunlock");

        $html.="<div style='clear: both; width: 650px;margin: auto;margin-top: 20px;'>当前时间：".date("Y-m-d H:i:s")."</div>";

        $this->setAttach("htmlcontent",$html);


        $this->Display("mqmonitor");


    }

    /**
     * 插件管理
     * @path({"pluginmanage","GET"})
     */
    public function pluginManagePage(){

        $this->setHtmlTitle("插件管理");
        $this->initNav();
        $this->setAttach("currnav","pluginmanage");

        try{
            $conf=config("upgrade");
            $softwaredata=iapi("upgrade.GetVersionData",null);
            $softwareplugins=rpc("softupgrade.GetPluginListRequest",['softtype'=>$conf['software']],["rsp_urls"=>$conf['softurl'],
                "appkey"=>"shengguo",
                "appsecret"=>"xinxikeji"]);
            $softwareplugins=getApiData($softwareplugins);

            if($softwareplugins['status']=="fail"){
                unset($softwareplugins);
            }

        }catch (\Exception $e){

        }

        $plugins=config("plugins");

        if(!is_array($plugins)){
            $plugins=[];
        }

        if(isset($softwareplugins)&&$softwareplugins){
            foreach ($softwareplugins as $item){
                if(isset($plugins[$item['plugintype']])){
                    $plugins[$item['plugintype']]['des']=$item['des'];
                    $plugins[$item['plugintype']]['softversion']=$item['softversion'];
                    if($plugins[$item['plugintype']]['version']!=$item['version']){
                        $plugins[$item['plugintype']]['lastversion']=$item['version'];
                    }
                    if(isset($softwaredata)&&$softwaredata){
                        $plugins[$item['plugintype']]['currsoftversion']=$softwaredata['software_version'];
                    }
                    $plugins[$item['plugintype']]['downloadurl']=$item['downloadurl'];

                }else{
                    $plugins[$item['plugintype']]=[
                        'name'=>$item['name'],
                        'lastversion'=>$item['version'],
                        'softversion'=>$item['softversion'],
                        'des'=>$item['des'],
                        'newplugin'=>1,
                        'version'=>'-',
                        'downloadurl'=>$item['downloadurl']
                    ];
                    if(isset($softwaredata)&&$softwaredata){
                        $plugins[$item['plugintype']]['currsoftversion']=$softwaredata['software_version'];
                    }
                }
            }
        }

        $this->setAttach("plugins",$plugins);

        $this->Display("pluginmanage");

    }

    /**
     * 在线升级
     * @path({"upgrade","GET"})
     */
    public function upgradePage(){

        $this->setHtmlTitle("在线升级");
        $this->initNav();

        $this->setAttach("currnav","upgrade");
        try{
            $softwaredata=iapi("upgrade.GetVersionData",null);
        }catch (\Exception $e){
            $softwaredata=config("upgrade");
        }
        $versioninfo="";
        if($softwaredata){
            $versioninfo=$softwaredata['software_name'].$softwaredata['software_version'];
        }
        $this->setAttach("versioninfo",$versioninfo);

        $this->Display("upgrade");

    }


}