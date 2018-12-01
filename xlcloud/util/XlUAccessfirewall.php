<?php
namespace xl\util;

/**
 * Class XlUAccessfirewall
 * @package xl\util
 * 访问防火墙
 */

class XlUAccessfirewall{

    protected $cache=null;
    protected $open=false;
    protected $logger=null;
    protected $whites=[];
    protected $blacks=[];
    protected $qc_unitsec=1;
    protected $qc_maxnumbyunitiproute=5;
    protected $blacks_autocalculate=false;
    protected $blacks_holdday=1;
    protected $blacks_frequentthresholdnum=86400;
    protected $request=[];
    protected $ip=null;
    protected $hostid=null;


    public function run(){

        if(!$this->open){
            return null;
        }
        if(!$this->ip){
            return null;
        }

        if($this->whites&&in_array($this->ip,$this->whites)){
            //白名单返回
            return null;
        }
        if($this->blacks&&in_array($this->ip,$this->blacks)){
            throw new XlUException("您的ip已经加入黑名单",403); //禁止访问
        }

        $this->hostid=$this->hostid?:$this->ip;

        $path=$this->request['path'];
        $path[]=$this->hostid;

        $pathstr=implode($path,"_");
        $accessQueue=$this->cache->get("__xl_accessqueue");
        $accessQueue=$accessQueue?:[];

        if(isset($accessQueue[$pathstr])){
            $accessQueue[$pathstr]++;
        }else{
            $accessQueue[$pathstr]=1;
        }

        if($this->blacks_autocalculate){
            $accessQueueBlackList=$this->cache->get("__xl_accessqueue_blacklist");
            if(!$accessQueueBlackList){
                $accessQueueBlackList=['day'=>SYS_CURR_DAY_INT,'blacklist'=>[],'blacks'=>[]];
            }else{
                if($accessQueueBlackList['day']-SYS_CURR_DAY_INT>=$this->blacks_holdday*86400){
                    $accessQueueBlackList=['day'=>SYS_CURR_DAY_INT,'blacklist'=>[],'blacks'=>[]];
                }
            }
            if(in_array($this->hostid,$accessQueueBlackList['blacks'])){
                throw new XlUException("刷新频繁,你的主机已被系统加入黑名单",403); //禁止访问
            }
        }
        if($accessQueue[$pathstr]>$this->qc_maxnumbyunitiproute){
            if($this->logger){
                $this->logger->write("note:".$pathstr." 访问时间".$this->qc_unitsec."超过".$this->qc_maxnumbyunitiproute."次".PHP_EOL,true);
            }
            if($this->blacks_autocalculate){

                if(isset($accessQueueBlackList['blacklist'][$this->hostid])){
                    $accessQueueBlackList['blacklist'][$this->hostid]++;
                }else{
                    $accessQueueBlackList['blacklist'][$this->hostid]=1; //出现次数
                }
                if($accessQueueBlackList['blacklist'][$this->hostid]>$this->blacks_frequentthresholdnum){
                    //主机超过多少次，加入黑名单
                    $accessQueueBlackList['blacks'][]=$this->hostid;
                }

                $this->cache->set("__xl_accessqueue_blacklist",$accessQueueBlackList);

            }
            //throw new XlUException("刷新频繁",403);
        }

        $this->cache->set("__xl_accessqueue",$accessQueue,$this->qc_unitsec); //设置缓存


    }




}