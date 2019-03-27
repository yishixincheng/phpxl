<?php

namespace xl\classs;

use xl\base\XlClassBase;
use xl\base\XlException;

/**
 * Class GlobalconfClass
 * @package xl\classs
 * 操作全局配置文件
 */

class GlobalconfClass extends XlClassBase{

    private $_confcache=null;
    private $_dbhostcache=null;
    private $_schemeHosts=null;
    private $_hostcache=null;

    public function __construct()
    {
        parent::__construct();
    }

    public function getConfArray(){

        if($this->_confcache!==null){
            return $this->_confcache;
        }
        $path=CONFIG_PATH.'globalconf.json';

        if(!file_exists($path)){
            return [];
        }
        $_jsonObj=sysclass("json");

        $this->_confcache=$_jsonObj->read($path,false);

        if(!$this->_confcache){
            $this->_confcache=[];
        }

        return $this->_confcache;

    }

    public function getProperty($name){
        $gconf=$this->getConfArray();
        return $gconf[$name]?:null;
    }

    public function getHosts(){

        $gconf=$this->getConfArray();

        return $gconf['hosts']?:[];

    }

    public function getSchemeHosts(){

        $gconf=$this->getConfArray();

        $scheme=$gconf['scheme'];

        if(empty($scheme)){
            return [];
        }

        $schemehosts=[];

        foreach ($scheme as $dbkey=>$node){

            $schemehosts[$dbkey]=['type'=>'database',
                                  'name'=>$node['name'],
                                  'masterhost'=>$this->getDbHostConfByHostName($node['masterhost']),
                                  'slavehost'=>$this->getDbHostConfByHostName($node['slavehost']?:$node['masterhost'])];
            if(isset($node['branch'])&&is_array($node['branch'])&&$node['branch']){

                foreach ($node['branch'] as $b_dbkey=>$b_node){

                    $schemehosts[$dbkey."_".$b_dbkey]=[
                         'type'=>'database',
                         'name'=>$b_node['name'],
                         'masterhost'=>$this->getDbHostConfByHostName($b_node['masterhost']),
                         'slavehost'=>$this->getDbHostConfByHostName($b_node['slavehost']?:$b_node['masterhost']),
                         'pkey'=>$dbkey
                    ];

                    if($b_node['tables']&&is_array($b_node['tables'])){
                        $this->_attachTablesHosts($schemehosts,$b_node['tables'],$dbkey."_".$b_dbkey);
                    }

                }

            }
            if(isset($node['tables'])&&is_array($node['tables'])&&$node['tables']){

                $this->_attachTablesHosts($schemehosts,$node['tables'],$dbkey);

            }

        }

        return $schemehosts;

    }

    //解析表所在服务器
    private function _attachTablesHosts(&$schemehosts,$tables,$pkey){


        foreach($tables as $tn=>$node){

            $key=$pkey.":".$tn;

            $tmp=[
                'type'=>'table',
                'name'=>$node['name']?:'',
                'pkey'=>$pkey
            ];
            if($node['masterhost']){
                $tmp['masterhost']=$this->getDbHostConfByHostName($node['masterhost']);
                $tmp['slavehost']=$this->getDbHostConfByHostName($node['slavehost']?:$node['masterhost']);
            }
            if($node['sharding']&&is_array($node['sharding'])){
                $sharding=[];
                foreach ($node['sharding'] as $skey=>$s_node){

                    $sharding[$skey]=[
                        'type'=>'sharding',
                        'masterhost'=>$this->getDbHostConfByHostName($s_node['masterhost']),
                        'slavehost'=>$this->getDbHostConfByHostName($s_node['slavehost']?:$s_node['masterhost'])
                    ];

                }
                $tmp['sharding']=$sharding;
            }

            $schemehosts[$key]=$tmp;

        }

    }

