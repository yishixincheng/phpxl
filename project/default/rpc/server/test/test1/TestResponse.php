<?php

namespace rpc\server\test\test1;

use rpc\server\Response;
use rpc\server\test\ApiuserTrait;


class TestResponse extends Response{

    use ApiuserTrait;

    public $postallow=[];

    function __construct($methodname=''){
        parent::__construct($methodname);
//        $rterror=error_get_last();
//        if($rterror){
//            $st=ob_get_contents();
//            logger("370100/"."app".__CLASS__)->write(print_r($st,true),true);
//        }
    }
    public function putParams($postparam){

        $this->autoGetParams($postparam);
    }
    public function getApiResult(){

        //验证接口，设置接口

        $rt=['a'=>'测试'];
        return $this->_dealresult($rt);
    }


}