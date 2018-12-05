<?php

namespace xl\base;

/**
 * Class XlSqlviewFactory
 * @package xl\base
 * 不能继承，mysql视图接口
 */

final class XlOldSqlviewFactory extends XlMvcBase
{

    public static $db_checktableexist=[];

    private $_dbconfig=null;
    private $_tablepre=null;

    private $_model_path=MODEL_PATH;

    private $_model=null;
    private $_tablename=null;
    private $_abstablename=null; //当前全表名包括前缀
    private $_database=null;
    private $_fields=null;

    private $_master=null; //主数据配置信息
    private $_slaves=null; //从数据库配置信息
    private $_slave=null;  //当前命中的从数据库
    private $_readdb=null;
    private $_modelsconfig=null;
    private $_isdebug=false;
    private $_connectconfig=null;

    public function __construct($modelname,$config=null,$modelsconfig=null,$model=null,$model_name=null){
        /**
         * config置入，可以改变Model里默认设置的配置信息，说明如下
         * 1.数组 ['database'=>'','tablename'=>'','master'=>'',workid=1,'slaves'=>[]],dataname如果是/开头，绝对数据库名，否则是配置文件的数据库+"_database"
         *           tablename如果是/开头，代表是绝对表名（不包括前缀），如果是不加，则是模型里设置的表名+"_tablename"
         * 2.字符串，@值，则调用model模型里，config(值)进行获取配置信息
         * 3.字符串，/开头，绝对表名。其他则是相对（model）的表名，即，表名+"_tablename"
         * (数据库，表名设置必须是20字一下，数字字母下划线）超过则报错
         */
        if($this->_Isplugin){
            $this->_model_path=PLUGIN_PATH.$this->_Ns.D_S."model".D_S;
        }
        parent::__construct();
        $this->_model=$model;
        $this->_modelsconfig=$modelsconfig?:null;
        $this->parseModelName($modelname,$config,$model_name);
        $this->parseSelectDb(); //选择数据表

    }
    private function _parseConfig($config=null){

        if(empty($config)&&$config!==0){
            return;
        }
        if(is_object($config)){
            $config =  json_decode( json_encode($config),true);
        }
        if(!is_array($config)){
            $configstr=trim($config);
            $config=[];
            if(strpos($configstr,"@")===0){
                if(!method_exists($this->_model,"config")){
                    throw new XlException(get_class($this->_model)." method config is not defined");
                }
                $config=$this->_model->config(substr($configstr,1));
                if(!is_array($config)){
                    throw new XlException(get_class($this->_model)." method config returntype is must array"); //返回值类型必须是数组
                }
            }else{
                $config['tablename']=$configstr;
            }
        }
        if(!$config){
            return;
        }
        if($config['connectconfig']){
            $this->_connectconfig=$config['connectconfig'];
        }
        if($tablename=$config['tablename']){
            if(strpos($tablename,"/")===0){
                $this->_tablename=substr($tablename,1);
            }else{
                $this->_tablename.="_".$tablename;
            }
        }

        $this->_database=$config['database']?:null;
        $this->_master=$config['master']?:null;
        $this->_slaves=$config['slaves']?:null;

    }

    /**
     * @param $modelname
     * @throws XlException
     * 解析绑定的Model
     */

