<?php
/**
 *  global.php 公共函数库
 *
 */

$__Xl_V_G=array();
function SetG($key , $value, $group = null) {
    global $__Xl_V_G;
    $k = explode('/', $group === null ? $key : $group.'/'.$key);
    Xl_Recursion_Set_Array_Value($__Xl_V_G,$k,$value);
    return true;
}

function GetG($key, $group = null) {
    global $__Xl_V_G;
    $k = explode('/', $group === null ? $key : $group.'/'.$key);
    return Xl_Recursion_Get_Array_Value($__Xl_V_G,$k);
}

function Xl_Recursion_Set_Array_Value(&$parent,&$keys,$value,$depth=0){

    if(empty($keys)&&$depth==0){
        if(is_array($value)){
            //保证根节点永远为数组
            $parent=$value;
        }
        return null;
    }
    $depth++;
    if($depth>1024){
        //避免无限递归
        return null;
    }
    $len=count($keys);
    if($len==1){
        $parent[$keys[0]]=$value;
    }else{
        $currkey=array_shift($keys);
        if(!isset($parent[$currkey])||!is_array($parent[$currkey])){
            $parent[$currkey]=[];
        }
        Xl_Recursion_Set_Array_Value($parent[$currkey],$keys,$value);
    }
    return null;
}
function Xl_Recursion_Get_Array_Value(&$parent,&$keys){

    if(empty($keys)){
        return $parent;
    }
    $len=count($keys);
    if($len==1){
        return $parent[$keys[0]];
    }else{
        $currkey=array_shift($keys);
        if(!isset($parent[$currkey])||!is_array($parent[$currkey])){
            return null;
        }
        return Xl_Recursion_Get_Array_Value($parent[$currkey],$keys);
    }

}

function Xl_Recursion_Del_Array_Value(&$parent,&$keys){

    if(empty($keys)){
        return null;
    }

    $len=count($keys);
    if($len==1){
        unset($parent[$keys[0]]);
    }else{
        $currkey=array_shift($keys);
        if(!isset($parent[$currkey])||!is_array($parent[$currkey])){
            return null;
        }
        Xl_Recursion_Del_Array_Value($parent[$currkey],$keys);
    }

}



function GDelFile($src){
    if(file_exists($src)){
        if(@chmod($src,0777)){
            @unlink($src);
        }else{
            @unlink($src);
        }
    }
}

function GW($str){
    echo $str;
}
function GSetJs($js){

    GW('<!DOCTYPE HTML>
        <html>
	    <head>
	    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<script type="text/javascript">
		</script>
	    </head>
	    <body>
		<script type="text/javascript">
	    '.$js.'
		</script>
		</body>
		</html>');
}

function GFormatTime($date)
{
    $timenow=time();
    if(preg_match("/^\d+$/",$date)){
        $time1=$date;
    }else{
        $time1=strtotime($date);
    }
    $time=($timenow-$time1); //获得秒数

    $h=floor($time/3600); //获得小时数
    $f=floor($time/60);
    if($h>87600)
    {
         return date("Y年m月d日 H:i",$time1);
    }
    if($h>24)
    {
         return date("m月d日 H:i",$time1);
    }
    if($h>12)
    {
        return '昨天'.date(" H:i",$time1);
    }
    else if($h>=1)
    {
        return $h.'小时前';
    }
    else if($f>=1)
    {
        return $f.'分钟前';
    }
    else
    {
        return $time.'秒前';
    }

}

function strip_selected_tags(&$str,$disallowable="<script><iframe><style><link>")
{
    $disallowable=trim(str_replace(array(">","<"),array("","|"),$disallowable),'|');
    $str=str_replace(array('&lt;', '&gt;'),array('<', '>'),$str);
    $str=preg_replace("~<({$disallowable})[^>]*>(.*?<\s*\/(\\1)[^>]*>)?~is",'',$str);
    return $str;
}

function filter(&$string,$item="",$density=false,$replace=false,$statistic=null)
{
    static $filter,$filter_keyword_list,$replace_rule_list,$replace_config;
    $string=trim($string);
    if($string) {
        if(false!==strpos($string,'<')) {
            $string=strip_selected_tags($string,"<script><iframe><style><link><meta>");
            $string=remove_xss($string);
        }
        if($filter===null) {
            $filter=(array)config('filter');
        }

        if(!$filter['enable']) {
            return false;
        }

        if(!empty($filter['keywords']))
        {
            if($filter_keyword_list===null)
            {
                $filter_keyword_list=explode("|",str_replace(array("\r\n","\r","\n","\t","\\|"),"|",trim($filter['keywords'])));
            }
            foreach ($filter_keyword_list as $keyword)
            {
                if(strpos($string,$keyword)!==false)
                {
                    $keyword_len=strlen($keyword);
                    if($keyword_len>2 && $keyword_len<40)
                    {
                        $statistic['filter_type']='keyword';
                        return "含有禁止发布的内容";
                    }
                }
            }
        }
    }

    return false;
}

/**
 * 返回经addslashes处理过的字符串或数组
 * @param $string 需要处理的字符串或数组
 * @return mixed
 */
function new_addslashes($string,$isdealbool=false){
    if(!is_array($string)) {
        if($isdealbool){
            if(is_numeric($string)){return $string;}
            if(is_bool($string)){return $string;}
            if($string=="false"){return false;}
            if($string=="true"){return true;}
        }
        return addslashes($string);
    }
    foreach($string as $key => $val) $string[$key] = new_addslashes($val,$isdealbool);
    return $string;
}

function pl_stripslashes($string) {
    if(!is_array($string)) return stripslashes($string);
    foreach($string as $key => $val) $string[$key] = pl_stripslashes($val);
    return $string;
}

function pl_htmlspecialchars($string) {
    if(!is_array($string)) return htmlspecialchars($string);
    foreach($string as $key => $val) $string[$key] = pl_htmlspecialchars($val);
    return $string;
}
/**
 * 安全过滤函数
 *
 * @param $string
 * @return string
 */
function safe_replace($string) {
    $string = str_replace('%20','',$string);
    $string = str_replace('%27','',$string);
    $string = str_replace('%2527','',$string);
    $string = str_replace('*','',$string);
    $string = str_replace('"','&quot;',$string);
    $string = str_replace("'",'',$string);
    $string = str_replace('"','',$string);
    $string = str_replace(';','',$string);
    $string = str_replace('<','&lt;',$string);
    $string = str_replace('>','&gt;',$string);
    $string = str_replace("{",'',$string);
    $string = str_replace('}','',$string);
    $string = str_replace('\\','',$string);
    $string = remove_xss($string);
    return $string;
}

/**
 * xss过滤函数
 *
 * @param $string
 * @return string
 */
function remove_xss($string) {
    $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $string);

    $parm1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');

    $parm2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');

    $parm = array_merge($parm1, $parm2);

    for ($i = 0; $i < sizeof($parm); $i++) {
        $pattern = '/';
        for ($j = 0; $j < strlen($parm[$i]); $j++) {
            if ($j > 0) {
                $pattern .= '(';
                $pattern .= '(&#[x|X]0([9][a][b]);?)?';
                $pattern .= '|(&#0([9][10][13]);?)?';
                $pattern .= ')?';
            }
            $pattern .= $parm[$i][$j];
        }
        $pattern .= '/i';
        $string = preg_replace($pattern, '', $string);
    }
    return $string;
}

