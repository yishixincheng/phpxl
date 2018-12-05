<?php

/**
 * 创瑞短信接口
 */

class ChuangRuiSms{


    public function __construct()
    {

    }
    public function send($telphone,$content,$timertime='',$sign='',$extno=''){

        $flag = 0;
        $params='';
        $sign=($sign?:config("sms/CHUANGRUI/sign"))?:config("sms/sign");

        //以下信息自己填以下
        $argv = array(
            'name'=>config("sms/CHUANGRUI/sms_account"),     //必填参数。用户账号
            'pwd'=>config("sms/CHUANGRUI/sms_password"),     //必填参数。（web平台：基本资料中的接口密码）
            'content'=>$content,   //必填参数。发送内容（1-500 个汉字）UTF-8编码
            'mobile'=>$telphone,   //必填参数。手机号码。多个以英文逗号隔开
            'stime'=>$timertime,   //可选参数。发送时间，填写时已填写的时间发送，不填时为当前时间发送
            'sign'=>$sign,    //必填参数。用户签名。
            'type'=>'pt',  //必填参数。固定值 pt
            'extno'=>$extno    //可选参数，扩展码，用户定义扩展码，只能为数字
        );
        ;
        foreach ($argv as $key=>$value) {
            if ($flag!=0) {
                $params .= "&";
                $flag = 1;
            }
            $params.= $key."="; $params.= urlencode($value);// urlencode($value);
            $flag = 1;
        }
        $url = "http://web.cr6868.com/asmx/smsservice.aspx?".$params; //提交的url地址

        $con=file_get_contents($url);
        $i=strpos($con,',');
        $con= substr( $con, 0, $i);  //获取信息发送后的状态

        if($con == '0'){
             return true;
        }else{
             return $con;
        }

    }

}