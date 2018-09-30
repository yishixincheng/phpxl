<?php
namespace rpc;

use xl\XlLead;

/**
 * Class ApiFactory
 * @package api
 * api根为api命名空间
 */
class ApiFactory{

    private $_method=null;
    private $_sdkns=null;
    private $_config=null;
    private $apiParas=null;
    private $_filenames=[];
    private $_isfileupload=false;


    /**
     * 单次请求
     */
    public function single($methodname,$param,$config=null){

        $this->setMethod($methodname);
        if(!$param){$param=[];}
        if(!$param['fields']){$param['fields']='status,code';}
        $this->setParam($param);
        $this->_config=$config;

        return $this->run();

    }

    /**
     * 并行请求
     */
    public function multi($param){

        $mh = curl_multi_init();

        $curlObjList=[];
        $callBackList=[];
        $objList=[];

        foreach ($param as $node){

            if(!is_array($node)){
                $this->halt("参数错误");
            }

            $_obj=new EachRequestThread($this,$node);

            $curlObj=$_obj->getCurlHandler();

            if($curlObj){
                curl_multi_add_handle ($mh,$curlObj);
                $curlObjList[]=$curlObj;
                $callBackList[]=$node[2]?:null;
                $objList[]=$_obj;
            }

        }
        $running=null;
        do{
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        foreach ($curlObjList as $k=>$curlObj){

            curl_error($curlObj);
            $data=curl_multi_getcontent($curlObj);
            curl_close($curlObj);

            if(is_callable($callBackList[$k])){

                $_header=null;
                if (!(strpos($data, "\r\n\r\n") === false)) {
                    list($_header, $data) = explode("\r\n\r\n", $data, 2);
                }
                $rt=__json_decode($data,true);
                if($rt==null){
                    $objList[$k]->getLogObj()->write($data."\r\n",true);
                    $rt=['status'=>'fail','error'=>1,'msg'=>'运行错误:'.$data];
                }
                $callBackList[$k]($rt,$_header);
            }

            curl_multi_remove_handle($mh,$curlObj);
        }

        curl_multi_close($mh);

    }


    public function setMethod($method=''){

        //请求的方法
        if(empty($method)){
            $this->halt('api method no exist');
        }

        preg_match("/(.+?)\.(.+)/",$method,$mh);

        $this->_sdkns=$mh[1];
        $this->_method=$mh[2];


    }

    protected function getPPs(){

        $pms=[];
        $pms[]='page';
        $pms[]='fields';
        $pms[]='num';
        $pms[]='offset';
        $pms[]='appkey';

        return $pms;
    }

    public function setParam($pm,$value=''){
        if(is_string($pm)){
            $pm=array($pm=>$value);
        }
        $pms=$this->getPPs();


        foreach($pm as $k=>$v){
            $this->apiParas[$k] =$v;
            if(!in_array($v,$pms)) {
                if(is_string($v)) {
                    if (substr($v, 0, 1) == "@") {
                        $this->_isfileupload = true;

                        $this->_filenames[$k] = substr($v,1);
                    }
                }
            }
        }

    }

    private function _dealcookie($ihttp){

        $authcode=GetG("member/authcode");
        if($authcode){
            $ihttp->setCookie($this->appkey.'-auth',$authcode);
        }
    }

    private function _getPostUrl($cnf){

        $url='';
        if($this->_config){
            $url=$this->_config['rsp_urls'];
        }
        if(empty($url)){
            $url=$cnf['rsp_urls'];
        }
        if(empty($url)){
            $url=GetG($this->_sdkns."/rsp_urls");
        }
        if(empty($url)){
            return 'http://localhost/rpcgateway';
        }
        $rsp_urlsArr=explode(',',$url);
        $rsp_url=$rsp_urlsArr[array_rand($rsp_urlsArr,1)];

        if(strpos($rsp_url,"?")===FALSE){
            $rsp_url.='/rpcgateway';
        }

        return $rsp_url;
    }
    private function _getAppKey($cnf){

        $appkey='';
        if($this->_config){
            $appkey=$this->_config['appkey'];
        }
        if(empty($appkey)){
            $appkey=GetG($this->_sdkns.'/appkey')?:$cnf['appkey'];
        }
        return $appkey;
    }
    private function _getAppSecret($cnf){

        $appsecret='';
        if($this->_config){
            $appsecret=$this->_config['appsecret'];
        }
        if(empty($appsecret)){
            $appsecret=GetG($this->_sdkns.'/appsecret')?:$cnf['appsecret'];
        }
        return $appsecret;

    }

    public function run(){

        if(!$this->apiParas['fields']){$this->apiParas['fields']='status,code,msg';}

        $this->checkRequestParam($this->apiParas); //验证参数

        $confpath=RPC_PATH."client".D_S.$this->_sdkns.D_S."conf.json";
        $cnf=sysclass("json")->read($confpath,false);

        if($cnf&&$cnf['hostname']){
            $hostconf=sysclass("globalconf")->getHostConfByHostName($cnf['hostname']);
            if(!$hostconf){
                throw new XlException("主机名".$cnf['hostname']."无效");
            }
            if(!$cnf['rsp_urls']){
                $url=trim($hostconf['url']?:$hostconf['host']);

                if(!preg_match("/[a-zA-Z]+:\/\/.+/",$url)){
                    $url="http://".$url;
                }

                $cnf['rsp_urls']=$url;
            }
            if(!$cnf['appkey']){
                $cnf['appkey']=$hostconf['rpc_appkey'];
                $cnf['appsecret']=$hostconf['rpc_appsecret'];
            }
        }

        $rsp_url=$this->_getPostUrl($cnf);
        $appkey=$this->_getAppKey($cnf);
        $appsecret=$this->_getAppSecret($cnf);

        $apiparam=$this->apiParas;

        ksort($apiparam);
        $poststr=sys_auth(http_build_query($apiparam),'ENCODE',$appsecret);

        $sign=md5(md5($poststr));
        $timestamp=time();//当前请求的时间戳
        $cls=sysclass("httpfactory",0);
        $ihttp=$cls::get_instance()->get_http();
        $postdata=['_argot'=>$poststr];
        $postdata['_appkey']=$appkey;
        $postdata['_sign']=$sign;
        $postdata['_timestamp']=$timestamp;
        $postdata['_method']=$this->_method;
        $postdata['_sdkns']=$this->_sdkns;


        $this->_dealcookie($ihttp);
        if($this->_isfileupload){
            $ihttp->upload($rsp_url,$postdata,$this->_filenames);
        }else{
            $ihttp->post($rsp_url,$postdata);
        }

        $mLog=XlLead::logger("rpccall_".$this->_sdkns.".".$this->_method);

        if($ihttp->isOK()){
            $data=$ihttp->getData();

            $rt=__json_decode($data,true);
            if($rt==null){
                $mLog->write($data."\r\n",true);
                $rt=['status'=>'fail','error'=>1,'msg'=>'运行错误:'.$data];
            }
            return $rt;
        }
        return ['status'=>'fail','error'=>1,'msg'=>$ihttp->getErrmsg()];


    }
    public function halt($msg,$isprint=true){
        if($isprint){
            echo $msg;
            exit;
        }else{
            return array('status'=>'fail','msg'=>$msg,'code'=>0);
        }
    }

    public function checkRequestParam($apiParas){

        $request=$this->getRequestOrResponseObj(true);

        if(!$request){
            //不验证参数合法性
            return;
        }
        $request->setApiParas($apiParas);
        $pms=$this->getPPs();
        $promisepms=$request->promiseParam();
        $pms=array_merge($pms,$promisepms);
        $request->checkPromiseParam($pms); //验证

        $request->check();

    }

    /**
     * @param bool $isrequest
     * 获取Request或Response对象
     */
    public function getRequestOrResponseObj($isrequest=true){


        if(substr($this->_method,-7)!='Request'){
            $this->_method.='Request';
        }
        $method=$this->_method;
        $path=RPC_PATH;
        $cls='rpc\\';
        if(!$isrequest){
            $method=preg_replace("/^(.+?)(Request)$/","$1Response",$this->_method);
            $path.='server'.D_S;
            $cls.="server\\";
        }else{
            $path.="client".D_S;
            $cls.="client\\";
        }
        $path.=$this->_sdkns.D_S;
        $path.=str_replace(".",D_S,$method);
        $path.='.php';

        if(!file_exists($path)){
            return null;
        }
        $cls.=$this->_sdkns."\\".str_replace(".","\\",$method);

        return new $cls($this->_method);

    }

    public function response($requestdata){

        $_argot=$requestdata['_argot'];
        $_appkey=$requestdata['_appkey'];
        $_sign=$requestdata['_sign'];
        $_timestamp=$requestdata['_timestamp'];
        $_method=$requestdata['_method'];
        $_sdkns=$requestdata['_sdkns'];

        if(empty($_appkey)||empty($_sign)||empty($_timestamp)||empty($_method)||empty($_sdkns)||empty($_argot)){
            $this->_failresult("参数缺失，请求无效。");
        }

        unset($requestdata['_argot']);
        unset($requestdata['_appkey']);
        unset($requestdata['_sign']);
        unset($requestdata['_timestamp']);
        unset($requestdata['_method']);
        unset($requestdata['_sdkns']);

        $nowtime=SYS_TIME;
        $difftime=$nowtime-$_timestamp;
        if($difftime>60||$difftime<-60){
            $this->_failresult("时间已过期。");
        }
        $this->_sdkns=$_sdkns;
        $this->_method=$_method;
        $response=$this->getRequestOrResponseObj(false);
        if(!$response){
            $this->_failresult("无效的方法名");
        }
        if(method_exists($response,"__getApiUser")){
            $apiuser=$response->__getApiUser($_appkey);
        }else {
            $apiuser = $response->getApiUser($_appkey);
        }
        if(!$apiuser){
            $this->_failresult("无效的appkey");
        }
        $appsecret=$apiuser['appsecret'];
        if(empty($appsecret)){
            $this->_failresult("秘钥没有设置!");
        }

        SetG("SSE/appkey",$_appkey); //保存子站运行环境参数
        SetG("SSE/appuser",$apiuser);

        $apiparamstr=sys_auth($_argot,'DECODE',$appsecret);
        if(empty($apiparamstr)){
            $this->_failresult("秘钥不正确，请求失败");
        }
        $apiparam=[];
        parse_str($apiparamstr,$apiparam);

        $apiparam=array_merge($apiparam,$requestdata);

        if(method_exists($response,"putParams")){
            $response->putParams($apiparam);
        }else{
            $response->autoGetParams($apiparam);
        }
        if(method_exists($response,"check")){
            if(!$response->check($apiuser['callerlevel']?:0)){
                return $response->geterror();
            }
        }

        if(!method_exists($response,'getApiResult')){

            $this->_failresult($_method."api方法名：".$_method."接口getApiResult没有实现");
        }

        return $response->getApiResult(); //返回结果

    }

    private function _failresult($msg){
        GW(__json_encode(['status'=>'fail','msg'=>$msg,'__'=>1]));
        exit;
    }

}

class EachRequestThread{