/**
 * 过滤ASCII码从0-28的控制字符
 * @return String
 */
function trim_unsafe_control_chars($str) {
    $rule = '/[' . chr ( 1 ) . '-' . chr ( 8 ) . chr ( 11 ) . '-' . chr ( 12 ) . chr ( 14 ) . '-' . chr ( 31 ) . ']*/';
    return str_replace ( chr ( 0 ), '', preg_replace ( $rule, '', $str ) );
}

/**
 * 格式化文本域内容
 *
 * @param $string 文本域内容
 * @return string
 */
function trim_textarea($string) {
    $string = nl2br ( str_replace ( ' ', '&nbsp;', $string ) );
    return $string;
}

/**
 * 转义 javascript 代码标记
 *
 * @param $str
 * @return mixed
 */
function trim_script($str) {
    if(is_array($str)){
        foreach ($str as $key => $val){
            $str[$key] = trim_script($val);
        }
    }else{
        $str = preg_replace ( '/\<([\/]?)script([^\>]*?)\>/si', '&lt;\\1script\\2&gt;', $str );
        $str = preg_replace ( '/\<([\/]?)iframe([^\>]*?)\>/si', '&lt;\\1iframe\\2&gt;', $str );
        $str = preg_replace ( '/\<([\/]?)frame([^\>]*?)\>/si', '&lt;\\1frame\\2&gt;', $str );
        $str = preg_replace ( '/]]\>/si', ']] >', $str );
    }
    return $str;
}
/**
 * 获取当前页面完整URL地址
 */
function get_url() {
    $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
    $php_self = $_SERVER['PHP_SELF'] ? safe_replace($_SERVER['PHP_SELF']) : safe_replace($_SERVER['SCRIPT_NAME']);
    $path_info = isset($_SERVER['PATH_INFO']) ? safe_replace($_SERVER['PATH_INFO']) : '';
    $relate_url = isset($_SERVER['REQUEST_URI']) ? safe_replace($_SERVER['REQUEST_URI']) : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.safe_replace($_SERVER['QUERY_STRING']) : $path_info);
    return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
}

/**
 * 获取ip
 * @return
 */
function ip() {
    $ip='';
    if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
        $ip = getenv('REMOTE_ADDR');
    } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return preg_match ( '/[\d\.]{7,15}/', $ip, $matches ) ? $matches [0] : '';
}

/**
 * 产生随机字符串
 *
 * @param    int        $length  输出长度
 * @param    string     $chars   可选的 ，默认为 0123456789
 * @return   string     字符串
 */
function random($length, $chars = '0123456789') {
    $hash = '';
    $max = strlen($chars) - 1;
    for($i = 0; $i < $length; $i++) {
        $hash .= $chars[mt_rand(0, $max)];
    }
    return $hash;
}