    /**
     * @param $hostname
     * 根据数据库主机名获取主机配置
     */
    private function getDbHostConfByHostName($hostname){

        if(!$hostname){
            return null;
        }
        if(!is_string($hostname)){
            if(is_array($hostname)){
                return $hostname;
            }else{
                return null;
            }
        }
        if(preg_match("/,/",$hostname)){
            //多个主机，随机获取一个
            $hostarr=explode(',',$hostname);
            $key=array_rand($hostarr,1);
            $hostname=$hostarr[$key];
        }
        if(!is_array($this->_dbhostcache)){
            $this->_dbhostcache=[];
        }

        if(!empty($this->_dbhostcache[$hostname])){
            return $this->_dbhostcache[$hostname];
        }

        $gconf=$this->getConfArray();

        $dbhosts=$gconf['dbhosts'];

        if(!is_array($dbhosts)){
            return null;
        }

        $dbhost=$dbhosts[$hostname];

        if(is_array($dbhost)){

            $conf=config("database")?:[];
            if(!isset($dbhost['driver'])){
                $dbhost['driver']=$conf['driver']?:"mysql";
            }else if($dbhost['driver']=="pdo"){
                $dbhost['driver']="mysql";
            }
            if(!isset($dbhost['tablepre'])){
                $dbhost['tablepre']=$conf['tablepre']?:'';
            }
            if(!isset($dbhost['engine'])){
                $dbhost['engine']=$conf['engine']?:'InnoDB';
            }
            if(!isset($dbhost['type'])){
                $dbhost['type']=$conf['type']?:'pdo';
            }
            if(!isset($dbhost['pconnect'])){
                $dbhost['pconnect']=$conf['pconnect']?:0;
            }
            if(!isset($dbhost['autoconnect'])){
                $dbhost['autoconnect']=$conf['autoconnect']?:0;
            }
            if(!isset($dbhost['charset'])){
                $dbhost['charset']=$conf['charset']?:'utf8';
            }
            if(!isset($dbhost['debug'])){
                $dbhost['debug']=$conf['debug']?:true;
            }

        }

        return $dbhost;

    }

    private function _parseHostDsn($hostdsn){

        $promisekeys=['host','port','username','password'];
        if(!is_array($hostdsn)){
            $hostdsnArr=explode(';',$hostdsn);
            $hostdsn=[];
            foreach ($hostdsnArr as $hostItem){
                $hostItemArr=explode('=',$hostItem);
                if($hostItemArr&&count($hostItemArr)==2){
                    $k=trim($hostItemArr[0]);
                    $v=trim($hostItemArr[1]);
                    if(in_array($k,$promisekeys)){
                        $hostdsn[$k]=$v;
                    }
                }
            }

        }else{
            foreach ($hostdsn as $k=>$v){
                if(!in_array($k,$promisekeys)){
                    unset($hostdsn[$k]);
                }
            }
        }
        $conf=config("database")?:[];
        if(!isset($hostdsn['driver'])){
            $hostdsn['driver']=$conf['driver']?:"mysql";
        }else if($hostdsn['driver']=="pdo"){
            $hostdsn['driver']="mysql";
        }
        if(!isset($hostdsn['tablepre'])){
            $hostdsn['tablepre']=$conf['tablepre']?:'';
        }
        if(!isset($hostdsn['engine'])){
            $hostdsn['engine']=$conf['engine']?:'InnoDB';
        }
        if(!isset($hostdsn['type'])){
            $hostdsn['type']=$conf['type']?:'pdo';
        }
        if(!isset($hostdsn['pconnect'])){
            $hostdsn['pconnect']=$conf['pconnect']?:0;
        }
        if(!isset($hostdsn['autoconnect'])){
            $hostdsn['autoconnect']=$conf['autoconnect']?:0;
        }
        if(!isset($hostdsn['charset'])){
            $hostdsn['charset']=$conf['charset']?:'utf8';
        }
        if(!isset($hostdsn['debug'])){
            $hostdsn['debug']=$conf['debug']?:true;
        }

        return $hostdsn;

    }



