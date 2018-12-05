<?php

namespace Xl_WeApp_SDK;

class Config{

    //微信小程序AppID
    private static $AppId='';

    //微信小程序AppSecret
    private static $AppSecret='';

    //商户号，用于微信支付
    private static $Mchid='';

    //key为商户平台设置的密钥key
    private static $KEY='';

    //支付成功异步通知地址
    private static $NOTIFY_URL='';

    //sslcert路径
    private static $SSLCERT_PATH='';
    //sslkey路径
    private static $SSLKEY_PATH='';

    //代理端口
    private static $CURL_PROXY_PORT=0;

    //是否需要上报数据
    private static $REPORT_LEVENL=0;

    // 微信消息通知 token
    private static $WxMessageToken = '';

    // 微信登录态有效期
    private static $WxLoginExpires = 7200;

    // 网络请求超时时长（单位：毫秒）
    private static $NetworkTimeout = 3000;


    public static function __callStatic($name, $arguemnts) {
        $class = get_class();
        if (strpos($name, 'get') === 0) {
            $key = preg_replace('/^get/', '', $name);
            if (property_exists($class, $key)) {
                $value = self::$$key;
                return $value;
            }
        }else if (strpos($name, 'set') === 0) {
            $key = preg_replace('/^set/', '', $name);
            $value = isset($arguemnts[0]) ? $arguemnts[0] : NULL;
            if (property_exists($class, $key)) {
                self::$$key = $value;
            }
        }
        return null;
    }

    public static function setup($config =NULL){
        if (!is_array($config)) {
            throw new \Exception("配置参数应该是数组");
        }
        $class = get_class();
        foreach ($config as $key => $value) {
            $key = ucfirst($key);
            if (property_exists($class, $key)) {
                if (gettype($value) === gettype(self::$$key)) {
                    if (gettype($value) === 'array') {
                        self::$$key = array_merge(self::$$key, $value);
                    } else {
                        self::$$key = $value;
                    }
                } else {
                    throw new \Exception('配置类型未指定: ' . $key);
                }
            }
        }
    }

}