/**
 * 将字符串转换为数组
 *
 * @param	string	$data	字符串
 * @return	array	返回数组格式，如果，data为空，则返回空数组
 */
function string2array($data) {
    if($data == '') return array();
    $array=[];
    @eval("\$array = $data;");
    return $array;
}

/**
 * 将数组转换为字符串
 *
 * @param	array	$data		数组
 * @param	bool	$isformdata	如果为0，则不使用new_stripslashes处理，可选参数，默认为1
 * @return	string	返回字符串，如果，data为空，则返回空
 */
function array2string($data, $isformdata = 1) {
    if($data == '') return '';
    if($isformdata) $data = pl_stripslashes($data);
    return addslashes(var_export($data, TRUE));
}



function removeemptynodefromarray(&$arr){
    $tem=array();
    foreach($arr as $k=>$r){
        if($r||$r===0){
            $tem[$k]=$r;
        }
    }
    $arr=$tem;
}

function fetchfromkeys($arr,$keys){

    $tmp=array();
    if(is_string($keys)){
        $keys=explode(',',$keys);
        foreach($keys as &$vv){
            $vv=trim($vv);
        }
    }
    if(empty($keys)){
        return $tmp;
    }
    if(!is_array($arr)&&!is_object($arr)){
        return $tmp;
    }
    foreach($arr as $k=>$v){

        if(in_array($k,$keys)){
            $tmp[$k]=$v;
        }

    }
    return $tmp;

}

/**
 * 转换字节数为其他单位
 *
 *
 * @param	string	$filesize	字节大小
 * @return	string	返回大小
 */
function sizecount($filesize) {
    if ($filesize >= 1073741824) {
        $filesize = round($filesize / 1073741824 * 100) / 100 .' GB';
    } elseif ($filesize >= 1048576) {
        $filesize = round($filesize / 1048576 * 100) / 100 .' MB';
    } elseif($filesize >= 1024) {
        $filesize = round($filesize / 1024 * 100) / 100 . ' KB';
    } else {
        $filesize = $filesize.' Bytes';
    }
    return $filesize;
}
/**
 * 字符串加密、解密函数
 *
 *
 * @param	string	$txt		字符串
 * @param	string	$operation	ENCODE为加密，DECODE为解密，可选参数，默认为ENCODE，
 * @param	string	$key		密钥：数字、字母、下划线
 * @param	string	$expiry		过期时间
 * @return	string
 */
function sys_auth($string, $operation = 'ENCODE', $key = '', $expiry = 0) {
    $key_length = 4;
    $key = md5($key != '' ? $key : config('system/auth_key'));
    $fixedkey = md5($key);
    $egiskeys = md5(substr($fixedkey, 16, 16));
    $runtokey = $key_length ? ($operation == 'ENCODE' ? substr(md5(microtime(true)), -$key_length) : substr($string, 0, $key_length)) : '';
    $keys = md5(substr($runtokey, 0, 16) . substr($fixedkey, 0, 16) . substr($runtokey, 16) . substr($fixedkey, 16));
    $string = $operation == 'ENCODE' ? sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$egiskeys), 0, 16) . $string : base64_decode(substr($string, $key_length));

    $i = 0; $result = '';
    $string_length = strlen($string);
    for ($i = 0; $i < $string_length; $i++){
        $result .= chr(ord($string{$i}) ^ ord($keys{$i % 32}));
    }
    if($operation == 'ENCODE') {
        return $runtokey . str_replace('=', '', base64_encode($result));
    } else {
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$egiskeys), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    }
}

function tpl($template){

    $itemplate = sysclass('template');
    return $itemplate->load($template);

}

/**
 * @param $key
 * @param string $value
 * @param int $expire
 * @param string $type
 * @param null $format
 * @return mixed
 * 读取缓存
 */

function setcache($key,$value='',$expire=0,$type='file',$format=null){

    $cls=sysclass('cachefactory',0);
    $cache = $cls::get_instance()->get_cache($type);

    if($format){
        $cache->setting($format); //设置格式类型，支持数组，序列化，文本，只对文件有效
    }

    return $cache->set($key, $value, $expire);

}


function getcache($key, $type='file', $format=null) {
    $cls=sysclass('cachefactory',0);
    $cache = $cls::get_instance()->get_cache($type);

    if($format){
        $cache->setting($format); //设置格式类型，支持数组，序列化，文本，只对文件有效
    }

    return $cache->get($key);
}

function getcachetime($key, $type='file'){

    $cls=sysclass('cachefactory',0);
    $cache = $cls::get_instance()->get_cache($type);

    return $cache->getcachetime($key);

}

/**
 * IE浏览器判断
 */

function is_ie() {
    $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
    if((strpos($useragent, 'opera') !== false) || (strpos($useragent, 'konqueror') !== false)) return false;
    if(strpos($useragent, 'msie ') !== false) return true;
    return false;
}


/**
 * 判断字符串是否为utf8编码，英文和半角字符返回ture
 * @param $string
 * @return bool
 */
