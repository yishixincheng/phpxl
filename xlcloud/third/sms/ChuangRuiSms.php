<?php

/**
 * 创瑞短信接口
 */

class ChuangRuiSms{


    public function send($mobile,$content,$templateId='',$sign='',$encode='UTF-8')
    {
        //发送链接（用户名，密码，手机号，内容）

        $accesskey=config("sms/CHUANGRUI/sms_accesskey")?:config("sms/CHUANGRUI/sms_account");
        $secret=config("sms/CHUANGRUI/sms_secret")?:config("sms/CHUANGRUI/sms_password");

        $url = "http://api.1cloudsp.com/api/v2/send?";
        $data=array
        (
            'accesskey'=>$accesskey,
            'secret'=>$secret,
            'encode'=>$encode,
            'mobile'=>$mobile,
            'content'=>$content,
            'sign'=>$sign,
            'templateId'=>$templateId


        );
        $result = $this->curlSMS($url,$data);
        //print_r($data); //测试

        if(stripos($result,"success")==0) {
            //提交成功
            return true;
            //逻辑代码
        } else {
            //提交失败
            return false;
        }

    }
    private function curlSMS($url,$post_fields=array())
    {
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_TIMEOUT,30);//30秒超时限制
        curl_setopt($ch,CURLOPT_HEADER,1);//将文件头输出直接可见。
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$post_fields);//post操作的所有数据的字符串。
        $data = curl_exec($ch);//抓取URL并把他传递给浏览器
        curl_close($ch);//释放资源
        $res = explode("\r\n\r\n",$data);//explode把他打散成为数组
        return $res[2]; //然后在这里返回数组。
    }

}