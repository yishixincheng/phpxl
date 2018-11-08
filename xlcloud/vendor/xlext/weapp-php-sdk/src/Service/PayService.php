<?php

namespace Xl_WeApp_SDK;
use Xl_WeApp_SDK\Lib\WxPayResult;
use Xl_WeApp_SDK\Service\ServiceBase;
use Xl_WeApp_SDK\Lib\WxPayParam as WxPayParam;

/**
 * Class PayService
 * @package Xl_WeApp_SDK
 * 小程序微信支付接口
 */
class PayService extends ServiceBase{

    public function checkMustParam($inputObj,$keys,$interfacename=''){

        foreach ($keys as $key){
            if(!$inputObj->isKeySet($key)){
                throw new \Exception("缺少".$interfacename."必填参数".$key."！");
            }
        }

    }
    /**
     * 统一下单
     * https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=9_1
     */
     public function unifiedOrder($params,$timeOut=6){

         $url="https://api.mch.weixin.qq.com/pay/unifiedorder";
         $inputObj=new WxPayParam();
         $inputObj->setValues($params); //置入参数

         //小程序，trade_type=JSAPI,openid必传
         $this->checkMustParam($inputObj,['out_trade_no','body','total_fee','trade_type'],"统一支付接口");

         if($inputObj->getValueByKey("trade_type")=="JSAPI"){
             if(!$inputObj->isKeySet("openid")){
                 throw new \Exception("openid参数缺失");
             }
         }else if($inputObj->getValueByKey("trade_type")=="NATIVE"){
             if(!$inputObj->isKeySet("product_id")){
                 throw new \Exception("product_id参数缺失");
             }
         }

         //关联参数
         if(!$inputObj->isKeySet("notify_url")){
             $inputObj->setValueByKey("notify_url",Config::getNOTIFY_URL());
         }
         $inputObj->setValueByKey("appid",Config::getAppId());
         $inputObj->setValueByKey("mch_id",Config::getMchid());
         $inputObj->setValueByKey("spbill_create_ip",$_SERVER['REMOTE_ADDR']);//终端ip
         $inputObj->setValueByKey("nonce_str",static::getNonceStr()); //随机字符串

         //签名
         $inputObj->SetSign();
         $xml = $inputObj->ToXml();

         $startTimeStamp = self::getMillisecond();//请求开始时间
         $response=static::postXmlCurl($xml,$url,false,$timeOut);
         $result=WxPayResult::Init($response);

         //上报数据
         $this->reportCostTime($url,$startTimeStamp,$result);

         return $result;

     }

    /**
     * 订单查询
     * https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=9_2
     */
     public function orderQuery($params,$timeOut=6){

         $url='https://api.mch.weixin.qq.com/pay/orderquery';

         $inputObj=new WxPayParam();
         $inputObj->setValues($params); //置入参数
         if(!$inputObj->isKeySet("transaction_id")&&!$inputObj->isKeySet("out_trade_no")){
             throw new \Exception("订单查询接口中，out_trade_no、transaction_id至少填一个！");
         }
         $inputObj->setValueByKey("appid",Config::getAppId());
         $inputObj->setValueByKey("mch_id",Config::getMchid());
         $inputObj->setValueByKey("nonce_str",static::getNonceStr()); //随机字符串

         //签名
         $inputObj->SetSign();
         $xml = $inputObj->ToXml();

         $startTimeStamp = self::getMillisecond();//请求开始时间
         $response=static::postXmlCurl($xml,$url,false,$timeOut);
         $result=WxPayResult::Init($response);

         //上报数据
         $this->reportCostTime($url,$startTimeStamp,$result);

         return $result;


     }

     /**
      * 关闭订单
      * https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=9_3
      */
     public function closeOrder($params,$timeOut=6){

         $url = "https://api.mch.weixin.qq.com/pay/closeorder";

         $inputObj=new WxPayParam();
         $inputObj->setValues($params); //置入参数

         $this->checkMustParam($inputObj,['out_trade_no'],"订单查询接口");

         $inputObj->setValueByKey("appid",Config::getAppId());
         $inputObj->setValueByKey("mch_id",Config::getMchid());
         $inputObj->setValueByKey("nonce_str",static::getNonceStr()); //随机字符串

         //签名
         $inputObj->SetSign();
         $xml = $inputObj->ToXml();

         $startTimeStamp = self::getMillisecond();//请求开始时间
         $response=static::postXmlCurl($xml,$url,false,$timeOut);
         $result=WxPayResult::Init($response);

         //上报数据
         $this->reportCostTime($url,$startTimeStamp,$result);

         return $result;

     }