function is_utf8($string) {
    return preg_match('%^(?:
					[\x09\x0A\x0D\x20-\x7E] # ASCII
					| [\xC2-\xDF][\x80-\xBF] # non-overlong 2-byte
					| \xE0[\xA0-\xBF][\x80-\xBF] # excluding overlongs
					| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
					| \xED[\x80-\x9F][\x80-\xBF] # excluding surrogates
					| \xF0[\x90-\xBF][\x80-\xBF]{2} # planes 1-3
					| [\xF1-\xF3][\x80-\xBF]{3} # planes 4-15
					| \xF4[\x80-\x8F][\x80-\xBF]{2} # plane 16
					)*$%xs', $string);
}


/**
 * 对用户的密码进行加密
 * @param $password
 * @param $encrypt //传入加密串，在修改密码时做认证
 * @return array/password
 */
function password($password, $encrypt='') {
    $pwd = array();
    $pwd['encrypt'] =  $encrypt ? $encrypt : create_randomstr();
    $pwd['password'] = md5(md5(trim($password)).$pwd['encrypt']);
    return $encrypt ? $pwd['password'] : $pwd;
}
/**
 * 生成随机字符串
 * @param string $lenth 长度
 * @return string 字符串
 */
function create_randomstr($lenth = 6) {
    return random($lenth, '123456789abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ');
}

/**
 * 检查密码长度是否符合规定
 *
 * @param STRING $password
 * @return 	TRUE or FALSE
 */
function is_password($password) {
    $strlen = strlen($password);
    if($strlen >= 6 && $strlen <= 20) return true;
    return false;
}
function is_number($num){

    if(is_numeric($num)||is_int($num)){
        return true;
    }
    return false;
}

/**
 * 判断email格式是否正确
 * @param $email
 */
function is_email($email) {
    return strlen($email) > 6 && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
}

/**
 *判断手机格式是否正确
 */
function is_telphone($tel){
    return preg_match("/^(\s*)1(3|5|6|4|7|8|9)\d{9}(\s*)$/",$tel);
}

/**
 *是否是字符
 **/
function is_char($s){

    if(is_string($s)||is_number($s)||is_float($s)){
        return true;
    }
    return false;
}

/**
 * 检查id是否存在于数组中
 *
 */
function some_in_array($id, $ids = '', $s = ',') {
    if(!$ids) return false;
    $ids = explode($s, $ids);
    return is_array($id) ? array_intersect($id, $ids) : in_array($id, $ids);
}

/**
 * 对数据进行编码转换
 * @param array/string $data       数组
 * @param string $input     需要转换的编码
 * @param string $output    转换后的编码
 */
function array_iconv($data, $input = 'gbk', $output = 'utf-8') {
    if (!is_array($data)) {
        return iconv($input, $output, $data);
    } else {
        foreach ($data as $key=>$val) {
            if(is_array($val)) {
                $data[$key] = array_iconv($val, $input, $output);
            } else {
                $data[$key] = iconv($input, $output, $val);
            }
        }
        return $data;
    }
}

/**
json输出forAajx
 **/

function __json_forajax($data,$isprint=false){

    //输入统一格式到客户
    $result=$data;
    $status="success";
    if(is_array($result)&&(isset($result['error'])||isset($result['status']))){
        if($result['error']||$result['status']=="fail"){
            $status="fail";
        }
    }
    $response=array("response"=>$status,'result'=>$result);

    return __json_encode($response,$isprint);

}

/**
encode编码
 **/
function __json_encode($data,$isprint=false){
    if(CHARSET=='gbk'){
        $data=array_iconv($data,'gbk','utf-8');
    }
    if($isprint){
        echo json_encode($data);
        return null;
    }else{
        return json_encode($data);
    }
}
function ___json_encode($data,$options=0,$isprint=false){


    if($options===0){
        return __json_encode($data,$isprint);
    }
    if(CHARSET=='gbk'){
        $data=array_iconv($data,'gbk','utf-8');
    }
    $r=json_encode($data,$options);

    if(!$r){

        if($options===JSON_UNESCAPED_UNICODE){
            //保留中文编码
            arrayrecursive($data, 'urlencode', true);
            $json = json_encode($data);
            $r=urldecode($json);
            if($isprint){
                echo $r;
            }
            return $r;
        }
    }
    if($isprint){
        echo $r;
        return null;
    }else{
        return $r;
    }
}

function arrayrecursive(&$array, $function, $apply_to_keys_also = false)
{
    static $recursive_counter = 0;
    if (++$recursive_counter > 1000) {
        die;
    }
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            arrayrecursive($array[$key], $function, $apply_to_keys_also);
        } else {
            $array[$key] = $function($value);
        }

        if ($apply_to_keys_also && is_string($key)) {
            $new_key = $function($key);
            if ($new_key != $key) {
                $array[$new_key] = $array[$key];
                unset($array[$key]);
            }
        }
    }
    $recursive_counter--;
}

/**
 **/
function __json_decode($data,$isarr=false){
    $data=json_decode($data,$isarr);
    if(CHARSET=='gbk'){
        $data=array_iconv($data,'utf-8','gbk');
    }
    return $data;
}

