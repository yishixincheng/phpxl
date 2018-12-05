<?php
namespace Xl_WeApp_SDK\Lib;
use Xl_WeApp_SDK\Lib\WxPayResult as WxPayResult;

/**
 * Class WxPayNotify
 * @package Xl_WeApp_SDK\Lib
 * 回调基类
 * https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=9_7&index=8
 */
class WxPayNotify extends WxPayParam{

    /**
     * @param $callback
     * @param bool $needSign
     * 需要签名验证
     */
    final public function handle($callback=null,$needSign = true){

        //获取通知的数据
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        //如果返回成功则验证签名
        $return=true;
        $msg='';
        $result=null;
        try {
            $result = WxPayResult::Init($xml);
        } catch (\Exception $e){
            $msg = $e->getMessage();
            $return=false;
        }

        if($return==false){

            $this->setValueByKey("return_code","FAIL");
            $this->setValueByKey("return_msg",$msg);

            $this->replyNotify(false);

            return;

        }else{
            $this->notifyCallBack($callback,$result);
            $this->setValueByKey("return_code","SUCCESS");
            $this->setValueByKey("return_msg","OK");
            $this->replyNotify($needSign);
        }
    }

    final public function notifyCallBack($callback=null,$data)
    {
        $msg = "OK";
        if(is_callable($callback)){
            $result=$callback($data,$msg); //不继承
        }else{
            $result = $this->notifyProcess($data, $msg); //继承方式
        }
        if($result == true){
            $this->setValueByKey("return_code","SUCCESS");
            $this->setValueByKey("return_msg","OK");
        } else {
            $this->setValueByKey("return_code","FAIL");
            $this->setValueByKey("return_msg",$msg);
        }
        return $result;
    }
    /**
     *
     * 回复通知
     * @param bool $needSign 是否需要签名输出
     */
    final private function replyNotify($needSign = true)
    {
        //如果需要签名
        if($needSign == true && $this->getValueByKey("return_code") == "SUCCESS")
        {
            $this->SetSign();
        }
        echo $this->ToXml();
    }

    /**
     *
     * 回调方法入口，子类可重写该方法
     * 注意：
     * 1、微信回调超时时间为2s，建议用户使用异步处理流程，确认成功之后立刻回复微信服务器
     * 2、微信服务器在调用失败或者接到回包为非确认包的时候，会发起重试，需确保你的回调是可以重入
     * @param array $data 回调解释出的参数
     * @param string $msg 如果回调处理失败，可以将错误信息输出到该方法
     * @return true回调出来完成不需要继续回调，false回调处理未完成需要继续回调
     */
    public function notifyProcess($data, &$msg)
    {
        //TODO 用户基础该类之后需要重写该方法，成功的时候返回true，失败返回false
        return true;
    }



}