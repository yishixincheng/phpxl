<?php

/*
 *
 * 项目名：乐房通软件
 *
 * */

//PHP7.0版本

require __DIR__ . '/../../../../xlcloud/XlLead.php';
\xl\XlLead::nude(['namespace'=>"lftsoft"]);

$projectname=$_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'index.php';
$projectarr=explode('/',dirname(dirname($projectname)));
$projectname=array_pop($projectarr);

$cls = sysclass("cachefactory", 0);
$cache = $cls::priority(['apc','xcache','eaccelerator','memcache','file']);

$cachekey="@xl_router_".$projectname;

$time=$cache->getcachetime($cachekey);

if($time){
    echo "上次缓存时间为：".date("Y-m-d H:i:s",$time).PHP_EOL;
}


$cache->delete("@xl_router_".$projectname);


echo "清除路由缓存成功！";