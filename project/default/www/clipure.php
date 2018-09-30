<?php

/*
 *
 * 项目名：乐房通数据中心
 *
 * */

//PHP7.0版本,纯净模式，不执行路由功能

define("ISCLIPURE",true);//cli或者回调模式

if(php_sapi_name()=="cli"){
    define("ISCLI",true);
    $entrypath="index.php";
}else{
    $entrypath=$_SERVER['DOCUMENT_ROOT']."/index.php";
}

require $entrypath;
