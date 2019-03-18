<?php

namespace sysmanage\module;

set_time_limit(0);

/**
 * Class UpgradeModule
 * @package sysmanage\module
 * @path("/sysmanage/upgrade")
 */
class UpgradeModule extends Base{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @path({"checkversion","POST"})
     */
    public function checkVersion($postParam){

        //检测软件当前版本
        $conf=config("upgrade");
        $softurl=$conf['softurl'];  //软件地址
        if(empty($softurl)){
            AjaxPrint($this->ErrorInf("软件中心地址没有配置"));
            return;
        }
        $software=$conf['software']; //软件类型
        try{
            $softwaredata=iapi("upgrade.GetVersionData",null);
        }catch (\Exception $e){
            $softwaredata=['software_version'=>$conf['software_version']];
        }
        //发送rpc请求校验
        $rt=rpc("softupgrade.GetVersionRequest",['software'=>$software,'data'=>$softwaredata],
            ["rsp_urls"=>$softurl,
             "appkey"=>"shengguo",
             "appsecret"=>"xinxikeji"]);

        $rt=getApiData($rt);

        if($rt['status']=="fail"){
            AjaxPrint($this->ErrorInf($rt['msg']));
            return;
        }
        if($rt['software_version']==$softwaredata['software_version']){
            AjaxPrint($this->SuccInf("已经是最新版本，无须更新",['tiptype'=>0]));
            return;
        }
        $versioninfo=$rt['software_name'].$rt['software_version'];

        config("upgrade/software_salt",$rt['software_salt']);
        config("upgrade/software_size",$rt['software_size']);

        config("upgrade/new_software_version",$rt['software_version']);
        config("upgrade/new_software_name",$rt['software_name']);

        config("upgrade/software_downloadurl",$rt['software_downloadurl'],true);

        AjaxPrint($this->SuccInf("有新的版本（".$rt['software_version']."）,您需要更新",
                               ['tiptype'=>1,'software_version'=>$rt['software_version'],
                                'softsize'=>$rt['software_size'],
                                'versioninfo'=>$versioninfo]));

    }
    /**
     * @path({"downloadsoft","POST"})
     */
    public function downLoadSoft($postParam){

        $conf=config("upgrade");
        $softurl=$conf['softurl'];  //软件地址
        if(empty($softurl)){
            AjaxPrint($this->ErrorInf("软件中心地址没有配置"));
            return;
        }
        $auth='';
        $software_downloadurl=$conf['software_downloadurl'];   //下载地址
        $software_size=$conf['software_size'];                 //软件大小
        $software_salt=$conf['software_salt'];

        try{
            $softwaredata=iapi("upgrade.GetVersionData",null);
            $appkey=$softwaredata['appkey'];
            $appsecret=$softwaredata['appsecret'];
            if($appkey&&$appsecret){
                $auth=sys_auth($appkey."|".$appsecret,"ENCODE",$software_salt);
            }

        }catch (\Exception $e){
        }

        if(empty($software_downloadurl)||empty($software_size)){
            AjaxPrint($this->ErrorInf("未找到软件压缩包！"));
            return;
        }
        if($auth){
            $software_downloadurl.="&auth=".$auth;
        }
        $fseek=config("upgrade/fseek")?:0;
        $fsize=102400;

        $result=$this->loadSoftFileFromRemote($software_downloadurl,$fseek,$fsize);

        if($result===-1){
            config("upgrade/fseek",0,true);
            AjaxPrint(['isover'=>1,'fseek'=>$fseek]); //下载结束
            return;
        }else if($result===1){
            config("upgrade/fseek",$fseek+$fsize,true);
            AjaxPrint(['isover'=>0,'fseek'=>$fseek]); //未下载结束
        }

    }

    /**
     * 获取文件
     */
    private function loadSoftFileFromRemote($url,$seek=0,$size=1024){

        $save_dir=dirname(XL_ROOT).D_S.'upgrade'.D_S;
        $filename='soft.zip';
        //创建保存目录
        if (!file_exists($save_dir) && !mkdir($save_dir, 0777, true)) {
            return false;
        }
        //获取远程文件所采用的方法
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        $headers=[];
        $headers[]="Range:".$seek."-".($seek+$size);

        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        $content = curl_exec($ch);
        curl_close($ch);

        if(empty($content)){
            return -1;
        }
        if(strncmp($content,"{",1)===0){
            echo $content;
            return false;
        }
        //文件大小

        if($seek==0){
            $fp2 = @fopen($save_dir . $filename, 'w+');
        }else{
            $fp2 = @fopen($save_dir . $filename, 'ab+');
        }

        fwrite($fp2, $content);
        fclose($fp2);
        unset($content, $url);

        return 1;

    }

