<?php

namespace xl\classs;

use xl\base\XlClassBase;
use xl\XlLead;

class MongodbClass extends XlClassBase {


    public static $dbclass=[];
    public static $_ccclass=[];

    private $_db=null;
    private $_dbname=null;
    private $_insert_id=null;


    /**
     * @param $config
     * 根据配置创建对象
     */
    public static function getInstance($config,$dbname){

        $hostdns=static::parseHostDns($config['hostdns']);

        $dbclass=null;
        if(static::$dbclass[$hostdns]){
            $dbclass=static::$dbclass[$hostdns];
        }else {
            $dbclass = new MongodbClass($hostdns);
            static::$dbclass[$hostdns]=$dbclass;
        }

        if($dbname){
            $dbclass->selectDb($dbname);
        }

        return $dbclass;
    }
    public static function parseHostDns($hostdns){

        $hostdnsstr=[];
        foreach ($hostdns as $hd){
             $hdstr='';
             if($hd['username']&&$hd['password']){
                 $hdstr.=$hd['username'].":".$hd['password'].'@';
             }
             $hdstr.=$hd['host'];
             if($hd['port']){
                 $hdstr.=':'.$hd['port'];
             }
             $hostdnsstr[]=$hdstr;
        }
        if(count($hostdnsstr)==1){
            return 'mongodb://'.$hostdnsstr[0];
        }
        $hostdnsstr=implode(',',$hostdnsstr);


        return 'mongodb://'.$hostdnsstr;

    }

    public function __construct($hostdns)
    {

        $this->_db=new \MongoClient($hostdns);

    }
    public function selectDb($dbname){

        $this->_dbname=$this->_db->{$dbname}; //获取数据库名称，自动创建

    }

    public function getCollection($tablename){

        //获取集合
        $key=$this->_dbname.'_'.$tablename;

        if(static::$_ccclass[$key]){
            return static::$_ccclass[$key];
        }

        static::$_ccclass[$key]=$this->_dbname->createCollection($tablename); //创建集合

        return static::$_ccclass[$key];

    }

    private function _dealColumns($columns){

        if(is_array($columns)){
            return $columns;
        }
        if($columns=="*"){
            return null;
        }
        $columns=explode(',',$columns);

        return $columns;

    }

    private  function _parseAndOrStrAppendToArray($str,&$andarr){

        if(preg_match("/\band\b/",$str)){

            $strarr=explode('and',$str);

            $andarr=array_merge($andarr,$strarr);

        }else if(preg_match("/\bor\b/",$str)){

            $strarr=explode('or',$str);

            $andarr=array_merge($andarr,$strarr);
        }

    }

    private function _parseOperStr($str,&$queryarr){

        if(preg_match("/([a-zA-Z0-9_-]+)\s+not\s+in\s*\((.+?)\)/",$str,$match)){
            //in 操作符
            $queryarr[trim($match[1])]=['$nin'=>explode(',',$match[2])];
            return null;
        }

        if(preg_match("/([a-zA-Z0-9_-]+)\s+in\s*\((.+?)\)/",$str,$match)){
            //in 操作符
            $queryarr[trim($match[1])]=['$in'=>explode(',',$match[2])];
            return null;
        }
        if(preg_match("/([a-zA-Z0-9_-]+)\s*(>|<|!)?=\s*((\'|\")?.+?(\'|\")?)/s",$str,$match)){

            $key=trim($match[1]);
            switch ($match[2]){
                case ">":
                    $queryarr[$key]=['$get'=>$match[3]];
                    break;
                case "<":
                    $queryarr[$key]=['$lte'=>$match[3]];
                    break;
                case "!":
                    $queryarr[$key]=['$ne'=>$match[3]];
                    break;
                default:
                    $queryarr[$key]=$match[3];
                    break;
            }
            return null;

        }

        if(preg_match("/([a-zA-Z0-9_-]+)\s*(>|<)\s*((\'|\")?.+?(\'|\")?)/s",$str,$match)){

            $key=trim($match[1]);
            switch ($match[2]){
                case ">":
                    $queryarr[$key]=['$gt'=>$match[3]];
                    break;
                case "<":
                    $queryarr[$key]=['$lt'=>$match[3]];
                    break;
            }

            return null;

        }

        if(preg_match("/([a-zA-Z0-9_-]+)\s*(not)?\s*like\s*(\'|\")?((%?)(.+?)(%?))(\'|\")/s",$str,$match)){

            $key=trim($match[1]);
            $not=$match[2];
            $pre_sym=$match[5];
            $v=$match[6];
            $next_sym=$match[7];

            if($pre_sym=="%"){
                $pre_sym=".*";
            }else{
                $pre_sym='';
            }
            if($next_sym=="%"){
                $next_sym=".*";
            }else{
                $next_sym='';
            }
            if($not=="not"){
                $patt="/(?!".$pre_sym.$v.$next_sym.")/";
            }else{
                $patt="/".$pre_sym.$v.$next_sym."/";
            }

            $queryarr[$key]=new \MongoRegex($patt);

        }


    }

