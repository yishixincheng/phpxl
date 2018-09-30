<?php
namespace lftsoft\module;

/**
 * Class IndexModule
 * @package lftshuju\module
 * @path("/")
 */
class IndexModule extends MasterModule{

    public function __construct(){
        parent::__construct();
    }

    /**
     * 数据中心初始化页面
     * @path({"","GET"})
     */
    public function init(){

        echo "test";

        $rt=rpc("test.test1.TestRequest",['param1'=>1]);

        var_dump($rt);

    }

    /**
     * @path({"test3","GET"})
     */
    public function test3(){

        echo "3333";

    }


}