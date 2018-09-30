<?php

namespace xl\classs;
use xl\base\XlClassBase;
use xl\XlInjector;

class TemplateClass extends XlClassBase{

    /**
     * 编译模板
     *
     * @param $module	模块名称
     * @param $template	模板文件名
     * @return unknown
     */
    private $templatefile='';
    private $compilefile='';
    private $templatestring='';


    public function load($template) {

        $this->templatefile='';  //模板文件
        $this->compilefile='';   //编译后的文件

        //移除后缀名
        if(strpos($template,'.')!==false){
            $template=preg_replace("/^(.+)(\..+)$/","$1",$template);
        }
        $template=ltrim($template,"/");
        $this->templatefile=TEMPLATE_PATH.$template.'.tpl';
        $this->compilefile=COMPILE_PATH.$template.'.php';

        if(!is_file($this->templatefile)){
            die("模板文件'".$this->templatefile."'不存在，请检查目录");
        }
        if(!$cache=XlInjector::$cache){
            $cls = sysclass("cachefactory", 0);
            $cache = $cls::priority(['apc','xcache','eaccelerator','memcache','file']);
        }

        $cachekey="@xl_tpl_".$template."_mtime";
        $lasttplemtime=intval($cache->get($cachekey)?:0);
        $nowtplmtime=filemtime($this->templatefile);

        if(is_file($this->compilefile)){
            if($nowtplmtime==$lasttplemtime){
                return $this->compilefile;
            }
        }
        $this->templatestring=file_get_contents($this->templatefile);
        $this->compile();
        $this->write();

        //记录缓存时间
        $cache->set($cachekey,$nowtplmtime);

        return $this->compilefile;

    }
    public function write()
    {
        $save_dir=dirname($this->compilefile);

        if(!is_dir($save_dir))$this->MakeDir($save_dir, 0777);

        if(!file_put_contents($this->compilefile,$this->templatestring)){
            die('模板无法写入,请检查目录是否有可写');
        }
        return true;
    }
    function MakeDir($dir_name, $mode = 0777)
    {
        if(mkdir($dir_name,$mode,true)){
            $dir_name = str_replace("\\", "/", $dir_name);
            $dir_name = preg_replace("#(/"."/+)#", "/", $dir_name);
            if (is_dir($dir_name) !== false)return true;
            $dir_name = explode("/", $dir_name);
            $dirs='';
            foreach($dir_name as $dir)
            {
                if (trim($dir) != '')
                {
                    $dirs .= $dir . "/";
                    if (is_dir($dirs)==false && @mkdir($dirs, $mode) === false)
                    {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * 解析模板
     *
     * @param $str	模板内容
     * @return ture
     */
    public function compile($str='') {
        if(empty($str)){$str=$this->templatestring;}

        $str = preg_replace("/(\<form.*? method=[\"\']?post[\"\']?)([^\>]*\>)/i","\\1 \\2\n<input type=\"hidden\" name=\"FORMHASH\" value='{FORMHASH}'/>",$str);
        $str = preg_replace ( "/\{template\s+(.+)\}/", "<?php include template(\\1); ?>", $str );
        $str = preg_replace ( "/\{tpl\s+(.+)\}/", "<?php include tpl(\\1); ?>", $str );
        $str = preg_replace ( "/\{include\s+(.+)\}/", "<?php include \\1; ?>", $str );
        $str = preg_replace ( "/\{php\s+(.+?)\}/", "<?php \\1?>", $str );

        //echo $str;
        $str = preg_replace ( "/\{if\s+(.+?)\}/", "<?php if(\\1) { ?>", $str );
        $str = preg_replace ( "/\{else\}/", "<?php } else { ?>", $str );
        $str = preg_replace ( "/\{elseif\s+(.+?)\}/", "<?php } elseif (\\1) { ?>", $str );
        $str = preg_replace ( "/\{\/if\}/", "<?php } ?>", $str );
        //for 循环
        $str = preg_replace("/\{for\s+(.+?)\}/","<?php for(\\1) { ?>",$str);
        $str = preg_replace("/\{\/for\}/","<?php } ?>",$str);
        //++ --
        $str = preg_replace("/\{\+\+(.+?)\}/","<?php ++\\1; ?>",$str);
        $str = preg_replace("/\{\-\-(.+?)\}/","<?php ++\\1; ?>",$str);
        $str = preg_replace("/\{(.+?)\+\+\}/","<?php \\1++; ?>",$str);
        $str = preg_replace("/\{(.+?)\-\-\}/","<?php \\1--; ?>",$str);
        $str = preg_replace ( "/\{loop\s+(\S+)\s+(\S+)\}/", "<?php \$n=1;if(is_array(\\1)) foreach(\\1 AS \\2) { ?>", $str );
        $str = preg_replace ( "/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/", "<?php \$n=1; if(is_array(\\1)) foreach(\\1 AS \\2 => \\3) { ?>", $str );
        $str = preg_replace ( "/\{\/loop\}/", "<?php \$n++;}unset(\$n); ?>", $str );
        $str = preg_replace ( "/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/", "<?php echo \\1;?>", $str );
        $str = preg_replace ( "/\{\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/", "<?php echo \\1;?>", $str );
        $str = preg_replace ( "/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/", "<?php echo \\1;?>", $str );
        $str = preg_replace_callback("/\{(\\$[a-zA-Z0-9_\[\]\'\"\$\x7f-\xff]+)\}/s", function($matchs){
            return $this->addquote('<?php echo '.$matchs[1].';?>');
        },$str);
        $str = preg_replace ( "/\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\}/s", "<?php echo \\1;?>", $str );
        $str = preg_replace ("/\{\\:([a-zA-Z0-9_\[\]\'\"\$\x7f-\xff]+)\}/s", "<?php echo \$__qd_conf__\\1;?>", $str );

        $str = "<?php defined('IS_XLIFREAM') or exit('No permission resources.'); ?>" . $str;
        $this->templatestring=$str;

        return null;
    }

    /**
     * 转义 // 为 /
     *
     * @param $var	转义的字符
     * @return 转义后的字符
     */
    public function addquote($var) {
        return str_replace ( "\\\"", "\"", preg_replace ( "/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var ) );
    }


    /**
     * 转换数据为HTML代码
     * @param array $data 数组
     */
    private static function arr_to_html($data) {
        if (is_array($data)) {
            $str = 'array(';
            foreach ($data as $key=>$val) {
                if (is_array($val)) {
                    $str .= "'$key'=>".self::arr_to_html($val).",";
                } else {
                    if (strpos($val, '$')===0) {
                        $str .= "'$key'=>$val,";
                    } else {
                        $str .= "'$key'=>'".new_addslashes($val)."',";
                    }
                }
            }
            return $str.')';
        }
        return false;
    }
}