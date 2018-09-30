<?php

namespace xl\classs\db;
use xl\XlLead;

trait Dbtrait{

    public static $ErrorCodeMap=[
        '1146'=>'TABLE_NOT_EXIST',
        '42S02'=>'TABLE_NOT_EXIST',
        '1194'=>'TABLE_NEED_REPAIR',    // 查询表需要修复
        '145'=>'TABLE_NEED_REPAIR',     // 插入表需要修复
        '1054'=>'BAD_FIELD_ERROR'       // 未知列
    ];

    public function getLinkKey($dbconfig){

        $host=$dbconfig['hostname']?:"localhost";
        $port=$dbconfig['port']?:3306;
        $username=$dbconfig['username']?:"root";

        return $host.":".$port."@".$username;

    }
    public function halt($message = '', $sql = '',$hook=null){

        if($this->config['debug']) {

            $errno=$this->errno();
            $errmsg=$this->error();
            $isreturn=false;
            if(is_callable($hook)){
                $errid=static::$ErrorCodeMap[$errno];
                $isreturn=$hook($errid,$errno,$errmsg);
            }
            if(!$isreturn){
                $msg = "<b>MySQL Query : </b> $sql <br /><b> MySQL Error : </b>".$errmsg." <br /> <b>MySQL Errno : </b>".$errno." <br /><b> Message : </b> $message <br />";
                echo '<div style="font-size:12px;text-align:left; border:1px solid #9cc9e0;padding:1px 4px;"><span>'.$msg.'</span></div>';
                if(!defined("ISCLI")&&ISCLI) {
                    //cli模式下不退出进程
                    exit;
                }
            }

        }

        return false;


    }
    public function add_special_char(&$value) {
        if('*' == $value || false !== strpos($value, '(') || false !== strpos($value, '.') || false !== strpos ( $value, '`')) {
            //不处理包含* 或者 使用了sql方法。
        } else {
            if(preg_match("/^(.+)\b(as|AS)\b(.+)$/i",$value,$matches)){

                if(strpos($value,'{')!==false){
                    $value='\''.trim($matches[1]).'\' as `'.trim($matches[3]).'`';
                }else{
                    $value='`'.trim($matches[1]).'` as `'.trim($matches[3]).'`';
                }

            }else{
                $value = '`'.trim($value).'`';
            }
        }
        if (preg_match("/\b(select|insert|update|delete)\b/i", $value)) {
            $value = preg_replace("/\b(select|insert|update|delete)\b/i", '', $value);
        }
        return $value;
    }
    public function add_special_char2(&$value) {
        if('*' == $value || false !== strpos($value, '(') || false !== strpos($value, '.') || false !== strpos ( $value, '`')) {
            //不处理包含* 或者 使用了sql方法。
        } else {
            $value = trim($value);
        }
        if (preg_match("/\b(select|insert|update|delete)\b/i", $value)) {
            $value = preg_replace("/\b(select|insert|update|delete)\b/i", '', $value);
        }
        return $value;
    }
    public function debugLog($debug=null,$sqlstr){

        if(empty($debug)){
            return true;
        }
        if($debug=="debug"){
            echo $sqlstr;
            return false;
        }else if($debug=="debugtolog"){
            XlLead::logger("sqldebug")->write($sqlstr,true);
        }
        return true;

    }

}