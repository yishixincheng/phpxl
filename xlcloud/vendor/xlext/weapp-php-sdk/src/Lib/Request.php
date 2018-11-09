<?php
namespace Xl_WeApp_SDK\Lib;

class Request {


    public static function get($options) {
        $options['method'] = 'GET';
        return self::send($options);
    }

    public static function post($options){

        $options['method']='POST';
        return self::send($options);

    }

    public static function jsonPost($options) {
        if (isset($options['data'])) {
            $options['data'] = json_encode($options['data']);
        }

        $options = array_merge_recursive($options, array(
            'method' => 'POST',
            'headers' => array('Content-Type: application/json; charset=utf-8'),
        ));

        return self::send($options);
    }

    public static function send($options) {
        $ch = curl_init();

        if (isset($options['data'])&&$options['method']=="POST") {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['data']);
        }else{
            if(isset($options['data'])&&$options['data']){

                if(is_array($options['data'])){
                    $queryStr=http_build_query($options['data']);
                }else{
                    $queryStr=$options['data'];
                }
                if(strpos($options['url'],"?")){
                    $options['url'].="&".$queryStr;
                }else {
                    $options['url'] .= "?" . $queryStr;
                }
            }
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $options['method']);
        curl_setopt($ch, CURLOPT_URL, $options['url']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if (isset($options['headers'])) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
        }

        if (isset($options['timeout'])) {
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $options['timeout']);
        }

        $result = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $body = json_decode($result, TRUE);
        if ($body === NULL) {
            $body = $result;
        }

        curl_close($ch);

        return ['status'=>$status,'body'=>$body];

    }
}

