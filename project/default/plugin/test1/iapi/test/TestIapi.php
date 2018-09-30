<?php

namespace test1\iapi\test;

use xl\base\XlIapiBase;

class TestIapi extends XlIapiBase{

    public function __construct()
    {
        parent::__construct();
    }

    public function getResult($params = null)
    {
        // TODO: Implement getResult() method.

        return "这个是插件test1的test.Test内部接口方法+eeeeee";

    }

}