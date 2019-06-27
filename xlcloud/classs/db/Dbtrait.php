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
    public function halt($message = '', $sql = '',$hook=null,$errhook=null){

        if($this->config['debug']) {

            $errno=$this->errno();
            $errmsg=$this->error();
            $isreturn=false;
            if(is_callable($hook)){
                $errid=static::$ErrorCodeMap[$errno];
                $isreturn=$hook($errid,$errno,$errmsg);
            }
            if(!$isreturn){

                if(!(defined("ISCLI")&&ISCLI)){
                    //cli模式下不退出进程
                    $msg = "<b>MySQL Query : </b> $sql <br /><b> MySQL Error : </b>".$errmsg." <br /> <b>MySQL Errno : </b>".$errno." <br /><b> Message : </b> $message <br />";
                    $_errormsg='<div style="font-size:12px;text-align:left; border:1px solid #9cc9e0;padding:1px 4px;"><span>'.$msg.'</span></div>';
                    echo $_errormsg;
                    exit;
                }else{
                    //cli模式下，将错误信息输出到错误日志中

                    $_errormsg="-------------------Begin--------------------".PHP_EOL;
                    $_errormsg.='时间:'.date("Y-m-d H:i:s").PHP_EOL;
                    $_errormsg.='MySQL Query:'.$sql.PHP_EOL;
                    $_errormsg.="MySQL Error:".$errmsg.PHP_EOL;
                    $_errormsg.="MySQL Errno:".$errno.PHP_EOL;
                    $_errormsg.="Message:".$message.PHP_EOL;
                    $_errormsg.="-------------------End---------------------".PHP_EOL.PHP_EOL;

                    $loggerObj=logger("__cli_mysqlerrorlog",1024);
                    $loggerObj->write($_errormsg,true,true);

                    if(is_callable($errhook)){
                        $errhook($errno,$loggerObj);
                    }

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

        if(is_string($value)||is_numeric($value)){
            $value = trim($value);
            if (preg_match("/\b(select|insert|update|delete)\b/i", $value)) {
                $value = preg_replace("/\b(select|insert|update|delete)\b/i", '', $value);
            }
            $this->add_backslash($value);
        }
        return $value;
    }

    public function add_backslash(&$value){
        if(strpos($value,"\\")!==false){
            $value= str_replace("\\","\\\\",$value);
        }
        if(strpos($value,"'")!==false){
            $value=str_replace("'","\'",$value);
        }

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