<?php

namespace test\point;
use xl\base\XlPointBase;

class TestPoint extends XlPointBase {



    /**
     * @point({"testpoint",3})
     */
    public function action2(){

       // logger("testpoint")->write("调用3".PHP_EOL,true,true);
        echo "我是测试3";
        echo "<br>";
        echo PHP_EOL;

    }

}