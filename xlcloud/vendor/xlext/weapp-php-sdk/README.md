# Xl_WeApp_SDK 使用说明

##介绍

基于腾讯云提供的sdk进行改装，提供了小程序后端登录和微信支付接口；
无内置数据表操作，只提供相关接口，方便与其他框架集成。

注明：客户端依然可以使用腾讯云SDK，仅支持小程序登录。

##安装

使用 PHP 包依赖管理工具 `composer` 执行以下命令安装

```sh
composer require xlext/weapp-php-sdk
```

##使用说明

######初始化注入配置信息：

```php

use Xl_WeApp_SDK\App as App;

App::run([
   'AppId'=>'',  //必填
   'AppSecret'=>'', //必填
   'Mchid'=>'',     //使用支付功能必填
   'KEY'=>'',       //同上
   'NOTIFY_URL'=>'', //支付异步通知
   'SSLCERT_PATH'=>'', //支付接口需要签名必填
   'SSLKEY_PATH'=>''   //支付接口需要签名必填
]);

```

######登录：

```php

$loginService=App::getService("Login");

$loginService->login(function($method,$params){

    swtich($method){
        case "findUserByOpenId":  //根据openid从数据表获取用户信息
        $result="";
        break;
        case "storeUserInfo":  //更新存储用户信息
        $result="";
        break;
        
    }
    return $result;

});

//check登录

$loginService->check(function($method,$params){

    if($method=="findUserBySKey"){
         //根据skey从数据表获取用户信息
         return $userinfo;
    }
   
});

```

######支付：

```php

$payService=App::getService("Pay");

//支付请求
$payService->unifiedOrder([
     'out_trade_no'=>'', //商户订单号
     'body'=>'',   //订单说明
     'total_fee'=>'', //金额（分）
     'trade_type'=>'JSAPI',
     'openid'=>''    //通过登录接口获取用户openid
]);

//订单查询
$payService->orderQuery([
      'out_trade_no'=>'', //商户订单号
      'transaction_id'=>''  //交易号
]);

//...其他接口见源码


//支付回调

use Xl_WeApp_SDK\Lib\WxPayNotify as WxPayNotify;

$notify=new WxPayNotify();

$notify->handle(function($result,&$msg){

    if($result['return_code']=="SUCCESS"){
    
         //支付成功执行商户逻辑
         
         return true;
        
    }else{
         $msg=$result['return_msg'];
         
         return false;
    }

});


```







