<?php

namespace xl\base\db;
use xl\base\XlException;
use xl\base\XlMvcBase;

final class XlMysqlModelFactory extends XlMvcBase {

    const  CACHE_TYPE_GETONE=1;
    const  CACHE_TYPE_GETROWS=2;
    const  CACHE_TYPE_GETROWNUM=4;
    private $_model_path=MODEL_PATH;

    //数据库配置
    public static $db_repairnum=[]; //修复次数统计
    private $_workid=1;
    private $_dbconfig=null;
    private $_tablepre=null;
    private $_model=null;
    private $_logicdatabase=null; //逻辑库名
    private $_primarykeys=[]; //主键
    private $_primarykey_types=[]; //主键对应的类型
    private $_increments=[];        //主键是否自增
    private $_keys=[];         //索引
    private $_hashfield=null; //分表划分字段
    private $_hashkey_type=null; //hash数据类型
    private $_lastid=null;           //最后自增的id
    private $_tablename=null;
    private $_shardtablename=null;
    private $_abstablename=null; //当前全表名包括前缀
    private $_writedb=null;   //主数据库实例
    private $_readdb=null;
    private $_selfmotionconfiguration=false; //是否自动配置
    private $_dbhostconf=null;


    /**
     * @var 注入属性
     */
    private $_database=null;
    private $_logictablename=null; //逻辑表名
    private $_isneedcreate=null;
    private $_isautorepairstruct=null;
    private $_sharding=null;
    private $_merge=null;
    private $_merge_insert=null;
    private $_partition=null;
    private $_fields=null;
    private $_engine=null;
    private $_charset=null;
    private $_opencache=null;
    private $_cachetime=null;
    private $_cachetype=6; //默认读取getrows,getrownum缓存
    private $_cachepre=null;
    private $_openslowlog=false;
    private $_longquerytime=null;
    private $_slowlogfile=null; //日志下面的目录



    public function __construct($modelname,$config=null,$model,$model_name,$dbhostconf)
    {

        if($this->_Isplugin){
            $this->_model_path=PLUGIN_PATH.$this->_Ns.D_S."model".D_S;
        }
        /**
         * config置入，可以改变Model里默认设置的配置信息，说明如下
         *
         * 1.数组 ['database'=>'','tablename'=>'',workid=1],dataname如果是/开头，绝对数据库名，否则是配置文件的数据库+"_database"
         *           tablename如果是/开头，代表是绝对表名（不包括前缀），如果是不加，则是模型里设置的表名+"_tablename"
         * 2.字符串，@值，则调用model模型里，config(值)进行获取配置信息
         *
         * 3.字符串，/开头，绝对表名。其他则是相对（model）的表名，即，表名+"_tablename"
         *
         * 说明：
         * (数据库，表名设置必须是20字一下，数字字母下划线）超过则报错
         *
         */
        parent::__construct();

        $this->_model=$model; //必须存在
        $this->_dbhostconf=$dbhostconf;
        $this->_dbconfig=$this->_dbhostconf['masterhost'];

        $this->_hookDbEnv($config);
        //根据modelname生成model具体实例，如user.User,或者user,img(表名找到对应的model类)
        $this->_parseModelName($modelname,$config,$model_name);

    }

    private function _hookDbEnv($config=null){


        $dbenvfunc_param=null;
        if(is_array($config)&&isset($config['dbenvparam'])){
            $dbenvfunc_param=$config['dbenvparam'];
        }

        if(method_exists($this->_model,"dbenv")){
            $_dbenvconf=$this->_model->dbenv($dbenvfunc_param);

            if(empty($_dbenvconf)||!is_array($_dbenvconf)){
                throw new XlException("dbenv method return val is invalid;");
            }
            $this->_dbhostconf=$_dbenvconf;
            $this->_dbhostconf['default']=true;
            $this->_dbconfig=$_dbenvconf['masterhost'];

        }

    }

    /**
     * 重新获取对象，检测配置文件有没有改变
     */
    public function __invoke(){

        if(!$this->_selfmotionconfiguration){
            return;
        }
        $config=null;
        if(method_exists($this->_model,"config")){
            $this->_selfmotionconfiguration=true;
            $config=$this->_model->config(null);
            if(!$config){
                return;
            }
        }
        if($tablename=$config['tablename']){
            if(strpos($tablename,"/")===0){
                $this->_tablename=substr($tablename,1);
            }else{
                $this->_tablename=$this->_logictablename."_".$tablename;
            }
        }
        if($config['database']){
            if(strpos($config['database'],"/")===0){
                $this->_database=$config['database'];
            }else{
                $this->_database=$this->_logicdatabase."_".$config['database'];
            }
        }

    }
    /**
     * @param $modelname
     * @throws XlException
     * 解析绑定的Model
     */
    private function _parseModelName($modelname,$config=null,$model_name=null){

        //只支持2层目录
        $this->_tablepre=$this->_dbconfig['tablepre']?:''; //表前缀
        $this->_engine=$this->_dbconfig['engine']??"InnoDB";
        try {
            if (empty($this->_model->alias)) {
                $this->_tablename = $this->_logictablename = strtolower($model_name?:$modelname); //逻辑表名
            } else {
                $this->_tablename = $this->_logictablename = $this->_model->alias; //逻辑表名
            }
            if ($this->_model->database) {
                $this->_database =$this->_logicdatabase = $this->_model->database; //逻辑库名
            }else{
                $this->_database =$this->_logicdatabase = $this->_dbhostconf['default']?$this->_dbhostconf['database']:null;

                if(empty($this->_database)){
                    throw new XlException("默认数据库没有设置！");
                }
            }
            $this->_isneedcreate=$this->_model->isneedcreate??$this->_isneedcreate;
            $this->_isautorepairstruct=$this->_model->isautorepairstruct??$this->_isautorepairstruct;
            $this->_tablepre=$this->_model->tablepre??$this->_tablepre;
            $this->_opencache=$this->_model->opencache??$this->_opencache;
            $this->_cachetime=$this->_model->cachetime??$this->_cachetime;
            $this->_cachetype=$this->_model->cachetype??$this->_cachetype;
            $this->_cachepre=$this->_model->cachepre??$this->_cachepre;
            $this->_sharding=$this->_model->sharding??$this->_sharding;
            $this->_openslowlog=$this->_model->openslowlog??$this->_openslowlog;
            $this->_longquerytime=$this->_model->longquerytime??$this->_longquerytime;
            $this->_slowlogfile=$this->_model->slowlogfile??$this->_slowlogfile;
            $this->_partition=$this->_model->partition??$this->_partition;
            $this->_fields=$this->_model->fields??null;
            if($this->_sharding){
                //merge引擎基于分表基础上
                if(!isset($this->_model->merge)){
                    $this->_merge=false; //默认不启用
                }else{
                    $this->_merge = $this->_model->merge;
                }
                if($this->_merge){
                    if ($this->_model->merge_insert) {
                        $this->_merge_insert = $this->_model->merge_insert;
                    }
                    $this->_engine="MyISAM"; //强制
                }
            }
            if(empty($this->_fields)){
                throw new XlException("Table　".$this->_tablename." fields is not set");
            }

            $this->_engine=$this->_model->engine??$this->_engine;
            $this->_charset=$this->_model->charset??"utf8mb4";

            //parse _fields 获取主键
            $this->_primarykeys=[];
            $this->_primarykey_types=[];
            $this->_increments=[];
            $this->_keys=[];
            $this->_hashfield=null;
            $this->_hashkey_type=null;

            foreach($this->_fields as $key=>$v){

                if(!$v){
                    continue;
                }
                if(!empty($v['primarykey'])){
                    $this->_primarykeys[]=$key; //主键
                    $this->_primarykey_types[$key]=strtolower($v['type']?:"");
                    $this->_increments[$key]=$v['increment']??null;
                }
                if(!empty($v['key'])){
                    $this->_keys[]=$key;
                }
                if(!$this->_hashfield){
                    if(!empty($v['hash'])){
                        $this->_hashfield=$key;
                        $this->_hashkey_type=strtolower($v['type']??"");
                    }
                }

            }

            if($this->_sharding){

                if(!$this->_hashfield){
                    if($this->_primarykeys){
                        $this->_hashfield=$this->_primarykeys[0];//第一主键作为分表hashkey
                        $this->_hashkey_type=$this->_primarykey_types[$this->_hashfield];
                    }
                }
                if(!$this->_hashfield){
                    $this->_sharding=false; //不分区
                }

            }
            //解析配置参数
            $this->_parseConfig($config);
        }catch (\Exception $e){
            throw new XlException($e->getMessage()); //抛出异常
        }
    }
    private function _parseConfig($config=null){

        $configfunc_param=null;
        $dbenvfunc_param=null;
        if(is_string($config)&&$config){
            $configstr=trim($config);
            $config=[];
            if(strpos($configstr,"@")===0){
                $configfunc_param=substr($configstr,1);
            }else{
                $config['tablename']=$configstr;
            }
        }else{
            if(isset($config['configparam'])){
                $configfunc_param=$config['configparam'];
            }
        }

        $ishaveconfigfunc=method_exists($this->_model,"config");
        if(empty($config)&&!$ishaveconfigfunc){
            return null;
        }
        if($configfunc_param&&!$ishaveconfigfunc){
            throw new XlException(get_class($this->_model)." method config is not defined");
        }
        $config=$config?:[];
        if($ishaveconfigfunc){
            $autoconfig=$this->_model->config($configfunc_param);
            if($configfunc_param==null){
                $this->_selfmotionconfiguration=true;
            }
            if(!is_array($autoconfig)){
                throw new XlException(get_class($this->_model)." method config returntype is must array"); //返回值类型必须是数组
            }
            $config=array_merge($config,$autoconfig); //函数调用覆盖
        }
        if(empty($config)){
            return null;
        }
        if($tablename=$config['tablename']){
            if(strpos($tablename,"/")===0){
                $this->_tablename=substr($tablename,1);
            }else{
                $this->_tablename=$this->_logictablename."_".$tablename;
            }
        }
        if($config['database']){

            if(strpos($config['database'],"/")===0){
                $this->_database=$config['database'];
            }else{
                $this->_database=$this->_logicdatabase."_".$config['database'];
            }
        }
        $this->_workid=$config['workid']?:$this->_workid;//不同的主机对应workid不一样

    }
    /**
     * @return null
     * 获得绑定的model对象
     */

