<?php

namespace xl\api;

/**
 * Class Qrcode
 * @package xl\api
 * 二维码生成
 */

class Qrcode extends XlApiBase{

    protected $text; //要生成的文本
    protected $errorLevel="L";
    protected $size=4;
    protected $margin=0;
    protected $filepath=false; //为false时直接输出

    public function run(){

        import("@xl.third.phpqrcode.qrlib"); //导入库

        \QRcode::png($this->text, $this->filepath, $this->errorLevel, $this->size,$this->margin);

    }

}
