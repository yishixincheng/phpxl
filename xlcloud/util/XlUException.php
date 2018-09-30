<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016-09-08
 * Time: 13:26
 */

namespace xl\util;

use xl\base\XlException;

class XlUException extends XlException{

    private static $MapCode=[
             'NotFound'=>['code'=>404,'msg'=>'404 Not Found'],
             'BadRequest'=>['code'=>400,'msg'=>'400 Bad Request'],
             'Forbidden'=>['code'=>403,'msg'=>'403 Forbidden'],
             'Timeout'=>['code'=>419,'msg'=>'419 Authentication Timeout'],
             'SysError'=>['code'=>500,'msg'=>'500 Internal Server Error']
        ];
    public function __construct($message='', $code=0, \Exception $previous=null)
    {
        if(!is_numeric($code)){
            $codearr=static::$MapCode[$code];
            if($codearr){
                $code=$codearr['code'];
                $message=$codearr['msg'];
            }else{
                $code=0;
            }
        }else if($code>0){

            foreach (static::$MapCode as $v){

                if($v['code']==$code){
                    $message=$v['msg'];
                    break;
                }

            }

        }

        parent::__construct($message, $code, $previous);
    }
    public function isHttpError(){

        $code=$this->getCode();
        $ishttperror=false;
        foreach (static::$MapCode as $v){

            if($v['code']==$code){
                $ishttperror=true;
                break;
            }
        }

        return $ishttperror;

    }


}