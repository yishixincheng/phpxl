<?php
namespace lftsoft\module;

use xl\base\XlModuleBase;
use \xl\util\XlUException;

/**
 * Class MasterModule
 * @package lftshuju\module
 */
class MasterModule extends XlModuleBase{

    public $Config=[];

    public function __construct(){
        parent::__construct();
        $this->intEnv();
    }

    public function intEnv(){
        SetG("evn/logintype",1);    // 0代表cookie,1代表session
        $this->Config=config("system");
        define("FORMHASH", substr(md5(substr(time(), 0, -7).$_SERVER['HTTP_HOST'].$this->Config['auth_key'].$_SERVER['HTTP_USER_AGENT']), 0, 16));
        $allowpostRoutes=config("allowpost")?:[];
        $path=$this->_Genv['path'];
        if(REQUEST_METHOD=="POST" && (isset($path[0]) && !in_array($path[0], $allowpostRoutes))){
            if ($this->_Post["FORMHASH"]!=FORMHASH || strpos($_SERVER["HTTP_REFERER"], $_SERVER["HTTP_HOST"])===false){
                X_IS_AJAX || $this->Messager("请求无效", null);
            }
        }

    }

    /**
     * 前端模版设置页面标题
     * @param string $title 标题
     */
    public function setHtmlTitle($title=''){
        $this->setAttach("Title", $title."-".config("system/seo_webname")."-".config("system/danwei_name"));
    }

    final public function fail($msg, $url='', $attach=null){
        if(X_IS_AJAX){
            ___fail($msg, $attach);
        }else{
            if($url){
                toUrl($url);
            }else{
                $this->Messager($msg);
            }
        }
    }

    public function checkSessionkey($sessionkey){

        $sessionkey_hold=GetG("member/sessionkey");

        if($sessionkey_hold&&$sessionkey_hold!=$sessionkey){
            return false;
        }else{
            return true;
        }

    }

    public function Messager($message, $redirectto='',$time = -1,$return_msg=false,$js=null){
        if($time==-1){$time=10;}
        $to_title=($redirectto==='' || $redirectto==-1)?"返回上一页":"跳转到指定页面";
        if($redirectto===null){
            $return_msg=$return_msg===false?"&nbsp;":$return_msg;
        }else{
            $redirectto=($redirectto!=='')?$redirectto:($from_referer=referer());
            if (is_numeric($redirectto)!==false && $redirectto!==0)
            {
                if($time!==null){
                    $url_redirect="<script language=\"JavaScript\" type=\"text/javascript\">\r\n";
                    $url_redirect.=sprintf("window.setTimeout(\"history.go(%s)\",%s);\r\n",$redirectto,$time*1000);
                    $url_redirect.="</script>\r\n";
                }
                $redirectto="javascript:history.go({$redirectto})";
            }else{
                if($message===null)
                {
                    $redirectto=rawurldecode($redirectto);
                    header("Location: $redirectto"); #HEADER跳转
                }
                if($time!==null)
                {
                    $url_redirect = $redirectto?'<meta http-equiv="refresh" content="' . $time . '; URL=' . $redirectto . '">':null;
                }
            }
        }
        $message_str=(is_array($message)?implode(',',$message):$message);
        $title="消息提示:".$message_str;
        $title=strip_tags($title);
        if($js!="") {
            $js="<script type=\"text/javascript\">{$js}</script>";
        }
        $additional_str = $url_redirect.$js;
        include tpl('/message');
        exit;
    }

}