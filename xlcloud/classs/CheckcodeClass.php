<?php

namespace xl\classs;

use xl\base\XlClassBase;

class CheckcodeClass extends XlClassBase{


//随机因子

    private $charset = 'abcdefghkmnpstuwxyABCDEFGHKMNPRSTUVWXY23456789';

    private $code;    //验证码

    private $codelen = 4;  //验证码长度

    private $width = 80;  //宽度

    private $height = 30;  //高度

    private $img;    //图像资源句柄

    private $font;    //字体

    private $fontcolor;   //字体颜色

    private $fontsize = 11;  //字体大小



    public function __construct(){

        $checkcodefont=config("system/checkcodefont")?:"elephant.ttf";

        $this->font =STATIC_PATH.'fonts'.D_S.$checkcodefont;//设置默认字体的样式

    }

    public function setFont($name){

        $this->font=STATIC_PATH.'fonts'.D_S.$name;

        return $this;
    }

    public function setFontPath($fontname,$isdefaultpath=true){

        if($isdefaultpath){
            $this->font=STATIC_PATH.'images'.D_S.'elephant.ttf';
        }else{
            $this->font=$fontname;
        }

        return $this;

    }

    public function setSize($w,$h,$fz)

    {

        $this->height=$h;

        $this->width=$w;

        $this->fontsize=$fz;

        return $this;

    }





    //生成验证码

    //mt_rand()该函数用了 Mersenne Twister 中已知的特性作为随机数发生器，它可以产生随机数值的平均速度比 libc 提供的 rand() 快四倍

    private function createCode(){

        $_len = strlen($this->charset)-1;

        for ($i=0;$i<$this->codelen;$i++) {

            $this->code .= $this->charset[mt_rand(0,$_len)];

        }

        //把code保存在cookie里
        GCookie("yzm",$this->code);

    }



    //生成背景

    //imagecreatetruecolor( int x_size, int y_size )新建一个真彩色图像

//imagecolorallocate ( resource image, int red, int green, int blue )为一幅图像分配颜色

// mt_rand ( [int min, int max] )生成更好的随机数

//imagefilledrectangle ( resource image, int x1, int y1, int x2, int y2, int color )  画一矩形并填充,左上角坐标为 x1，y1，右下角坐标为 x2，y2



    private function createBg(){

        $this->img = imagecreatetruecolor($this->width, $this->height);

        $color = imagecolorallocate($this->img, mt_rand(230,255), mt_rand(230,255), mt_rand(255,255));

        imagefilledrectangle($this->img,0,$this->height,$this->width,0,$color);

    }



    //生成文字

    //imagettftext ( resource image, float size, float angle, int x, int y, int color, string fontfile, string text ) 用 TrueType 字体向图像写入文本

    //angle角度制表示的角度，0 度为从左向右读的文本。更高数值表示逆时针旋转。例如 90 度表示从下向上读的文本。



    private function createFont(){

        $_x = $this->width / $this->codelen;

        for ($i=0;$i<$this->codelen;$i++) {

            $this->fontcolor = imagecolorallocate($this->img,mt_rand(0,100),mt_rand(0,100),mt_rand(0,100));
            imagettftext($this->img,$this->fontsize,mt_rand(-10,30),$_x*$i+mt_rand(5,10),$this->height / 1.4,$this->fontcolor,$this->font,$this->code[$i]);

        }

    }



    //生成线条和雪花

    //imageline ( resource image, int x1, int y1, int x2, int y2, int color )用 color 颜色在图像 image 中从坐标 x1，y1 到 x2，y2（图像左上角为 0, 0）画一条线段

//imagestring ( resource image, int font, int x, int y, string s, int col ) 水平地画一行字符串

//用 col 颜色将字符串 s 画到 image 所代表的图像的 x，y 坐标处（这是字符串左上角坐标，整幅图像的左上角为 0，0）。如果 font 是 1，2，3，4 或 5，则使用内置字体。



    private function createLine(){

        for ($i=0;$i<6;$i++) {

            $color = imagecolorallocate($this->img,mt_rand(100,156),mt_rand(100,156),mt_rand(100,156));

            imageline($this->img,mt_rand(0,$this->width),mt_rand(0,$this->height),mt_rand(0,$this->width),mt_rand(0,$this->height),$color);

        }

        for ($i=0;$i<10;$i++) {

            $color = imagecolorallocate($this->img,mt_rand(200,255),mt_rand(200,255),mt_rand(200,255));

            imagestring($this->img,mt_rand(1,5),mt_rand(0,$this->width),mt_rand(0,$this->height),'*',$color);

        }

    }



    //输出

    private function output(){

        header('Content-Type:image/png');

        imagepng($this->img);

        imagedestroy($this->img);

    }



    //对外生成

    public function doimg(){

        $this->createBg();

        $this->createCode();

        $this->createLine();

        $this->createFont();

        $this->output();

    }



    //获取验证码

    public function getCode(){

        return strtolower(GCookie("yzm"));

    }

}