<?php

namespace xl\classs;

use xl\base\XlClassBase;

class GdClass extends XlClassBase {

        private $image = null;
        private $type = null;
        // 构造函数
        public function __construct(){}
        // 析构函数

        public function __destruct()
        {
            if($this->image!==null) imagedestroy($this->image);
        }
        // 载入图像
        public function open($path)
        {
            //
            list($width,$height,$type)=getimagesize($path);
            if($type==1)
                $type='gif';
            else if( $type==2 )
                $type='jpg';
            else if( $type==3 )
                $type='png';
            else
                $type='jpg';
            $this->type=$type;

            switch($type){
                case 'jpg':
                    $this->image=imagecreatefromjpeg($path);
                    break;
                case 'gif':
                    $this->image=imagecreatefromgif($path);
                    break;
                case 'png':
                    $this->image=imagecreatefrompng($path);
                    break;
            }

        }
        public function cropandfill($x=0,$y=0,$width=null, $height=null){

            $src_width=$this->get_width();   //原图片的宽
            $src_height=$this->get_height(); //原图片的高
            if($width==null){
                $width=$src_width;
                $height=$src_height;
            }
            $targetimg = imagecreatetruecolor($width,$height);
            imagefilledrectangle($targetimg, 0, 0, $width, $width, imagecolorallocatealpha($targetimg,255,255,255,0));
            $bx=0;$by=0;$ex=0;$ey=0;
            if($x<0){
                $ex=0;
                $bx=-$x;
            }else{
                $bx=0;
                $ex=$x;
            }
            if($y<0){
                $ey=0;
                $by=-$y;
            }else{
                $ey=$y;
                $by=0;
            }
            /*$background = imagecolorallocate($this->image, 0, 0, 0);
            imagecolortransparent($this->image, $background);
            imagealphablending($this->image, false);*/
            imagesavealpha($this->image, true);
            imagecopyresampled($targetimg,$this->image,$bx,$by,$ex,$ey,$src_width,$src_height,$src_width,$src_height);
            imagedestroy($this->image);
            $this->image=$targetimg;

        }
        public function crop($x=0, $y=0, $width=null, $height=null)
        {
            $src_width=$this->get_width();   //原图片的宽
            $src_height=$this->get_height(); //原图片的高
            if($width==null){
                $width=$src_width;
                $height=$src_height;
            }
            $targetimg = imagecreatetruecolor($width,$height);
            imagecopyresampled($targetimg,$this->image,0,0,$x,$y,$width,$height,$src_width,$src_height);
            imagedestroy($this->image);
            $this->image=$targetimg;
        }
        /*

        * 更改图像大小

        $fit: 适应大小方式

        'force': 把图片强制变形成 $width X $height 大小

        'scale': 按比例在安全框 $width X $height 内缩放图片, 输出缩放后图像大小 不完全等于 $width X $height

        'scale_fill': 按比例在安全框 $width X $height 内缩放图片，安全框内没有像素的地方填充色, 使用此参数时可设置背景填充色 $bg_color = array(255,255,255)(红,绿,蓝, 透明度) 透明度(0不透明-127完全透明))

        其它: 智能模能 缩放图像并载取图像的中间部分 $width X $height 像素大小

        $fit = 'force','scale','scale_fill' 时： 输出完整图像

        $fit = 图像方位值 时, 输出指定位置部分图像

        字母与图像的对应关系如下:



        north_west   north   north_east

        west         center        east

        south_west   south   south_east

        */

