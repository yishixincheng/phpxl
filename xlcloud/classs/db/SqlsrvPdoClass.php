<?php

namespace xl\classs\db;

use xl\base\XlClassBase;

class SqlsrvPdoClass extends XlClassBase implements DbInterface
{
    use SqlsrvDbtrait;
    public static $dbhostlist=[];
    public static $tryconnectnum=[];
    private $config = null;
    public $link = null;
    public $pdo=null;
    public $lastqueryid = null;
    public $querycount = 0;
    private $_linkkey=null;

    public function open($config)
    {
        // TODO: Implement open() method.
        $this->config = $config;
        if($config['autoconnect'] == 1) {
            $this->connect($config);
        }
        return $this;
    }

    private function _getDsn($config){

        if(!empty($config['dsn'])){
            return $config['dsn'];
        }
        $dsn="sqlsrv:Server=".$config['hostname'];
        if($config['port']){
            $dsn.=",".$config['port'];
        }
        $dsn.=";Database=".$config['database'];

        return $dsn;

    }

    public function connect($dbconfig)
    {
        // TODO: Implement connect() method.
        if(!($linkey=$this->_linkkey)){
            $this->_linkkey=$linkey=md5($this->getLinkKey($dbconfig));
        }
        if(empty(static::$dbhostlist[$linkey])){

            try{
                $this->pdo=new \PDO($this->_getDsn($dbconfig),$dbconfig['username'],$dbconfig['password']);
                if($dbconfig['pconnect']){
                    $this->pdo->setAttribute(\PDO::ATTR_PERSISTENT,true); //长连接
                }
                $this->pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, true);  // 设置当字符串为空转换为 SQL 的 NULL

            }catch (\PDOException $e){

                if($e->getCode()=="42000"){
                    //创建数据库
                    $this->_autoCreateDb($dbconfig['database']);
                }else{
                    $this->halt($e->getMessage());
                }
                return false;

            }


            if(defined("ISCLI")&&ISCLI){
                if(count(self::$dbhostlist)>10) {
                    array_shift(self::$dbhostlist);
                }
            }
            self::$dbhostlist[$linkey] = $this->pdo;

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
            $dbconfig['database']="master";
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


    public function getSqlObj()
    {
        // TODO: Implement getSqlObj() method.
        return $this->pdo;

    }

    public function getOne($table,$columns="*",$condition,$debug=null,$hook=null,$aop=null)
    {
        // TODO: Implement getOne() method.
        $sql=$this->S($table,$columns,$condition,"one",$debug,$hook,$aop);

        if(!$sql){return null;}

        return $this->getQueryResult($sql);

    }
    public function getRowNum($table,$condition,$isgroup=false,$debug=null,$hook=null)
    {
        // TODO: Implement getRowNum() method.
        return $this->C($table,"*",$condition,$debug,$isgroup,$hook);
    }
    public function getRows($table, $columns = "*", $condition, $debug = null, $hook = null, $aop = null)
    {
        // TODO: Implement getRows() method.

        $sql=$this->S($table,$columns,$condition,"rows",$debug,$hook,$aop);

        if(!$sql){return null;}

        $arr=array();
        while($r=$this->getQueryResult($sql)){
            array_push($arr,$r);
        }
        return $arr;

    }

    public function insert($table, array $columns, $debug = null, $hook = null, $aop = null)
    {
        // TODO: Implement insert() method.
        return $this->I($table,$columns,$debug,$hook,$aop);

    }

    public function inserts($table, array $columns, array $values, $debug = null, $hook = null, $aop = null)
    {
        // TODO: Implement inserts() method.

        if(empty($table)||empty($columns)||empty($values)){
            return false;
        }

        $dstr='';
        $istrs='';
        foreach($columns as $v){
            $dstr.=$v.",";
        }
        foreach($values as $vs){

            if($vs&&is_array($vs)){
                array_walk($vs, [$this, 'add_special_char2']);
                $istrs.='(';
                foreach($vs as $v){
                    if(is_array($v)){
                        $v=json_encode($v);
                        $this->add_backslash($v);
                    }
                    $istrs.="'".$v."',";
                }
                $istrs=rtrim($istrs,",");
                $istrs.='),';
            }
        }
        $dstr=rtrim($dstr,',');
        $istrs=rtrim($istrs,',');

        $sqlstr="insert into ".$this->_getTableFullName($table)." ($dstr) values $istrs;";

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
    public function setColumn($table, array $columns, $condition, $debug = null, $hook = null, $aop = null)
    {
        // TODO: Implement setColumn() method.
        return $this->U($table,$columns,$condition,$debug,$hook,$aop);
    }
    public function delete($table, $condition, $debug = null, $hook = null, $aop = null)
    {
        // TODO: Implement delete() method.
        return $this->D($table,$condition,$debug,$hook,$aop);
    }

    public function unionAll($tables, $columns, $conditions, $debug = null, $hook = null)
    {
        // TODO: Implement unionAll() method.

        $tpm=array();
        $len=count($tables);
        if(!is_array($columns[0])){
            $columns[0]=$columns;
        }
        for($i=0;$i<$len;$i++){
            $table=$tables[$i];
            $column=$columns[$i]?$columns[$i]:$columns[0];
            $condition=$conditions[$i]?$conditions[$i]:$conditions[0];

            array_walk($column, [$this, 'add_special_char']);
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
        $this->lastqueryid = $this->pdo->query($sql) or $this->halt($this->error(), $sql,$hook,function ($errno,$logger) use($sql,$hook,$aop){

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

                $this->pdo=null;
                $_errormsg="超时连接，已重新连接".PHP_EOL;
                $_errormsg.="SqlSrv Query ".$sql.PHP_EOL;
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

    private function _getTableFullName($table){

        if(strpos($table,".")===false){
            $user=(isset($this->config['schemaname'])&&$this->config['schemaname'])?$this->config['schemaname']:"dbo";
            return $this->config['database'].'.'.$user.'.'.$table;
        }else{
            return $this->config['database'].$table;
        }

    }

    /**
     * @param $condition
     *
     */
    private function _parseConditionLimitAndOrderBy($condition,$parseorder=true){

        $exp="/\b(order\s+by\s+[^\s]+\s+.*(asc|desc)\b)/i";
        $order=null;$limit=null;$num=null;$offset=null;
        if($parseorder&&preg_match($exp,$condition,$mt)){
            $order=$mt[1];
            $condition=preg_replace($exp,"",$condition);
        }
        $exp="/\b(limit\s+(\d+)(\s*,\s*(\d+))?\b)/i";
        if(preg_match($exp,$condition,$mt)){
            $limit=$mt[1];
            if(count($mt)==3){
                $num=$mt[2];
                $offset=0;
            }else{
                $offset=$mt[2];
                $num=$mt[4];
            }
            $condition=preg_replace($exp,"",$condition);
        }

        return [
            'condition'=>$condition,
            'order'=>$order,
            'limit'=>$limit,
            'offset'=>$offset,
            'num'=>$num
        ];

    }

    private function S($table,$columns="*",$condition,$oneorrow="",$debug=null,$hook=null,$aop=null){

        $fulltable=$this->_getTableFullName($table);
        $str="*";
        if(is_string($columns)){
            $str=$columns;
        }
        else if(is_array($columns))
        {
            array_walk($columns, [$this, 'add_special_char']);
            $str=implode(",",$columns);
        }
        if($str&&$table)
        {
            //解析limit,order by
            if($oneorrow=="one"){
                $conarr=$this->_parseConditionLimitAndOrderBy($condition,false);
                $condition=$conarr['contition'];
                $sqlstr="SELECT top 1 $str FROM ".$fulltable.' '.$condition;
            }else{
                $conarr=$this->_parseConditionLimitAndOrderBy($condition);
                if($conarr['limit']){
                    //有分页的情况
                    if($conarr['offset']==0){
                        if($conarr['order']){
                            $condition=$conarr['contition']." ".$conarr['order'];
                        }else{
                            $condition=$conarr['contition'];
                        }
                        $sqlstr="SELECT top {$conarr['num']} $str FROM ".$fulltable.' '.$condition;
                    }else{
                        //带limit的情况
                        $sqlstr="SELECT top {$conarr['num']} o.* FROM ";
                        if(!$order=$conarr['order']){
                            $pri=$this->getPrimary($table);
                            if($pri) {
                                if (is_array($pri)) {
                                    $pri = $pri[0];
                                }
                            }else{
                                throw new \Exception("SqlSrv:无法排序查询");
                            }
                            $order="order by $pri desc";
                        }
                        $sqlstr.="(SELECT row_number() over($order) as __rowno,* FROM (SELECT $str FROM {$fulltable} {$conarr['condition']}) as oo) as o";
                        $sqlstr.=" where __rowno>".$conarr['offset'];

                    }

                }else{
                    $sqlstr="SELECT $str FROM ".$fulltable.' '.$condition;
                }
            }

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

    private function I($table,array $columns,$debug=null,$hook=null,$aop=null){

        if($table&&$columns)
        {
            $dstr="";
            $istr="";
            array_walk($columns, [$this, 'add_special_char2']);
            foreach($columns as $key=>$value)
            {
                $dstr.=$key.",";
                if(is_array($value)){
                    $value=json_encode($value);
                    $this->add_backslash($value);
                }
                $istr.="'".$value."',";

            }
            $dstr=rtrim($dstr,",");
            $istr=rtrim($istr,",");
            $sqlstr="insert into ".$this->_getTableFullName($table)." ($dstr) values ($istr)";

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
        array_walk($columns, [$this, 'add_special_char2']);
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
                        $arrstr.=$key."=".substr($value,2).",";
                        break;
                    default:
                        $arrstr.=$key."='".$value."',";
                        break;
                }

            }else{
                if(is_array($value)){
                    $value=json_encode($value);
                    $this->add_backslash($value);
                }
                $arrstr.=$key."='".$value."',";
            }
        }
        if($arrstr)
        {
            $arrstr=rtrim($arrstr,",");
            if($table)
            {
                //更新数据表
                $conarr=$this->_parseConditionLimitAndOrderBy($condition,false);
                $condition=$conarr['contition'];
                $sqlstr="UPDATE ".$this->_getTableFullName($table)."  SET $arrstr ".$condition;

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
            $sqlstr="DELETE FROM ".$this->_getTableFullName($table)." ".$condition;
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

    private function C($table,$column="*",$condition,$debug=null,$isgroup=false,$hook=null,$aop=null){

        return $this->_calculatecolumn($table,'COUNT',$column,$condition,$debug,$isgroup,$hook,$aop);


    }
    public function sumRow($table,$column,$condition,$debug=null,$hook=null,$aop=null){
        return $this->_calculatecolumn($table,'SUM',$column,$condition,$debug,false,$hook,$aop);
    }
    private function _calculatecolumn($table,$op,$column,$condition,$debug=null,$isgroup=false,$hook=null,$aop=null){

        $sqlstr="SELECT $op($column) as CCC FROM ".$this->_getTableFullName($table)." ".$condition;
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

    public function tableExists($table){

        $tables = $this->listTables();
        return in_array($table, $tables) ? 1 : 0;

    }

    public function listTables(){

        $tables = [];
        $this->_selectDb($this->config['database']);
        $this->execute("select name from sysobjects where xtype='U' ");
        while($r = $this->fetchNext()) {
            $tables[] = $r['name'];
        }
        return $tables;
    }

    public function affectedRows() {

        return $this->lastqueryid->rowCount();
    }
    public function getPrimary($table) {

        $table=$this->_getTableFullName($table);
        $sqlstr="SELECT name FROM syscolumns WHERE id=Object_Id('{$table}') and colid IN(SELECT keyno from sysindexkeys WHERE id=Object_Id('{$table}'))";
        $this->execute($sqlstr);
        $pris=[];
        while($r = $this->fetchNext()) {
            $pris[]=$r['name'];
        }
        $count=count($pris);
        if($count==0){
           return null;
        }else if($count==1){
            return $pris[0];
        }
        return $pris;

    }

    public function getFields($table) {

        $table=$this->_getTableFullName($table);
        $fields = [];
        $sqlstr="SELECT name FROM syscolumns WHERE id=Object_Id('{$table}')";
        $this->execute($sqlstr);
        while($r = $this->fetchNext()) {
            $fields[$r['name']] = $r; //Type,Null,Key,Default,Extra
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

    public function close()
    {
        // TODO: Implement close() method.
        if($this->_linkkey){
            if(static::$dbhostlist[$this->_linkkey]){
                static::$dbhostlist[$this->_linkkey]=null;   //关闭
                unset(static::$dbhostlist[$this->_linkkey]); //移除
            }
        }
        if($this->pdo){
            unset($this->pdo);
        }

    }



}