function referer($islocal=false){
    if(!$islocal){
        return $_SERVER['HTTP_REFERER'];
    }
    //判断是否是当前域名
    $rr=$_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:config("system/site_url").'/';
    $parts = parse_url($rr);
    $parts2=parse_url(config("system/site_url"));

    if($parts['host']==$parts2['host']){
        return $rr;
    }else{
        return config("system/site_url").'/';
    }
}


/**
 * Function dateformat
 * 时间转换
 * @param $n INT时间
 */
function dateformat($n) {
    $hours = floor($n/3600);
    $minite	= floor($n%3600/60);
    $secend = floor($n%3600%60);
    $minite = $minite < 10 ? "0".$minite : $minite;
    $secend = $secend < 10 ? "0".$secend : $secend;
    if($n >= 3600){
        return $hours.":".$minite.":".$secend;
    }else{
        return $minite.":".$secend;
    }

}

/*时间转化*/
function totime($date){

    if(is_numeric($date)){
        return $date;
    }
    if(empty($date)){return '';}
    return strtotime($date);

}

function multiexplode ($delimiters,$string) {
    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return  $launch;
}

function allin_array($v,$a,$t='value'){
    $a=is_array($a)?$a:array();
    if($t=='value'){
        if(is_array($v)){
            return !array_diff($v,$a);
        }
        return in_array($v,$a);
    }
    if($t=='key'){
        if(is_array($v)){
            return !array_diff(array_keys($v),$a);
        }
        return in_array($v,array_keys($a));
    }

    return null;

}

function getfromarraysbysomekeyvalue($arrs,$key,$keyvalue=false){

    foreach($arrs as $k=>$arr){
        if($keyvalue===false){
            if($k===$key){
                return $arr;
            }
        }else{
            if(is_array($arr)){
                if(isset($arr[$key])){
                    if($arr[$key]==$keyvalue){
                        return $arr;
                    }
                }
            }
        }
    }
    return false;
}

function nosetsetdefault(&$vb,$default){

    if(!isset($vb)){
        $vb=$default;
    }
}
function emptysetdefault(&$vb,$default){
    if(empty($vb)){
        $vb=$default;
    }
}
//批量设置数据类型

function setdatatype(&$vb,$func){

    if(is_callable($func)){
        $vb=$func($vb);
        return;
    }
    if(function_exists($func)){
        $vb=$func($vb);
        return;
    }
    if(is_string($func)){
        $func=multiexplode(",|",$func);
    }
    if(is_array($func)){
        foreach($func as $fc){
            if(function_exists($fc)){
                $vb=$fc($vb);
            }
        }
    }

}

function plsetdatatype(&$param,$needs,$func,$attach=false){

    if(!is_array($param)){
        return;
    }
    if(is_string($needs)){
        $needs=preg_replace("/\s+/","",$needs);
        $needs=explode(',',$needs);
    }
    foreach ($needs as $key){

        if(is_null($param[$key])){
            unset($param[$key]);
            continue;
        }
        if($attach){
            setdatatype($param[$key],$func);
        }else if(isset($param[$key])){
            setdatatype($param[$key],$func);
        }
    }

}
function plsetdefault(&$param,$needs,$default,$type=0){

    if(!is_array($param)){
        return;
    }
    if(is_string($needs)){
        $needs=preg_replace("/\s+/","",$needs);
        $needs=explode(',',$needs);
    }
    foreach ($needs as $key){
        if($type==0){
            emptysetdefault($param[$key],$default);
        }else if(isset($param[$key])){
            nosetsetdefault($param[$key],$default);
        }
    }

}

function getbrowser($isarr=false){
    $agent=$_SERVER["HTTP_USER_AGENT"];
    $browser='unknow';
    $version='unknow';
    if(strpos($agent,'MSIE')!==false || strpos($agent,'rv:11.0')) {
        if (preg_match('/MSIE\s(\d+)\..*/i', $agent, $regs)) {
            $version=$regs[1];
        }
        $browser="ie";
    } else if(strpos($agent,'Firefox')!==false) {

        if(preg_match('/FireFox\/(\d+)\..*/i', $agent, $regs)){
            $version=$regs[1];
        }
        $browser="firefox";
    } else if(strpos($agent,'Chrome')!==false) {

        if (preg_match('/Chrome\/(\d+)\..*/i', $agent, $regs)) {
            $version = $regs[1];
        }
        $browser="chrome";
    }
    else if(strpos($agent,'Opera')!==false) {

        if (preg_match('/Opera[\s|\/](\d+)\..*/i', $agent, $regs)) {
            $version = $regs[1];
        }
        $browser="opera";
    }
    else if((strpos($agent,'Chrome')==false)&&strpos($agent,'Safari')!==false) {

        if(preg_match('/Safari\/(\d+)\..*$/i', $agent, $regs)){
            $version=$regs[1];
        }
        $browser='safari';
    }
    if($isarr){
        return ['browser'=>$browser,'version'=>$version];
    }
    return $browser.$version;
}