        public function resize_to($width = 100, $height = 100, $fit = 'center', $fill_color = array(255,255,255,0) )
        {

            switch($fit)
            {
                case 'force':
                    $src_width=$this->get_width();   //原图片的宽
                    $src_height=$this->get_height(); //原图片的高
                    $targetimg = imagecreatetruecolor($width, $height);
                    imagecopyresampled($targetimg,$this->image,0,0,0,0,$width,$height,$src_width,$src_height);
                    imagedestroy($this->image);
                    $this->image=$targetimg;
                    break;
                case 'scale':
                    $src_width=$this->get_width();   //原图片的宽
                    $src_height=$this->get_height(); //原图片的高
                    $dst_width = $width;
                    $dst_height = $height;
                    if($src_width*$height > $src_height*$width)
                    {
                        $dst_height = intval($width*$src_height/$src_width);
                    }
                    else
                    {
                        $dst_width = intval($height*$src_width/$src_height);
                    }
                    $targetimg = imagecreatetruecolor($dst_width, $dst_height);
                    imagecopyresampled($targetimg,$this->image,0,0,0,0,$dst_width,$dst_height,$src_width,$src_height);
                    imagedestroy($this->image);
                    $this->image=$targetimg;
                    break;
                case 'scale_fill':
                    $src_width=$this->get_width();   //原图片的宽
                    $src_height=$this->get_height(); //原图片的高
                    $x = 0;
                    $y = 0;
                    $dst_width = $width;
                    $dst_height = $height;
                    if($src_width*$height > $src_height*$width)
                    {
                        $dst_height = intval($width*$src_height/$src_width);
                        $y = intval( ($height-$dst_height)/2 );
                    }
                    else
                    {
                        $dst_width = intval($height*$src_width/$src_height);
                        $x = intval( ($width-$dst_width)/2 );
                    }
                    $targetimg = imagecreatetruecolor($width, $height);
                    imagecolorallocatealpha($targetimg,$fill_color[0],$fill_color[1],$fill_color[2],$fill_color[3]);
                    imagecopyresampled($targetimg,$this->image,$x,$y,0,0,$dst_width,$dst_height,$src_width,$src_height);
                    imagedestroy($this->image);
                    $this->image=$targetimg;
                    break;
                default:
                    $src_width=$this->get_width();   //原图片的宽
                    $src_height=$this->get_height(); //原图片的高
                    $crop_x = 0;
                    $crop_y = 0;

                    $crop_w = $src_width;
                    $crop_h = $src_height;
                    if($src_width*$height > $src_height*$width)
                    {
                        $crop_w = intval($src_height*$width/$height);
                    }
                    else
                    {
                        $crop_h = intval($src_width*$height/$width);

                    }
                    switch($fit)
                    {
                        case 'north_west':
                            $crop_x = 0;
                            $crop_y = 0;
                            break;
                        case 'north':
                            $crop_x = intval( ($src_width-$crop_w)/2 );
                            $crop_y = 0;
                            break;
                        case 'north_east':
                            $crop_x = $src_width-$crop_w;
                            $crop_y = 0;
                            break;
                        case 'west':
                            $crop_x = 0;
                            $crop_y = intval( ($src_height-$crop_h)/2 );
                            break;
                        case 'center':
                            $crop_x = intval( ($src_width-$crop_w)/2 );
                            $crop_y = intval( ($src_height-$crop_h)/2 );
                            break;
                        case 'east':
                            $crop_x = $src_width-$crop_w;
                            $crop_y = intval( ($src_height-$crop_h)/2 );
                            break;
                        case 'south_west':
                            $crop_x = 0;
                            $crop_y = $src_height-$crop_h;
                            break;
                        case 'south':
                            $crop_x = intval( ($src_width-$crop_w)/2 );
                            $crop_y = $src_height-$crop_h;
                            break;
                        case 'south_east':
                            $crop_x = $src_width-$crop_w;
                            $crop_y = $src_height-$crop_h;
                            break;
                        default:
                            $crop_x = intval( ($src_width-$crop_w)/2 );
                            $crop_y = intval( ($src_height-$crop_h)/2 );
                    }
                    $targetimg = imagecreatetruecolor($width,$height);
                    imagecopyresampled($targetimg,$this->image,0,0,$crop_x,$crop_y,$width,$height,$crop_w,$crop_h);
                    imagedestroy($this->image);
                    $this->image=$targetimg;

            }

        }
        // 添加水印图片
        public function add_watermark($path, $x = 0, $y = 0)
        {
            if(!file_exists($path)){return false;}

            list($w,$h,$type)=getimagesize($path);
            switch($type){
                case 1:
                    $waterimg=imagecreatefromgif($path);
                    break;
                case 2:
                    $waterimg=imagecreatefromjpeg($path);
                    break;
                case 3:
                    $waterimg=imagecreatefrompng($path);
                    break;
                default:
                    $waterimg=imagecreatefromjpeg($path);
                    break;
            }
            $width=$this->get_width(); //原图片宽度
            $height=$this->get_height(); //原图片高度
            if($width<$w||$height<$h){
                return;
            }
            imagealphablending($this->image,true);
            imagecopy($this->image,$waterimg,$x,$y,0,0,$w,$h);
            imagedestroy($waterimg);

        }
        // 添加水印文字
        public function add_text($text, $x = 0 , $y = 0, $angle=0, $style=array())
        {
            $width=$this->get_width();
            $height=$this->get_height();

            $temp=imagettfbbox(ceil($style['font_size']*5),0,"./cour.ttf",$text);
            $w=$temp[2]-$temp[6];
            $h=$temp[3]-$temp[7];
            unset($temp);
            if($width<$w||$height<$h){
                return;
            }
            imagealphablending($this->image,true);
            if(!empty($style['fill_color'])&&strlen($style['fill_color'])==7){
                $R=hexdec(substr($style['fill_color'],1,2));
                $G=hexdec(substr($style['fill_color'],3,2));
                $B=hexdec(substr($style['fill_color'],5));
            }
            imagestring($this->image,$style['font_size'],$x,$y,$text,imagecolorallocate($this->image,$R,$G,$B));

        }
        // 保存到指定路径
        public function save_to( $path )
        {
            switch($this->type){
                case 'jpg':
                    imagejpeg($this->image,$path);
                    break;
                case 'gif':
                    imagegif($this->image,$path);
                    break;
                case 'png':
                    imagepng($this->image,$path);
                    break;
            }

        }
        // 输出图像
        public function output($header = true)
        {

            switch($this->type){
                case 'jpg':
                    header('Content-type: image/jpeg');
                    imagejpeg($this->image);
                    break;
                case 'gif':
                    header('Content-type: image/gif');
                    imagegif($this->image);
                    break;
                case 'png':
                    header('Content-type: image/png');
                    imagepng($this->image);
                    break;
            }

        }
        public function get_width()
        {
            return imagesx($this->image);
        }
        public function get_height()
        {
            return imagesy($this->image);
        }
        // 设置图像类型， 默认与源类型一致
        public function set_type( $type='png' )
        {

        }
        // 获取源图像类型

