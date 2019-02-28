<?php

namespace test\module;
use xl\base\XlModuleBase;

/**
 * Class IndexModule
 * @package lftshuju\module
 * @path("/")
 */
class IndexModule extends XlModuleBase {

    public function __construct(){
        parent::__construct();
    }

    /**
     * 数据中心初始化页面
     * @path({"/test","GET"})
     */
    public function init(){


        echo "插件里的路由";

        //$this->triggerEvent("test",['x'=>1,'y'=>1,'z'=>2]);


        $model=$this->Model("test");
        //$model->add(["name"=>"测试"]);
        //$rt=$model->getOne("*","where 1 limit 1");
        //exit;

        /*
        $dataset=$this->Dataset("pub.Ditie");
        $datalist=$dataset->getDataList();
        print_r($datalist);
        exit;
        */

        /*

        $dataset=$this->Dataset("test");

        $datalist=$dataset->getDataList();

        print_r($datalist);

        exit;

        */

        /*
        $logic=$this->Logic("test");

        $logic->run();
        exit;
        */

        //模型流操作
        /*
        $ms=$this->MS("test");
        $rt=$ms->add(['name'=>"您好"])->done();
        print_r($rt);
        exit;
        */

        //任务流操作

        /*
        $rt=$this->TS(null,['a'=>1])->task("test")->done();

        print_r($rt);

        exit;
        */
        /*
        echo iapi("test",['param'=>1]);
        echo PHP_EOL;
        echo "<br>";
        exit;
        */
        /*
        echo iapi("test1:test.Test",['param'=>2]);

        echo PHP_EOL;
        echo "<br>";
        */

        echo 2;

       // $this->Display();

    }

    /**
     * @path({"/test2","GET"})
     */
    public function test2($getParam){

        echo "test2222333";

    }


}