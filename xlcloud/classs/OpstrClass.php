<?php

namespace xl\classs;
use xl\base\XlClassBase;

class OpstrClass extends XlClassBase {

    function __construct()
    {
    }
    public function utf8Substr($str, $from, $len)
    {
        return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$from.'}'.
            '((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'.$len.'}).*#s',
            '$1',$str);
    }
    public function strlen($str)
    {
        $i = 0;
        $count = 0;
        $len = strlen ($str);
        while ($i < $len) {
            $chr = ord ($str[$i]);
            $count++;
            $i++;
            if($i >= $len) break;
            if($chr & 0x80){
                $chr <<= 1;
                while ($chr & 0x80){
                    $i++;
                    $chr <<= 1;
                }
            }
        }
        return $count;
    }
    public function substr($str,$start,$len)
    {
        $len=($this->strlen($str)-$start)>$len ? $len:($this->strlen($str)-$start);
        $str=$this->utf8Substr($str,$start,$len);
        return $str;
    }
    public function left($str,$len,$flag=true)
    {
        $slen=$this->strlen($str);
        if($len>=$slen)
        {
            return $this->utf8Substr($str,0,$slen);
        }
        else
        {
            if($flag)
            {
                return $this->utf8Substr($str,0,$len);
            }
            else
            {
                return $this->utf8Substr($str,0,$len).'...';
            }
        }
    }
    public function unescape($str)
    {
        $ret = '';
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++)
        {
            if ($str[$i] == '%' && $str[$i+1] == 'u')
            {
                $val = hexdec(substr($str, $i+2, 4));

                if ($val < 0x7f) $ret .= chr($val);
                else if($val < 0x800) $ret .= chr(0xc0|($val>>6)).chr(0x80|($val&0x3f));
                else $ret .= chr(0xe0|($val>>12)).chr(0x80|(($val>>6)&0x3f)).chr(0x80|($val&0x3f));

                $i += 5;
            }
            else if ($str[$i] == '%')
            {
                $ret .= urldecode(substr($str, $i, 3));
                $i += 2;
            }
            else $ret .= $str[$i];
        }
        return $ret;
    }

}