        public function get_type()
        {
            return $this->type;
        }

        // 当前对象是否为图片
        public function is_image()
        {
            if( $this->image )
                return true;
            else
                return false;

        }
        public function thumbnail($width = 100, $height = 100, $fit = true){ }
        // 生成缩略图 $fit为真时将保持比例并在安全框 $width X $height 内生成缩略图片
        /*

        添加一个边框

        $width: 左右边框宽度

        $height: 上下边框宽度

        $color: 颜色: RGB 颜色 'rgb(255,0,0)' 或 16进制颜色 '#FF0000' 或颜色单词 'white'/'red'...

        */
        public function border($width, $height, $color='rgb(220, 220, 220)')
        {
        }
        public function blur($radius, $sigma){} // 模糊
        public function gaussian_blur($radius, $sigma){} // 高斯模糊
        public function motion_blur($radius, $sigma, $angle){} // 运动模糊
        public function radial_blur($radius){} // 径向模糊
        public function add_noise($type=null){} // 添加噪点
        public function level($black_point, $gamma, $white_point){} // 调整色阶
        public function modulate($brightness, $saturation, $hue){} // 调整亮度、饱和度、色调
        public function charcoal($radius, $sigma){} // 素描
        public function oil_paint($radius){} // 油画效果
        public function flop(){} // 水平翻转
        public function flip(){} // 垂直翻转


}