    private $_parent=null;
    private $_method=null;
    private $_config=null;
    private $_sdkns=null;
    private $apiParas=null;
    private $_filenames=[];
    private $_isfileupload=false;
    private $_callback=null;
    private $_mLog=null;

    public function __construct($parent,$pm)
    {

        $this->_parent=$parent;
        $this->setMethod($pm[0]);
        $this->setParam($pm[1]); //设置参数
        $this->_callback=$pm[2]?:null;
        $this->_config=$pm[3]?:null;

        $this->_mLog=XlLead::logger("rpccall_".$this->_sdkns.".".$this->_method);

    }

    public function setMethod($method=''){

        //请求的方法
        if(empty($method)){
            $this->_parent->halt('api method no exist');
        }

        preg_match("/(.+?)\.(.+)/",$method,$mh);

        $this->_sdkns=$mh[1];
        $this->_method=$mh[2];


    }
    protected function getPPs(){

        $pms=[];
        $pms[]='page';
        $pms[]='fields';
        $pms[]='num';
        $pms[]='offset';
        $pms[]='appkey';

        return $pms;
    }

    public function setParam($pm,$value=''){
        if(is_string($pm)){
            $pm=array($pm=>$value);
        }
        $pms=$this->getPPs();
        foreach($pm as $k=>$v){
            $this->apiParas[$k] =$v;
            if(!in_array($v,$pms)) {
                if(is_string($v)) {
                    if (substr($v, 0, 1) == "@") {
                        $this->_isfileupload = true;
                        $this->_filenames[] = substr($v,1);
                    }
                }
            }
        }

    }

