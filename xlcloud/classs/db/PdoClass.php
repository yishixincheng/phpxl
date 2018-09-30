<?php

namespace xl\classs\db;

use xl\base\XlClassBase;

class PdoClass extends XlClassBase implements DbInterface {

    use Dbtrait;

    public static $dbhostlist=[];
    private $config = null;
    public $link = null;
    public $pdo=null;
    public $lastqueryid = null;
    public $querycount = 0;
    private $_linkkey=null;

    /**
     * 连接数据库
     */

    public function open($config){


        $this->config = $config;
        if($config['autoconnect'] == 1) {
            $this->connect($config);
        }
        return $this;

    }
    public function getSqlObj(){

        return $this->pdo;

    }
    private function _getDsn($config){

        if($config['dsn']){
            return $config['dsn'];
        }
        $dblib=$config['driver']?:"mysql"; //默认是mysql引擎

        $dsn=$dblib.":host=".$config['hostname'];
        if($config['port']){
            $dsn.=";port=".$config['port'];
        }
        $dsn.=";dbname=".$config['database'];

        return $dsn;

    }

    /**
     * @param $config
     * 测试连接是否成功
     */
    public function test($config){

        $isright=true;
        try{
            new \PDO($this->_getDsn($config),$config['username'],$config['password']);
        }catch (\PDOException $e){
            if($e->getCode()!="1049"){
                $isright=false;
            }
        }
        return $isright;
    }

    public function getCacheKey($tablename,$condition=null){

        if(!$this->_linkkey) {
            $this->_linkkey=md5($this->getLinkKey($this->config));
        }

        $key=$this->_linkkey.'_'.$this->config['database'].'_'.$tablename;
        if($condition){
            $key.="_".md5($condition);
        }
        return $key;

    }
    public function connect($dbconfig){

        if(!($linkey=$this->_linkkey)){
            $this->_linkkey=$linkey=md5($this->getLinkKey($dbconfig));
        }
        if(!static::$dbhostlist[$linkey]){

            try{

                $this->pdo=new \PDO($this->_getDsn($dbconfig),$dbconfig['username'],$dbconfig['password']);
                if($dbconfig['pconnect']){
                    $this->pdo->setAttribute(\PDO::ATTR_PERSISTENT,true); //长连接
                }
                $this->pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, true);  // 设置当字符串为空转换为 SQL 的 NULL
                $charset = isset($dbconfig['charset']) ? $dbconfig['charset'] : 'uft8';
                $this->pdo->query('set names '.$charset.';');

            }catch (\PDOException $e){

                if($e->getCode()=="1049"){
                    //创建数据库
                    $this->_autoCreateDb($dbconfig['database']);
                }else{
                    $this->halt($e->getMessage());
                }
                return false;

            }

            if(!(defined("ISCLI")&&ISCLI)){
                self::$dbhostlist[$linkey] = $this->pdo;
            }

        }else{
            $this->pdo=static::$dbhostlist[$linkey];
        }

        if($dbconfig['database']&&!$this->_selectDb($dbconfig['database'])) {
            //创建数据库
            $this->_autoCreateDb($dbconfig['database']);//自动创建数据库
        }

