<?php

namespace Xl_WeApp_SDK\Service;

use Xl_WeApp_SDK\Config;
use Xl_WeApp_SDK\Lib\Request as Request;

class ServiceBase{


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

        return $body['access_token'];

    }


}