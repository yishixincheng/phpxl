<?php

namespace xl\base;

/**
 * Class XlMongodbFactory
 * @package xl\base
 * Model-Mongodb工厂，属于模型一部分，不支持分表模式
 */

final class XlMongodbFactory extends XlMvcBase {


    private $_workid=1;
    private $_dbconfig=null;
    private $_tablepre=null;

    private $_model=null;
    private $_tablename=null;
    private $_abstablename=null; //当前全表名包括前缀
    private $_database=null;
    private $_fields=null;
    private $_charset=null;

    private $_hostdsn=null; //主数据配置信息
    private $_db=null;


    public function __construct($modelname,$config=null) {

        /**
         * config置入，可以改变Model里默认设置的配置信息，说明如下
         *
         * 1.数组 ['database'=>'','tablename'=>'',workid=1,'hostdsn'=>''],dataname如果是/开头，绝对数据库名，否则是配置文件的数据库+"_database"
         *           tablename如果是/开头，代表是绝对表名（不包括前缀），如果是不加，则是模型里设置的表名+"_tablename"
         *
         *
         * 2.字符串，@值，则调用model模型里，config(值)进行获取配置信息
         *
         * 3.字符串，/开头，绝对表名。其他则是相对（model）的表名，即，表名+"_tablename"
         *
         * 说明：
         * (数据库，表名设置必须是20字一下，数字字母下划线）超过则报错
         *
         */

        parent::__construct();

        //根据modelname生成model具体实例，如user.User,或者user,img(表名找到对应的model类)
        $this->parseModelName($modelname,$config);
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

        if($tablename=$config['tablename']){
            if(strpos($tablename,"/")===0){
                $this->_tablename=substr($tablename,1);
            }else{
                $this->_tablename.="_".$tablename;
            }
        }

        $this->_database=$config['database']?:null;
        $this->_hostdsn=$config['hostdsn']?:null;
        $this->_workid=$config['workid']?:$this->_workid;//不同的主机对应workid不一样

    }

    /**
     * @param $modelname
     * @throws XlException
     * 解析绑定的Model
     */
    public function parseModelName($modelname,$config=null){

        //只支持2层目录
        if(($pos=strpos($modelname,'.'))){
            $folder=substr($modelname,0,$pos);
            $modelname=substr($modelname,$pos+1);
            $classname=ucfirst($modelname).'Model';
            $path=MODEL_PATH.$folder.D_S.$classname.'.php'; //文件路径
            if(!is_file($path)){
                $path=false;
            }
        }else{
            //查找
            $classname=ucfirst($modelname).'Model';
            $path=findfile(MODEL_PATH,$classname.'.php');
        }
        if(!$path){
            throw new XlException($classname." file is not exist!");
        }
        //包含文件
        include($path);
        $this->_model = new $classname; //实例化Model
        if (!$this->_model) {
            throw new XlException($classname . " is not defined");
        }

        try {
            if ($this->_model->alias) {
                $this->_tablename = $this->_model->alias;
            } else {
                $this->_tablename = strtolower($modelname); //表名即是model类名
            }
            if ($this->_model->database) {
                $this->_database = $this->_model->database;
            }

            if ($this->_model->hostdsn) {
                $this->_hostdsn = $this->_model->hostdsn;
            }

            if ($this->_model->fields) {
                $this->_fields = $this->_model->fields;
            }
            if(empty($this->_fields)){
                throw new XlException("Table　".$this->_tablename." fields is not set");
            }

            $this->_charset=$this->_model->charset?:"utf8";


            //解析配置参数
            $this->_parseConfig($config);

        }catch (\Exception $e){

            throw new XlException($e->getMessage()); //抛出异常

        }

    }
    public function parseSelectDb(){

        //选择数据表
        $this->_dbconfig=config("mongodb");

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
        $this->_tablepre=$this->_dbconfig['tablepre'];
        $this->_workid=$this->_dbconfig['workid']?:1; //主机编号，为了生成唯一的uuid

        if(!$this->_hostdsn){
            $this->_hostdsn=$this->_dbconfig['hostdsn'];
        }

        unset($this->_dbconfig['hostdsn']);

        if(!$this->_hostdsn){
            throw new XlException("mongodb hostdsn is not configure");
        }

        $hostdsn=$this->_hostdsn;

        if(!is_array($hostdsn)){
            $hostdsn=explode(',',$hostdsn);
        }
        $hostdsns=[];
        foreach ($hostdsn as $hn){

            $this->_parseHostDsn($hn);

            $hostdsns[]=$hn;
        }

        $this->_hostdsn=$hostdsns;

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
        $dbf=sysclass("mongodb",0);
        $dbconfig=$this->_dbconfig;
        $dbconfig['hostdns']=$this->_hostdsn;

        $this->_db = $dbf::getInstance($dbconfig,$this->_database);

    }
    /**
     * @return null
     * 获得绑定的model对象
     */

    public function getModelObj(){

        return $this->_model;

    }

    private function _table($table=''){

        $table=$table?:$this->_tablename;

        $this->_abstablename=$this->_tablepre.$table;
        return $this->_abstablename;

    }

    /**
     * @param string $columns
     * @param $condition
     * @param null $debug
     * 检索数据表
     */

    public function getOne($columns="*",$condition,$debug=null){

        return $this->_db->getOne($this->_table(),$columns,$condition,$debug);

    }

    /**
     * @param string $columns
     * @param $condition
     * @param null $debug
     * @return mixed
     * 获取多行
     */
    public function getRows($columns="*",$condition,$debug=null){

        return $this->_db->getRows($this->_table(),$columns,$condition,$debug);
    }

    /**
     * @param array $columns
     * @param null $debug
     * 插入一行
     */
    public function insert(array $columns,$debug=null){

        return $this->_db->insert($this->_table(),$columns,$debug);
    }

    /**
     * @param array $columns
     * @param $condition
     * @param null $debug
     * @return mixed
     * 更新
     */
    public function setColumn(array $columns,$condition,$debug=null){

        return $this->_db->setColumn($this->_table(),$columns,$condition,$debug);

    }

    /**
     * @param $condition
     * @param null $debug
     */
    public function delete($condition,$debug=null){

        return $this->_db->delete($this->_table(),$condition,$debug);

    }

    /**
     * @return mixed
     * 获得自增的id
     */
    public function getId(){

        //获取主键的id
        return $this->_db->insert_id();

    }




}