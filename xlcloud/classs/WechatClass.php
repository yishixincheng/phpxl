<?php

namespace xl\classs;

use xl\base\XlClassBase;

class WechatClass extends XlClassBase {

    private $token;
    private $data=array();
    function __construct($token=''){

        $this->token=$token;
        $ischeckwxurl=config("system/ischeckwxurl"); //判断是否验证微信url

        if(!$ischeckwxurl){

            $this->checkSignature()||exit;
        }
        if(IS_GET){

            echo $_GET['echostr'];
            exit;
        }else{
            $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
            $xml=file_get_contents("php://input");

            $xml= new \SimpleXMLElement($xml);
            $xml||exit;
            foreach($xml as $key => $value){
                $this->data[$key]=strval($value);
            }
        }

    }
    public function request(){
        return $this->data;
    }

    //回复响应
    public function response($content,$type='',$flag=0){
        $type=$type?$type:'text'; //默认消息类型

        $this->data=array('ToUserName'=>$this->data['FromUserName'],'FromUserName'=>$this->data['ToUserName'],'CreateTime'=>NOW_TIME,'MsgType'=>$type,);
        $this->$type($content);
        $this->data['FuncFlag']=$flag;
        $xml = new \SimpleXMLElement('<xml></xml>');
        $this->dataToXml($xml,$this->data);
        echo $xml->asXML(); //输出

        exit;
    }
    private function text($content){

        $this->data['Content']=$content;
    }
    public function music($music){
        //音乐
        list($music['Title'],$music['Description'],$music['MusicUrl'],$music['HQMusicUrl'],$music['ThumbMediaId'])=$music;
        $this->data['Music']=$music;
    }
    public function news($news){
        $articles= array();
        foreach($news as $key=>$value){
            list($articles[$key]['Title'],$articles[$key]['Description'],$articles[$key]['PicUrl'],$articles[$key]['Url'])=$value;
            if($key>9){
                break; //最多支持10条图文
            }
        }
        $this->data['ArticleCount']=count($articles);
        $this->data['Articles']=$articles;
    }
    public function video($video){

        //视频回复
        list($video['MediaId'],$video['Title'],$video['Description'])=$video;
        $this->data['Video']=$video;

    }
    public function voice($voice){
        //语音回复
        list($voice['MediaId'])=$voice;
        $this->data['Voice']=$voice;

    }
    public function image($image){
        //图片回复
        list($image['MediaId'])=$image;
        $this->data['Image']=$image;
    }

    private function dataToXml($xml,$data,$item='item'){

        foreach($data as $key=>$value){

            is_numeric($key) && $key=$item;
            if(is_array($value)||is_object($value)){
                $child=$xml->addChild($key);
                $this->dataToXml($child,$value,$item);
            }else{
                if(is_numeric($value)){
                    $xml->addChild($key,$value);
                }else{
                    $child=$xml->addChild($key);
                    $node= dom_import_simplexml($child);
                    $node->appendChild($node->ownerDocument->createCDATASection($value));
                }
            }

        }
    }
    private function checkSignature(){

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = sha1(implode( $tmpArr ));


        return $tmpStr === $signature;
    }


}