     /**
      * 申请退款
      * 需要证书
      * https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=9_4
      */
     public function refund($params,$timeOut=6){

         $url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
         $inputObj=new WxPayParam();
         $inputObj->setValues($params); //置入参数

         if(!$inputObj->isKeySet("transaction_id")&&!$inputObj->isKeySet("out_trade_no")){
             throw new \Exception("退款申请接口中，out_trade_no、transaction_id至少填一个！");
         }
         $this->checkMustParam($inputObj,['out_refund_no','total_fee','refund_fee','op_user_id'],'退款申请接口');

         $inputObj->setValueByKey("appid",Config::getAppId());
         $inputObj->setValueByKey("mch_id",Config::getMchid());
         $inputObj->setValueByKey("nonce_str",static::getNonceStr()); //随机字符串

         //签名
         $inputObj->SetSign();
         $xml = $inputObj->ToXml();

         $startTimeStamp = self::getMillisecond();//请求开始时间
         $response=static::postXmlCurl($xml,$url,true,$timeOut);
         $result=WxPayResult::Init($response);

         //上报数据
         $this->reportCostTime($url,$startTimeStamp,$result);

         return $result;

     }

     /**
      * 查询退款
      * 提交退款申请后，通过调用该接口查询退款状态。
      * 退款有一定延时，用零钱支付的退款20分钟内到账，银行卡支付的退款3个工作日后重新查询退款状态。
      * https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=9_5
      */
     public function refundQuery($params,$timeOut=6){

         $url="https://api.mch.weixin.qq.com/pay/refundquery";

         $inputObj=new WxPayParam();
         $inputObj->setValues($params); //置入参数

         if(!$inputObj->isKeySet("out_refund_no")&&
             !$inputObj->isKeySet("out_trade_no")&&
             !$inputObj->isKeySet("transaction_id")&&
             !$inputObj->isKeySet("refund_id")
         ){
             throw new \Exception("退款查询接口中，out_refund_no、out_trade_no、transaction_id、refund_id四个参数必填一个！");
         }

         $inputObj->setValueByKey("appid",Config::getAppId());
         $inputObj->setValueByKey("mch_id",Config::getMchid());
         $inputObj->setValueByKey("nonce_str",static::getNonceStr()); //随机字符串

         //签名
         $inputObj->SetSign();
         $xml = $inputObj->ToXml();

         $startTimeStamp = self::getMillisecond();//请求开始时间
         $response=static::postXmlCurl($xml,$url,true,$timeOut);
         $result=WxPayResult::Init($response);

         //上报数据
         $this->reportCostTime($url,$startTimeStamp,$result);

         return $result;

     }

     /**
      * 下载对账单
      * https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=9_6
      */
     public function downloadBill($params,$timeOut=6){

         $url="https://api.mch.weixin.qq.com/pay/downloadbill";

         $inputObj=new WxPayParam();
         $inputObj->setValues($params); //置入参数

         $this->checkMustParam($inputObj,['bill_date'],'对账单接口');

         $inputObj->setValueByKey("appid",Config::getAppId());
         $inputObj->setValueByKey("mch_id",Config::getMchid());
         $inputObj->setValueByKey("nonce_str",static::getNonceStr()); //随机字符串

         //签名
         $inputObj->SetSign();
         $xml = $inputObj->ToXml();

         $startTimeStamp = self::getMillisecond();//请求开始时间
         $response=static::postXmlCurl($xml,$url,true,$timeOut);
         $result=WxPayResult::Init($response);

         //上报数据
         $this->reportCostTime($url,$startTimeStamp,$result);

         return $result;

     }

     /**
      * 下载资金账单
      * https://api.mch.weixin.qq.com/pay/downloadfundflow
      */
     public function downLoadFundFlow($params,$timeOut=6){

         $url="https://api.mch.weixin.qq.com/pay/downloadfundflow";

         $inputObj=new WxPayParam();
         $inputObj->setValues($params); //置入参数

         $this->checkMustParam($inputObj,['bill_date','account_type'],'资金账单接口');

         $inputObj->setValueByKey("appid",Config::getAppId());
         $inputObj->setValueByKey("mch_id",Config::getMchid());
         $inputObj->setValueByKey("nonce_str",static::getNonceStr()); //随机字符串

         //签名
         $inputObj->SetSign();
         $xml = $inputObj->ToXml();

         $startTimeStamp = self::getMillisecond();//请求开始时间
         $response=static::postXmlCurl($xml,$url,true,$timeOut);
         $result=WxPayResult::Init($response);

         //上报数据
         $this->reportCostTime($url,$startTimeStamp,$result);

         return $result;


     }

