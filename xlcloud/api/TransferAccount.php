<?php

namespace xl\api;
use xl\XlLead;

/**
 * Class TransferAccount
 * @package xl\api
 * 转账接口
 */

final class TransferAccount extends XlApiBase{

    protected $model;             //插入数据表模型
    protected $payplat;           //支付平台，1,alipay;2,wxpay,3.wxpaytobank(微信支付到银行卡）
    protected $logger;            //日志
    protected $payconfig=[];      //支付参数
    protected $account;           //账号
    protected $realname;          //真实姓名
    protected $fee;                //转账金额，单位元
    protected $title;              //标题
    protected $remark;             //备注
    protected $orderno;
    protected $fromuid=0;            //平台转账uid
    protected $touid=0;              //平台要转账的uid
    protected $bankno=0;               //银行卡编码
    protected $tradeno;

    public function banknameToBankno($name){

        $banks=[
           '1002'=>'工商银行',
           '1005'=>'农业银行',
           '1026'=>'中国银行',
           '1003'=>'建设银行',
           '1001'=>'招商银行',
           '1066'=>'邮储银行',
           '1020'=>'交通银行',
           '1004'=>'浦发银行',
           '1006'=>'民生银行',
           '1009'=>'兴业银行',
           '1010'=>'平安银行',
           '1021'=>'中信银行',
           '1025'=>'华夏银行',
           '1027'=>'广发银行',
           '1022'=>'光大银行',
           '1032'=>'北京银行',
           '1056'=>'宁波银行'
        ];
        foreach ($banks as $k=>$v){
            if($name==$v){
                return $k;
            }
        }
        return 0;
    }

    private function createOrderNo(){

        //创建订单号
        return date("YmdHis").random(8);

    }

    public function run(){


        return $this->dispatch($this->payplat);


    }
    public function dispatch($payplat){

        $rt=$this->ErrorInf("payplat参数无效");
        switch ($payplat){
            case "wxpaytobank":
                $rt=$this->proxyToPay("startWxpayToBank");
                break;
            case "wxpay":
                $rt=$this->proxyToPay("startWxpay");
                break;
            case "alipay":
                $rt=$this->proxyToPay("startAlipay");
                break;
            case "wxpaytowalletqueryresult":
                $rt=$this->startWxpayToWalletQueryResult();
                break;
            case "wxpaytobankqueryresult":
                $rt=$this->startWxpayToBankQueryResult();
                break;
            case "alipayqueryresult":
                $rt=$this->startAlipayQueryResult();
                break;
        }

        return $rt;
    }

    public function proxyToPay($method){

        if(empty($this->payplat)||empty($this->account)||empty($this->fee)) {

            return $this->ErrorInf("参数缺失");

        }

        if(!$this->logger){
            $this->logger=XlLead::logger("transferaccount_".date("Y-m-d"));
        }

        if($this->orderno){
            if($this->model) {
                $count = $this->model->getRowNum("*", "where orderno='{$this->orderno}'");
                if ($count) {

                    return $this->ErrorInf("该订单号已存在不能重复转账");
                }
            }
        }else{
            $this->orderno=$this->createOrderNo();
        }

        return $this->$method();

    }

    public function startWxpayToBank(){

        if(!is_numeric($this->bankno)){
            $this->bankno=$this->banknameToBankno($this->bankno);
        }
        if(empty($this->bankno)) {
            return $this->ErrorInf("参数缺失bankno");
        }

        $includepath=$this->payconfig['#include'];
        if(!$includepath){
            return $this->ErrorInf("缺少微信配置文件");
        }
        if(substr($includepath,0,1)=="/"){
            $includepath=ROOT_PATH.substr($includepath,1);
        }else{
            $includepath=ROOT_PATH.'config/'.$includepath;
        }
        require_once($includepath);//包含配置文件

        import("@xl.third.pay.wxpaysdk.WxPay#Api"); //导入支付宝sdk入口文件

        $input = new \WxPayTransferToBank();

        $input->SetAmount($this->fee*100);
        $input->SetBank_code($this->bankno);
        $input->SetEnc_bank_no($this->account);
        $input->SetEnc_true_name($this->realname);
        $input->SetPartner_trade_no($this->orderno);
        $input->SetDesc($this->remark);

        $rt=\WxPayApi::payTransferToBank($input);

        if($rt['return_code']=="SUCCESS"&&$rt['result_code']=="SUCCESS"){

            $attach=['orderno'=>$this->orderno,'tradeno'=>$rt['payment_no'],'time'=>$rt['payment_time']?strtotime($rt['payment_time']):SYS_TIME];

            $this->_saveToModelAndRecordLogger($attach);

            return $this->SuccInf("支付成功",$attach);

        }else{

            return $this->ErrorInf($rt['return_msg']);
        }


    }