    public function getModelObj(){

        return $this->_model;

    }

    private function _table($table='',$hashindex=null){

        $this->_shardtablename=($table?:$this->_tablename).($hashindex?$hashindex:'');
        $this->_abstablename=$this->_tablepre.$this->_shardtablename;
        return $this->_abstablename;

    }

    private function _getTable($parseval=null){

        //智能获取表名

        if(empty($parseval)||!$this->_sharding){

            return $this->_table();

        }
        //分片自动计算hash
        $hashvalue=null;
        if(is_string($parseval)){

            preg_match("/(or\s+)?`?(?:\b)(".$this->_hashfield.")`?\s*=\s*\'?\"?\s*([^\s\'\"]*)\s*\'?\"?(\s+or\s+)?/i",$parseval,$match);

            if($match){
                $matchcount=count($match);
                if(!(($matchcount==5&&strtolower(trim($match[4]))=="or")||strtolower(trim($match[1]))=="or")){
                    $hashvalue=$match[3];

                }
            }

        }else if(is_array($parseval)){

            foreach ($parseval as $k=>$v){

                if($k==$this->_hashfield){
                    $hashvalue=$v;
                    break;
                }

            }
        }
        if(!$hashvalue){
            return $this->_table();
        }

        //根据hash找到对应的值
        if(method_exists($this->_model,"hash")){

            $tableindex=$this->_model->hash($hashvalue);

        }else{

            $tableindex=$this->_defaultHash($hashvalue);

        }
        if(!$tableindex){
            return $this->_table();
        }

        return $this->_table(null,$tableindex); //获得对应的表


    }
    private function _defaultHash($hash){

        $hash=intval($hash);

        if($hash==0){
            return 1;
        }
        $hash=substr($hash,0,-1); //最后一位取模

        $tableindex=$hash%10;

        return $tableindex;

    }
    private function _parseConditionArray($condtion){

        if(!is_array($condtion)){
            return $condtion;
        }

        $condtionstr="where 1 ";
        $condtionarr=[];
        foreach ($condtion as $k=>$v){
            $condtionarr[]='`'.$k."`='".$v."'";
        }
        $condtionstr.=" and ".implode(" and ",$condtionarr);

        return $condtionstr;

    }

    public function connectDbHostAndGetTable($condition=null){

        $tablename=$this->_getTable($condition);
        if($this->_shardtablename!=$this->_tablename){
            $sharding=substr($this->_shardtablename,strlen($this->_tablename));
        }else{
            $sharding=null;
        }
        if($this->_dbhostconf['default']){
            //无分布式
            $dbconf=$this->_dbhostconf;
        }else{
            $dbconf=sysclass("globalconf")->getDbHostConf($this->_database,$this->_tablename,$sharding);
        }
        if(!$dbconf){
            throw new XlException("抱歉，未找到数据库".$this->_database." 数据表".$this->_tablename."对应的主机");
        }

        $masterhost=$dbconf['masterhost']?:[];
        $slavehost=$dbconf['slavehost']?:[];

        $dbf=sysclass("dbfactory",0);
        $writeconfig=$this->_dbconfig;
        $readconfig=$this->_dbconfig;
        $writeconfig['hostname']=$masterhost['host']?:'localhost';
        $writeconfig['port']=$masterhost['port']?:'3306';
        $writeconfig['username']=$masterhost['username'];
        $writeconfig['password']=$masterhost['password'];
        $writeconfig['database']=$this->_database;

        $readconfig['hostname']=$slavehost['host']?:'localhost';
        $readconfig['port']=$slavehost['port']?:'3306';
        $readconfig['username']=$slavehost['username'];
        $readconfig['password']=$slavehost['password'];
        $readconfig['database']=$this->_database;

        $this->_writedb = $dbf::getInstance($writeconfig)->getDbObj($this->_database);
        $this->_readdb=$dbf::getInstance($readconfig)->getDbObj($this->_database);

        return $tablename;

    }

    public function autoCreateTable(){
        $this->_parseAndCreateTable();
    }

    private function _parseAndCreateTable(){

        //创建数据表
        if($this->_sharding){

            //分表情况，先创建分表，再创建总表
            $rt=$this->_createTable($this->_shardtablename,$this->_fields);

            if(is_bool($rt)){
                return $rt;
            }
            if($this->_merge){
                $rt=$this->_createMergeTable($this->_tablename,$this->_shardtablename,$rt['sqlline']);
            }

        }else{

            $rt=$this->_createTable($this->_tablename,$this->_fields);


        }

        if(is_bool($rt)){
            return $rt;
        }
        return $rt['result'];

    }
    public function tableExists($table){

        $tablekey=$this->_database.$table;

        if($this->staticCacheIsSet("tableexist",$tablekey)){
            return $this->staticCacheGet("tableexist",$tablekey);
        }
        if(!$this->_readdb){
            $this->connectDbHostAndGetTable();
        }
        $istableexist=$this->_readdb->tableExists($this->_tablepre.$table);
        $this->staticCacheSet("tableexist",$tablekey,$istableexist);
        return $istableexist;
    }
    private function _createTable($tablename,$fields){

        if(!$this->_isneedcreate){
            return true; //无需创建
        }
        //判断表是否存在
        if($this->tableExists($tablename)){
            return true;
        }
        //不存在则创建
        $sqlline=[];
        foreach($fields as $k=>$v){
            $node='`'.$k.'` ';
            $node.=$this->_getcreatesqllinenode($v);
            array_push($sqlline,$node);
        }

        $primarykeys=$this->_primarykeys;
        $keys=$this->_keys;

        if($primarykeys){

            $pkstr="PRIMARY KEY (";
            $pkeys=[];
            foreach($primarykeys as $v){
                array_push($pkeys,"`$v`");
            }
            $pkstr.=implode(',',$pkeys);
            $pkstr.=")";
            array_push($sqlline,$pkstr);
        }
        if(!empty($keys)){

            foreach($keys as $v){
                array_push($sqlline,'KEY `'.$v.'` (`'.$v.'`)');
            }
        }

        $rt=$this->_create_table($tablename,$sqlline,$this->_engine,$this->_charset);

        return ['result'=>$rt,'sqlline'=>$sqlline];


    }
    private function _getTableUnions($tablename){

        //动态获取unions
        $sql=$this->query("show create table ".$this->_tablepre.$tablename);
        $rt=$this->getQueryResult($sql);

        $ct=$rt['Create Table'];

        $unionsArr=[];
        if($ct) {

            preg_match("/union=\((.+?)\)/i", $ct, $match);

            if ($match) {

                $unions = $match[1];
                if ($unions) {

                    $unions = str_replace('`', '', $unions);

                    $unionsArr = explode(',', $unions);
                }
            }
        }

        return $unionsArr;


    }
    private function _createMergeTable($tablename,$shardtablename,$sqlline){

        if(!$this->_isneedcreate){
            return false; //无需创建
        }
        if($this->tableExists($tablename)){

            //如果存在，则动态修改union值
            if($tablename!=$shardtablename){
                $unions=$this->_getTableUnions($tablename);
                $unions[]=$this->_tablepre.$shardtablename;
                $unions=array_unique($unions);
                $sqlstr="ALTER TABLE {{tablepre}}".$tablename."  UNION=(".implode(',',$unions).")";

                $this->query($sqlstr); //动态修改union值
            }
            return true;
        }

        $attach="union(".$this->_tablepre.$shardtablename.")";

        if($this->_merge_insert){
            $attach.=" insert_method=".$this->_merge_insert;
        }

        //创建merge表

        return $this->_create_table($tablename,$sqlline,"MRG_MyISAM",$this->_charset,$attach);

    }
    private function _create_table($table,$sqlline,$e='InnoDB',$charset='utf8mb4',$attach=null){


        //不存在，则创建
        if(is_array($sqlline)){
            $sqlline=implode(',',$sqlline);
        }
        $sqlstr="CREATE TABLE IF NOT EXISTS `".$this->_tablepre.$table."` (".$sqlline.")  ENGINE=".$e." DEFAULT CHARSET=".$charset;

        if($attach){
            $sqlstr.=" ".$attach;
        }

        $rt=$this->query($sqlstr);

        if($rt){
            $this->staticCacheSet("tableexist",$this->_database.$table,1);
        }

        if($rt){
            return true;
        }
        return false;

    }