    public function checkRequestParam($apiParas){

        $request=$this->getRequestObj();
        if(!$request){
            //不验证参数合法性
            return;
        }
        $request->setApiParas($apiParas);
        $pms=$this->getPPs();
        $promisepms=$request->promiseParam();
        $pms=array_merge($pms,$promisepms);
        $request->checkPromiseParam($pms); //验证

        $request->check();

    }

    public function getRequestObj(){


        if(substr($this->_method,-7)!='Request'){
            $this->_method.='Request';
        }
        $method=$this->_method;
        $path=RPC_PATH;
        $cls='rpc\\';
        $path.="client".D_S;
        $cls.="client\\";
        $path.=$this->_sdkns.D_S;
        $path.=str_replace(".",D_S,$method);
        $path.='.php';

        if(!file_exists($path)){
            return null;
        }
        $cls.=$this->_sdkns."\\".str_replace(".","\\",$method);

        return new $cls($this->_method);

    }

    private function _dealcookie($ihttp){

        $authcode=GetG("member/authcode");
        if($authcode){
            $ihttp->setCookie($this->appkey.'-auth',$authcode);
        }
    }

    private function _getPostUrl($cnf){

        $url='';
        if($this->_config){
            $url=$this->_config['rsp_urls'];
        }
        if(empty($url)){
            $url=$cnf['rsp_urls'];
        }
        if(empty($url)){
            $url=GetG($this->_sdkns."/rsp_urls");
        }
        if(empty($url)){
            return 'http://localhost/rpcgateway';
        }
        $rsp_urlsArr=explode(',',$url);
        $rsp_url=$rsp_urlsArr[array_rand($rsp_urlsArr,1)];

        if(strpos($rsp_url,"?")===FALSE){
            $rsp_url.='/rpcgateway';
        }
        return $rsp_url;
    }
    private function _getAppKey($cnf){

        $appkey='';
        if($this->_config){
            $appkey=$this->_config['appkey'];
        }
        if(empty($appkey)){
            $appkey=GetG($this->_sdkns.'/appkey')?:$cnf['appkey'];
        }
        return $appkey;
    }
    private function _getAppSecret($cnf){

        $appsecret='';
        if($this->_config){
            $appsecret=$this->_config['appsecret'];
        }
        if(empty($appsecret)){
            $appsecret=GetG($this->_sdkns.'/appsecret')?:$cnf['appsecret'];
        }
        return $appsecret;

    }

