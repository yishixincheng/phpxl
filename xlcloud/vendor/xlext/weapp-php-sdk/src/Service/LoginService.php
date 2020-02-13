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
                $wxUserInfo = $userInfo['user_info'];
                $callback("storeUserInfo",["userinfo"=>$wxUserInfo,'openid'=>$openid,"skey"=>$skey,"session_key"=>$session_key]);
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

            $user=$callback("findUserByOpenId",["openid"=>$openid]);
            $userinfo=$user['user_info'];
            $callback("storeUserInfo",["userinfo"=>$userinfo?:json_decode($decryptData,true),'openid'=>$openid,"skey"=>$skey,"session_key"=>$session_key]);

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

            if(empty($skey)){

                return [
                    'loginState' => 0,
                    'userinfo' => []
                ];

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

            $user_info=json_decode($userinfo['user_info'], true);

            if(!($user_info&&$user_info['uid'])){
                $user_info=[];
            }


            return [
                'loginState' => 1,
                'userinfo' => $user_info
            ];


        }catch(\Exception $e){

            return [
                'loginState' => 0,
                'error' => $e->getMessage()
            ];

        }


    }

    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function decryptData(&$data,$session_key=null,$encryptedData=null, $iv=null)
    {

        $encryptData = $encryptedData?:$_SERVER['HTTP_X_WX_ENCRYPTED_DATA'];
        $iv = $iv?:$_SERVER['HTTP_X_WX_IV'];

        $aesKey=base64_decode($session_key);


        if (strlen($iv) != 24) {
            return ErrorCode::$IllegalIv;
        }
        $aesIV=base64_decode($iv);

        $aesCipher=base64_decode($encryptedData);

        $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj=json_decode( $result);
        if( $dataObj  == NULL )
        {
            return ErrorCode::$IllegalBuffer;
        }

        $data = $dataObj->phoneNumber;

        return ErrorCode::$OK;
    }


}

class ErrorCode
{
    public static $OK = 0;
    public static $IllegalAesKey = -41001;
    public static $IllegalIv = -41002;
    public static $IllegalBuffer = -41003;
    public static $DecodeBase64Error = -41004;
}