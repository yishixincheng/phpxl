<?php

namespace xl\api;

//图片上传接口

define("IMGOPTYPE_FORCE",1);
define("IMGOPTYPE_CUT",2);
define("IMGOPTYPE_RCUT",3);
define("IMGOPTYPE_WHCHECK",4);      //宽高校验
define("IMGOPTYPE_WMAXLIMIT",5);    //超过最大宽度则裁切，低于最大宽度则成功
define("IMGOPTYPE_WMINLIMIT",6);    //不能小于最低宽度
define("IMGOPTYPE_MINWH",7);        //不能小于设定的宽高

class Upload extends XlApiBase{

    private $_iimg=null;
    private $_ioppic=null;

    protected $model=null; //imgmodel操作数据表模型
    protected $filename=null;
    protected $optype=null;
    protected $width=null;
    protected $height=null;
    protected $crop=null;
    protected $watermark=null;
    protected $uid=null;


    public function run()
    {

        if(!$this->model){
            throw new \Exception("model参数缺失");
        }
        if(!$this->filename){
            throw new \Exception("filename参数缺失");
        }
        $result=$this->_upload();
        if($result){
            if($result['status']=="fail"){
                static::delPicByAdress($result['picaddress']);
                unset($result['piccode']);
            }else{
                //判断是否需要添加水印
                $crop=$this->crop;
                $watermark=$this->watermark;
                if($crop){
                    $this->_cropByPos($crop, $result);
                }
                if($watermark) {
                    $this->_addWaterMark($watermark, $result);
                }
                $param=[
                    'code'=>$result['imgcode'],
                    'imgpath'=>$result['imgpath'],
                    'imgsrc'=>$result['abspath'],
                    'host'=>$result['host'], 'width'=>$result['width'],'height'=>$result['height'],
                    'uid'=>$this->uid?:0,'time'=>SYS_TIME
                ];
                $this->model->insert($param, false);
            }
        }
        return $result;

    }
    private function _upload(){

        $filename=$this->filename;
        $optype=$this->optype?:0;
        $width=$this->width;
        $height=$this->height;
        /**
         * 要注入的属性
         */
        $imgcode=$this->model->createId(); //创建id
        $host=1; //当前主机号
        $properties=[
            'filename'=>$filename,
            'maxsize'=>'50M',
            'imgcode'=>$imgcode
        ];
        $iimg=sysclass("upload", 1, ["properties"=>$properties]); //调用并注入属性
        $this->_iimg=$iimg;
        $iimg->setparam($filename, '50M', '');
        $picarr=$iimg->save(2);
        $pictype=$iimg->getPictype();
        if($pictype=="flash"){
            $optype=0;
        }
        $status=$picarr['status'];
        $picarr=$picarr['result'];
        if($status=="fail"){
            return $this->ErrorInf("图片大小已经超过限制");
        }
        $picarr['picurl']=static::getPicUrl($picarr['imgpath'], $host); //根据主机获取图片地址
        $picarr['host']=$host;
        //裁切缩放处理
        if($optype==0){
            //无需处理
            return $this->SuccInf("上传成功", $picarr);
        }
        if(!$this->_ioppic){
            $oppiccls=sysclass('oppicfactory', 0);
            $this->_ioppic=$oppiccls::get_instance()->getinterface();
        }

        if($optype==IMGOPTYPE_FORCE){
            //强制压缩
            if(!$width || !$height){
                return $this->ErrorInf("图片宽度或高度尺寸不符合");
            }
            $this->_ioppic->open($picarr['abspath']);
            $swidth=$this->_ioppic->get_width();
            $sheight=$this->_ioppic->get_height();
            if($width==$swidth && $height==$sheight){

            }else{
                $this->_ioppic->resize_to($width, $height,'force');
                $this->_ioppic->save_to($picarr['abspath']);
                list($width,$height)=@getimagesize($picarr['abspath']);
            }
            $picarr['width']=$width;
            $picarr['height']=$height;
            return $this->SuccInf("上传成功", $picarr);
        }
        if($optype==IMGOPTYPE_CUT){
            //适用裁切
            if($picarr['width']<$width){
                return $this->ErrorInf("图片宽度不能小于{$width}像素");
            }
            $this->_ioppic->open($picarr['abspath']);
            $this->_ioppic->resize_to($width, $height,'scale');
            $this->_ioppic->save_to($picarr['abspath']);
            list($width,$height)=@getimagesize($picarr['abspath']);
            $picarr['width']=$width;
            $picarr['height']=$height;
            return $this->SuccInf("上传成功", $picarr);
        }
        if($optype==IMGOPTYPE_RCUT){
            //反比例裁切
            if(!$width||!$height){
                return $this->ErrorInf("请制定盒子宽和高");
            }
            $this->_ioppic->open($picarr['abspath']);
            $this->_ioppic->resize_to($width, $height,'north_west');
            $this->_ioppic->save_to($picarr['abspath']);
            list($width,$height)=@getimagesize($picarr['abspath']);
            $picarr['width']=$width;
            $picarr['height']=$height;
            return $this->SuccInf("上传成功", $picarr);
        }
        if($optype==IMGOPTYPE_WHCHECK){
            if($width&&($width!=$picarr['width'])){
                return $this->ErrorInf("图片宽度与指定大小不相符合");
            }else if($height&&($height!=$picarr['height'])){
                return $this->ErrorInf("图片高度与指定大小不相符合");
            }
            return $this->SuccInf("上传成功", $picarr);
        }

        if($optype==IMGOPTYPE_WMAXLIMIT){
            if($picarr['width']<=$width){
                return $this->SuccInf("上传成功",$picarr);
            }else{
                $this->_ioppic->open($picarr['abspath']);
                $this->_ioppic->resize_to($width, $height,'scale');
                $this->_ioppic->save_to($picarr['abspath']);
                list($width,$height)=@getimagesize($picarr['abspath']);
                $picarr['width']=$width;
                $picarr['height']=$height;
                return $this->SuccInf("上传成功", $picarr);
            }
        }

        if($optype==IMGOPTYPE_WMINLIMIT){
            if($picarr['width']<$width){
                return $this->ErrorInf("宽度不能小于".$width."像素");
            }else{
                if($picarr['width']>2000){
                    $this->_ioppic->open($picarr['abspath']);
                    $this->_ioppic->resize_to(2000, 20000,'scale');
                    $this->_ioppic->save_to($picarr['abspath']);
                    list($width,$height)=@getimagesize($picarr['abspath']);
                    $picarr['width']=$width;
                    $picarr['height']=$height;
                }
                return $this->SuccInf("上传成功", $picarr);
            }
        }else if($optype==IMGOPTYPE_MINWH){
            if($picarr['width']<$width){
                return $this->ErrorInf("宽度不能小于".$width."像素");
            }
            if($picarr['height']<$height){
                return $this->ErrorInf("高度不能小于".$height."像素");
            }
            return $this->SuccInf("上传成功",$picarr);
        }
        return $this->ErrorInf("不能识别的操作类型");

    }

