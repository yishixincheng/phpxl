<?php

namespace xl\classs\db;

use xl\base\XlClassBase;

class MysqliClass extends XlClassBase implements DbInterface{

    use Dbtrait;
    public static $dbhostlist=[];
    public static $tryconnectnum=[];
    private $config = null;
    public $link = null;
    public $mysqli=null;
    public $lastqueryid = null;
    public $querycount = 0;
    private $_linkkey=null;

    public function open($config){

        $this->config = $config;
        if($config['autoconnect'] == 1) {
            $this->connect($config);
        }
        return $this;
    }

    public function getSqlObj(){

        return $this->mysqli;

    }

    /**
     * @param $config
     * 测试连接是否成功
     */
    public function test($config){

        $this->mysqli=new \mysqli($config['hostname'],$config['username'],$config['password'],"",$config['port']?:ini_get("mysqli.default_port"));
        if(mysqli_connect_errno()){
            return false;
        }
        return true;
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

    public function connect($dbconfig=null){

        if(!($linkey=$this->_linkkey)){
            $this->_linkkey=$linkey=md5($this->getLinkKey($dbconfig));
        }

        if(!static::$dbhostlist[$linkey]){

            $this->mysqli=new \mysqli($dbconfig['hostname'],$dbconfig['username'],$dbconfig['password'],"",$dbconfig['port']?:ini_get("mysqli.default_port"));
            if(mysqli_connect_errno()){
                $this->halt('Cannot connect database from mysqlli');
                return false;
            }
            $charset = isset($dbconfig['charset']) ? $dbconfig['charset'] : 'uft8';
            $this->mysqli->set_charset($charset);

            if(defined("ISCLI")&&ISCLI){
                if(count(self::$dbhostlist)>10) {
                    array_shift(self::$dbhostlist);
                }
            }
            self::$dbhostlist[$linkey] = $this->mysqli;

        }else{
            $this->mysqli=static::$dbhostlist[$linkey];
        }

        if($dbconfig['database'] && !$this->mysqli->select_db($dbconfig['database'])) {
            //创建数据库
            $sqlstr="create database ".$dbconfig['database'];
            $this->execute($sqlstr);
            $this->mysqli->select_db($dbconfig['database']);//自动选择
        }

        $this->database = $dbconfig['database'];
        return $this->mysqli;


    }
    public function getOne($table,$columns="*",$condition,$debug=null,$hook=null,$aop=null){

        $sql=$this->S($table,$columns,$condition,$debug,$hook,$aop);

        if(!$sql){return null;}

        return $this->getQueryResult($sql);


    }
    public function getRows($table,$columns="*",$condition,$debug=null,$hook=null,$aop=null){

        $sql=$this->S($table,$columns,$condition,$debug,$hook,$aop);

        if(!$sql){return null;}

        $arr=array();
        while($r=$this->getQueryResult($sql)){
            array_push($arr,$r);
        }
        return $arr;

    }
    public function setColumn($table,array $columns,$condition,$debug=null,$hook=null,$aop=null){


        return $this->U($table,$columns,$condition,$debug,$hook,$aop);

    }

    public function insert($table,array $columns,$debug=null,$hook=null,$aop=null){

        return $this->I($table,$columns,$debug,$hook,$aop);

    }
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
    public function delete($table,$condition,$debug=null,$hook=null,$aop=null){

        return $this->D($table,$condition,$debug,$hook);

    }

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
    public function getRowNum($table,$condition,$isgroup=false,$debug=null,$hook=null,$aop=null){

       return $this->C($table,"*",$condition,$debug,$isgroup,$hook,$aop);

    }
    public function query($sql){

        return $this->execute($sql);

    }
    public function getQueryResult($sql){

        return $sql->fetch_assoc();

    }
    /*old 兼容*/
    public function insert_id(){

        return $this->mysqli->insert_id;
    }
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
            $sqlstr="SELECT $str FROM `".$this->config['database']."`.`".$table."`" .$condition;

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
            $r=$sql->num_rows;
            $CCC=$r;
        }else{
            $r=$sql->fetch_array();
            $CCC=$r['CCC'];
        }
        return $CCC;

    }
    public function affectedRows() {

        return $this->mysqli->affected_rows;
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
    public function tableExists($table){

        $tables = $this->listTables();
        return in_array($table, $tables) ? 1 : 0;

    }
    public function listTables(){

        $tables = array();
        $this->execute("SHOW TABLES");
        while($r = $this->fetchNext()) {
            $tables[] = $r['Tables_in_'.$this->config['database']];
        }
        return $tables;
    }
    public function fetchNext($type=MYSQLI_ASSOC) {

        $res=$this->lastqueryid->fetch_array($type);
        if(!$res) {
            $this->_freeResult();
        }
        return $res;
    }
    private function _freeResult() {
        if($this->lastqueryid) {
            $this->lastqueryid->free();
            $this->lastqueryid = null;
        }
    }
    private function execute($sql,$hook=null,$aop=null) {
        if(!$this->mysqli) {
            $this->connect($this->config);
        }else{
            $this->mysqli->select_db($this->config['database']); //选择数据库
        }
        $beforeparam=null;
        if($aop){
            $beforeparam=call_user_func_array($aop,['before']);
        }
        $this->lastqueryid = $this->mysqli->query($sql) or $this->halt($this->mysqli->error, $sql,$hook,function ($errno,$logger) use($sql,$hook,$aop){

            //cli模式下的错误回调
            if($errno==2006){
                //MySQL server has gone away 情况下，重新连接数据库
                if (isset(static::$tryconnectnum[$this->_linkkey])){
                    //重试三次
                    if(static::$tryconnectnum[$this->_linkkey]>3){
                        return null;
                    }
                }else{
                    static::$tryconnectnum[$this->_linkkey]=1;
                }

                unset(self::$dbhostlist[$this->_linkkey]);

                if($this->mysqli){
                    $this->mysqli->close();
                }
                $this->mysqli=null;
                $_errormsg="超时连接，已重新连接".PHP_EOL;
                $_errormsg.="MySQL Query ".$sql.PHP_EOL;
                $_errormsg.="重新执行第".static::$tryconnectnum[$this->_linkkey]."次".PHP_EOL;
                $_errormsg.="--------------------------------".PHP_EOL;

                if($logger){
                    $logger->write($_errormsg,true,true);
                }

                static::$tryconnectnum[$this->_linkkey]++;

                $this->execute($sql,$hook,$aop); //重新执行
            }

        });
        if($aop){
            if(is_array($beforeparam)){
                $beforeparam['sqlstr']=$sql;
            }
            call_user_func_array($aop,['after',$beforeparam]);
        }
        unset(static::$tryconnectnum[$this->_linkkey]);
        $this->querycount++;
        return $this->lastqueryid;
    }

    public function beginTransaction(){

        if(!$this->mysqli) {
            $this->connect($this->config);
        }

        $this->mysqli->autocommit(false); //自动提交模式设为false

    }

    public function commit(){

        $this->mysqli->commit(); //开启事务
    }

    public function rollBack(){

        $this->mysqli->rollback();
    }


    public function error(){

        return $this->mysqli->error;

    }
    public function errno(){
        return $this->mysqli->errno;
    }
    public function close(){

        if($this->_linkkey){
            if(static::$dbhostlist[$this->_linkkey]){
                static::$dbhostlist[$this->_linkkey]->close();
                unset(static::$dbhostlist[$this->_linkkey]); //移除
            }
        }
        if($this->mysqli){
            $this->mysqli->close();
        }
    }

}