    private function autoRepairSqlException($errid){

        $isreturn=false;
        if($errid=="TABLE_NOT_EXIST"){
            $isreturn=$this->_parseAndCreateTable();
        }else if($errid=="BAD_FIELD_ERROR"){

            //列不存在
            $isreturn=$this->repairTableStruct(); //修复表结构

        }else if($errid=="TABLE_NEED_REPAIR"){

            //表需要修复
            $isreturn=$this->repairTable();

        }

        return $isreturn;

    }

    private function _getTableTypefromTypeAndSize($type,$size=''){

        if(!in_array($type,array('text','tinytext','medinumtext','longtext','date','datetime','json'))){
            return $type.'('.$size.')';
        }
        return $type;

    }

    public function repairTableStruct(){

        if(!$this->_isautorepairstruct){
            //不能自动修正表结构
            return false;
        }

        $tablename=$this->getAllTableName(true);

        if(static::$db_repairnum[$tablename]){

            throw new XlException("修复失败，请检查字段是否与模型定义一致");
        }

        $fields=$this->_fields;

        $tbfds=$this->getFields(); //域

        $addops=[];$changeops=[];
        $dropops=[];$increment='';
        $primarykey=[];$key=[];
        foreach($fields as $fk=>$fv){

            if(!isset($fv['comment'])){
                if($fv['name']){
                    $fv['comment']=$fv['name'];
                }
            }
            $sqlstr=$this->_getTableTypefromTypeAndSize($fv['type'],$fv['size'])." ";

            if(!in_array($fv['type'],$this->_notSizechartypes())) {

                if ($fv['type'] != "set" && $fv['type'] != "enum") {

                    if (!isset($fv['default'])) {
                        if (in_array($fv['type'], $this->_tableGetchartypes())) {
                            $fv['default'] = '';
                        } else {
                            $fv['default'] = 0;
                        }
                    }
                    if (!$fv['increment'] && isset($fv['default'])) {
                        if ($fv['default'] === "") {
                            $sqlstr .= " default '' ";
                        } else {
                            if ($fv['default'] === "null") {
                                $sqlstr .= " default NULL ";
                            } else {
                                $sqlstr .= " default '{$fv['default']}' ";
                            }
                        }
                    } else {
                        $increment = " CHANGE `{$fk}` `{$fk}` " . $sqlstr . " AUTO_INCREMENT " . ($fv['comment'] ? " COMMENT '{$fv['comment']}'" : ""); //自增
                    }
                }
            }else{

                if ($fv['type'] == 'date') {
                    if (!preg_match("/^\s*\d{4}-\d{2}-\d{2}\s*/", $fv['default'])) {
                        $sqlstr .= " default null ";
                    }
                } elseif ($fv['type'] == "datetime") {
                    if (!preg_match("/^\s*\d{4}-\d{2}-\d{2}\s \d{2}:\d{2}:\d{2}*/", $fv['default'])) {
                        $sqlstr .= " default null ";
                    }
                }else{
                    if(!$fv['default']){
                        $sqlstr.=" default null ";
                    }else{
                        $sqlstr.="  default '{$fv['default']}' ";
                    }
                }


            }
            if($fv['comment']){
                $sqlstr.=" COMMENT '{$fv['comment']}' ";
            }
            if(array_key_exists($fk,$tbfds)){
                //已经存在
                $sqlstr=" CHANGE `{$fk}` `{$fk}` ".$sqlstr;
                $changeops[]=$sqlstr;
            }else{
                //不存在，添加
                $sqlstr=" ADD `{$fk}` ".$sqlstr;
                $addops[]=$sqlstr;
            }
            if($fv['primarykey']){
                //主键
                array_push($primarykey,$fk);
            }
            if($fv['key']){
                array_push($key,$fk);
            }
        }
        foreach($tbfds as $fk=>$fv){

            if(!array_key_exists($fk,$fields)){
                //删除项
                $sqlstr=" Drop `$fk` ";
                $dropops[]=$sqlstr;
            }

        }
        $ops=array_merge($dropops,$changeops,$addops);
        if(!empty($primarykey)){
            $pkstr=" DROP PRIMARY KEY , ADD PRIMARY KEY (";
            $pkeys=array();
            foreach($primarykey as $v){
                array_push($pkeys,"`$v`");
            }
            $pkstr.=implode(',',$pkeys);
            $pkstr.=")";
            array_push($ops,$pkstr);
        }
        if(!empty($key)){
            foreach($key as $v){
                $kstr=" DROP INDEX `$v`, ADD INDEX (`$v`) ";
                array_push($ops,$kstr);
            }
        }
        $sqlline=implode(",",$ops);

        $sqlstr="ALTER TABLE {$tablename} ".$sqlline;

        $rt=$this->query($sqlstr);

        if($increment){
            $sqlstr="ALTER TABLE {$tablename} ".$increment;
            $rt=$this->query($sqlstr);
        }
        if(!isset(static::$db_repairnum[$tablename])){
            static::$db_repairnum[$tablename]=1;
        }else{
            static::$db_repairnum[$tablename]++;
        }
        if($rt){
            return true;
        }
        return false;

    }

    public function repairTable(){


        $tablename=$this->getAllTableName(true);
        $sqlstr="REPAIR TABLE {$tablename} ";

        $rt=$this->query($sqlstr);

        if($rt){
            return true;
        }
        return false;

    }

    public function query($sql,$isread=null) {

        $sql = preg_replace('/{{tablepre}}/', $this->_tablepre, $sql,1);

        if(!$this->_writedb){
            $this->connectDbHostAndGetTable(); //连接数据库
        }

        if($isread){
            return $this->_readdb->query($sql);
        }else{
            return $this->_writedb->query($sql);
        }

    }

    /**
     * 从缓存中读取
     */

    private function _getCacheKey($optype,$tablename,$columns,$condition){

        if(is_array($columns)){
            $columns=implode(',',$columns);
        }
        $attach=$columns."_".$condition;
        $cachekey=$optype.'_'.$this->_readdb->getCacheKey($tablename,$attach);
        if($this->_cachepre){
            $cachekey="/".$this->_cachepre.$cachekey;
        }

        return $cachekey;

    }

    private function _readFromCache($optype,$tablename,$columns,$condition,&$cachekey){

        if(!$this->_opencache){
            return null;
        }
        if(!$this->_cachetype&$optype){
            //不启用缓存
            return null;
        }
        $cachekey=$this->_getCacheKey($optype,$tablename,$columns,$condition);

        return $this->superGetMemData($cachekey); //从缓存中读取

    }

    private function _writeToCache($cachekey,$value){

        $this->superSetMemData($cachekey,$value,$this->_cachetime?:0);//设置缓存

    }

    /**
     * @param $point
     * @param $beforeparam
     * 切点函数
     */
    public function aopRecordSqlSlowLogFunc($point,$beforeparam){

        if($point=="before"){
            $starttime=microtime(true); //开始时间
            return ['starttime'=>$starttime];
        }else if($point=="after"){

            $endtime=microtime(true);
            $starttime=$beforeparam['starttime'];
            $difftime=$endtime-$starttime;

            if($difftime>=$this->_longquerytime){
                //记录到慢日志中
                logger($this->_slowlogfile?:"mysqlslowlog_".date("Ymd"))->write("时间：".$difftime." 语句：".$beforeparam['sqlstr'].PHP_EOL,true);
            }
        }
        return null;


    }

    /**
     * @param string $columns
     * @param $condition
     * @param null $debug
     */
    public function getVal($columns="*",$condition,$debug=null){
        $rt=$this->getOne($columns,$condition,$debug);
        if($rt){
            if(count($rt)==1){
                return array_pop($rt);
            }
            return $rt;
        }
        return null;
    }

    /**
     * @return mixed
     * 检索数据表
     */
    public function getOne($columns="*",$condition,$debug=null){

        //检索数据表,避免重新获取错误，最大智能尝试一次
        $condition=$this->_parseConditionArray($condition);
        $tablename=$this->connectDbHostAndGetTable($condition);
        $iscreatetable=false;

        $cachekey=null;

        if($debug==null){
            $rt=$this->_readFromCache(self::CACHE_TYPE_GETONE,$tablename,$columns,$condition,$cachekey);
            if($rt!=null){
                return $rt;
            }
        }
        $rt=$this->_readdb->getOne($tablename,$columns,$condition,$debug,function($errid) use(&$iscreatetable){
            //异常捕获,表不存在，则检测是否创建
            $iscreatetable=$this->autoRepairSqlException($errid);
            return $iscreatetable;
        },$this->_openslowlog?[$this,"aopRecordSqlSlowLogFunc"]:null);
        if($iscreatetable){
            return $this->getOne($columns,$condition,$debug); //重新获取
        }

        if($cachekey){

            //设置缓存
            $this->_writeToCache($cachekey,$rt);
        }

        return $rt;

    }