    /**
     * 通过坐标裁切图片
     * @param $crop
     * @param $picdata
     * @return array
     */
    public function _cropByPos($crop,&$picdata){
        if(!is_array($crop) || empty($crop)){
            //未指定水印
            return $this->ErrorInf("裁切参数错误");
        }
        $zoom=$crop['zoom']?:1;
        $x=(int)$crop['x'];
        $y=(int)$crop['y'];
        $cutwidth=(int)$crop['cutwidth']?:$crop['width']; //裁切宽度
        $cutheight=(int)$crop['cutheight']?:$crop['height'];//裁切高度
        $imgpath=$picdata['imgpath'];
        $abspath=($picdata['abspath']?:$picdata['imgsrc'])?:(PIC_PATH.$imgpath);
        if(!$this->_ioppic){
            $oppiccls=sysclass('oppicfactory', 0);
            $this->_ioppic=$oppiccls::get_instance()->getinterface();
        }
        $d_w=(int)($picdata['width']*$zoom);
        $d_h=(int)($picdata['height']*$zoom);
        $this->_ioppic->open($abspath);
        $this->_ioppic->resize_to($d_w, $d_h,'force');
        $this->_ioppic->cropandfill($x, $y, $cutwidth, $cutheight);
        $this->_ioppic->save_to($abspath);
        $picdata['width']=$cutwidth;
        $picdata['height']=$cutheight;
        return $this->SuccInf("裁切成功");
    }

    /**
     * 添加水印
     * @param $watermark
     * @param $picdata
     */
    private function _addWaterMark($watermark, $picdata){
        //添加水印
        if(!$watermark || !$picdata){
            return;
        }
        if(!$this->_ioppic){
            $oppiccls=sysclass('oppicfactory', 0);
            $this->_ioppic=$oppiccls::get_instance()->getinterface();
        }
        if(is_string($watermark)){
            $watermark=config("system/".$watermark);
        }
        if(!is_array($watermark) || empty($watermark)){
            //未指定水印
            return;
        }
        $watermark_abspath=DOC_ROOT.$watermark['imgpath'];
        $abspath=$picdata['abspath']; //全路径
        $this->_ioppic->open($abspath);
        if(!$watermark['pos']){
            $x=($picdata['width']-$watermark['width'])/2;
            $y=($picdata['height']-$watermark['height'])/2;
        }else{
            $x=$watermark['x']?:0;
            $y=$watermark['y']?:0;
        }
        $this->_ioppic->add_watermark($watermark_abspath, $x, $y);
        $this->_ioppic->save_to($abspath); //保存图片
    }

    public static function getPicUrl($picadress,$host=1){
        $picurl=config("system/pichosturl");
        if($picurl){
            $picurl=preg_replace("/{{host}}/",$host,$picurl);
        }else{
            $picurl='/pic';
        }
        $picurl.=$picadress;
        $picurl=str_replace("//", "/", $picurl);
        return $picurl;
    }

    public static function delPicByAdress($picaddress){
        if(empty($picaddress)){
            return;
        }
        $iimg=sysclass("upload");
        $iimg->delPic($picaddress);
    }

}