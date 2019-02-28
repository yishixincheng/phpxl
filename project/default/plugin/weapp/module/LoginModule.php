<?php

namespace weapp\module;

use Xl_WeApp_SDK\App as App;
/**
 * Class LoginModule
 * @package weapp\module
 * @path("/weapp")
 */
class LoginModule extends MasterModule{

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * @path({"/login","GET"})
     */
    public function loginDone($getParam){

        $loginService=App::getService("Login");
        $rt=$loginService->login(function($method,$params){
            switch($method){
                case "findUserByOpenId":  //根据openid从数据表获取用户信息
                $result="";
                break;
                case "storeUserInfo":  //更新存储用户信息
                    $result="";
                break;
            }
            return $result;
        });

        var_dump($rt);


    }

}