    /**
     * @param $columns
     * @param $condition
     * @param string $debug
     * @return mixed
     * 获取多行
     */

    public function getRows($columns="*",$condition,$debug=null){

        //检索数据表,避免重新获取错误，最大智能尝试一次

        $condition=$this->_parseConditionArray($condition);
        $tablename=$this->connectDbHostAndGetTable($condition);
        $iscreatetable=false;

        $cachekey=null;

        if($debug==null){
            $rt=$this->_readFromCache(self::CACHE_TYPE_GETROWS,$tablename,$columns,$condition,$cachekey);
            if($rt!=null){
                return $rt;
            }
        }

        $rt=$this->_readdb->getRows($tablename,$columns,$condition,$debug,function ($errid) use(&$iscreatetable){

            //异常捕获,表不存在，则检测是否创建
            $iscreatetable=$this->autoRepairSqlException($errid);

            return $iscreatetable;

        },$this->_openslowlog?[$this,"aopRecordSqlSlowLogFunc"]:null);

        if($iscreatetable){
            return $this->getRows($columns,$condition,$debug); //重新获取
        }

        if($cachekey){
            //设置缓存
            $this->_writeToCache($cachekey,$rt);
        }

        return $rt;

    }

    public function createId($hashkey=null){

        //创建自动创建id
        if(method_exists($this->_model,"id")){
            $id=$this->_model->id();

            if(is_string($id)||is_numeric($id)){
                return $id;
            }
            if(is_array($id)){

                return $this->_bindModelCreateId(isset($id['model'])?$id['model']:null);

            }
            if(is_object($id)){

                return $this->_bindModelCreateId($id);
            }
            if(is_null($id)){
                return $this->_bindModelCreateId();
            }

        }

        if($hashkey&&$this->_hashkey_type=="bigint"){

            if($this->_increments[$hashkey]){
                //自增字段，则生成全局唯一id
                return null;
            }
            return getuuid($this->_workid); //系统自动创建唯一id

        }
        //根据数据类型，创建id
        foreach ($this->_increments as $k=>$v){
            if($v==true){
                return null; //有自增的字段无须创建id
            }
        }
        if(count($this->_primarykey_types)!=1){
            return null;
        }

        $fieldtype=$this->_primarykey_types[$this->_primarykeys[0]];

        if($fieldtype=="bigint"){

            return getuuid($this->_workid); //系统自动创建唯一id

        }

        return null;

    }

    /**
     * @param null $model
     * 根据id生成model
     */
    private function _bindModelCreateId($model=null){

        if($model==null){

            return $this->_createincid();

        }else{

            $model->insert(['id'=>null]);

            return $model->getId();

        }

    }

    /**
     * 创建自增id
     */
    private function _createincid(){

        $this->connectDbHostAndGetTable(); //链接

        $iscreatetable=false;

        $tablename=$this->_tablepre.$this->_logictablename."_incid";

        $this->_writedb->insert($tablename,['id'=>null],null,function ($errid) use(&$iscreatetable,$tablename){

            //异常捕获,表不存在，则检测是否创建
            if($errid=="TABLE_NOT_EXIST"){
                $sqlline="`id` bigint(20) NOT NULL AUTO_INCREMENT,PRIMARY KEY (`id`)";
                $sqlstr="CREATE TABLE IF NOT EXISTS `".$tablename."` (".$sqlline.")  ENGINE=".($this->_engine?:'InnoDB')." DEFAULT CHARSET=".($this->_charset?:'utf8mb4');
                $iscreatetable=$this->query($sqlstr);

            }else if($errid=="TABLE_NEED_REPAIR"){
                //表需要修复
                $iscreatetable=$this->query("REPAIR TABLE ".$tablename);

            }

            return $iscreatetable;


        },null);

        if($iscreatetable){
            return $this->_createincid(); //重新获取
        }


        return $this->_writedb->insert_id(); //获取最新自增id



    }


    /**
     * @return mixed
     * 获取自增id
     */

    public function insert_id() {
        return $this->_writedb->insert_id();
    }

    final public function getId(){

        //获得最后一次id
        if($this->_lastid){
            return $this->_lastid;
        }
        return $this->insert_id(); //自增产生的id

    }

    /**
     * @param array $columns
     * @param null $debug
     * 插入数据表
     */

    public function insert(array $columns,$debugorcleancache=null){

        $this->_lastid=null;

        if($this->_sharding){

            //分表情况
            if(!$columns[$this->_hashfield]){

                //没有要设置的主键
                $id=$this->createId($this->_hashfield);

                if(!$id){
                    //id不存在，则抛出异常，以防获得我们不需要的id
                    throw new XlException("hashkey's value is not null");
                }
                $columns[$this->_hashfield]=$id;

            }

        }

        //不分表
        if($this->_primarykeys){

            if(count($this->_primarykeys)>1){
                foreach($this->_primarykeys as $pk){
                    if(!$columns[$pk]){
                        throw new XlException("primarykey:".$pk." value is not null");
                    }
                }
            }else{
                if(empty($columns[$this->_primarykeys[0]])){
                    $id=$this->createId(); //获取id值
                    if($id){
                        //id不存在
                        $columns[$this->_primarykeys[0]]=$id;
                    }else{

                        if(!$this->_increments[$this->_primarykeys[0]]){
                            //不是自增的情况，id必须指定
                            throw new XlException("primarykey:".$this->_primarykeys[0]." value is not null");
                        }
                    }
                }

            }

        }

        $iscreatetable=false;
        $tablename=$this->connectDbHostAndGetTable($columns);

        $rt=$this->_writedb->insert($tablename,$columns,$debugorcleancache,function ($errid) use(&$iscreatetable){

            //异常捕获,表不存在，则检测是否创建
            $iscreatetable=$this->autoRepairSqlException($errid);
            return $iscreatetable;

        },$this->_openslowlog?[$this,"aopRecordSqlSlowLogFunc"]:null);

        if($iscreatetable){
            return $this->insert($columns,$debugorcleancache); //重新获取
        }

        if($rt){
            if($id=$columns[$this->_primarykeys[0]]){
                $this->_lastid=$id;
            }
        }

        //删除缓存
        if($debugorcleancache==="cleancache"){
            $this->cleanCache($tablename);
        }

        $this->_readdb=$this->_writedb; //防止读取延迟

        return $rt;

    }

    /**
     * 清除缓存
     */
    public function cleanCache($tablename){

        if(!$this->_opencache){
            return;
        }

        $cachekey='*_'.$this->_readdb->getCacheKey($tablename)."*";

        $this->superDelMemData($cachekey); //移除缓存

    }

    public function setColumn(array $columns,$condition,$debugorcleancache=null){

        $iscreatetable=false;
        if($this->_sharding){
            //分片的情况下，不能改变哈希值
            unset($columns[$this->_hashfield]);
        }
        $condition=$this->_parseConditionArray($condition);
        $tablename=$this->connectDbHostAndGetTable($condition);

        $rt=$this->_writedb->setColumn($tablename,$columns,$condition,$debugorcleancache,function($errid) use(&$iscreatetable){

            //异常捕获,表不存在，则检测是否创建
            $iscreatetable=$this->autoRepairSqlException($errid);
            return $iscreatetable;
        },$this->_openslowlog?[$this,"aopRecordSqlSlowLogFunc"]:null);

        if($iscreatetable){
            return $this->setColumn($columns,$debugorcleancache); //重新获取
        }

        //删除缓存
        if($debugorcleancache==="cleancache"){
            $this->cleanCache($tablename);
        }

        $this->_readdb=$this->_writedb; //防止读取延迟

        return $rt;

    }

    /**
     * @param $condition
     * @param string $debug
     * @return mixed
     * 删除数据表
     */

    public function delete($condition,$debugorcleancache=null){


        $condition=$this->_parseConditionArray($condition);

        $tablename=$this->connectDbHostAndGetTable($condition);

        $iscreatetable=false;

        $rt=$this->_writedb->delete($tablename,$condition,$debugorcleancache,function($errid) use(&$iscreatetable){

            //异常捕获,表不存在，则检测是否创建
            if($errid=="TABLE_NOT_EXIST"){

                $iscreatetable=true;

            }

            return true; //不报错,删除不自动检测创建表

        },$this->_openslowlog?[$this,"aopRecordSqlSlowLogFunc"]:null);

        if($iscreatetable){
            return true;
        }

        //删除缓存
        if($debugorcleancache==="cleancache"){
            $this->cleanCache($tablename);
        }

        if($rt){
            $this->_readdb=$this->_writedb; //方式读取延迟
        }

        return $rt;

    }

    /**
     * @param $table
     * @param $columns
     * @param $values
     * @param string $debug
     * @return mixed
     * 多行插入
     */

