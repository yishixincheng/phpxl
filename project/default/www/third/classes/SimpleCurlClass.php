<?php
class SimpleCurlClass{

    private $_block=true;
    private $_post;
    private $_header;
    private $_cookies=[];
    private $_response_cookie=[];
    private $_method=null;
    private $_curl=null;
    private $_error;
    private $response;
    private $_response_headers;
    private $_request_headers;
    private $_response_body;
    const USER_AGENT='Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';

    /**
     * curl实例化
     * HttpCurlClass constructor.
     * @throws \ErrorException
     */
    public function __construct(){
        if(!extension_loaded('curl')){
            throw new \ErrorException('Curl is not support');
        }
        $this->reset();
    }

    /**
     * curl请求初始化
     */
    public function reset(){
        $this->close();
        $this->_method='GET';
        $this->_cookies=[];
        $this->_post = '';
        $this->_header = '';
        $this->_block=true;
        $this->_data='';
        $this->_curl=curl_init();
        $this->setUserAgent(static::USER_AGENT);
        $this->setOpt(CURLINFO_HEADER_OUT, true);       // 启用时追踪句柄的请求字符串
        $this->setOpt(CURLOPT_HEADER, true);            // 头文件信息作为数据流输出
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);    // 将curl_exec()获取的信息以文件流的形式返回，而不是直接输出
        $this->setOpt(CURLOPT_HTTPHEADER, ['Expect:']);  // http/1.1 100 continue
        $this->setOpt(CURLOPT_FOLLOWLOCATION, true);
        $this->setOpt(CURLOPT_AUTOREFERER, true);
    }

    /**
     * 设置浏览器代理人
     * @param string $agent 设置浏览器代理人
     */
    public function setUserAgent($agent){
        $this->setOpt(CURLOPT_USERAGENT, $agent);
    }

    /**
     * 取得当前curl对象
     */
    public function getCurlObj(){
        return $this->_curl;
    }

    /**
     * 设置curl option选项
     * @param string $option curl选项
     * @param mixed $value 选项值
     * @return bool
     */
    public function setOpt($option, $value){
        return curl_setopt($this->_curl, $option, $value);
    }

    /**
     * 请求referer头、防盗链
     * @param string $referer 参照地址
     * @return $this
     */
    public function setReferer($referer=''){
        $this->setOpt(CURLOPT_REFERER, $referer);
        return $this;
    }

    /**
     * 设置最小传输速度、小于此速度时会取消传输
     * @param int $limit b/s
     * @return $this
     */
    public function setLimit($limit=0){
        $this->setOpt(CURLOPT_LOW_SPEED_LIMIT, $limit);
        return $this;
    }

    /**
     * 设置请求超时取消
     * @param int $timeout s
     * @return $this
     */
    public function setTimeOut($timeout=30){
        $this->setOpt(CURLOPT_TIMEOUT, $timeout);
        return $this;
    }

    /**
     * 设置请求头里的cookie
     * @param string $key cookie键值
     * @param mixed $value cookie值
     */
    public function setRequestCookie($key, $value){
        $this->_cookies[$key]=$value;
        $this->setOpt(CURLOPT_COOKIE, http_build_query($this->_cookies, '', '; ', PHP_QUERY_RFC3986));
    }

    /**
     * 设置请求头里的cookie、cookie直接是键值对数组
     * @param array $cookiearr $cookiearr键值对数组
     */
    public function setRequestCookies($cookiearr){
        $newcookiearr=[];
        foreach($cookiearr as $k=>$v){
            if(!(empty($k) || (empty($v)))){
                $newcookiearr[trim($k)]=trim($v);
            }
        }
        $this->_cookies=$newcookiearr;
        mydebug(http_build_query($this->_cookies, '', '; ', PHP_QUERY_RFC3986));
        $this->setOpt(CURLOPT_COOKIE, http_build_query($this->_cookies, '', '; ', PHP_QUERY_RFC3986));
    }

    /**
     * 获取请求头
     */
    public function getRequestHeader(){
        return $this->_request_headers;
    }

    /**
     * 获取响应头里的cookie（考虑循环分号炸开、再等号炸开、只要第一个值）
     * @param bool $keyvalue 返回cookie键值对
     * @return mixed
     */
    public function getResponseCookie($keyvalue=false){
        if($keyvalue){
            $cookie=[];
            foreach($this->_response_cookie as $k=>$v){
                $cookie_value=explode(';', $v);
                $cookie_arr=explode('=', $cookie_value[0]);
                $cookie[$cookie_arr[0]]=$cookie_arr[1];
            }
            return $cookie;
        }
        return $this->_response_cookie;
    }

    /**
     * 获取响应头
     */
    public function getResponseHeader(){
        return $this->_response_headers;
    }

    /**
     * 获取响应主体数据
     */
    public function getResponseBody(){
        return $this->_response_body;
    }

    /**
     * 获取所有响应内容 返回数组['header', 'body', 'jumpurl']
     * @return array
     */
    public function getResponseData(){
        $lasturl=$this->getLastUrl();
        return [
            'header'=>$this->_response_headers,
            'body'=>$this->_response_body,
            'jumpurl'=>$lasturl
        ];
    }

    /**
     * 装载post请求数据、并进行一些处理（模拟复选框处理）
     * @param array $data 请求数据
     * @return array|mixed|string
     */
    public function setPostData($data=[]){
        if(is_array($data) || is_object($data)){
            $data=http_build_query($data);
            // 模拟复选框
            $data=preg_replace('/%5B[0-9]+%5D/simU', '%5B%5D', $data);
        }
        return $data;
    }

    /**
     * post发送请求
     * @param string $url 请求地址
     * @param array $data
     */
    public function post($url, $data=[]){
        $this->preparePost($url, $data);
        $this->request();
    }

    /**
     * post请求预处理
     * @param string $url 请求地址
     * @param array $data
     */
    public function preparePost($url, $data=[]){
        $this->setOpt(CURLOPT_URL, $url);
        $data=$this->setPostData($data);
        $this->setOpt(CURLOPT_POST, true);
        $this->setOpt(CURLOPT_POSTFIELDS, $data);
    }

    /**
     * get发送请求
     * @param string $url 请求地址
     * @param array $data
     */
    public function get($url, $data=[]){
        if($data){
            $datastr=http_build_query($data);
            if(preg_match("/\?[^=]+=", $datastr)){
                $url.='&'.$datastr;
            }else{
                $url.='?'.$datastr;
            }
        }
        $this->setOpt(CURLOPT_URL, $url);
        $this->setOpt(CURLOPT_HTTPGET, true);
        $this->request();
    }

    /**
     * 上传文件式请求
     * @param string $url 请求地址
     * @param array $data post数据
     * @param array $files 请求文件
     */
    public function upload($url, $data=[], $files=[]){
        $this->prepareUpload($url, $data, $files);
        $this->request();
    }

    /**
     * 准备上传表单数据（包含文件）
     * @param string $url 请求地址
     * @param array $data post数据
     * @param array $files 上传文件
     * @return array|mixed|string
     */
    public function prepareUpload($url, $data=[], $files=[]){
        $this->setOpt(CURLOPT_URL, $url);
        foreach($files as $key=>$value){
            if(class_exists('\CURLFile')){
                curl_setopt($this->_curl, CURLOPT_SAFE_UPLOAD, true);
                $files[$key]=new \CURLFile(realpath($value)); // >=5.5
            }else{
                if(defined('CURLOPT_SAFE_UPLOAD')){
                    curl_setopt($this->_curl, CURLOPT_SAFE_UPLOAD, false);
                }
                if(strpos($key, '@')===0){
                    continue;
                }else{
                    $files[$key]='@'.realpath($value); // <=5.5
                }
            }
        }
        $data=array_merge($data, $files);
        $this->setOpt(CURLOPT_POST, true);
        $this->setOpt(CURLOPT_POSTFIELDS, $data);
    }

    /**
     * 执行请求
     */
    protected function request(){
        $this->response=curl_exec($this->_curl);
        $errno=curl_errno($this->_curl);
        $errstr=curl_error($this->_curl);
        $curl_error=!($errno===0);
        $http_status_code=curl_getinfo($this->_curl, CURLINFO_HTTP_CODE);
        $http_error=in_array(floor($http_status_code/100), [4, 5]);
        $haserror=$curl_error || $http_error;
        $error_code=$haserror?($curl_error?$errno:$http_status_code):0;
        $this->_request_headers=preg_split('/\r\n/', curl_getinfo($this->_curl, CURLINFO_HEADER_OUT), null, PREG_SPLIT_NO_EMPTY);
        $this->_response_headers='';
        if(!(strpos($this->response, "\r\n\r\n")===false)){
            list($response_header, $this->_response_body)=explode("\r\n\r\n", $this->response, 2);
            while(strtolower(trim($response_header))==='http/1.1 100 continue'){
                list($response_header, $this->response)=explode("\r\n\r\n", $this->response, 2);
            }
            list($response_header, $this->_response_body)=explode("\r\n\r\n", $this->response, 2); // 保证只获取响应主体内容、不包含头信息
            if(preg_match_all('/Set-Cookie:([^\r\n]*)/i', $response_header, $response_cookies)){
                $this->_response_cookie=$response_cookies[1];
            }
            $this->_response_headers=preg_split('/\r\n/', $response_header, null, PREG_SPLIT_NO_EMPTY);
        }
        $http_error_message=$haserror?(isset($this->_response_headers['0'])?$this->_response_headers['0']:''):'';
        $error_message=$curl_error?$errstr:$http_error_message;
        $this->_error=['errcode'=>$error_code, 'errmsg'=>$error_message];
        return $this->_error;
    }

    /**
     * 获取最后响应地址
     */
    public function getLastUrl(){
        $jumpurl=curl_getinfo($this->_curl, CURLINFO_EFFECTIVE_URL);
        $jumpurl=urldecode($jumpurl);
        return $jumpurl;
    }

    /**
     * 判断当前curl请求是否成功
     */
    public function isOK(){
        if($this->_error && $this->_error['errcode']){
            return false;
        }
        return true;
    }

    /**
     * 获取当前curl错误原因 ['errcode'=>$error_code, 'errmsg'=>$error_message]
     */
    public function getError(){
        return $this->_error;
    }

    /**
     * 关闭当前curl
     */
    public function close(){
        if(is_resource($this->_curl)){
            curl_close($this->_curl);
        }
    }

    /**
     * 析构函数、关闭当前资源
     */
    public function __destruct(){
        $this->close();
    }
}