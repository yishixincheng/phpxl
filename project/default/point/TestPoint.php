<?php

namespace lftsoft\point;

use xl\base\XlPointBase;

class TestPoint extends XlPointBase {


    /**
     * @point({"testpoint",1})
     */
    public function action1(){


        logger("testpoint")->write("调用1".PHP_EOL,true,true);

        echo "测试";
        echo "<br>";
        echo PHP_EOL;

        return $this->SuccInf("我返回了");

    }
    /**
     * @point({"testpoint",1})
     */
    public function action2(){

        echo "测试";
        echo "<br>";
        echo PHP_EOL;

        logger("testpoint")->write("调用2".PHP_EOL,true,true);

        return $this->ErrorInf("我亦返回");

    }

}