    public function inserts($columns,array $values,$debugorcleancache=null){

        if($this->_sharding){
            //分表情况，则循环遍历插入
            $rt=false;
            foreach($values as $value){

                if(is_array($value)){
                    $rt=$this->insert($value,$debugorcleancache);
                }

            }
            return $rt;
        }
        //不分表情况
        if($this->_primarykeys){

            if(count($this->_primarykeys)>1){
                foreach($this->_primarykeys as $pk){
                    if(!in_array($pk,$columns)){
                        throw new XlException("primarykey:".$pk." value is not null");
                    }
                }
            }else{

                if(is_array($columns)){


                    if(!in_array($this->_primarykeys[0],$columns)) {

                        $columns[] = $this->_primarykeys[0];

                        foreach ($values as &$rows) {

                            $id = $this->createId(); //获取id值
                            if ($id) {
                                //id不存在
                                $rows[] = $id;
                            }
                        }
                    }

                }

            }

        }

        $iscreatetable=false;

        $tablename=$this->connectDbHostAndGetTable();

        $rt=$this->_writedb->inserts($tablename,$columns,$values,$debugorcleancache,function ($errid) use(&$iscreatetable){

            $iscreatetable=$this->autoRepairSqlException($errid);

            return $iscreatetable;

        },$this->_openslowlog?[$this,"aopRecordSqlSlowLogFunc"]:null);

        if($iscreatetable){
            return $this->inserts($columns,$values,$debugorcleancache); //重新获取
        }

        //删除缓存
        if($debugorcleancache==="cleancache"){
            $this->cleanCache($tablename);
        }

        $this->_readdb=$this->_writedb;

        return $rt;

    }

    /**
     * @param $condition
     * @param null $debug
     * @param bool $isgroup
     * @return mixed
     * @throws XlException
     * 获取检索的行数
     */

    public function getRowNum($condition,$isgroup=false,$debug=null){

        $condition=$this->_parseConditionArray($condition);
        $iscreatetable=false;
        $tablename=$this->connectDbHostAndGetTable($condition);

        $cachekey=null;

        if($debug==null){
            $rt=$this->_readFromCache(self::CACHE_TYPE_GETROWNUM,$tablename,intval($isgroup),$condition,$cachekey);

            if($rt!=null){
                return $rt;
            }

        }

        $rt=$this->_readdb->getRowNum($tablename,$condition,$isgroup,$debug,function($errid) use (&$iscreatetable){

            $iscreatetable=$this->autoRepairSqlException($errid);

            return $iscreatetable;

        },$this->_openslowlog?[$this,"aopRecordSqlSlowLogFunc"]:null);

        if($iscreatetable){
            return $this->getRowNum($condition,$isgroup,$debug); //重新获取
        }

        if($cachekey){
            //设置缓存
            $this->_writeToCache($cachekey,$rt);
        }

        return $rt;

    }

    /**
     * @param $condition
     * @param null $debug
     * @param bool $isgroup
     * @return mixed
     * @throws XlException
     * 计算某一列的之和
     */
    public function sumRow($column,$condition,$debug=null){

        $condition=$this->_parseConditionArray($condition);
        $iscreatetable=false;
        $tablename=$this->connectDbHostAndGetTable($condition);
        $rt=$this->_readdb->sumRow($tablename,$column,$condition,$debug,function($errid) use (&$iscreatetable){

            $iscreatetable=$this->autoRepairSqlException($errid);

            return $iscreatetable;

        });
        if($iscreatetable){
            return $this->sumRow($column,$condition,$debug); //重新获取
        }
        return $rt;

    }

    /**
     * @return 获取当前表名，不带前缀，非分表表名
     */

    public function getTableName(){

        return $this->_tablename;

    }

    final public function getAllTableName($ishasdb=false){

        if($this->_sharding){
            $tablename=$this->_shardtablename;
        }else{
            $tablename=$this->_tablename;
        }

        if($ishasdb){
            return "`".$this->_database."`.`".$this->_tablepre.$tablename."`";
        }else{
            return $this->_tablepre.$tablename;
        }

    }
    public function getDbName(){

        return $this->_database;
    }

    /**
     * 获得唯一id
     * @return mixed
     */
    public function getUUID(){

        return getuuid($this->_workid);
    }

    /**
     * @param $params
     * @param string $debug
     * @return array
     * 添加数据自动检测传入参数是否合法
     * 自动处理列的长度
     */
    public function add($params,$dealcolumnlen=false,$debug=null){

        $fileds=$this->_fields;
        $needfileds=array();
        foreach($params as $key=>$value){
            if($fileds[$key]){
                if(!empty($fileds[$key]['forbit_add'])){
                    return $this->ErrorInf($key."为禁止添加字段");
                }
                $needfileds[$key]=$fileds[$key];
                $needfileds[$key]['value']=$value;
            }
        }
        $datas=array();
        foreach($needfileds as $k=>$v){
            $ab=$this->_getcheckdatatype($v['type'],$v);
            if(empty($v['value'])){
                if(in_array($ab['check'],array("number","float","int","numeric","bigint"))){
                    $v['value']=0;
                }
            }
            if(is_array($ab)){
                if($dealcolumnlen&&isset($v['size'])&&is_numeric($v['size'])){
                    $v['value']=strLeft(trim($v['value']),intval($v['size']));
                }
                $rt=$this->_checkV(isset($v['name'])?$v['name']:$k,$v['value'],$ab['check'],$ab['range'],'');
                if($rt['status']=="fail"){
                    return $rt;
                }
            }
            $datas[$k]=$v['value'];
        }

        if($this->insert($datas,$debug)){

            $pkey=$this->_primarykeys[0];
            $attach=0;
            if($pkey){
                $id=$this->getId();
                $attach=[$pkey=>$id,'id'=>$id];
            }

            return $this->SuccInf("操作成功",$attach);
        }

        return $this->ErrorInf("操作数据库失败");

    }

    /**
     * @param $params
     * @param string $condition
     * @param bool $dealcolumnlen
     * @param null $debug
     * @return array
     * 编辑数据自动检测传入参数是否合法
     * 自动处理列的长度
     */

    public function edit($params,$condition='',$dealcolumnlen=false,$debug=null){

        $fileds=$this->_fields;
        $needfileds=array();
        foreach($params as $key=>$value){
            if($fileds[$key]){
                if($fileds[$key]['forbit_edit']){
                    return $this->ErrorInf($key."为禁止编辑字段");
                }
                $needfileds[$key]=$fileds[$key];
                $needfileds[$key]['value']=$value;
            }
        }
        if(!$condition){
            if($this->_primarykeys&&is_array($this->_primarykeys)){
                $pkeystr=[];
                foreach ($this->_primarykeys as $pkey){
                    if($needfileds[$pkey]){
                        $pkeystr[]=" $pkey='".$needfileds[$pkey]['value']."' ";
                        unset($needfileds[$pkey]);
                    }
                }
                if($pkeystr){
                    if(count($pkeystr)==1){
                        $pkeystr=$pkeystr[0];
                    }else{
                        $pkeystr=implode(" and ",$pkeystr);
                    }
                    $condition="where ".$pkeystr." limit 1";
                }
            }

        }

        if(!$condition){
            $this->ErrorInf("条件不能为空");
        }
        $datas=array();
        foreach($needfileds as $k=>$v){
            $ab=$this->_getcheckdatatype($v['type'],$v);
            if(is_array($ab)){
                if($dealcolumnlen&&isset($v['size'])&&is_numeric($v['size'])){
                    $v['value']=strLeft(trim($v['value']),intval($v['size']));
                }
                $rt=$this->_checkV(isset($v['name'])?$v['name']:$k,$v['value'],$ab['check'],$ab['range'],'');
                if($rt['status']=="fail"){

                    return $rt;
                }
            }
            $datas[$k]=$v['value'];
        }

        if($this->setColumn($datas,$condition,$debug)){
            return $this->SuccInf("操作成功");
        }
        return $this->ErrorInf("操作数据库失败");

    }

    /**
     * @param $vname
     * @param $v
     * @param string $checkfun
     * @param string $size
     * @param string $default
     * @param bool $isecho
     * @param string $debug
     * @return array
     * 验证纠正字段精简，去掉无用的
     */
    private function _checkV($vname,&$v,$checkfun='',$size='',$default='',$debug=null){
        //检测字符串
        $rt=static::checkValue($vname,$v,$checkfun,$size,$default);
        if($rt===true){
            return $this->SuccInf("验证通过");
        }else{
            if($debug=="debug"){
                var_dump($rt);
            }
            if($rt===false){
                return $this->ErrorInf($vname."验证不合法");

            }
            return $this->ErrorInf($rt); //返回错误信息
        }

    }

    /**
     * @param string $except
     * @return array|string
     * 获得所有的field列，除了except除外
     */
    public function fields($except=''){

        if(empty($this->_fields)){return '*';} //没有则返回所有
        $ect=array();
        if(is_string($except)){
            if(!empty($except)){
                $ect=explode(',',$except);
            }
        }else if(is_array($except)){
            $ect=$except;
        }
        $keys=array_keys($this->_fields);
        return array_diff($keys,$ect); //差集
    }

