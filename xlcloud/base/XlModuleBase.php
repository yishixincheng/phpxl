<?php

namespace xl\base;

class XlModuleBase extends XlMvcBase{

    private $__attachStack=[];

    public function __construct(){
        parent::__construct();
    }
    public function setAttach($k,$v=''){

        $this->__attachStack[$k]=$v;

        return $v;
    }
    public function getAttach($k=''){

        if(empty($k)){
            return $this->__attachStack;
        }

        return $this->__attachStack[$k];
    }
    public function resetAttach(){
        $this->__attachStack=[];
    }

    /**
     * @param null $path 模板路径
     * @param null $cachetime 静态缓存时间,单位为秒
     */
    public function Display($path=null,$cachetime=null){


        if($path===null){
            $path=$this->_Genv['path'];
        }
        if(is_array($path)){
            $path=implode('/',$path);
        }else if(is_string($path)&&strpos($path,'@')===0){
            $Gv_path=$this->_Genv['path'];
            $last=count($Gv_path)-1;
            $last=$last>0?$last:0;
            $Gv_path[$last]=substr($path,1);
            $path=implode('/',$Gv_path);
        }
        if(empty($path)){
            $path='index';
        }
        $path=ltrim(trim($path),'/');
        //解析前端配置文件
        if($cachetime!=null&&is_int($cachetime)){
            $opfile=sysclass("opfile");
            if($this->_Isplugin){
                $cachepath=CACHE_PATH.'plugin'.D_S.$this->_Ns.D_S;
            }else{
                $cachepath=CACHE_PATH;
            }
            $htmlpath=$cachepath.'htmls'.D_S.$path.".html";
            $opfile->setParam($htmlpath,false);
            if(file_exists($htmlpath)){
                if(SYS_TIME-filemtime($htmlpath)<$cachetime){
                    echo $opfile->Read(true);
                    return null;
                }
            }
        }
        $configpath=TEMPLATE_PATH.'conf.json';
        $patcharr=sysclass("json")->read($configpath,false);
        if($this->_Isplugin){
            $configpath=TEMPLATE_PATH.'plugin'.D_S.$this->_Ns.D_S."conf.json";
            $pluginpatcharr=sysclass("json")->read($configpath,false);
            if($pluginpatcharr!=null&&is_array($pluginpatcharr)){
                if($patcharr!=null&&is_array($patcharr)){
                    $patcharr=array_merge($patcharr,$pluginpatcharr); //合并配置项，插件里的配置优先
                }
            }
            if(strpos($path,$this->_Ns.D_S)===0){
                $path=substr($path,strlen($this->_Ns.D_S));
            }
            $path="plugin".D_S.$this->_Ns.D_S.$path; //重置路径
        }
        if($patcharr!=null&&is_array($patcharr)){

            foreach ($patcharr as $k=>$v){

                if(preg_match("/^@(.+?)(\/.+)$/",$v,$mch)){
                    $hosturl=sysclass("globalconf")->getHostUrlByHostName($mch[1]);
                    $v=$hosturl.$mch[2];
                }

                $this->setAttach("__qd_conf__".$k,$v);
            }
        }

        $attach=$this->getAttach();
        extract($attach);

        if(isset($htmlpath)&&isset($opfile)){

            ob_start(); //开启缓存区

            include tpl($path);
            $opfile->Write(ob_get_contents());

            ob_end_flush();

        }else{
            include tpl($path);
        }
    }

    /**
     * 注入参数
     *
     */
    public $_Get;
    public $_Post;
    public $_Files;
    public $_Cookie;
    public $_Session;
    public $_Genv;

}