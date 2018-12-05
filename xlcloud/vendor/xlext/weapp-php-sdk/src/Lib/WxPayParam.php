<?php
namespace Xl_WeApp_SDK\Lib;
use Xl_WeApp_SDK\Config as Config;

/**
 * Class WxPayData
 * @package Xl_WeApp_SDK\Lib
 * 微信支付数据，基类
 */
class WxPayParam{

    protected $values = [];

    /**
     * 设置签名，详见签名生成算法
     * @param string $value
     **/
    public function setSign()
    {
        $sign = $this->MakeSign();
        $this->values['sign'] = $sign;
        return $sign;
    }

    /**
     * 获取签名，详见签名生成算法的值
     * @return 值
     **/
    public function getSign()
    {
        return $this->values['sign'];
    }

    /**
     * 判断键值是否存在
     * @param $key
     * @return bool
     */
    public function isKeySet($key){

        return array_key_exists($key,$this->values); //判断某键是否在values存在

    }

    /**
     * 输出xml字符
     * @throws WxPayException
     **/
    public function toXml()
    {
        if(!is_array($this->values)
            || count($this->values) <= 0)
        {
            throw new \Exception("数组数据异常！");
        }

        $xml = "<xml>";
        foreach ($this->values as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @throws WxPayException
     */
    public function fromXml($xml)
    {
        if(!$xml){
            throw new \Exception("xml数据异常！");
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $this->values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $this->values;
    }

    /**
     * 格式化参数格式化成url参数
     */
    public function toUrlParams()
    {
        $buff = "";
        foreach ($this->values as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function makeSign()
    {
        //签名步骤一：按字典序排序参数
        ksort($this->values);
        $string = $this->ToUrlParams();
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".Config::getKEY();
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    public function setValues($values){

        if (!is_array($values)) {
            throw new \Exception("参数应该是数组形式");
        }
        $this->values=array_merge($this->values,$values);

        return null;
    }

    /**
     * 获取设置的值
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * 根据key获得值
     */
    public function getValueByKey($key){

        return $this->values[$key]?:null;

    }

    /**
     * 设置key值
     * @param $key
     * @param $value
     */
    public function setValueByKey($key,$value){

        $this->values[$key]=$value;

    }

}

/**
 *
 * 接口调用结果类
 * @author widyhu
 *
 */
class WxPayResult extends WxPayParam
{
    /**
     *
     * 检测签名
     */
    public function CheckSign()
    {
        //fix异常
        if(!$this->isKeySet("sign")){
            throw new \Exception("签名错误！");
        }

        $sign = $this->MakeSign();
        if($this->getSign() == $sign){
            return true;
        }
        throw new \Exception("签名错误！");
    }

    /**
     *
     * 使用数组初始化
     * @param array $array
     */
    public function FromArray($array)
    {
        $this->values = $array;
    }

    /**
     *
     * 使用数组初始化对象
     * @param array $array
     * @param 是否检测签名 $noCheckSign
     */
    public static function InitFromArray($array, $noCheckSign = false)
    {
        $obj = new self();
        $obj->FromArray($array);
        if($noCheckSign == false){
            $obj->CheckSign();
        }
        return $obj;
    }

    /**
     *
     * 设置参数
     * @param string $key
     * @param string $value
     */
    public function SetData($key, $value)
    {
        $this->values[$key] = $value;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @throws WxPayException
     */
    public static function Init($xml)
    {
        $obj = new self();
        $obj->FromXml($xml);
        //fix bug 2015-06-29
        if($obj->values['return_code'] != 'SUCCESS'){
            return $obj->GetValues();
        }
        $obj->CheckSign();
        return $obj->GetValues();
    }
}
