<?php

namespace Xl_WeApp_SDK\Service;

use Xl_WeApp_SDK\Config;
use Xl_WeApp_SDK\Lib\Request as Request;

class ServiceBase{

    public $access_token=null;

    final public function getSessionKey($code)
    {
        $appId=Config::getAppId();
        $appSecret=Config::getAppSecret();
        $requestParams = [
            'appid' => $appId,
            'secret' => $appSecret,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        ];
        $result=Request::get([
            'url' =>'https://api.weixin.qq.com/sns/jscode2session',
            'data'=>http_build_query($requestParams),
            'timeout' => Config::getNetworkTimeout()
        ]);
        $status=$result['status'];
        $body=$result['body'];
        if ($status !== 200 || !$body || isset($body['errcode'])) {
            throw new \Exception('请求错误: ' . json_encode($body));
        }

        return $body;

    }

    final public function getAccessToken(){

        if($this->access_token){
            return $this->access_token;
        }

        $appId=Config::getAppId();
        $appSecret=Config::getAppSecret();

        $requestParams = [
            'appid' => $appId,
            'secret' => $appSecret,
            'grant_type'  => 'client_credential',
        ];

        $result=Request::get([
            'url' =>'https://api.weixin.qq.com/cgi-bin/token',
            'data'=>http_build_query($requestParams),
            'timeout' => Config::getNetworkTimeout()
        ]);

        $status=$result['status'];
        $body=$result['body'];
        if ($status !== 200 || !$body || isset($body['errcode'])) {
            throw new \Exception('请求错误: ' . json_encode($body));
        }

        $this->access_token=$body['access_token'];

        return $this->access_token;

    }

    final public function getWxaQrcode($path,$width=430){

        $access_token=$this->getAccessToken();
        $url = "https://api.weixin.qq.com/wxa/getwxacode?access_token={$access_token}";
        $data = array();
        $data['path'] = $path;
        //最大32个可见字符，只支持数字，大小写英文以及部分特殊字符：!#$&'()*+,/:;=?@-._~，其它字符请自行编码为合法字符（因不支持%，中文无法使用 urlencode 处理，请使用其他编码方式）
        $data['width'] = $width;
        //二维码的宽度，默认为 430px

        $result=Request::jsonPost([
            'url' =>$url,
            'data'=>$data
        ]);

        return $result;

    }

    /**
     * 文字检测
     */
    final public function wordCheck($content){

        $access_token=$this->getAccessToken();
        $url = "https://api.weixin.qq.com/wxa/msg_sec_check?access_token={$access_token}";
        $data = [];
        $data['content'] = $content;

        $result=Request::jsonPostEx([
            'url' =>$url,
            'data'=>$data
        ]);

        return $result;

    }

    /**
     * 图片检测
     */
    final public function picCheck($picpath){

        $access_token=$this->getAccessToken();
        $url = "https://api.weixin.qq.com/wxa/img_sec_check?access_token={$access_token}";
        $data = [];
        $data['media'] = new \CURLFile($picpath);

        $result=Request::mediaPost([
            'url' =>$url,
            'data'=>$data
        ]);

        return $result;

    }

    //格式形如 { "key1": { "value": any }, "key2": { "value": any } }

    final public function sendSubscribeMsg($openid,$page,$data,$template_id){

        //向用户发送一条订阅消息

        $access_token=$this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$access_token}";
        $data1 = [];
        $data1['touser'] = $openid;
        $data1['page']=$page;
        $data1['data']=$data;
        $data1['template_id']=$template_id;

        $result=Request::jsonPost([
            'url' =>$url,
            'data'=>$data1
        ]);

        return $result;


    }


}