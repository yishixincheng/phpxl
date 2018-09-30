<?php

namespace rpc\client\test\test1;

use rpc\client\Request;

class TestRequest extends Request{

    function __construct($methodname=''){
        parent::__construct($methodname);
    }
    public function promiseParam(){
        return array("param1","endtime","citycode");
    }
//
//    public function check(){
//        $this->checkNotAllNull("param1");
//    }

}