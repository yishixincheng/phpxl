<?php

namespace sysmanage\module;

/**
 * Class AccountModule
 * @package sysmanage\module
 * @path("/sysmanage")
 */
class AccountModule extends Base
{
    public $nochecklogin=true;

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * @path({"login","GET"})
     */
    public function login(){

        $this->getLoginInfo();

        if(GetG("member")){
            toUrl("/sysmanage");
        }

        $this->setHtmlTitle("登录");
        $this->Display();

    }

    /**
     * 验证码图片
     * @path({"account/getcodeimg", "GET"})
     */
    public function getCodeImg($getParam){
        $width=$getParam['width'];
        $height=$getParam['height'];
        $fontsize=$getParam['fontsize'];
        $width=$width?$width:85;
        $height=$height?$height:30;
        $fontsize=$fontsize?$fontsize:16;
        sysclass("checkcode")->setSize($width, $height, $fontsize)->doimg();
    }

    /**
     * @path({"account/logindone","POST"})
     */
    public function loginDone($postParam){

        $username=$postParam['username'];
        $password=$postParam['password'];
        $checkcode=$postParam['checkcode'];

        $founder=config("founder")?:[];

        //检测验证码是否正确
        if(empty($checkcode)){
            AjaxPrint($this->ErrorInf("验证码不能为空"));
            exit;
        }
        $syzm=sysclass("checkcode")->getCode();
        if($syzm!=strtolower($checkcode)){
            AjaxPrint($this->ErrorInf("验证码不正确"));
            exit;
        }

        if($founder['__USERNAME__']!=$username&&$founder['__PASSWORD__']!=$password){

            AjaxPrint($this->ErrorInf("用户名或密码错误！"));
            exit;
        }

        $user=['uid'=>1,'username'=>$username,'code'=>$checkcode];

        GCookie("founderaccount",$user);

        AjaxPrint($this->SuccInf("登录成功！"));

    }

}