    public function parseModelName($modelname,$config=null,$model_name=null){

        //只支持2层目录
        if($this->_model==null){
            if(($pos=strrpos($modelname,'.'))){
                $folder=substr($modelname,0,$pos);
                $folder=str_replace(".",D_S,$folder);
                $modelname=substr($modelname,$pos+1);
                $classname=ucfirst($modelname).'Sqlview';
                $path=$this->_model_path.$folder.D_S.$classname.'.php'; //文件路径
                if(!is_file($path)){
                    $path=false;
                }
            }else{
                //查找
                $classname=ucfirst($modelname).'Sqlview';
                $path=findfile($this->_model_path,$classname.'.php');
            }
            if(!$path){
                throw new XlException($classname." file is not exist!");
            }
            //包含文件
            include_once($path);
            $this->_model = new $classname; //实例化Model
            if (!$this->_model) {
                throw new XlException($classname . " is not defined");
            }
        }
        try {
            if ($this->_model->alias) {
                $this->_tablename = $this->_model->alias;
            } else {
                $this->_tablename = strtolower($model_name?:$modelname).'_view'; //表名加后缀
            }
            if ($this->_model->database) {
                $this->_database = $this->_model->database;
            }
            if ($this->_model->master){
                $this->_master = $this->_model->master;
            }
            if($this->_model->slaves){
                $this->_slaves = $this->_model->slaves;
            }
            if($this->_model->fields){
                $this->_fields = $this->_model->fields;
            }
            if ($this->_model->tablepre){
                $this->_tablepre=$this->_model->tablepre;
            }
            //解析配置参数
            $this->_parseConfig($config);

        }catch (\Exception $e){

            throw new XlException($e->getMessage()); //抛出异常

        }

    }
    public function parseSelectDb(){

        //选择数据表
        $databaseconfig=$this->_connectconfig;
        if($databaseconfig){
            if(!is_array($databaseconfig)){
                $connectconfighashid=null;
                if(is_string($databaseconfig)){
                    if(substr($databaseconfig,0,1)=="@"){
                        $connectconfighashid=substr($databaseconfig,1);
                    }
                }
                if(method_exists($this->_model,"connectconfig")){
                    $databaseconfig=$this->_model->connectconfig($connectconfighashid);
                }
            }
            if(!is_array($databaseconfig)){
                $databaseconfig=null;
            }
        }else{
            if(method_exists($this->_model,"connectconfig")){
                $databaseconfig=$this->_model->connectconfig(null);
            }
        }
        $this->_dbconfig=$databaseconfig?:config("database");
        if($this->_database){
            if(strpos($this->_database,"/")===0){
                $this->_dbconfig['database']=substr($this->_database,1);
            }else{
                $this->_dbconfig['database'].="_".$this->_database;
            }
        }
        $pattern="/^[A-Za-z0-9_]+$/";
        if(!preg_match($pattern,$this->_dbconfig['database'])){
            throw new XlException("database ".$this->_dbconfig['database']." must in A-Za-z0-9_");
        }
        if(!preg_match($pattern,$this->_tablename)){
            throw new XlException("tablename ".$this->_tablename." must in A-Za-z0-9_");
        }

        $this->_database=$this->_dbconfig['database'];
        $this->_tablepre=$this->_tablepre?:$this->_dbconfig['tablepre'];

        if(!$this->_master){
            $this->_master=$this->_dbconfig['master'];
        }
        if(!$this->_slaves){
            $this->_slaves=$this->_dbconfig['slaves'];
        }
        unset($this->_dbconfig['slaves']);
        unset($this->_dbconfig['master']);

        if(!$this->_master){
            throw new XlException("sqlserver master is not configure");
        }
        $isalone=true;
        if($this->_slaves){
            $isalone=false;
            $this->_slave=$this->_slaves[array_rand($this->_slaves,1)]; //随机命中
        }

        $this->_parseHostDsn($this->_master);

        if($isalone){
            $this->_slave=$this->_master;
        }else{
            $this->_parseHostDsn($this->_slave);
        }

        $this->switchDb(); //选择数据库

    }
    private function _parseHostDsn(&$hostdsn){

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
        if(empty($hostdsn)){
            throw new XlException("hostdsn parse is invalid");
        }
    }

    public function switchDb($config=null){

        if($config&&is_array($config)) {
            $this->_dbconfig = $config;
            $this->_database=$this->_dbconfig['database'];
            $this->_tablepre=$this->_dbconfig['tablepre'];
        }
        $dbf=sysclass("dbfactory",0);
        $readconfig=$this->_dbconfig;

        $readconfig['hostname']=$this->_slave['host']?:'localhost';
        $readconfig['port']=$this->_slave['port']?:'3306';
        $readconfig['username']=$this->_slave['username'];
        $readconfig['password']=$this->_slave['password'];

        $this->_readdb=$dbf::getInstance($readconfig)->getDbObj($this->_database);

    }

    public function getTableName(){

        return $this->_tablename;

    }
    public function getAllTableName($ishasdb=false){

        if($ishasdb){
            return "`".$this->_database."`.`".$this->_tablepre.$this->_tablename."`";
        }else{
            return $this->_tablepre.$this->_tablename;
        }

    }
    public function getDbName(){

        return $this->_database;
    }

    public function query($sql) {
        $sql = preg_replace('/{{tablepre}}/', $this->_tablepre, $sql,1);
        return $this->_readdb->query($sql);
    }