    /**
     * 拉取订单评价数据
     * https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=9_17&index=11
     */
     public function batchQueryComment($params,$timeOut=6){

         $url="https://api.mch.weixin.qq.com/billcommentsp/batchquerycomment";

         $inputObj=new WxPayParam();
         $inputObj->setValues($params); //置入参数

         $this->checkMustParam($inputObj,['begin_time','end_time','offset'],'资金账单接口');

         $inputObj->setValueByKey("appid",Config::getAppId());
         $inputObj->setValueByKey("mch_id",Config::getMchid());
         $inputObj->setValueByKey("nonce_str",static::getNonceStr()); //随机字符串

         //签名
         $inputObj->SetSign();
         $xml = $inputObj->ToXml();

         $startTimeStamp = self::getMillisecond();//请求开始时间
         $response=static::postXmlCurl($xml,$url,true,$timeOut);
         $result=WxPayResult::Init($response);

         //上报数据
         $this->reportCostTime($url,$startTimeStamp,$result);

         return $result;

     }


    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    /**
     * 获取毫秒级别的时间戳
     */
    private static function getMillisecond()
    {
        //获取毫秒的时间戳
        $time = explode ( " ", microtime () );
        $time = $time[1] . ($time[0] * 1000);
        $time2 = explode( ".", $time );
        $time = $time2[0];
        return $time;
    }

    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml  需要post的xml数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @throws WxPayException
     */
    private static function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        //如果有配置代理这里就设置代理
        if(Config::getCURL_PROXY_HOST() != "0.0.0.0" && Config::getCURL_PROXY_PORT() != 0){
            curl_setopt($ch,CURLOPT_PROXY, Config::getCURL_PROXY_HOST());
            curl_setopt($ch,CURLOPT_PROXYPORT, Config::getCURL_PROXY_PORT());
        }
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, Config::getSSLCERT_PATH());
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, Config::getSSLKEY_PATH());
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new \Exception("curl出错，错误码:$error");
        }
    }

    /**
     *
     * 上报数据， 上报的时候将屏蔽所有异常流程
     * @param string $usrl
     * @param int $startTimeStamp
     * @param array $data
     */
    private function reportCostTime($url, $startTimeStamp, $data)
    {
        //如果不需要上报数据
        if(Config::getREPORT_LEVENL() == 0){
            return null;
        }
        //如果仅失败上报
        if(Config::getREPORT_LEVENL() == 1 &&
            array_key_exists("return_code", $data) &&
            $data["return_code"] == "SUCCESS" &&
            array_key_exists("result_code", $data) &&
            $data["result_code"] == "SUCCESS")
        {
            return null;
        }
        //上报逻辑
        $endTimeStamp = self::getMillisecond();
        $objInput = new WxPayParam();
        $objInput->setValues([
            'interface_url'=>$url,
            'execute_time_'=>$endTimeStamp-$startTimeStamp,
        ]);
        //返回状态码
        if(array_key_exists("return_code", $data)){
            $objInput->setValueByKey("return_code",$data['return_code']);
        }
        //返回信息
        if(array_key_exists("return_msg", $data)){
            $objInput->setValueByKey("return_msg",$data['return_msg']);
        }
        //业务结果
        if(array_key_exists("result_code", $data)){
            $objInput->setValueByKey("result_code",$data['result_code']);
        }
        //错误代码
        if(array_key_exists("err_code", $data)){
            $objInput->setValueByKey('err_code',$data["err_code"]);
        }
        //错误代码描述
        if(array_key_exists("err_code_des", $data)){
            $objInput->setValueByKey("err_code_des",$data['err_code_des']);
        }
        //商户订单号
        if(array_key_exists("out_trade_no", $data)){
            $objInput->setValueByKey("out_trade_no",$data['out_trade_no']);
        }
        //设备号
        if(array_key_exists("device_info", $data)){
            $objInput->setValueByKey("device_info",$data['device_info']);
        }
        try{
            $url="https://api.mch.weixin.qq.com/payitil/report";
            $this->checkMustParam($objInput,['interface_url','return_code','result_code','user_ip','execute_time_'],'测试接口');
            $objInput->setValueByKey("appid",Config::getAppId());
            $objInput->setValueByKey("mch_id",Config::getMchid());
            $objInput->setValueByKey("user_ip",$_SERVER['REMOTE_ADDR']);//终端ip
            $objInput->setValueByKey("nonce_str",static::getNonceStr()); //随机字符串
            $objInput->setValueByKey("time",date("YmdHis"));

            $objInput->setSign();
            $xml = $objInput->ToXml();

            $response = self::postXmlCurl($xml, $url, false, 1);
            return $response;

        } catch (\Exception $e){
            //不做任何处理
        }
        return null;
    }



}