    /**
     * @path({"installsoft","POST"})
     * 安装软件
     */
    public function installSoft($postParam){

        $step=$postParam['step'];

        if($step==2){

            $fromdir=dirname(XL_ROOT) . D_S . 'upgrade' . D_S . 'soft'.D_S; //拷贝源目录
            $todir=dirname(XL_ROOT).D_S;//目标目录
            $fromdir1=$fromdir;
            $this->recodeInstallLogger("",true);
            $this->copydir($fromdir,$fromdir1,$todir);

            GDelFile($fromdir1); //移除

            //安装完毕设置新版本
            try{

                config("upgrade/software_version",config("upgrade/new_software_version"));
                config("upgrade/software_name",config("upgrade/new_software_name"),true);

                iapi("upgrade.SetVersionData",['software_version'=>config("upgrade/new_software_version"),
                                                                'software_name'=>config("upgrade/new_software_name")]);

            }catch(\Exception $e){

            }

            AjaxPrint(['isover' =>1, 'msg' => '安装完毕']); //未下载结束

        }else {

            //解压
            $soft_path = dirname(XL_ROOT) . D_S . 'upgrade' . D_S . 'soft.zip';
            if (!is_file($soft_path)) {
                AjaxPrint($this->ErrorInf("抱歉，安装包不存在，请刷新页面从新下载！"));
                return;
            }
            $zip = new \ZipArchive();
            if ($zip->open($soft_path)) {
                $zip->extractTo(dirname(XL_ROOT) . D_S . 'upgrade' . D_S . 'soft');//假设解压缩到在当前路径下images文件夹的子文件夹php
                $zip->close();//关闭处理的zip文件
                @unlink($soft_path); //删除压缩包
                AjaxPrint(['isover' => 1, 'msg' => '解压完毕']); //未下载结束
                return;
            }
            AjaxPrint($this->ErrorInf("解压失败，请检测系统是否支持zip解压缩！"));
        }

    }

    private function copydir($mdl_dir,$source_dir,$target_dir){

        $dir = null;
        if(is_dir($mdl_dir)){
            $dir = @dir($mdl_dir);
            $geteach = function ()use($dir){
                $name = $dir->read();
                if(!$name){
                    return $name;
                }
                return $name;
            };

        }else{
            if(is_file($mdl_dir)){
                $files = [$mdl_dir];
                $mdl_dir = '';
            }else{
                return;
            }
            $geteach = function ()use(&$files){
                $item =  fun_adm_each($files);
                if($item){
                    return $item[1];
                }else{
                    return false;
                }
            };
        }
        while( !!($entry = $geteach()) ){

            if($entry=="."||$entry==".."){
                continue;
            }
            $path = $mdl_dir. str_replace('\\', D_S, $entry);
            if(is_file($path)){

                $xd_path=substr($path,strlen($source_dir));
                $target_path=$target_dir.$xd_path;

                @mkdir(dirname($target_path),0777,true);

                @copy($path,$target_path); //拷贝文件

                //写日志
                $this->recodeInstallLogger("正在复制".$xd_path."文件".PHP_EOL,false);

            }elseif(is_dir($path)){

                $this->copydir($path.D_S,$source_dir,$target_dir);

            }
        }
        if($dir !== null){
            $dir->close();
        }

    }
    private function recodeInstallLogger($content,$isinit=false){

        $logger_path = dirname(XL_ROOT) . D_S . 'upgrade' . D_S."logger.txt";
        if($isinit){
            $fp2 = @fopen($logger_path, 'w+');
        }else{
            $fp2 = @fopen($logger_path, 'a+');
        }

        @fwrite($fp2,$content);
        @fclose($fp2);

    }

    /**
     * @path({"readinstalllog","POST"})
     */
    public function readInstallLog($postParam){


        $logger_path = dirname(XL_ROOT) . D_S . 'upgrade' . D_S."logger.txt";

        $fp = @fopen($logger_path, 'r');

        $filearr=[];
        while (!feof($fp)) {
            array_unshift($filearr,fgets($fp));
        }

        @fclose($fp);

        AjaxPrint($filearr);

    }

