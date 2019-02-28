<?php

namespace test1\iapi;

use xl\base\XlIapiBase;

class TestIapi extends XlIapiBase{

    public function __construct()
    {
        parent::__construct();
    }

    public function getResult($params = null)
    {
        // TODO: Implement getResult() method.

        return "这个是插件test1的Test内部接口方法+xxxxxxfff";

    }

}