    public function startWxpayToBankQueryResult(){

        $includepath=$this->payconfig['#include'];
        if(!$includepath){
            return $this->ErrorInf("缺少微信配置文件");
        }
        if(substr($includepath,0,1)=="/"){
            $includepath=ROOT_PATH.substr($includepath,1);
        }else{
            $includepath=ROOT_PATH.'config/'.$includepath;
        }
        require_once($includepath);//包含配置文件

        import("@xl.third.pay.wxpaysdk.WxPay#Api"); //导入支付宝sdk入口文件

        $input = new \WxPayTransferToBankResultQuery();
        $input->SetPartner_trade_no($this->orderno); //商户订单号

        $rt=\WxPayApi::payTransferToBankResultQuery($input);

        return $rt;

    }

    public function startWxpay(){


        $includepath=$this->payconfig['#include'];
        if(!$includepath){
            return $this->ErrorInf("缺少微信配置文件");
        }
        if(substr($includepath,0,1)=="/"){
            $includepath=ROOT_PATH.substr($includepath,1);
        }else{
            $includepath=ROOT_PATH.'config/'.$includepath;
        }

        require_once($includepath);//包含配置文件

        import("@xl.third.pay.wxpaysdk.WxPay#Api"); //导入支付宝sdk入口文件

        $input = new \WxPayTransferToWallet();

        $input->SetAmount($this->fee*100);
        $input->SetOpenid($this->account);
        $input->SetCheck_name("FORCE_CHECK"); //验证真实姓名
        $input->SetRe_user_name($this->realname);
        $input->SetPartner_trade_no($this->orderno);
        $input->SetDesc($this->remark);

        $rt=\WxPayApi::payTransferToWallet($input);

        if($rt['return_code']=="SUCCESS"&&$rt['result_code']=="SUCCESS"){

            $attach=['orderno'=>$this->orderno,'tradeno'=>$rt['payment_no'],'time'=>$rt['payment_time']?strtotime($rt['payment_time']):SYS_TIME];

            $this->_saveToModelAndRecordLogger($attach);

            return $this->SuccInf("支付成功",$attach);

        }else{

            return $this->ErrorInf($rt['return_msg']);
        }

    }

    public function startWxpayToWalletQueryResult(){


        $includepath=$this->payconfig['#include'];
        if(!$includepath){
            return $this->ErrorInf("缺少微信配置文件");
        }
        if(substr($includepath,0,1)=="/"){
            $includepath=ROOT_PATH.substr($includepath,1);
        }else{
            $includepath=ROOT_PATH.'config/'.$includepath;
        }
        require_once($includepath);//包含配置文件

        import("@xl.third.pay.wxpaysdk.WxPay#Api"); //导入支付宝sdk入口文件

        $input = new \WxPayTransferToWalletResultQuery();
        $input->SetPartner_trade_no($this->orderno); //商户订单号

        $rt=\WxPayApi::payTransferToWalletResultQuery($input);

        return $rt;


    }

