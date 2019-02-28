<?php

namespace weapp\module;
use xl\base\XlModuleBase;
import("@xl.vendor.autoload");

use \Xl_WeApp_SDK\App as App;

App::run(['AppId'=> 'wxe294651e22568a2d','AppSecret'=> '626904bee4e9a01ed23ed4f2169ace0e']);

class MasterModule extends XlModuleBase{


    public function __construct()
    {

        parent::__construct();
    }

    public function json($arr){

        echo json_encode($arr);

    }



}
