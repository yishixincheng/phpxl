<?php

namespace test\point\child;
use xl\base\XlPointBase;

class TestPoint extends XlPointBase {



    /**
     * @point({"testpoint",4})
     */
    public function action2(){

       // logger("testpoint")->write("调用4".PHP_EOL,true,true);
        echo "我是测试4";
        echo "<br>";
        echo PHP_EOL;

    }

}