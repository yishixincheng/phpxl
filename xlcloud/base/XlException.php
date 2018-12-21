<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-09-08
 * Time: 13:26
 */

namespace xl\base;

class XlException extends \Exception{

    public function __construct($message="", $code=0, \Exception $previous=null)
    {
        parent::__construct($message, $code, $previous);
    }

    function getName(){
        return  'Exception';
    }
    public static function errorHandlerCallback($errno, $errstr, $errfile, $errline) {

        if($errno==8) return false;
        $errfile = str_replace(ROOT_PATH,'',$errfile);
        if(config('system/errorlog')) {
            error_log(date('m-d H:i:s',SYS_TIME).' | '.$errno.' | '.str_pad($errstr,30).' | '.$errfile.' | '.$errline."\r\n", 3, LOG_PATH.'_error_log'.date("Y-m-d",SYS_TIME).'.txt');
        } else {
            $str = '<div style="padding:1px 4px;"><span>errorno:' . $errno . ',str:' . $errstr . ',file:<span style="color:#0000ff">' . $errfile . '</span>,line' . $errline .'<br /></span></div>';
            echo $str;
        }
        return false;
    }
}