function isWeixin(){
    //是否在微信浏览浏览器里访问
    $agent=$_SERVER['HTTP_USER_AGENT'];
    if(strpos($agent,"MicroMessenger")){
        return true;
    }else{
        return false;
    }
}
function isMobile() {
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])){
        return true;
    }
    //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA'])) {
        //找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    //判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array (
            'nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
            'mobile'
        );
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    //协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;

}


function request_uri(){

    if (isset($_SERVER['REQUEST_URI']))
    {
        $uri = $_SERVER['REQUEST_URI'];
    }
    else
    {
        if (isset($_SERVER['argv']))
        {
            $uri = $_SERVER['PHP_SELF'] .'?'. $_SERVER['argv'][0];
        }
        else
        {
            $uri = $_SERVER['PHP_SELF'] .'?'. $_SERVER['QUERY_STRING'];
        }
    }
    return $uri;
}


/**
 * @param $methodname
 * @param $param
 * @return mixed
 * 客户端调用
 */
function rpc($methodname,$param=null,$conf=null){

    $apiClass="rpc\\ApiFactory";
    $get_api = new $apiClass();
    if(is_array($methodname)){
        return $get_api->multi($methodname);
    }else{
        return $get_api->single($methodname,$param,$conf);
    }

}


/**
 * @param $requestdata
 * @return mixed
 * 服务端响应
 */

function rpcserver($requestdata){

    $apiClass="rpc\\ApiFactory";
    $get_api = new $apiClass();

    $apiresult=$get_api->response($requestdata);

    GW(__json_encode($apiresult));

    return;

}


function findfile($root,$filename){

    if(@is_file($root.$filename)){
        return $root.$filename;
    }
    if(is_dir($root)){
        $arr=scandir($root);
        foreach($arr as $v)
        {
            if($v==".") continue;
            if( $v=="..") continue;
            if(strpos($v,'.php')){
                continue;
            }
            $dir1=$root.$v.DIRECTORY_SEPARATOR;
            if(is_dir($dir1)){
                if($file=findfile($dir1,$filename)){
                    return $file;
                }
            }
        }
    }
    return false;
}

function sysclass($classname,$initialize=1,$binds=null,$iscache=1){

    static $sysclasscache=[];
    $classname=ucfirst($classname); //首字母大写
    $classname.='Class';
    if($initialize==1){
        if(empty($sysclasscache[$classname])||!$iscache) {
            $cls="xl\\classs\\".$classname;
            if(\xl\XlLead::$factroy){
                $sysclasscache[$classname]=\xl\XlLead::$factroy->binds($binds)->getInstance($cls);
            }else{
                $sysclasscache[$classname]=new $cls;
            }
        }
        return $sysclasscache[$classname];
    }else{
        $cls="xl\\classs\\".$classname;
        return $cls;
    }
}


function affair($affair,$binds=null,$iscache=1){
    return __autocreaterunobject("affair",$affair,$iscache,$binds);
}

function __autocreaterunobject($mdl,$clsname,$iscache=1,$binds=null,$ns=null){

    $folder='';
    if(($pos=strrpos($clsname,'.'))){
        $s_clsname=str_replace('.','_',$clsname);
        $folder=substr($clsname,0,$pos);
        $folder=str_replace(".","\\",$folder);
        $clsname=substr($clsname,$pos+1);
    }else{
        $s_clsname=$clsname;
    }
    $s_clsname=$mdl."_".$s_clsname;
    static $__runmdlclasscache=[];
    $clsname=ucfirst($clsname);
    $clsname.=ucfirst($mdl);
    if($iscache&&$__runmdlclasscache[$s_clsname]){
        return $__runmdlclasscache[$s_clsname];
    }
    if(!$ns){
        $ns=ROOT_NS;
    }
    if($folder){
        $cls = $ns."\\".$mdl."\\".$folder."\\".$clsname;
    }else {
        $cls = $ns."\\".$mdl."\\" . $clsname;
    }
    if(\xl\XlLead::$factroy) {
        $__runmdlclasscache[$s_clsname] = \xl\XlLead::$factroy->binds($binds)->getInstance($cls);
    }else{
        $__runmdlclasscache[$s_clsname] = new $cls;
    }

    return $__runmdlclasscache[$s_clsname];

}

/*逻辑库*/
function logic($clsname,$binds=null,$iscache=1){
    return __autocreaterunobject("logic",$clsname,$iscache,$binds);
}

//导入类库
function import($path){

    $pathkey=$path;
    static $static_importcache=[];
    if(isset($static_importcache[$pathkey])){
        return;
    }
    if(strpos($path,'@')===0){
        $patharr=explode(".",$path);
        $pathroot=xl\AutoLoad::$aliases[array_shift($patharr)];
        $path=$pathroot.D_S.implode(D_S,$patharr);
    }else{
        $path=str_replace('.','/',$path);
    }
    if(strpos($path,"#")!=0){
        $path=str_replace("#",'.',$path);
    }
    if(strpos($path,'/')===0){
        $path=$path.'.php';
    }
    require $path;
    $static_importcache[$pathkey]=1;

}