    public function startAlipay(){

        import("@xl.third.pay.alipaysdk.AopSdk"); //导入支付宝sdk入口文件
        $c = new \AopClient;
        $c->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $c->appId = $this->payconfig['appId'];
        $c->rsaPrivateKey = $this->payconfig['rsaPrivateKey']; //'请填写开发者私钥去头去尾去回车，一行字符串' ;
        $c->format = $this->payconfig["format"]?:"json";
        $c->charset= $this->payconfig["charset"]?:"UTF-8";
        $c->signType= $this->payconfig["signType"]?:"RSA2";
        $c->alipayrsaPublicKey = $this->payconfig['alipayrsaPublicKey'];//'请填写支付宝公钥，一行字符串';
        $c->encryptKey = $this->payconfig['encryptKey']?:null;
        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.open.public.template.message.industry.modify
        $request = new \AlipayFundTransToaccountTransferRequest();
        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        //此次只是参数展示，未进行字符串转义，实际情况下请转义
        $request->setBizContent("{" .
            "    \"out_biz_no\":\"{$this->orderno}\"," . // 可选 开发者对消息的唯一标识，服务器会根据这个标识避免重复发送。
            "    \"payee_type\":\"ALIPAY_LOGONID\"," .
            "    \"payee_account\":\"{$this->account}\"," .
            "    \"amount\":\"{$this->fee}\"," .
            "    \"payer_show_name\":\"{$this->title}\"," .
            "    \"payee_real_name\":\"{$this->realname}\"," .
            "    \"remark\":\"{$this->remark}\"" .
            "  }");
        $response= $c->execute($request);

        $request=$response->alipay_fund_trans_toaccount_transfer_response;

        if($request->code=="10000"){

            $attach=['orderno'=>$this->orderno,'tradeno'=>$request->order_id,'time'=>$request->pay_date?strtotime($request->pay_date):SYS_TIME];

            $this->_saveToModelAndRecordLogger($attach);

            return $this->SuccInf("支付成功",$attach);

        }else{
            return $this->ErrorInf($request->msg);
        }
    }

    public function startAlipayQueryResult(){

        import("@xl.third.pay.alipaysdk.AopSdk"); //导入支付宝sdk入口文件
        $c = new \AopClient;
        $c->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $c->appId = $this->payconfig['appId'];
        $c->rsaPrivateKey = $this->payconfig['rsaPrivateKey']; //'请填写开发者私钥去头去尾去回车，一行字符串' ;
        $c->format = $this->payconfig["format"]?:"json";
        $c->charset= $this->payconfig["charset"]?:"UTF-8";
        $c->signType= $this->payconfig["signType"]?:"RSA2";
        $c->alipayrsaPublicKey = $this->payconfig['alipayrsaPublicKey'];//'请填写支付宝公钥，一行字符串';
        $c->encryptKey = $this->payconfig['encryptKey']?:null;
        $request = new \AlipayFundTransOrderQueryRequest();
        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        //此次只是参数展示，未进行字符串转义，实际情况下请转义

        $this->orderno=$this->orderno?:'';
        $this->tradeno=$this->tradeno?:'';

        $request->setBizContent("{" .
            "\"out_biz_no\":\"{$this->orderno}\"," .
            "\"order_id\":\"{$this->tradeno}\"" .
            "  }");

        $response= $c->execute($request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";

        return json_decode( json_encode( $response->$responseNode ), true );

    }

    private function _saveToModelAndRecordLogger($attach){


        if($this->model){

            $cls=['orderno'=>$this->orderno,'tradeno'=>$attach['tradeno'],'time'=>$attach['time']?:SYS_TIME,
                  'payplat'=>$this->payplat,'account'=>$this->account,'realname'=>$this->realname,'bankno'=>$this->bankno,
                  'fee'=>$this->fee,'title'=>$this->title,'remark'=>$this->remark];

            $this->model->add($cls,true);

        }

        if($this->logger){

            $loggercontent=date("Y-m-d H:i:s")."：【{$this->fromuid}】向【{$this->touid}】账号：".$this->account;
            $loggercontent.=" 实名：".$this->realname." 转了".$this->fee."元 支付方式：".$this->payplat;

            if($this->bankno){
                $loggercontent.=" 银行编码：".$this->bankno;
            }
            $loggercontent.=PHP_EOL;

            $this->logger->write($loggercontent,true);

        }


    }

}