    public function search($pm,$debug=null){

        //封装搜索结果集
        /*
         保持唯一",params，搜索键值对,keywords,关键字列表，others(比如between,or)，orders,排序列表，page=>1，页，num=>每页数量，
         needallcount=>true,是否计算个数
        */
        $params=$pm['params']; //键值对
        $keywords=$pm['keywords']; //关键字键值对
        $others=$pm['others']; //其他搜索项，支持数组和字符串
        $orders=$pm['orders']; //排序列表
        $ins=$pm['ins']; //in操作
        $betweens=$pm['betweens'];
        $insets=$pm['insets']; //find_in_set查询
        $groups=$pm['groups']; //搜素组
        $page=$pm['page']?$pm['page']:1;
        $num=$pm['num']?$pm['num']:10;
        $needallcount=$pm['needallcount'];
        $columns=$pm['columns'];
        $condition=$pm['condition']?:'';

        if(is_array($params)){
            $this->orz_and_condition($this->orz_and_equalstr($params),$condition);
        }else{
            $params=array();
        }
        $this->orz_and_condition('',$condition); //添加where
        if($others){

            if(is_array($others)){
                foreach($others as $c){
                    $this->orz_and_condition($c,$condition);
                    $params[]=$c;
                }
            }else if(is_string($others)){
                $this->orz_and_condition($orders,$condition);
                $params[]=$orders;
            }
        }
        if($ins&&is_array($ins)){
            foreach($ins as $k=>$v){
                if($v){
                    $instr=$this->orz_in_str($k,$v);
                    $this->orz_and_condition("and ".$instr,$condition);
                    $params[]=$v;
                }
            }
        }
        if($keywords&&is_array($keywords)){
            foreach($keywords as $k=>$v){
                if($v!==""&&!is_array($v)){
                    $k=trim($k);
                    $karr=multiexplode(array(",","|"," "),$k);
                    $kvstr=array();
                    if(stripos($v,"%")===false){
                        foreach($karr as $kw){
                            if(!empty($kw)){
                                //$kvstr[]=" $kw like '%$v%' ";
                                $kvstr[]=" instr($kw,'$v')>0 ";
                            }
                        }
                    }else{
                        foreach($karr as $kw){
                            if(!empty($kw)){
                                $kvstr[]=" $kw like '$v' ";
                            }
                        }
                    }
                    if(!empty($kvstr)){
                        $this->orz_and_condition("and ( ".implode("or",$kvstr).' ) ',$condition);
                    }
                    $params[]=$v;
                }
            }
        }
        if($betweens&&is_array($betweens)){
            //array(key=>array(b,e));
            foreach($betweens as $k=>$v){
                if(is_array($v)&&count($v)==2){
                    $this->orz_and_condition($this->orz_between_str($k,$v[0],$v[1]),$condition);
                    $params[]=$v[0].'_'.$v[1];
                }
            }
        }
        if($insets&&is_array($insets)){
            //array(key=>array(a,b,c,d);
            foreach($insets as $k=>$v){
                if(is_array($v)){
                    //多对多查询
                    $kvstr=array();
                    foreach($v as $v_n){
                        if($v_n&&!is_array($v_n)){
                            $kvstr[]=" find_in_set('$v_n',$k) ";
                            $params[]=$v_n;
                        }
                    }
                    if(!empty($kvstr)){
                        $this->orz_and_condition("and ".implode("or",$kvstr),$condition);
                    }
                }else{
                    $this->orz_and_condition("and find_in_set('$v',$k) ",$condition);
                }
            }

        }
        $isgroup=false;
        if($groups){
            $condition.=$this->orz_groups($groups);
            $isgroup=true;
        }
        if($needallcount){
            //需要调用
            if ($isgroup) {
                $count = $this->getRowNum($condition,true);
            } else {
                $count = $this->getRowNum($condition);
            }
            $allcount=array('allcount'=>(int)$count);
        }
        if($orders){
            $condition.=$this->orz_orders($orders);
        }
        $condition.=$this->orz_limit($page,$num);

        $lists=$this->getRows($columns?$columns:$this->fields(),$condition,$debug);
        $lists=$lists?$lists:array();
        if(isset($allcount)&&$allcount){
            $lists=array('allcount'=>$allcount['allcount'],'datalist'=>$lists);
        }
        return $lists;

    }

    /**
     * @param $sql
     * @return mixed
     * sql语句执行结果
     */

    public function getQueryResult($sql,$isread=null)
    {
        if ($isread) {
            return $this->_readdb->getQueryResult($sql);
        }
        return $this->_writedb->getQueryResult($sql);
    }

    public function getPrimary() {
        return $this->_writedb->getPrimary($this->getAllTableName(false));
    }

    public function getFields($table_name = '') {
        if (empty($table_name)) {
            $table_name = $this->getAllTableName(false);
        } else {
            $table_name = $this->_tablepre.$table_name;
        }
        if(!$this->_readdb){
            $this->connectDbHostAndGetTable();
        }
        return $this->_readdb->getFields($table_name);
    }

    public function getFiledName($key){

        $_fd=$this->_fields[$key];

        if(!$_fd){
            return '';
        }

        return $_fd['name']?:$_fd['comment']?:$key;

    }
    private function _tableGetchartypes(){

        //字符类型
        return array('varchar','text','char','tinytext','medinumtext','longtext','binary','enum','set');

    }

    private function _notSizechartypes(){

        return array('text','tinytext','medinumtext','longtext','date','datetime','json');
    }

    /**
     * @param $tablename
     * @param $sqlstrtpl
     * 从文件中自动读取sql语句执行
     */
    public function execSqlFromFile($path){

        if(!file_exists($path)){
            return $this->ErrorInf("文件不存在");
        }
        $sqlstr=file_get_contents($path);
        if(empty($sqlstr)){
            return $this->ErrorInf("文件内容不存在");
        }
        $sqlstr=trim($sqlstr);
        $tablename=$this->_tablename;
        $this->getOne("*","where 1"); //执行语句自动创表

        $count=$this->execSqlTplContent($tablename,$sqlstr);

        return $this->SuccInf("执行成功".$count."条sql语句");

    }

    public function execSqlTplContent($tablename,$sqlstrtpl){

        if(empty($sqlstrtpl)){return 0;}

        $datamap=array('tablename'=>$this->_tablepre.$tablename);

        $sqlstr=preg_replace_callback("/{{([a-zA-z_0-9]+)}}/i",function($matchs) use($datamap){
            return $datamap[$matchs[1]];
        },$sqlstrtpl);

        //得到sql语句直接执行
        $sqlstrarray=explode(");",$sqlstr);

        $allcount=0;
        foreach($sqlstrarray as $slr){

            if($slr){
                if($this->query($slr.');')){
                    $allcount++;
                }
            }

        }
        return $allcount;
    }

    /**
     * @param $sn
     * @return string
     * 创建数据表每个字段
     */
    private function _getcreatesqllinenode($sn){

        $type=$sn['type'];
        $size=$sn['size'];
        $default=$sn['default'];
        $type=$type?:"varchar";
        $sqlline='';
        $null=$sn['null']?" null ":"";

        if(!in_array($type,$this->_notSizechartypes())){
            if($type=="set"||$type=="enum"){
                $sqlline.=' '.$type.'('.$size.') '.$null;
                if($default){
                    $sqlline.=" default '{$default}' ";
                }else{
                    $sqlline.=" default null ";
                }
            }else{
                if(!isset($sn['size'])){
                    $size=10;
                }
                $sqlline=' '.$type.'('.$size.') '.$null;
                if(!$sn['increment']){
                    if(!isset($sn['default'])){
                        if(in_array($type,$this->_tableGetchartypes())){
                            $default='';
                        }else{
                            $default=0;
                        }
                    }
                    if($default==='null'){
                        $sqlline.=" default NULL ";
                    }else{
                        $sqlline.=" default '$default' ";
                    }

                }else{
                    $sqlline.=" auto_increment ";
                }
            }
        }else{

            $sqlline.=' '.$type.' '.$null;
            if($type=="date"){
                if(preg_match("/^\s*\d{4}-\d{2}-\d{2}\s*/",$default)){
                    $sqlline.=" default '{$default}' ";
                }else{
                    $sqlline.=" default null ";
                }
            }else if($type=="datetime"){
                if(preg_match("/^\s*\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\s*/",$default)){
                    $sqlline.=" default '{$default}' ";
                }else{
                    $sqlline.=" default null ";
                }
            }else{
                $sqlline.=" default null ";
            }

        }
        if(!isset($sn['comment'])){
            if($sn['name']){
                $sn['comment']=$sn['name'];
            }
        }
        if($sn['comment']){
            $sqlline.=" COMMENT '{$sn['comment']}' ";
        }

        return $sqlline;

    }

    /**
     * 检查字段是否存在
     * @param $field 字段名
     * @return boolean
     */
    public function fieldExists($field) {
        $fields = $this->_readdb->getFields($this->getAllTableName(false));
        return array_key_exists($field, $fields);
    }

    public function listTables() {
        return $this->_readdb->listTables();
    }

    public function orz_in_str($key,$vals){

        $ks=$this->_parse_orz_in_vals($vals);

        if($ks){

            return $key.' in ('.$ks.')';
        }

        return '';

    }

    public function orz_notin_str($key,$vals){


        $ks=$this->_parse_orz_in_vals($vals);

        if($ks){

            return $key.' not in ('.$ks.')';
        }

        return '';

    }

    private function _parse_orz_in_vals($vals){

        if(empty($vals)){
            return '';
        }
        $ks=array();
        if(!is_array($vals)){
            $vals=explode(',',$vals);
        }
        if(is_array($vals)){

            $vals=array_unique($vals);
            foreach($vals as $v){
                if(is_numeric($v)){
                    $ks[]=$v;
                }else if(is_string($v)){
                    $ks[]="'".$v."'";
                }
            }
            $ks=implode(',',$ks);
        }

        return $ks;

    }