function config($config,$value=null,$iswrite=false){

    $oc=new xl\XlConfig($config,$value,$iswrite);
    return $oc->exec();
}

function GZoomSize($sw,$sh,$boxw,$boxh){
    $boxw=$boxw?$boxw:20000;
    $boxh=$boxh?$boxh:20000;
    if($sw<=$boxw&&$sh<$boxh){
        return array('w'=>$sw,'h'=>$sh);
    }
    $b1=$boxw/$sw;
    $b2=$boxh/$sh;
    $b=$sw/$sh;
    if($b1<=$b2){
        $w=$boxw;
        $h=$w/$b;
        return array('w'=>$w,'h'=>$h);
    }else{
        $h=$boxh;
        $w=$b*$h;
        return array('w'=>$w,'h'=>$h);
    }
}

function dealaddquotes($str){
    if(!is_array($str)){
        if(is_int($str)){
            return $str;
        }
        if(is_string($str)){
            $str=explode(',',$str);
        }
    }
    if(!is_array($str)){return;}

    $narr=array();

    foreach($str as $n){
        if(is_string($n)){
            $n='\''.$n.'\'';
        }
        array_push($narr,$n);
    }

    return implode(',',$narr);
}

function GU($url){

    if(is_string($url)){
        if(strpos($url,'/')!==0){
            $url='/'.$url;
        }
    }

    return $url;

}

/*跳转url*/
function toUrl($url='',$sec=0){

    if(empty($url)){
        $url=GU("/");
    }else{
        $site_url=config("system/site_url");
        if(strpos('/',trim($url))===0){
            $url=$site_url.$url;
        }
    }
    if($sec==0){
        header('Location: '.$url);
    }else{
        header('Refresh: '.$sec.'; url='.$url);
    }
}

//处理字符串

function strLeft($str,$len,$flag=true){

    return sysclass("opstr")->left($str,$len,$flag);
}
function strLength($str){
    return sysclass("opstr")->strlen($str);
}

function getServerName()
{
    $ServerName =strtolower($_SERVER['HTTP_HOST']?$_SERVER['HTTP_HOST']:$_SERVER['SERVER_NAME']);
    if( strpos($ServerName,'http://') )
    {
        return str_replace('http://','',$ServerName);
    }
    return $ServerName;
}

function dealimgtagsforlazy($c,$fc=''){


    $placeholder = config("system/defaultimg"); //占位符图片
    $preg = "/<img ([^>]*)src=\"(.*)\" ([^>]*)\/>/iU"; //匹配图片正则
    $replaced = '<img class="lazy '.$fc.'" \\1src="'.$placeholder.'" data-original="\\2" \\3/>';

    $c= preg_replace($preg, $replaced, $c);

    return $c;
}

function fetchimgsrcfromstr($c){

    //从字符串中提取图片地址

    $preg="/<img [^>]*src=(\"|\')(.*)(\"|\')[^>]*>/iU";

    if(preg_match_all($preg,$c,$m)){

        $picarr=$m[2];
        return $picarr;
    }

    return array();
}

function mkdirm($path)
{
    if(!file_exists($path))
    {
        mkdirm(dirname($path));;
        @mkdir($path);
    }
}

function GSession($key,$val=null){
    return sysclass('cookiesession')->sgSession($key,$val);
}
function GCookie($key,$val=null,$time=null){
    return sysclass('cookiesession')->sgCookie($key,$val,$time); //双功能
}

function removescripttags(&$c){
    $c=preg_replace('/<(script|div)(.*)<\/(script|div)>/i','',$c); //除去非法标签
    return $c;
}

/**
 * @param $array
 * @param int $length
 * @return array|bool
 *
 * $param=> [1,2,2,3,1,1,4,5,6];
 * $return =>[ '1' => 3 '2' => 2 '3' => 1 '4' => 1 '5' => 1 '6' => 1 ]
 *
 */

function mostRepeatedValues($array,$length=0){
    if(empty($array) or !is_array($array)){
        return false;
    }
    //1. 计算数组的重复值
    $array = array_count_values($array);
    //2. 根据重复值 倒排序
    arsort($array);
    if($length>0){
        //3. 返回前 $length 重复值
        $array = array_slice($array, 0, $length, true);
    }
    return $array;
}



function dealPhoneForSecret($telphone){

    $telphone=trim($telphone);
    //加密手机号
    if(!is_telphone($telphone)){
        return $telphone;
    }

    return preg_replace("/(\d{3})(\d{4})(\d{4})/","$1****$3",$telphone);

}


function ___succ($msg='操作成功',$data=''){
    $r=array('status'=>'succss','msg'=>$msg);
    if($data){
        $r['data']=$data;
    }
    GW(__json_forajax($r));
    exit;
}
function ___fail($msg="操作失败",$data=''){
    $r=array('status'=>'fail','msg'=>$msg);
    if($data){
        $r['data']=$data;
    }
    GW(__json_forajax($r));
    exit;
}

function urlToPath($url){
    return str_replace(config("system/site_url").'/',DOC_ROOT,$url); //兼容
}