    /**
     * @path({"downloadsoftplugin","POST"})
     */
    public function downLoadSoftPlugin($postParam){

        $plugintype=$postParam['plugintype'];
        $softversion=$postParam['softversion'];
        $currsoftversion=$postParam['currsoftversion'];
        //$version=$postParam['version'];
        $lastversion=$postParam['lastversion'];
        $downloadurl=$postParam['downloadurl'];

        if(empty($plugintype)||empty($lastversion)||empty($downloadurl)){
            AjaxPrint($this->ErrorInf("参数缺失！"));
            return;
        }
        if($softversion<$currsoftversion){
            AjaxPrint($this->ErrorInf("插件依赖软件版本".$softversion.",请先升级软件！"));
            return;
        }
        $conf=config("upgrade");
        $auth='';
        $softplugin_downloadurl=$downloadurl;   //插件下载地址
        $software_salt=$conf['software_salt'];
        try{
            $softwaredata=iapi("upgrade.GetVersionData",null);
            $appkey=$softwaredata['appkey'];
            $appsecret=$softwaredata['appsecret'];
            if($appkey&&$appsecret){
                $auth=sys_auth($appkey."|".$appsecret,"ENCODE",$software_salt);
            }
        }catch (\Exception $e){
        }
        if(empty($softplugin_downloadurl)){
            AjaxPrint($this->ErrorInf("未找到软件压缩包！"));
            return;
        }
        if($auth){
            $softplugin_downloadurl.="&auth=".$auth;
        }
        $fseek_key="plugin_".$plugintype."_fseek";
        $fseek=config("upgrade/".$fseek_key)?:0;
        $fsize=102400;

        $result=$this->loadSoftPluginFileFromRemote($softplugin_downloadurl,$plugintype,$fseek,$fsize);

        if($result===-1){
            config("upgrade/".$fseek_key,0,true);
            AjaxPrint(['isover'=>1,'fseek'=>$fseek]); //下载结束
            return;
        }else if($result===1){
            config("upgrade/".$fseek_key,$fseek+$fsize,true);
            AjaxPrint(['isover'=>0,'fseek'=>$fseek]); //未下载结束
        }


    }
    private function loadSoftPluginFileFromRemote($url,$plugintype,$seek=0,$size=1024){

        $save_dir=dirname(XL_ROOT).D_S.'upgrade'.D_S.'plugin'.D_S;
        $filename=$plugintype.'.zip';
        //创建保存目录
        if (!file_exists($save_dir) && !mkdir($save_dir, 0777, true)) {
            return false;
        }
        //获取远程文件所采用的方法
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        $headers=[];
        $headers[]="Range:".$seek."-".($seek+$size);
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
        $content = curl_exec($ch);
        curl_close($ch);

        if(empty($content)){
            return -1;
        }
        if(strncmp($content,"{",1)===0){
            echo $content;
            return false;
        }

        //文件大小
        if($seek==0){
            $fp2 = @fopen($save_dir . $filename, 'w+');
        }else{
            $fp2 = @fopen($save_dir . $filename, 'ab+');
        }

        fwrite($fp2, $content);
        fclose($fp2);
        unset($content, $url);

        return 1;

    }

    /**
     * @path({"installplugin","POST"})
     */
    public function installPlugin($postParam){

        //安装插件
        $plugintype=$postParam['plugintype'];
        $pluginname=$postParam['pluginname'];
        $version=$postParam['version'];
        if(empty($plugintype)){
            AjaxPrint($this->ErrorInf("参数缺失！"));
            return;
        }
        $soft_path=dirname(XL_ROOT).D_S.'upgrade'.D_S.'plugin'.D_S.$plugintype.'.zip';
        if (!is_file($soft_path)) {
            AjaxPrint($this->ErrorInf("抱歉，安装包不存在，请刷新页面从新下载！"));
            return;
        }
        $zip = new \ZipArchive();
        if ($zip->open($soft_path)) {
            $zip->extractTo(dirname(XL_ROOT) . D_S . 'upgrade' . D_S . 'plugin'.D_S.$plugintype.D_S);
            $zip->close();//关闭处理的zip文件
            @unlink($soft_path); //删除压缩包
        }else{
            AjaxPrint($this->ErrorInf("解压失败，请检测系统是否支持zip解压缩！"));
            return;
        }
        //copy到插件目录
        $fromdir=dirname(XL_ROOT) . D_S . 'upgrade' . D_S . 'plugin'.D_S.$plugintype.D_S; //拷贝源目录
        $todir=dirname(DOC_ROOT).D_S;//目标目录
        $fromdir1=$fromdir;

        $this->recodeInstallLogger("",true);
        $this->copydir($fromdir,$fromdir1,$todir);



        //安装完毕设置新版本
        try{

            GDelFile($fromdir1); //移除

            config("plugins/".$plugintype."/version",$version);
            config("plugins/".$plugintype."/name",$pluginname,true);

            iapi("upgrade.InstallPluginComplete",['plugintype'=>$plugintype,'version'=>$version,'pluginname'=>$pluginname]);

        }catch(\Exception $e){

        }

        AjaxPrint(['isover' =>1, 'msg' => '安装完毕']); //未下载结束

    }



}