    public function orz_and_equalstr($pm){
        $arr=array();
        foreach($pm as $k=>$v){
            if(empty($v)){
                array_push($arr,1);
                continue;
            }
            $k=trim($k);
            $karr=multiexplode(array(",","|"," "),$k);
            $tmparr=array();
            foreach($karr as $k1){
                if(is_array($v)){
                    $value=$v['value'];
                    if(is_array($value)){
                        continue;
                    }
                    $checkempty=$v['checkempty'];
                    $check0=$v['check0']; //是否校验0为的值
                    if($checkempty){
                        if(empty($value)){
                            array_push($tmparr," $k1 is null or $k1='' ");
                            continue;
                        }
                    }
                    if(empty($value)&&!$check0){
                        array_push($tmparr,1);
                    }else{
                        array_push($tmparr," $k1='$value'");
                    }
                }else{
                    array_push($tmparr," $k1='$v'" );
                }
            }
            if(count($tmparr)<=1){
                array_push($arr,$tmparr[0]);
            }else{
                array_push($arr,implode(' or ',$tmparr));
            }
        }
        return implode(' and ',$arr);
    }
    public function orz_between_str($k,$bv,$ev){
        if($ev){
            return " $k>='$bv' and $k<='$ev' ";
        }else if($bv){
            return " $k>='$bv' ";
        }
        return '';
    }
    public function orz_orders($pm){
        $order=array();
        foreach($pm as $k=>$v){
            if($v==2){
                array_push($order," $k asc ");
            }else if($v==1){
                array_push($order," $k desc ");
            }
        }
        if($order){
            return ' order by '.implode(',',$order);
        }
        return '';
    }
    public function orz_groups($pm){

        if(is_string($pm)){
            return ' group by '.$pm.' ';
        }
        if(is_array($pm)){
            $pm=implode(',',$pm);
            return ' group by '.$pm.' ';
        }
        return '';

    }
    public function orz_limit($page,$num,$offset=0){
        if($page&&$num){
            $offset=($page-1)*$num+$offset;
            return " limit $offset,$num";
        }
        return '';
    }
    public function orz_and_condition($c,&$condition){
        if($condition){
            if(stripos(trim($c),'and')===0){
                if(strtolower(trim($c))==="and"){
                    $c=" and 1 ";
                }
                if(stripos(trim($condition),'where')===0){
                    $condition.=" ".$c." ";
                }else{
                    $condition="where ".$condition." ".$c." ";
                }
            }else{
                if($c){
                    if(stripos(trim($condition),'where')===0){
                        $condition.=" and ".$c." ";
                    }else{
                        $condition="where ".$condition." and ".$c." ";
                    }
                }else{

                    if(stripos(trim($condition),'where')!==0){
                        $condition="where ".$condition." ";
                    }
                }
            }
        }else{
            if(stripos(trim($c),'and')===0){
                $c=substr(trim($c),3);
                $condition="where ".$c." ";
            }else if($c){
                $condition="where ".$c." ";
            }else{
                $condition="where 1 ";
            }
        }
        return $condition;
    }

    /**
     * @param $params
     * @param $musts
     * @param array $fields
     * @return array
     * 验证传参必须存在
     */

    public function checkMust($params,$musts,$fields=[]){

        //验证必须传的参数
        if(!is_array($params)){
            return $this->ErrorInf("参数错误");
        }
        if(is_string($musts)){
            $musts=multiexplode([",","|"," "],$musts);
        }
        if(!is_array($musts)){
            return $this->SuccInf("限制条件不存在");
        }

        foreach($musts as $mkey){
            if($mkey){
                if(!isset($params[$mkey])) {
                    if ($fields[$mkey]) {
                        return $this->ErrorInf(($fields[$mkey]['name']?:$mkey) . "不存在");
                    } else {
                        return $this->ErrorInf("参数" . $mkey . "必须存在");
                    }
                }
            }
        }

        return $this->SuccInf("验证通过");

    }

    /**
     * @param $params
     * @param $musts
     * @param array $fields
     * @return array
     * 验证不能为空
     */

    public function checkNoEmpty($params,$musts,$fields=[]){

        //验证必须传的参数
        if(!is_array($params)){
            return $this->ErrorInf("参数错误");
        }
        if(is_string($musts)){
            $musts=multiexplode([",","|"," "],$musts);
        }
        if(!is_array($musts)){
            return $this->SuccInf("限制条件不存在");
        }
        foreach($musts as $mkey){
            if($mkey){
                if(empty($params[$mkey])) {
                    if ($fields[$mkey]) {
                        return $this->ErrorInf(($fields[$mkey]['name']?:$mkey) . "必填");
                    } else {
                        return $this->ErrorInf("参数" . $mkey . "必填");
                    }
                }
            }
        }

        return $this->SuccInf("验证通过");

    }

    /**
     * @param $columns
     * @param $condition
     * @param string $tablename
     * @return bool
     * 增幅字段的数量+inc，-=
     */

    public function incColumn($columns,$condition,$debugorcleancache=null){

        //columns结构：array(column=>inc);
        if(!is_array($columns)){
            return false;
        }
        $params=array();
        foreach($columns as $column=>$inc){

            if(is_string($column)&&is_number($inc)){
                if($inc==0){
                    continue;
                }
                $inc=(int)$inc;
                if($inc>0){
                    $params[$column]="+=".$inc;
                }else{
                    $params[$column]="-=".abs($inc);
                }
            }else if(strpos($inc,"#")===0){
                $params[$column]=substr($inc,1);
            }
        }

        return $this->setColumn($params,$condition,$debugorcleancache);

    }

    /**
     * @param $type
     * @param $v
     * @return array
     * 保留
     */

    private function _getcheckdatatype($type,$v){

        $range=$v['range']??null;
        $size=$v['size']??null;
        if(!$range){
            if(is_numeric($v['size'])){
                $range="0-".$v['size'];
            }
        }
        $rt=[];
        switch($type){
            case 'int':
                $rt= ['check'=>'number','range'=>$range];
                break;
            case 'bigint':
                $rt= ['check'=>'number','range'=>$range];
                break;
            case 'tinyint':
                $rt=['check'=>'number','range'=>$range];
                break;
            case 'char':
                $rt= ['check'=>'string','range'=>$range];
                break;
            case 'varchar':
                $rt= ['check'=>'string','range'=>$range];
                break;
            case 'text':
                $rt= ['check'=>'string','range'=>$range];
                break;
            case 'longtext':
                $rt= ['check'=>'string','range'=>$range];
                break;
            case 'enum':
                $rangestr='array('.$size.')';
                $range=string2array($rangestr);
                if(is_array($range)){
                    $rt= ['check'=>'string','range'=>$range];
                }
                break;
            case 'set':
                $rangestr='array('.$size.')';
                $range=string2array($rangestr);
                if(is_array($range)){
                    $rt= ['check'=>'string','range'=>$range];
                }
                break;
            case 'float':
                $rt=['check'=>'float'];
                break;
            case 'double':
                $rt=['check'=>'float'];
                break;
            case 'json':
                $rt= ['check'=>is_array($v)?'array':'string'];
                break;
            default:
                $rt=['check'=>'string','range'=>$range];
                break;
        }

        return $rt;

    }