//获得唯一id
function getuuid($workid=1){
    $cls=sysclass("idhash",0);
    return $cls::getuuid($workid);
}

//输出变量

function arrayToJsV($arr,$obj='g_this'){

    $arr=is_array($arr)?$arr:array();
    $jsarr=array();
    foreach($arr as $k=>$v){
        if($k&&!is_number($k)){
            if(is_array($v)){
                $jsarr[]=$k.':"'.__json_encode($v).'"';
            }else if(is_int($v)||is_float($v)){
                $jsarr[]=$k.':'.$v;
            }else{
                $jsarr[]=$k.':"'.$v.'"';
            }
        }
    }
    $jsarrstr=implode(',',$jsarr);
    $jsstr='var '.$obj.'={'.$jsarrstr.'}';

    return $jsstr;

}

function getApiData($r){

    if(!is_array($r)){
        var_dump($r);
        exit;
    }

    if(isset($r['__'])&&$r['status']){
        return $r;
    }else{
        if(isset($r['data'])){
            return $r['data'];
        }
    }
    return $r;
}

function emptyToZero(&$pm,$keys,$zero=0){

    if(!is_array($keys)){
        $keys=explode(',',$keys);
    }
    if(!is_array($keys)){
        return null;
    }
    if(!is_array($pm)){
        return null;
    }
    foreach($pm as $k=>&$v){
        if(in_array($k,$keys)){
            if(!is_numeric($v)){
                $v=$zero;
            }
        }
    }
    return $pm;
}

function plDateToTime(&$pm,$keys){

    if(!is_array($keys)){
        $keys=explode(',',$keys);
    }
    if(!is_array($keys)){
        return;
    }
    if(!is_array($pm)){
        return;
    }
    foreach($pm as $k=>&$v){
        if(in_array($k,$keys)){
            if(strpos($v,'-')===false){
                continue;
            }else{
                $v=strtotime($v);
            }
        }
    }

}


function GP($data) {
    echo '<pre>';
    if(is_array($data)){
        print_r($data);
    }else if(is_object($data)){
        var_dump($data);
    }else if($data){
        echo $data;
    }else{
        var_dump($data);
    }
    echo '</pre>';
}

function JsonPrint($data){

    //json输出
    GW(__json_encode($data));

}

function AjaxPrint($data){

    GW(__json_forajax($data));
}


function logger($logname='',$filemaxsize=null){
    return \xl\XlLead::logger($logname,$filemaxsize);
}

function encodeSqlStr($var){

    return sys_auth(serialize($var),"ENCODE","sql");

}

function decodeSqlStr($var){

    return unserialize(sys_auth($var,"DECODE","sql"));
}


function fun_adm_each(&$array){
    $res = array();
    $key = key($array);
    if($key !== null){
        next($array);
        $res[1] = $res['value'] = $array[$key];
        $res[0] = $res['key'] = $key;
    }else{
        $res = false;
    }
    return $res;
}

function to404page(){

    throw new \xl\util\XlUException("404 Not Found",404);

}

/**
 * 模型流程
 */
function MS($methodname,$config=null,$isplugin=null,$ns=null){

    $cls = "xl\\base\\XlModelStream";
    if(\xl\XlLead::$factroy) {
        return \xl\XlLead::$factroy->bind("properties",['_Isplugin'=>$isplugin,'_Ns'=>$ns])->bind("construct_args",[$methodname,$config])->getInstance($cls);
    }else{
        return new $cls($methodname,$config);
    }

}

/**
 * 任务流程
 */
function TS($name,$params=null,$isplugin=null,$ns=null){

    $cls = "xl\\base\\XlTaskStream";
    if(\xl\XlLead::$factroy) {
        return \xl\XlLead::$factroy->bind("properties",['_Isplugin'=>$isplugin,'_Ns'=>$ns])->bind("construct_args",[$name,$params])->getInstance($cls);
    }else{
        return new $cls($name,$params);
    }

}

/**
 *
 * @return mixed
 * 内部接口调用函数
 */
function iapi($methodname,$params){

    $ns=null;
    if(($pos=strpos($methodname,":"))===false){
        //全局方法
        $ns=defined("ROOT_NS")?ROOT_NS:'';
        $isplugin=false;
    }else{
        //插件
        $ns=substr($methodname,0,$pos);
        $methodname=substr($methodname,$pos+1);
        $isplugin=true;

    }

    $methodname=trim($methodname);
    if(strpos($methodname,".")===false){
        $methodname=ucfirst($methodname);
    }
    $cls=$ns."\\iapi\\".str_replace(".","\\",$methodname)."Iapi";

    $obj=\xl\XlLead::$factroy->bind("properties",['_Isplugin'=>$isplugin,'_Ns'=>$isplugin?$ns:null])->getInstance($cls);

    $obj->setParams($params);

    return $obj->getResult($obj->getParams());

}

//埋点
function burypoint($name,$params=null,$async=false,$filter=null){

    return \xl\core\XlPointEntry::buryPointAndCall($name,$params,$async,$filter);

}