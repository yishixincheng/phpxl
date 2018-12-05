<?php

namespace Xl_WeApp_SDK\Service;

use Xl_WeApp_SDK\Config;

/**
 * Class LoginService
 * @package Xl_WeApp_SDK\Service
 * 小程序登录接口
 */
class LoginService extends ServiceBase{

    public function login($callback,$code=null,$encryptData=null,$iv=null){

        if(!is_callable($callback)){
            throw new \Exception("callback必须为回调函数！");
        }
        try {
            if (!$code) {
                $code = $_SERVER['HTTP_X_WX_CODE'] ?: '';
                $encryptData = $_SERVER['HTTP_X_WX_ENCRYPTED_DATA'] ?: '';
                $iv = $_SERVER['HTTP_X_WX_IV'] ?: '';
                if (!$code) {
                    throw new \Exception("请求头未包含 code，请配合客户端 SDK 登录后再进行请求");
                }
            }
            $pack=$this->getSessionKey($code);
            $session_key=$pack['session_key'];
            $openid=$pack['openid'];
            // 2. 生成 3rd key (skey)
            $skey = sha1($session_key . mt_rand());
            // 如果只提供了 code
            // 就用 code 解出来的 openid 去查数据库
            if ($code && !$encryptData && !$iv) {

                $userInfo=$callback("findUserByOpenId",["openid"=>$openid]);
                $wxUserInfo = json_decode($userInfo['user_info'],true);
                $callback("storeUserInfo",["wxUserInfo"=>$wxUserInfo,"skey"=>$skey,"session_key"=>$session_key]);
                return [
                    'loginState' => 1,
                    'userinfo' => [
                        'userinfo' => $wxUserInfo,
                        'skey' => $skey
                    ]
                ];
            }
            $decryptData = \openssl_decrypt(
                base64_decode($encryptData),
                'AES-128-CBC',
                base64_decode($session_key),
                OPENSSL_RAW_DATA,
                base64_decode($iv)
            );
            $userinfo = json_decode($decryptData,true);
            $callback("storeUserInfo",["userinfo"=>$userinfo,"skey"=>$skey,"session_key"=>$session_key]);

            return [
                'loginState' => 1,
                'userinfo' => compact('userinfo', 'skey')
            ];

        }catch (\Exception $e){
            return [
                'loginState' => 0,
                'error' => $e->getMessage()
            ];
        }

    }

    public function check($callback,$skey=null){

        if(!is_callable($callback)){
            throw new \Exception("callback必须为回调函数！");
        }

        if(!$skey){
            $skey=$_SERVER['HTTP_X_WX_SKEY'];
            if(!$skey){
                throw new \Exception("请求头未包含 skey，请配合客户端 SDK 登录后再进行请求");
            }
        }
        try{
            $userinfo=$callback("findUserBySKey",["skey"=>$skey]);
            if ($userinfo === NULL) {
                return [
                    'loginState' => 0,
                    'userinfo' => []
                ];
            }
            $wxLoginExpires = Config::getWxLoginExpires();
            $timeDifference = time() - strtotime($userinfo['last_visit_time']);
            if ($timeDifference > $wxLoginExpires) {
                return [
                    'loginState' => 0,
                    'userinfo' => []
                ];
            } else {
                return [
                    'loginState' => 1,
                    'userinfo' => json_decode($userinfo['user_info'], true)
                ];
            }


        }catch(\Exception $e){

            return [
                'loginState' => 0,
                'error' => $e->getMessage()
            ];

        }


    }


}