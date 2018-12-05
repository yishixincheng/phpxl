<?php

namespace xl\util;

/**
 * Class XlURequest
 * @package xl\util
 * 兼容老系统
 */

class XlURequest
{

    function __construct(){

        $data = $GLOBALS;



        $data['header'] = $this->getAllHeaders();
        if(!get_magic_quotes_gpc()) {
            $_POST = new_addslashes($_POST,true);
            $_GET = new_addslashes($_GET);
            $_REQUEST = new_addslashes($_REQUEST,true);
            $_COOKIE = new_addslashes($_COOKIE);
            $data['_POST']=$_POST;
            $data['_REQUEST']=$_GET;
            $data['_COOKIE']=$_COOKIE;
        }

        $full_path = $data['_SERVER']['REQUEST_URI'];
        list($full_path,) = explode('?', $full_path);

        $paths = explode('/', $full_path);
        $paths = array_filter($paths,function ($i){return $i !== '';});
        $paths = array_slice($paths, 0);
        $data['path'] = $paths;

        $this->data=$data;

    }
    public function getData(){

        return $this->data;

    }
    /**
     * 获取http请求的所有header信息
     * @return array
     */
    function getAllHeaders() {
        $headers = array();
        foreach ($_SERVER as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$name] = $value;
            } else if ($name == "CONTENT_TYPE") {
                $headers["Content-Type"] = $value;
            } else if ($name == "CONTENT_LENGTH") {
                $headers["Content-Length"] = $value;
            }
        }
        return $headers;
    }

}