    /**
     * 根据实例数据库名，实例表名和切片值获取主机配置
     */
    public function getDbHostConf($database=null,$tablename=null,$sharding=null){

        if(empty($database)){
            //直接从配置默认的配置文件中读取
            $conf=config("database");
            if(empty($conf)){
                return null;
            }
            $return=['type'=>'database','name'=>'默认库','default'=>true,'database'=>$conf['database']]; //设置默认库
            $return['masterhost']=$this->_parseHostDsn($conf['master']);
            if($conf['slaves']){
                $slave=$conf['slaves'][array_rand($conf['slaves'],1)]; //随机命中
                $return['slavehost']=$this->_parseHostDsn($slave);
            }
            $return['slavehost']=$return['slavehost']?:$return['masterhost'];

            return $return;

        }
        if($this->_schemeHosts==null){
            $this->_schemeHosts=$this->getSchemeHosts();
        }

        if(!$tablename){
            //表名不存在
            return $this->_schemeHosts[$database]?:(function() use($database){
                if(preg_match("/(.+)_(.+)/",$database,$mch)){
                    return $mch[1]?$this->getDbHostConf($mch[1],null,null):null;
                }else{
                    return $this->getDbHostConf(); //获取默认的
                }
            })();
        }
        $hostnode=$this->_schemeHosts[$database.":".$tablename]??null;
        if(!$hostnode){
            return $this->_schemeHosts[$database]?:(function() use($database){
                if(preg_match("/(.+)_(.+)/",$database,$mch)){
                    return $mch[1]?$this->getDbHostConf($mch[1],null,null):null;
                }else{
                    return $this->getDbHostConf(); //获取默认的
                }
            })();
        }
        if($sharding){

            $sharding=intval($sharding);
            $shardinghost=$hostnode['sharding']?:[];
            foreach ($shardinghost as $range=>$node){
                $rangeArr=explode("_",$range);
                $minR=intval($rangeArr[0]);
                $maxR=intval($rangeArr[1]);
                if(!$maxR){
                    $maxR=PHP_INT_MAX;
                }
                if($sharding>=$minR&&$sharding<=$maxR){
                    if($node['masterhost']){
                        return $node;
                    }
                    break;
                }
            }
        }
        if(!$hostnode['masterhost']){
            return $this->_schemeHosts[$database]?:(function() use($database){
                if(preg_match("/(.+)_(.+)/",$database,$mch)){
                    return $mch[1]?$this->getDbHostConf($mch[1],null,null):null;
                }else{
                    return $this->getDbHostConf(); //获取默认的
                }
            })(); //表没有设置，则返回对应的数据库所在主机
        }

        return $hostnode;

    }

    /**
     * @param $hostname
     * 根据主机名获取主机配置
     */
    public function getHostConfByHostName($hostname){

        if(!$hostname){
            return null;
        }
        if(!is_string($hostname)){
            if(is_array($hostname)){
                return $hostname;
            }else{
                return null;
            }
        }
        if(preg_match("/,/",$hostname)){
            //多个主机，随机获取一个
            $hostarr=explode(',',$hostname);
            $key=array_rand($hostarr,1);
            $hostname=$hostarr[$key];
        }
        if(!is_array($this->_hostcache)){
            $this->_hostcache=[];
        }
        if($this->_hostcache[$hostname]){
            return $this->_hostcache[$hostname];
        }
        $gconf=$this->getConfArray();

        $hosts=$gconf['hosts'];

        if(!is_array($hosts)){
            return null;
        }

        return $hosts[$hostname];

    }
    public function getHostUrlByHostName($hostname){

        $host=$this->getHostConfByHostName($hostname);

        if(!$host){
            return null;
        }

        $url=$host['url']?:$host['host'];

        if(!preg_match("/[a-zA-Z]+:\/\/.+/",$url)){
            $url="http://".$url;
        }
        $url=rtrim($url,"/");

        return $url;

    }



}