    private function  _where($str){

              $str=str_replace("where","",$str); //移除where
              $matchorpattern="/\([^\)]+?\b(and|or)\b[^\(]+?\)/";
              preg_match_all($matchorpattern,$str,$match);
              if($match){
                  $str=preg_replace($matchorpattern,"",$str);
              }
        $andarr=explode(' and ',$str);
        $andarr=$andarr?:[];
        $orarr=[];
        if($match){
            $matchoprarr=$match[1];
            foreach ($matchoprarr as $key=>$mo){
                if($mo=="and"){
                    $this->_parseAndOrStrAppendToArray(trim($match[0][$key],")("),$andarr);
                }else if($mo=="or"){
                    $this->_parseAndOrStrAppendToArray(trim($match[0][$key],")("),$orarr);
                }
            }

        }
        $wherearr=[];

        foreach ($andarr as $andstr){

            //解析and参数
            $andstr=trim($andstr);
            if($andstr){
                $this->_parseOperStr($andstr,$wherearr);
            }

        }

        if($orarr) {

            $orarr = array_chunk($orarr, 2);

            if (count($orarr) == 1) {

                $wherearr['$or'] = [];
                foreach ($orarr[0] as $item) {

                    $tmp = [];
                    $this->_parseOperStr($item, $tmp);
                    $wherearr['$or'][] = $tmp;
                }

            } else {

                $wherearr['$and']=[];

                foreach ($orarr as $i=>$orarr_item){

                    $wherearr['$and'][$i]['$or']=[];
                    foreach ($orarr_item as $item) {
                        $tmp = [];
                        $this->_parseOperStr($item, $tmp);
                        $wherearr['$and'][$i]['$or'][] = $tmp;
                    }

                }

            }

        }

        return $wherearr;

    }

    private function _dealWhere($where){

        if(is_array($where)){
            return $where;
        }else{

            return $this->_where($where);

        }

    }

    public function debugLog($debug=null,$condition=null,$columns=null){

        if(empty($debug)){
            return true;
        }
        if($debug=="tiaoshi"){

            if(is_array($condition)){
                print_r($condition);
            }
            if(is_array($columns)){
                print_r($columns);
            }
            return false;
        }

        $logstr='';
        if(is_array($condition)){
            $logstr.=print_r($condition,true);
        }
        if(is_array($columns)){
            $logstr.=print_r($columns,true);
        }

        XlLead::logger("mongodbdebug")->write($logstr,true);

        return true;

    }

    /**
     * @param $tablename
     * @param string $columns
     * @param $condition
     * @param null $debug
     * 获取单行
     */
    public function getOne($tablename,$columns="*",$condition,$debug=null){

        $cc=$this->getCollection($tablename);

        $condition=$this->_dealWhere($condition);
        $columns=$this->_dealColumns($columns);

        if(!$this->debugLog($debug,$condition,$columns)){
            return false;
        }

        return $cc->findOne($condition,$columns);

    }

    /**
     * @param $tablename
     * @param string $columns
     * @param $condition
     * @param null $debug
     * 获取多行
     */
    public function getRows($tablename,$columns="*",$condition,$debug=null){


        $cc=$this->getCollection($tablename);

        if(is_array($condition)){

            $offset=$condition['$offset'];
            $num=$condition['$num'];

            unset($condition['$offset']);
            unset($condition['$num']);
        }else{

            preg_match("/(.+?)limit\s+(\d+)\s*,\s*(\d+)/",$condition,$match);
            if($match){
                $condition=$match[1];
                $offset=$match[2];
                $num=$match[3];
            }

        }

        if(!isset($offset)){
            $offset=0;
        }

        $condition=$this->_dealWhere($condition);
        $columns=$this->_dealColumns($columns);

        if(!$this->debugLog($debug,$condition,$columns)){
            return false;
        }

        $cursor=$cc->find($condition,$columns)->skip($offset?:0);

        if(isset($num)){
            $cursor->limit($num);
        }

        return $cursor;

    }

    /**
     * @param $tablename
     * @param array $columns
     * @param null $debug
     * 插入一行
     */

    public function insert($tablename,array $columns,$debug=null){

        $this->_insert_id=null;
        $cc=$this->getCollection($tablename);

        if(!$this->debugLog($debug,null,$columns)){
            return false;
        }

        $rt=$cc->insert($columns);

        $this->_insert_id=$rt['_id'];

        return $rt;

    }

    /**
     * @param $tablename
     * @param array $columns
     * @param $condition
     * @param null $debug
     * 修改一行
     */
    public function setColumn($tablename,array $columns,$condition,$debug=null){

        $cc=$this->getCollection($tablename);

        if(!$this->debugLog($debug,$condition,$columns)){
            return false;
        }

        $condition=$this->_dealWhere($condition);

        return $cc->update($condition,['$set'=>$columns]);
    }

    /**
     * @param $tablename
     * @param $condition
     * @param null $debug
     * 删除
     */
    public function delete($tablename,$condition,$debug=null){


        $cc=$this->getCollection($tablename);

        if(!$this->debugLog($debug,$condition,null)){
            return false;
        }
        if(is_array($condition)){
            $num=$condition['$num'];
            unset($condition['$offset']);
            unset($condition['$num']);
        }else{
            preg_match("/(.+?)limit\s+(\d+)\s*,\s*(\d+)/",$condition,$match);
            if($match){
                $condition=$match[1];
                $num=$match[3];
            }
        }

        $condition=$this->_dealWhere($condition);

        if(isset($num)&&$num==1){
            $delranage=['justOne'=>$num];
        }else{
            $delranage=null;
        }

        return $cc->remove($condition,$delranage);


    }

    /**
     * @return null
     */

    public function insert_id(){

        //获得最后一次插入id

        return $this->_insert_id;

    }

    /**
     * 删除数据表
     */
    public function drop($tablename){

        $cc=$this->getCollection($tablename);

        return $cc->drop();

    }


}