    public function getCurlHandler(){


        if(!$this->apiParas['fields']){$this->apiParas['fields']='status,code,msg';}

        $this->checkRequestParam($this->apiParas); //验证参数

        $confpath=RPC_PATH."client".D_S.$this->_sdkns.D_S."conf.json";
        $cnf=sysclass("json")->read($confpath,false);

        if($cnf&&$cnf['hostname']){
            $hostconf=sysclass("globalconf")->getHostConfByHostName($cnf['hostname']);
            if(!$hostconf){
                throw new XlException("主机名".$cnf['hostname']."无效");
            }
            if(!$cnf['rsp_urls']){
                $url=$hostconf['url']?:$hostconf['host'];
                if(!preg_match("/[a-zA-Z]+:\/\/.+/",$url)){
                    $url="http://".$url;
                }
                $cnf['rsp_urls']=$url;
            }
            if(!$cnf['appkey']){
                $cnf['appkey']=$hostconf['rpc_appkey'];
                $cnf['appsecret']=$hostconf['rpc_appsecret'];
            }
        }

        $rsp_url=$this->_getPostUrl($cnf);
        $appkey=$this->_getAppKey($cnf);
        $appsecret=$this->_getAppSecret($cnf);

        $apiparam=$this->apiParas;

        ksort($apiparam);
        $poststr=sys_auth(http_build_query($apiparam),'ENCODE',$appsecret);

        $sign=md5(md5($poststr));
        $timestamp=time();//当前请求的时间戳
        $cls=sysclass("httpcurl",0);
        $ihttp=new $cls();
        $postdata=['_argot'=>$poststr];
        $postdata['_appkey']=$appkey;
        $postdata['_sign']=$sign;
        $postdata['_timestamp']=$timestamp;
        $postdata['_method']=$this->_method;
        $postdata['_sdkns']=$this->_sdkns;

        $this->_dealcookie($ihttp);
        if($this->_isfileupload){
            $ihttp->prepareUpload($rsp_url,$postdata,$this->_filenames);
        }else{
            $ihttp->preparePost($rsp_url,$postdata);
        }

        return $ihttp->getCurlObj();

    }

    public function getLogObj(){

        return $this->_mLog;

    }

}