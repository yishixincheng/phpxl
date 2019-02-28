<?php

namespace sysmanage\module;

use Symfony\Component\Config\Definition\Exception\Exception;
use xl\base\XlModuleBase;

class Base extends XlModuleBase{

    public $nochecklogin=false;

    public function __construct()
    {
        parent::__construct();

        $this->intEnv();

    }
    public function intEnv(){

        $this->Config=config("system");
        define("FORMHASH", substr(md5(substr(time(), 0, -7).$_SERVER['HTTP_HOST'].$this->Config['auth_key'].$_SERVER['HTTP_USER_AGENT']), 0, 16));
        $allowpostRoutes=config("allowpost")?:[];
        $path=$this->_Genv['path'];
        if(REQUEST_METHOD=="POST" && (isset($path[0]) && !in_array($path[0], $allowpostRoutes))){
            if ($this->_Post["FORMHASH"]!=FORMHASH || strpos($_SERVER["HTTP_REFERER"], $_SERVER["HTTP_HOST"])===false){
                   X_IS_AJAX || exit("请求无效");
            }
        }

        if(!$this->nochecklogin){
            $this->checkLogin();
        }


    }

    public function getLoginInfo(){

        try{
            if($data=iapi("GetAdminLoginData",null)){
                SetG("member",$data);
            }
        }catch (\Exception $e){
            $data=GCookie("founderaccount");
            if($data&&is_array($data)){
                SetG("member",$data);
            }

        }
    }

    /**
     * 检测是否登录
     */
    private function checkLogin(){

        $this->getLoginInfo();

        if(!GetG("member")){
            if(X_IS_AJAX){
                AjaxPrint(['status'=>'fail','msg'=>'你需要重新登录']);
                exit;
            }else{
                toUrl("/sysmanage/login");
            }
        }

    }



    /**
     * 前端模版设置页面标题
     * @param string $title 标题
     */
    public function setHtmlTitle($title=''){
        $this->setAttach("Title", $title."-管理后台-".config("system/seo_webname"));
    }



}