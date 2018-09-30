<?php

namespace xl\classs;

use xl\base\XlClassBase;

class IpcityClass extends XlClassBase{

    private $conf;
    function __construct(){

        $cf=config("system");
        $this->conf=array('citychange_open'=>$cf['citychange_open'],
            'default_city'=>$cf['default_city']);

    }
    public function accessqqipdb($ip){
        //访问QQ纯真id库
        if(!preg_match("/^(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$/", $ip)) {
            return 'IP Address Error';
        }
        //打开IP数据文件
        if(!$fd = @fopen($this->dat_path, 'rb')){
            return 'IP date file not exists or access denied';
        }

        //分解IP进行运算，得出整形数
        $ip = explode('.', $ip);
        $ipNum = $ip[0] * 16777216 + $ip[1] * 65536 + $ip[2] * 256 + $ip[3];

        //获取IP数据索引开始和结束位置
        $DataBegin = fread($fd, 4);
        $DataEnd = fread($fd, 4);
        $ipbegin = implode('', unpack('L', $DataBegin));
        if($ipbegin < 0) $ipbegin += pow(2, 32);
        $ipend = implode('', unpack('L', $DataEnd));
        if($ipend < 0) $ipend += pow(2, 32);
        $ipAllNum = ($ipend - $ipbegin) / 7 + 1;

        $BeginNum = 0;
        $EndNum = $ipAllNum;

        //使用二分查找法从索引记录中搜索匹配的IP记录
        while($ip1num>$ipNum || $ip2num<$ipNum) {
            $Middle= intval(($EndNum + $BeginNum) / 2);

            //偏移指针到索引位置读取4个字节
            fseek($fd, $ipbegin + 7 * $Middle);
            $ipData1 = fread($fd, 4);
            if(strlen($ipData1) < 4) {
                fclose($fd);
                return 'System Error';
            }
            //提取出来的数据转换成长整形，如果数据是负数则加上2的32次幂
            $ip1num = implode('', unpack('L', $ipData1));
            if($ip1num < 0) $ip1num += pow(2, 32);

            //提取的长整型数大于我们IP地址则修改结束位置进行下一次循环
            if($ip1num > $ipNum) {
                $EndNum = $Middle;
                continue;
            }

            //取完上一个索引后取下一个索引
            $DataSeek = fread($fd, 3);
            if(strlen($DataSeek) < 3) {
                fclose($fd);
                return 'System Error';
            }
            $DataSeek = implode('', unpack('L', $DataSeek.chr(0)));
            fseek($fd, $DataSeek);
            $ipData2 = fread($fd, 4);
            if(strlen($ipData2) < 4) {
                fclose($fd);
                return 'System Error';
            }
            $ip2num = implode('', unpack('L', $ipData2));
            if($ip2num < 0) $ip2num += pow(2, 32);

            //没找到提示未知
            if($ip2num < $ipNum) {
                if($Middle == $BeginNum) {
                    fclose($fd);
                    return 'Unknown';
                }
                $BeginNum = $Middle;
            }
        }

        $ipFlag = fread($fd, 1);
        if($ipFlag == chr(1)) {
            $ipSeek = fread($fd, 3);
            if(strlen($ipSeek) < 3) {
                fclose($fd);
                return 'System Error';
            }
            $ipSeek = implode('', unpack('L', $ipSeek.chr(0)));
            fseek($fd, $ipSeek);
            $ipFlag = fread($fd, 1);
        }

        if($ipFlag == chr(2)) {
            $AddrSeek = fread($fd, 3);
            if(strlen($AddrSeek) < 3) {
                fclose($fd);
                return 'System Error';
            }
            $ipFlag = fread($fd, 1);
            if($ipFlag == chr(2)) {
                $AddrSeek2 = fread($fd, 3);
                if(strlen($AddrSeek2) < 3) {
                    fclose($fd);
                    return 'System Error';
                }
                $AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
                fseek($fd, $AddrSeek2);
            } else {
                fseek($fd, -1, SEEK_CUR);
            }

            while(($char = fread($fd, 1)) != chr(0))
                $ipAddr2 .= $char;

            $AddrSeek = implode('', unpack('L', $AddrSeek.chr(0)));
            fseek($fd, $AddrSeek);

            while(($char = fread($fd, 1)) != chr(0))
                $ipAddr1 .= $char;
        } else {
            fseek($fd, -1, SEEK_CUR);
            while(($char = fread($fd, 1)) != chr(0))
                $ipAddr1 .= $char;

            $ipFlag = fread($fd, 1);
            if($ipFlag == chr(2)) {
                $AddrSeek2 = fread($fd, 3);
                if(strlen($AddrSeek2) < 3) {
                    fclose($fd);
                    return 'System Error';
                }
                $AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
                fseek($fd, $AddrSeek2);
            } else {
                fseek($fd, -1, SEEK_CUR);
            }
            while(($char = fread($fd, 1)) != chr(0)){
                $ipAddr2 .= $char;
            }
        }
        fclose($fd);

        //最后做相应的替换操作后返回结果
        if(preg_match('/http/i', $ipAddr2)) {
            $ipAddr2 = '';
        }
        $ipaddr = "$ipAddr1 $ipAddr2";
        $ipaddr = preg_replace('/CZ88.Net/is', '', $ipaddr);
        $ipaddr = preg_replace('/^s*/is', '', $ipaddr);
        $ipaddr = preg_replace('/s*$/is', '', $ipaddr);
        if(preg_match('/http/i', $ipaddr) || $ipaddr == '') {
            $ipaddr = 'Unknown';
        }

        if($this->character=="utf-8"){
            $ipaddr=Giconv('GB2312','UTF-8',$ipaddr);
        }
        return $ipaddr;
    }
    public function getCitycookie(){


        $city=GCookie("city");

        return $city?$city:array();
    }
    public function setCitycookie($city){
        GCookie("city",$city);
    }
    public function getCurrcity($ip='',$isdefault=true){
        if(empty($ip)){
            $ip=ip(); //ip
        }
        $city=$this->accessqqipdb($ip);   //从纯真ip库获得相关的城市
        $city=$this->formatcity($city);   //格式化城市

        if($city=='局域网'){
            $city=$this->conf['default_city'];
        }
        if($isdefault){
            $city=$this->conf['default_city']; //前期默认为合肥
        }
        return $city;

    }
    private function formatcity($city){

        //从纯真ip库中获得当前用户所属于的城市。
        //内蒙古，宁夏，新疆，广西，西藏不带省份。先处理
        $cityarr=explode(' ',$city);
        if(count($cityarr)==2){
            $city=$cityarr[0];
        }
        $city=trim(str_replace('市','',$city));
        foreach(array('内蒙古','宁夏','新疆','广西','西藏') as $v){

            if(strpos($city,$v)!==false){
                //已经找到
                $city=str_replace($v,'',$city);
                return $city;
            }
        }
        //除去省
        $cstr=sysclass('opstr');
        $index=strpos($city,'省');
        if($index!=0){
            $index/=3;
            $city=$cstr->substr($city,$index+1,10);
        }
        //
        return $city;
    }


}