        $this->database = $dbconfig['database'];
        return $this->pdo;

    }
    private function _autoCreateDb($database){

        if(!$this->pdo){
            $dbconfig=$this->config; //异常则连接默认的数据库
            $dbconfig['database']="mysql";
            $this->pdo=new \PDO($this->_getDsn($dbconfig),$dbconfig['username'],$dbconfig['password']);
        }
        $sqlstr="create database ".$database;
        $this->pdo->exec($sqlstr); //执行

        $this->_selectDb($database); //选择数据库

    }
    private function _selectDb($database){


        $sqlstr="use ".$database;

        $row=$this->pdo->exec($sqlstr); //执行

        if($row===false) {
            return false;
        }
        return true;

    }


    /**
     * 功能：获取一行
     */

    public function getOne($table,$columns="*",$condition,$debug=null,$hook=null,$aop=null){

        $sql=$this->S($table,$columns,$condition,$debug,$hook,$aop);

        if(!$sql){return null;}

        return $this->getQueryResult($sql);

    }

    /**
     * 获取多行
     */
    public function getRows($table,$columns="*",$condition,$debug=null,$hook=null,$aop=null){

        $sql=$this->S($table,$columns,$condition,$debug,$hook,$aop);

        if(!$sql){return null;}

        $arr=array();
        while($r=$this->getQueryResult($sql)){
            array_push($arr,$r);
        }
        return $arr;

    }

    /**
     * 设置列字段
     */

    public function setColumn($table,array $columns,$condition,$debug=null,$hook=null,$aop=null){

        return $this->U($table,$columns,$condition,$debug,$hook,$aop);

    }

    /**
     * 插入
     */

    public function insert($table,array $columns,$debug=null,$hook=null,$aop=null){

        return $this->I($table,$columns,$debug,$hook,$aop);

    }

    /**
     * 多行插入
     */
    public function inserts($table,array $columns,array $values,$debug=null,$hook=null,$aop=null){

        if(empty($table)||empty($columns)||empty($values)){
            return false;
        }

        $dstr='';
        $istrs='';
        foreach($columns as $v){
            $dstr.="`".$v."`,";
        }
        foreach($values as $vs){

            if($vs&&is_array($vs)){
                array_walk($vs, array($this, 'add_special_char2'));
                $istrs.='(';
                foreach($vs as $v){
                    $istrs.="'".$v."',";
                }
                $istrs=rtrim($istrs,",");
                $istrs.='),';
            }
        }
        $dstr=rtrim($dstr,',');
        $istrs=rtrim($istrs,',');

        $sqlstr="insert into `".$this->config['database']."`.`".$table."` ($dstr) values $istrs;";

        if(!$this->debugLog($debug,$sqlstr)){
            return false;
        }

        if($this->sql=$this->execute($sqlstr,$hook,$aop))
        {
            return true;
        }
        else
        {
            return false;
        }

    }


    /**
     * 删除
     */
    public function delete($table,$condition,$debug=null,$hook=null,$aop=null){

        return $this->D($table,$condition,$debug,$hook,$aop);

    }


    /*
     * unoin查询
     */

    public function unionAll($tables,$columns,$conditions,$debug=null,$hook=null){

        $tpm=array();
        $len=count($tables);
        if(!is_array($columns[0])){
            $columns[0]=$columns;
        }
        for($i=0;$i<$len;$i++){
            $table=$tables[$i];
            $column=$columns[$i]?$columns[$i]:$columns[0];
            $condition=$conditions[$i]?$conditions[$i]:$conditions[0];

            array_walk($column, array($this, 'add_special_char'));
            $str=implode(",",$column);
            $sqlstr="SELECT $str FROM `".$this->config['database']."`.`".$table."`" .$condition;
            $tpm[]=$sqlstr;
        }
        $sqlstr=implode(' UNION ALL ',$tpm);

        if(!$this->debugLog($debug,$sqlstr)){
            return null;
        }

        if($sql=$this->execute($sqlstr,$hook))
        {
            $arr=array();
            while($r=$this->getQueryResult($sql)){
                array_push($arr,$r);
            }
            return $arr;
        }

        return null;

    }

    /**
     * 个数
     */

    public function getRowNum($table,$condition,$isgroup=false,$debug=null,$hook=null){

        return $this->C($table,"*",$condition,$debug,$isgroup,$hook);

    }


    /**
     * query 自定义查询，{db}{tb}
     */

    public function query($query){

        return $this->execute($query);

    }


    public function getQueryResult($sql){

        return $sql->fetch(\PDO::FETCH_ASSOC); //获得下一行

    }


    public function insert_id(){

        return $this->pdo->lastInsertId();

    }

    public function execute($sql,$hook=null,$aop=null){

        if(!$this->pdo) {
            $this->connect($this->config);
        }else{
            $this->_selectDb($this->config['database']); //选择数据库
        }
        $beforeparam=null;
        if($aop){
            $beforeparam=call_user_func_array($aop,['before',$beforeparam]);
        }
        $this->lastqueryid = $this->pdo->query($sql) or $this->halt($this->error(), $sql,$hook);

        if($aop){
            if(is_array($beforeparam)){
                $beforeparam['sqlstr']=$sql;
            }
            call_user_func_array($aop,['after',$beforeparam]);
        }

        $this->querycount++;
        return $this->lastqueryid;

    }

    public function beginTransaction(){

        if(!$this->pdo) {
            $this->connect($this->config);
        }

        $this->pdo->beginTransaction(); //开启事务

    }

    public function commit(){

        $this->pdo->commit(); //开启事务
    }

    public function rollBack(){

        $this->pdo->rollBack();
    }


    /*快捷操作*/

    private function I($table,array $columns,$debug=null,$hook=null,$aop=null){

        if($table&&$columns)
        {
            $dstr="";
            $istr="";
            array_walk($columns, array($this, 'add_special_char2'));
            foreach($columns as $key=>$value)
            {
                $dstr.="`".$key."`,";
                $istr.="'".$value."',";
            }
            $dstr=rtrim($dstr,",");
            $istr=rtrim($istr,",");
            $sqlstr="insert into `".$this->config['database']."`.`".$table."` ($dstr) values ($istr)";

            if(!$this->debugLog($debug,$sqlstr)){
                return false;
            }

            if($this->sql=$this->execute($sqlstr,$hook,$aop))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        return false;


    }

    private function U($table,array $columns,$condition,$debug=null,$hook=null,$aop=null){

        if(empty($condition)){return false;}
        $arrstr="";
        array_walk($columns, array($this, 'add_special_char2'));
        foreach($columns as $key=>$value)
        {
            if(is_string($value)){

                $op=substr($value,0,2);
                switch($op){
                    case '+=':
                        $arrstr.=$key."=".$key."+".(int)substr($value,2).",";
                        break;
                    case '-=':
                        $arrstr.=$key."=".$key."-".(int)substr($value,2).",";
                        break;
                    case '&=':
                        $arrstr.=$key."=`".substr($value,2)."`,";
                        break;
                    default:
                        $arrstr.=$key."='".$value."',";
                        break;
                }

            }else{
                $arrstr.=$key."='".$value."',";
            }
        }
        if($arrstr)
        {
            $arrstr=rtrim($arrstr,",");
            if($table)
            {
                //更新数据表
                $sqlstr="UPDATE `".$this->config['database']."`.`".$table."`  SET $arrstr ".$condition;

                if(!$this->debugLog($debug,$sqlstr)){
                    return false;
                }

                if($this->execute($sqlstr,$hook,$aop))
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }
        }

        return false;

    }

    private function D($table,$condition,$debug=null,$hook=null,$aop=null){

        if(empty($condition))
        {
            return false;
        }
        else if($condition=="all")
        {
            $condition=""; //删除所有的数据
        }
        if($table)
        {
            $sqlstr="DELETE FROM `".$this->config['database']."`.`".$table."` ".$condition;
            if(!$this->debugLog($debug,$sqlstr)){
                return false;
            }
            if($this->execute($sqlstr,$hook,$aop))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        return false;

    }

    private function S($table,$columns="*",$condition,$debug=null,$hook=null,$aop=null){

        $str="*";
        if(is_string($columns))
        {
            $str=$columns;
        }
        else if(is_array($columns))
        {
            array_walk($columns, array($this, 'add_special_char'));
            $str=implode(",",$columns);
        }
        if($str&&$table)
        {
            $sqlstr="SELECT $str FROM `".$this->config['database']."`.`".$table."` " .$condition;

            if(!$this->debugLog($debug,$sqlstr)){
                return null;
            }

            if($sql=$this->execute($sqlstr,$hook,$aop))
            {
                return $sql;
            }
            else
            {
                return false;
            }
        }

        return false;

    }
    private function C($table,$column="*",$condition,$debug=null,$isgroup=false,$hook=null,$aop=null){

        return $this->_calculatecolumn($table,'COUNT',$column,$condition,$debug,$isgroup,$hook,$aop);


    }
    public function sumRow($table,$column,$condition,$debug=null,$hook=null,$aop=null){
        return $this->_calculatecolumn($table,'SUM',$column,$condition,$debug,false,$hook,$aop);
    }
    private function _calculatecolumn($table,$op,$column,$condition,$debug=null,$isgroup=false,$hook=null,$aop=null){

        $sqlstr="SELECT $op($column) as CCC FROM `".$this->config['database']."`.`".$table."` ".$condition;

        if(!$this->debugLog($debug,$sqlstr)){
            return null;
        }
        $sql=$this->execute($sqlstr,$hook,$aop);
        if($isgroup){
            $r=$sql->rowCount();
            $CCC=$r;
        }else{
            $r=$sql->fetch(\PDO::FETCH_ASSOC);

            $CCC=$r['CCC'];
        }
        return $CCC;

    }

    /**
     * 功能集合
     */

    public function tableExists($table){

        $tables = $this->listTables();
        return in_array($table, $tables) ? 1 : 0;

    }

    public function listTables(){

        $tables = [];
        $this->execute("SHOW TABLES FROM ".$this->config['database']);
        while($r = $this->fetchNext()) {
            $tables[] = $r['Tables_in_'.$this->config['database']];
        }

        return $tables;
    }

    public function affectedRows() {

        return $this->lastqueryid->rowCount();
    }
    public function getPrimary($table) {
        $this->execute("SHOW COLUMNS FROM $table");
        while($r = $this->fetchNext()) {
            if($r['Key'] == 'PRI') break;
        }
        return $r['Field'];
    }

    public function getFields($table) {
        $fields = array();
        $this->execute("SHOW COLUMNS FROM $table");
        while($r = $this->fetchNext()) {
            $fields[$r['Field']] = $r; //Type,Null,Key,Default,Extra
        }
        return $fields;
    }

    public function fetchNext($type=\PDO::FETCH_ASSOC) {

        $res=$this->lastqueryid->fetch($type);

        return $res;
    }

    public function errno(){

        $error=$this->pdo->errorInfo(); //42S02,1146表不存在

        return $error[1];

    }

    public function error(){

        $error=$this->pdo->errorInfo();

        return $error[2];

    }

    /**
     * 关闭数据库连接
     */

    public function close(){

        if($this->_linkkey){
            if(static::$dbhostlist[$this->_linkkey]){
                static::$dbhostlist[$this->_linkkey]=null;   //关闭
                unset(static::$dbhostlist[$this->_linkkey]); //移除
            }
        }

    }


}