    public function getQueryResult($sql)
    {
        return $this->_readdb->getQueryResult($sql);
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
    public function queryEx($sqltpl,$pmmap=null){

        $this->autoCreateView();
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

        return $this->query($sqlstr);

    }

    final public function fields($except=''){

        if(empty($this->_fields)){return '*';} //没有则返回所有
        $ect=array();
        if(is_string($except)){
            if(!empty($except)){
                $ect=explode(',',$except);
            }
        }else if(is_array($except)){
            $ect=$except;
        }
        if(is_array($this->_fields)){
            $keys=$this->_fields;
        }else{
            $keys=explode(',',$this->_fields);
        }

        return array_diff($keys,$ect); //差集
    }

    public function autoCreateView(){

        //自动检测视图是否存在
        $this->createView();

    }

    /**
     * 创建视图
     */
    public function createView(){


        $tablename=$this->_getTable();

        $sqlstr="CREATE OR REPLACE VIEW ".$tablename." ";
        if($this->_fields){
            if(is_array($this->_fields)){
                $fields=implode(',',$this->_fields);
            }else{
                $fields=$this->_fields;
            }
            $sqlstr.="(".$fields.") ";
        }
        $tablemap=$this->_model->tableMap(); //对应的表映射
        $asselect=$this->_model->asSelect();
        if(!$tablemap){
            throw new XlException("view ".$tablename." tableMap not defined!");
        }
        if(!$asselect){
            throw new XlException("view ".$tablename." asSelect not defined!");
        }

        foreach ($tablemap as $tb=>$mdparam){
            if(is_string($mdparam)){

                if($this->_modelsconfig&&$this->_modelsconfig[$tb]){

                    $_model=$this->Model($mdparam,$this->_modelsconfig[$tb]);
                }else{
                    $_model=$this->Model($mdparam);
                }
                $_model->autoCreateTable();
                $tablemap[$tb]=$_model->getAllTableName(true);

            }elseif(is_array($mdparam)){
                if($mdparam['model']){
                    if($this->_modelsconfig&&$this->_modelsconfig[$tb]){
                        $_model=$this->Model($mdparam['model'],$this->_modelsconfig[$tb]);
                    }else{
                        $_model=$this->Model($mdparam['model']);
                    }
                    $_model->autoCreateTable();
                    $tablemap[$tb]=$_model->getAllTableName(true);
                }else if($mdparam['sqlview']){
                    if($this->_modelsconfig&&$this->_modelsconfig[$tb]){
                        $_model=$this->SqlView($mdparam['model'],$this->_modelsconfig[$tb]);
                    }else{
                        $_model=$this->SqlView($mdparam['model']);
                    }
                    $_model->autoCreateView();
                    $tablemap[$tb]=$_model->getAllTableName(true);
                }elseif($mdparam['tablename']){
                    $tablemap[$tb]=$mdparam['tablename'];
                }else{
                    unset($tablemap[$tb]);
                }
            }else{
                unset($tablemap[$tb]);
            }
        }

        $asselect=preg_replace_callback("/{{([a-zA-z_0-9]+)}}/i",function($matchs) use($tablemap){
            return $tablemap[$matchs[1]];
        },$asselect);

        $sqlstr.=" AS ".$asselect;

        if($this->_isdebug){
            echo $sqlstr;
        }

        $rt=$this->query($sqlstr);

        if($rt){
            static::$db_checktableexist[$this->_database.$tablename]=1;
        }

        if($rt){
            return true;
        }
        return false;

    }

    public function setDebugMode($isdebug=false){

        $this->_isdebug=$isdebug;

    }

    public function dropView(){

        $this->query("DROP VIEW IF EXISTS ".$this->_getTable());

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

    private function _getTable(){

        //智能获取表名
        $this->_abstablename=$this->_tablepre.$this->_tablename;
        return $this->_abstablename;

    }

    public function getOne($columns="*",$condition,$debug=null){

        //检索数据表,避免重新获取错误，最大智能尝试一次

        $condition=$this->_parseConditionArray($condition);

        $iscreatetable=false;

        $rt=$this->_readdb->getOne($this->_getTable(),$columns,$condition,$debug,function($errid) use(&$iscreatetable){

            //异常捕获,表不存在，则检测是否创建
            if($errid=="TABLE_NOT_EXIST"){
                $iscreatetable=$this->createView();
            }

            return $iscreatetable;

        });

        if($iscreatetable){
            return $this->getOne($columns,$condition,$debug); //重新获取
        }


        return $rt;

    }

    public function getRows($columns="*",$condition,$debug=null){

        //检索数据表,避免重新获取错误，最大智能尝试一次

        $condition=$this->_parseConditionArray($condition);

        $iscreatetable=false;


        $rt=$this->_readdb->getRows($this->_getTable(),$columns,$condition,$debug,function ($errid) use(&$iscreatetable){

            //异常捕获,表不存在，则检测是否创建
            if($errid=="TABLE_NOT_EXIST"){
                $iscreatetable=$this->createView();
            }

            return $iscreatetable;

        });

        if($iscreatetable){
            return $this->getRows($columns,$condition,$debug); //重新获取
        }


        return $rt;

    }

    public function getRowNum($condition,$debug=null,$isgroup=false){

        $condition=$this->_parseConditionArray($condition);

        $iscreatetable=false;

        $rt=$this->_readdb->getRowNum($this->_getTable(),$condition,$debug,$isgroup,function($errid) use (&$iscreatetable){

            //异常捕获,表不存在，则检测是否创建
            if($errid=="TABLE_NOT_EXIST"){
                $iscreatetable=$this->createView();
            }
            return $iscreatetable;
        });

        if($iscreatetable){
            return $this->getRowNum($condition,$debug,$isgroup); //重新获取
        }


        return $rt;


    }
    public function sumRow($column,$condition,$debug=null){


        $condition=$this->_parseConditionArray($condition);
        $iscreatetable=false;

        $rt=$this->_readdb->sumRow($this->_getTable(),$column,$condition,$debug,function($errid) use (&$iscreatetable){

            //异常捕获,表不存在，则检测是否创建
            if($errid=="TABLE_NOT_EXIST"){
                $iscreatetable=$this->createView();
            }

            return $iscreatetable;

        });

        if($iscreatetable){
            return $this->sumRow($column,$condition,$debug); //重新获取
        }

        return $rt;


    }
    final public function search($pm,$debug=null){

        //封装搜索结果集
        /*
         namespace="命名空间，保持唯一",params，搜索键值对,keywords,关键字列表，others(比如between,or)，orders,排序列表，page=>1，页，num=>每页数量，
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
        $countcache=$pm['countcache']?$pm['countcache']:30; //30秒更新一次计算数字
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
                if($v&&(is_array($v)||is_string($v))){
                    $instr=$this->orz_in_str($k,$v);
                    $this->orz_and_condition("and ".$instr,$condition);
                    $params[]=$v;
                }
            }
        }
        if($keywords&&is_array($keywords)){
            foreach($keywords as $k=>$v){
                if($v!==""&&is_string($v)){
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
        $namespace=$this->_database.'/'.$this->_tablename; //命名标识组成唯一条件
        if($needallcount){
            //需要调用
            $tag_cache_name=md5($condition);
            if(!$count=getcache('searchcount/'.$namespace."/".$tag_cache_name)) {
                if ($isgroup) {
                    $count = $this->getRowNum($condition,null,true);
                } else {
                    $count = $this->getRowNum($condition);
                }
                if($count){
                    setcache('searchcount/'.$namespace."/".$tag_cache_name,$count,$countcache);
                }
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
     * 检查字段是否存在
     * @param $field 字段名
     * @return boolean
     */
    final public function fieldExists($field) {
        $fields = $this->_readdb->getFields($this->_table());
        return array_key_exists($field, $fields);
    }

    final public function listTables() {
        return $this->_readdb->listTables();
    }

    final public function orz_in_str($key,$vals){

        $ks=$this->_parse_orz_in_vals($vals);

        if($ks){

            return $key.' in ('.$ks.')';
        }

        return '';

    }

    final public function orz_notin_str($key,$vals){


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

    final public function orz_and_equalstr($pm){
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
    final public function orz_between_str($k,$bv,$ev){
        if($ev){
            return " $k>='$bv' and $k<='$ev' ";
        }else if($bv){
            return " $k>='$bv' ";
        }
        return '';
    }
    final public function orz_orders($pm){
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
    final public function orz_groups($pm){

        if(is_string($pm)){
            return ' group by '.$pm.' ';
        }
        if(is_array($pm)){
            $pm=implode(',',$pm);
            return ' group by '.$pm.' ';
        }
        return '';

    }
    final public function orz_limit($page,$num,$offset=0){
        if($page&&$num){
            $offset=($page-1)*$num+$offset;
            return " limit $offset,$num";
        }
        return '';
    }
    final public function orz_and_condition($c,&$condition){
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



}