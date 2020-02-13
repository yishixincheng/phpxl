<?php

namespace xl\classs;
use xl\base\XlClassBase;

class HttpcurlClass extends XlClassBase {

    private $_post;
    private $_header;
    private $_block=true;
    private $_data='';
    private $_curl=null;
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';
    private $_opensavecookie=true;

    public function __construct() {

        if (!extension_loaded('curl')) {
            throw new \ErrorException('Curl is not support');
        }

        $this->reset();
    }
    public function reset(){

        $this->close();
        $this->_method = 'GET';
        $this->_cookies = [];
        $this->_post = '';
        $this->_header = '';
        $this->_block=true;
        $this->_data='';
        $this->_curl = curl_init();
        $this->setUserAgent(static::USER_AGENT);
        $this->setOpt(CURLINFO_HEADER_OUT, true);   //启用时追踪句柄的请求字符串
        $this->setOpt(CURLOPT_HEADER, true);         //头文件信息作为数据流输出
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);//将curl_exec()获取的信息以文件流的形式返回，而不是直接输出
        $this->setOPt(CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_0);
        $this->setOpt(CURLOPT_HTTPHEADER,["Expect: "]);
        $this->setOpt(CURLOPT_IPRESOLVE,CURL_IPRESOLVE_V4);
        $this->setOpt(CURLOPT_DNS_USE_GLOBAL_CACHE,true);

    }

    public function setProperty($key,$val){

        $this->{"_".$key}=$val;
    }

    private function _setSaveToCookieFile(){

        if($this->_opensavecookie){
            $cookiefile=CACHE_PATH.'cookie.txt';
            mkdirm($cookiefile); //创建目录
            $this->setOpt( CURLOPT_COOKIEFILE,$cookiefile);
            $this->setOpt( CURLOPT_COOKIEJAR,$cookiefile);
        }

    }

    public function setUserAgent($agent){

        $this->setOpt(CURLOPT_USERAGENT,$agent);

    }
    public function getCurlObj(){

        return $this->_curl;
    }

    public function setOpt($option,$value){
        return curl_setopt($this->_curl,$option,$value);
    }
    public function setPostData($data=[]){
        //装载数据
        if(is_array($data)||is_object($data)){
            $data=http_build_query($data);
            $data = preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $data);
        }
        return $data;
    }
    public function setReferer($referer=''){
        $this->setOpt(CURLOPT_REFERER,$referer);
        return $this;
    }
    public function setLimit($limit=0){

        $this->setOpt(CURLOPT_LOW_SPEED_LIMIT,$limit);
        return $this;
    }
    public function setTimeOut($timeout=30){

        $this->setOpt(CURLOPT_TIMEOUT,$timeout);
        return $this;
    }
    public function setBlock($block=true){
        //noting done
        $this->_block=$block;
        return $this;
    }
    public function setCookie($key,$value){

        $this->_cookies[$key]=$value;
        $this->setOpt(CURLOPT_COOKIE,http_build_query($this->_cookies,'','; '));
    }
    public function getCookie(){

        if(preg_match_all('/Set-Cookie:stest=(.*)/i', $this->_response_headers, $results)){
            return $results;
        }

        return null;

    }
    public function getHeader(){

        return $this->_response_headers;
    }
    public function getRequestHeader(){
        return $this->_request_headers;
    }
    public function getData(){

        return $this->response;
    }
    public function post($url,$data=[]){
        $this->preparePost($url,$data);
        $this->request();
    }

    public function preparePost($url,$data=[]){
        $this->setOpt(CURLOPT_URL,$url);
        $data=$this->setPostData($data);
        $this->setOpt(CURLOPT_POST,true);
        $this->setOpt(CURLOPT_POSTFIELDS,$data);
    }

    public function get($url,$data=[]){

        if($data){
            $datastr=http_build_query($data);
            if(preg_match("/\?[^=]+=/",$url)){
                $url.='&'.$datastr;
            }else{
                $url.='?'.$datastr;
            }
        }
        $this->setOpt(CURLOPT_URL,$url);
        $this->setOpt(CURLOPT_HTTPGET,true);
        $this->request();
    }

    public function upload($url,$data=[],$files=[]){
        $this->prepareUpload($url,$data,$files);
        $this->request();
    }
    public function prepareUpload($url,$data=[],$files=[]){
        $this->setOpt(CURLOPT_URL,$url);
        foreach ($files as $key=>$value){
            if(strpos($key,'@')===0){
                continue;
            }else{
                unset($files[$key]);
                if(class_exists('\CURLFile')){
                    $files[$key]=new \CURLFile(realpath($_FILES[$value]['tmp_name'])); // >=5.5
                }else{
                    $files[$key]='@'.$value;
                }
            }
        }
        $data=array_merge($data,$files);
        $this->setOpt(CURLOPT_POST,true);
        $this->setOpt(CURLOPT_POSTFIELDS,$data);

    }

    protected function request(){

        //执行请求

        $this->_setSaveToCookieFile();

        $this->response=curl_exec($this->_curl);
        $this->_errno=curl_errno($this->_curl);
        $this->_errstr=curl_error($this->_curl);
        $this->_curl_error = !($this->_errno === 0);
        $this->_http_status_code=curl_getinfo($this->_curl,CURLINFO_HTTP_CODE);
        $this->_http_error = in_array(floor($this->_http_status_code / 100), array(4, 5));
        $this->_error = $this->_curl_error || $this->_http_error;
        $this->_error_code = $this->_error ? ($this->_curl_error ? $this->_errno : $this->_http_status_code) : 0;

        $this->_request_headers=preg_split('/\r\n/', curl_getinfo($this->_curl, CURLINFO_HEADER_OUT), null, PREG_SPLIT_NO_EMPTY);
        $this->_response_headers='';

        if (!(strpos($this->response, "\r\n\r\n") === false)) {

            list($response_header, $this->response) = explode("\r\n\r\n", $this->response, 2);
            while (strtolower(trim($response_header)) === 'http/1.1 100 continue') {
                list($response_header, $this->response) = explode("\r\n\r\n", $this->response, 2);
            }
            $this->_response_headers = preg_split('/\r\n/', $response_header, null, PREG_SPLIT_NO_EMPTY);
        }

        $this->_http_error_message = $this->_error ? (isset($this->_response_headers['0']) ? $this->_response_headers['0'] : '') : '';
        $this->_error_message = $this->_curl_error ? $this->_errno : $this->_http_error_message;

        return $this->_error_code;

    }
    public function getCurlInfo(){

        $jumpurl=curl_getinfo($this->_curl, CURLINFO_EFFECTIVE_URL);
        $jumpurl=urldecode($jumpurl);
        return $jumpurl;

    }
    public function isOK(){

        if($this->_errno||$this->_curl_error){
            return false;
        }
        return true;

    }
    public function getError(){
        return $this->_error_code;
    }
    public function getErrmsg(){
        return $this->_error_message;
    }
    public function close(){

        if(is_resource($this->_curl)){
            curl_close($this->_curl);
        }
    }
    public function __destruct()
    {
        $this->close();
    }

}