    /**
     * 根据操作符组织条件语句
     *
     */
    public function buildCondition($param,$spmap){


        $cons=[];
        $param=$param?:[];
        $spmap=$spmap?:[];
        foreach($param as $k=>$v){

            if(!isset($spmap[$k])){
                continue;
            }
            $spmnode=$spmap[$k];
            if(empty($v)){
                if($spmnode['check0']){
                    $cons[]=$k.'="'.$v.'"';
                }else{
                    continue;
                }
            }
            if(is_array($spmnode[0])){
                foreach ($spmnode as $spmnode_item){
                    $return=$this->_dealbuildconnode($cons,$k,$v,$spmnode_item,$param);
                    if($return==='__break'){
                        break;
                    }
                }
            }else{
                $this->_dealbuildconnode($cons,$k,$v,$spmnode,$param);
            }

        }
        if($cons){
            $cons=implode(' and ',$cons);
            $cons=' and '.$cons;
        }else{
            $cons='';
        }
        return $cons;

    }
    private function _dealbuildconnode(&$cons,$k,$v,$spmnode,$param){

        $return='';
        if(empty($v)&&!$spmnode['check0']){
            return null;
        }
        $match=$spmnode['match'];
        $nomatch=$spmnode['nomatch'];

        if($match){
            if(!preg_match($match,$v)){
                return null;
            }
        }
        if($nomatch){

            if(preg_match($nomatch,$v)) {
                return null;
            }
        }
        if($spmnode['isjump']){
            $return='__break';
        }
        $mapname=$spmnode['mapname']?:$k;
        if(is_array($mapname)){
            foreach($mapname as $m_k=>$m_v){
                if(isset($param[$m_k])){
                    if(isset($m_v[$param[$m_k]])){
                        $mapname=$m_v[$param[$m_k]];
                        break;
                    }else{
                        if(isset($m_v['_default'])){
                            $mapname=$m_v['_default'];
                            break;
                        }
                    }
                }
            }
        }
        $oper=$spmnode['oper']?:'=';
        $logicoper=$spmnode['logicoper']?:'and';

        if(in_array($oper,['=','!=','>','>=','<','<=','<>'])){
            if(is_numeric($v)){
                $cons[]=$mapname.$oper.$v;
            }else{
                $cons[]=$mapname.$oper."'".$v."'";
            }
            return $return;
        }
        if(strpos($oper,'%')!== false){
            /*like*/
            $operval="'".str_replace('*',$v,$oper)."'";
            if(strpos($mapname,'|')!==false){
                $mapnamearr=explode('|',$mapname);
                $itemcons=[];
                foreach ($mapnamearr as $mn){
                    $itemcons[]=$mn.' like '.$operval;
                }
                $itemcons=implode(' or ',$itemcons);
                $cons[]='('.$itemcons.')';

            }else if(strpos($mapname,'&')!==false) {
                $mapnamearr=explode('&',$mapname);
                foreach ($mapnamearr as $mn) {
                    $cons[] = $mn . ' like ' . $operval;
                }
            }else{
                $cons[]=$mapname.' like '.$operval;
            }
        }
        if($oper=="case"){

            $case=$spmnode['case'];
            $opercon=$case[$v];
            if($opercon){

                if(strpos($mapname,'|')!==false){
                    $mapnamearr=explode('|',$mapname);
                    $itemcons=[];
                    foreach ($mapnamearr as $mn){
                        $itemcons[]=$mn.$opercon;
                    }
                    $itemcons=implode(' or ',$itemcons);
                    $cons[]='('.$itemcons.')';

                }else if(strpos($mapname,'&')!==false) {
                    $mapnamearr=explode('&',$mapname);
                    foreach ($mapnamearr as $mn) {
                        $cons[] = $mn.$opercon;
                    }
                }else{
                    $cons[]=$mapname.$opercon;
                }

            }

        }
        if($oper=="instr"){

            if(!is_array($v)){
                $varr=multiexplode([',','，','-','、'],$v);
            }else{
                $varr=$v;
            }
            $itemcons=[];
            foreach ($varr as $vitem){
                $itemcons[]=" instr($mapname,',{$vitem},') ";
            }
            $itemcons=implode(' '.$logicoper.' ',$itemcons);
            $cons[]='('.$itemcons.')';

        }
        return $return;

    }
    public function orz_addcomma($val,$first=true,$end=true){

        if(empty($val)){
            return '';
        }
        if($first&&substr($val,0,1)!=","){
            $val=",".$val;
        }
        if($end&&substr($val,-1,1)!=","){
            $val.=",";
        }

        return $val;
    }

    /**
     * 智能模板执行sql语句
     * {{this}}指当前表
     * select * from {{table1}}
     *
     * $pmmap=[
     *    'table1'=>['modelname','modelparam']
     * ]
     * $isread=true，从从库里执行sql语句
     */
    public function queryEx($sqltpl,$pmmap=null,$isread=null){

        $this->autoCreateTable();
        if(!$pmmap||!is_array($pmmap)){
            $pmmap=['this'=>$this];
        }else{
            $pmmap['this']=$this;
        }
        $tablenames=[];
        foreach ($pmmap as $tb=>$pn){
            $model='';$sqlview='';$modelconfig=null;$_model=null;
            if($pn==$this){
                $_model=$this;
            }else {
                if (is_array($pn)) {
                    $modelname = $pn[0];
                    if (is_array($modelname)) {
                        if ($modelname['model']) {
                            $model = $modelname['mode'];
                        } else if ($modelname['sqlview']) {
                            $sqlview = $modelname['sqlview'];
                        }
                    } else {
                        $model = $modelname;
                    }
                    if (isset($pn[1])) {
                        $modelconfig = $pn[1];
                    }
                } else {
                    $model = $pn;
                }
                if($model){
                    $_model=$this->Model($model,$modelconfig);
                    $_model->autoCreateTable();

                }elseif($sqlview){
                    $_model=$this->SqlView($sqlview,$modelconfig);
                    $_model->autoCreateView();
                }
            }
            if($_model){
                $tablenames[$tb]=$_model->getAllTableName(true);
            }
        }

        $sqlstr=preg_replace_callback("/{{([a-zA-z_0-9]+)}}/i",function($matchs) use($tablenames){
            return $tablenames[$matchs[1]];
        },$sqltpl);

        return $this->query($sqlstr,$isread);

    }

    public function beginTransaction(){

        if(strtolower($this->_engine)!="innodb"){
            throw new XlException("数据库引擎类型不支持事务！");
        }

        $db=$this->_writedb?:$this->_readdb;

        if(!$db){
            $this->connectDbHostAndGetTable(); //连接数据库
        }

        $db->beginTransaction();

    }

    public function commit(){

        if(strtolower($this->_engine)!="innodb"){
            throw new XlException("数据库引擎类型不支持事务！");
        }

        $db=$this->_writedb?:$this->_readdb;

        if(!$db){
            $this->connectDbHostAndGetTable(); //连接数据库
        }

        $db->commit();

    }

    public function rollBack(){

        if(strtolower($this->_engine)!="innodb"){
            throw new XlException("数据库引擎类型不支持事务！");
        }

        $db=$this->_writedb?:$this->_readdb;

        if(!$db){
            $this->connectDbHostAndGetTable(); //连接数据库
        }

        $db->rollBack();

    }

    /**
     * 主动关闭数据库连接
     */
    public function close(){

        if($this->_writedb){
            $this->_writedb->close(); //关闭数据库连接
        }
        if($this->_readdb){
            $this->_readdb->close();
        }

    }
    /**
     * 静态方法区域
     */

    /**
    验证变量
    @param $vname名称，$v变量，$checkfun,验证方法，$size,大小长度
     */
    public static function checkValue($vname="",&$v,$checkfun,$size="",$default=''){

        $checkfun=strtolower(trim($checkfun));
        $cfarr=multiexplode([',','|',' '],$checkfun);
        if(count($cfarr)>1){
            if(in_array("empty",$cfarr)){
                if($v==""){
                    return true; //可以为空
                }
            }
            if(in_array("null",$cfarr)){
                if($v==null){
                    return true;
                }
            }
            if(in_array("zore",$cfarr)){
                if($v==0){
                    return true;
                }
            }
            $cfarr=array_filter($cfarr,function($s){
                if($s=="empty"||$s=="null"||$s=="zore"||empty($s)){
                    return false;
                }
                return true;
            });
            $checkfun=$cfarr[0];
        }
        if(!isset($v)){
            //没设置
            if(empty($default)){
                if(in_array($checkfun,array('int','float','double'))){
                    $v=0;
                }else if($checkfun=="object"){
                    $v=null;
                }else if($checkfun=="array"){
                    $v=array();
                }else if(in_array($checkfun,array("number","numeric"))){
                    $v="0";
                }else{
                    $v="";
                }
            }else{
                $v=$default; //设置为默认值
            }
        }
        if(!preg_match("/^is_.[a-z_0-9]+$/is",$checkfun)){
            $checkfun="is_".$checkfun;
        }
        if(!function_exists($checkfun)){
            //验证方法不存在
            $checkfun="is_string";//设置默认
        }
        if($checkfun=="is_string"){
            if(is_number($v)){
                $v=(string)$v;
            }
        }
        //检测长度
        if($size){
            $vtype=trim(substr($checkfun,3));
            if($vtype=="int"){
                $cv=(int)$v;
            }else if($vtype=="float"){
                $cv=(float)$v;
            }else if($vtype=="array"){
                $cv=(array)$v;
            }else if($vtype=="object"){
                $cv=(object)$v;
            }else{
                $cv=(string)$v;
            }
            if(is_array($size)){
                if(!in_array($cv,$size)){
                    return $vname."取值不合法";
                }
            }else{
                if(is_numeric($size)){
                    $max=(int)$size;
                    $min=0;
                }else{
                    $sz=explode("-",$size);
                    $max=$sz[1]===""?PHP_INT_MAX:(int)$sz[1];
                    $min=$sz[0]===""?-PHP_INT_MAX:(int)$sz[0];
                }
                if(is_int($cv)||is_float($cv)){
                    if($cv>$max){
                        return $vname.'不能大于最大值'.$max;
                    }
                    if($cv<$min){
                        return $vname.'不能小于最小值'.$min;
                    }
                }
                if(is_string($cv)||is_numeric($cv)){
                    $l=strLength($cv);

                    if($l>$max){

                        return $vname.'不能超过'.$max.'字';
                    }
                    if($l<$min){

                        return $vname.'不能小于'.$min.'字';
                    }
                }
                if(is_array($cv)){
                    $c=count($cv);
                    if($c>$max){
                        return $vname.'长度不能超过'.$max;
                    }
                    if($c<$min){
                        return $vname.'长度不能小于'.$min;
                    }
                }
            }
        }
        if(in_array($checkfun,array("is_int","is_float"))){$checkfun="is_number";}
        if(!$checkfun($v)){
            if($checkfun=="is_string"&&is_bool($v)){
                return true;
            }
            return false;//检测不合法
        }

        return true; //恒等于判断
    }

}