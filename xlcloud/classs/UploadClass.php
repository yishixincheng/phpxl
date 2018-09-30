<?php

namespace xl\classs;

use xl\base\XlClassBase;

class UploadClass extends XlClassBase {


    /**
     * 注入参数
     */
    public $picroot; //根
    public $maxsize; //最大尺寸
    public $imgcode; //文件编码名
    public $childpath; //子路径
    public $pictype; //类型
    public $filename;  //上传的文件名


    public function getPicRoot(){

        return $this->picroot?:PIC_PATH;

    }

    public function getMaxSize(){

        if(empty($this->maxsize)){
            return 50000000;
        }
        if(is_numeric($this->maxsize)){
            return $this->maxsize;
        }
        $dw=strtoupper(substr($this->maxsize,-1,1));

        $size=substr($this->maxsize,0,-1);

        if(!preg_match("/^\d+$/",$size)){
            return 50000000;
        }

        switch($dw){

            case 'G':
                $size=$size*1000000000;
                break;
            case 'M':
                $size=$size*1000000;
                break;
            case 'K':
                $size=$size*1000;
                break;

        }

        return $size;


    }

    public function getImgcode(){

        if($this->imgcode){
            return $this->imgcode;
        }
        $cls=sysclass('idhash',0);
        $imgcode=$cls::createImgCode(); //创建imgcode

        return $imgcode;

    }

    public function getChildPath(){

        if($this->childpath){
            return $this->childpath;
        }

        return date("Y").D_S.date("m").D_S.mt_rand(0,30).D_S;

    }

    public function getPicType(){

        return $this->pictype?:"pic";

    }

    public function getFileName(){

        return $this->filename?:'dcomfile';
    }

    public function returndotfile($filename)
    {

        //返回文件结尾名
        if(empty($filename)){return '';}
        $farr=explode('.',$filename);
        $filetype=array_pop($farr);
        return $filetype;
    }
    public function mkdirm($path)
    {

        if(is_dir($path)){
           return true;
        }

        return mkdir($path,0777,true);

    }

    /**
     * @param int $limittype
     * @return array
     * 上传图片
     *
     */

    public function save($limittype=0){

        $_filename=$this->getFileName();
        $_pictype=$this->getPicType();
        $_maxsize=$this->getMaxSize();

        $result="";
        if(is_uploaded_file($_FILES[$_filename]['tmp_name']))
        {
            $success=true;
            list($width,$height,$type)=getimagesize($_FILES[$this->filename]['tmp_name']);
            if($type==1)
                $type='.gif';
            else if( $type==2 )
                $type='.jpg';
            else if( $type==3 )
                $type='.png';
            else if($type==4||$type==13)
            {
                $type='.swf';
                $_pictype='flash';
            }
            else
            {
                $result='上传类型不符合！';
                $success=false;
            }
            if($limittype==0)
            {
                //代表上传的是图片
                if($type==".swf")
                {
                    $success=false;
                }
            }
            else if($limittype==1)
            {
                if($type!=".swf")
                {
                    $success=false;
                }
            }
            if($_FILES[$_filename]['size']>$_maxsize)
            {
                $success=false;
            }
            if($success)
            {
                $imgcode=$this->getImgcode();
                $filename=$imgcode.$type;
                $picroot=$this->getPicRoot();  //图片根路径
                $childpath=$this->getChildPath(); //子路径
                $path=$picroot.$childpath;
                $parth=D_S.$childpath;
                $parth=str_replace("\\","/",$parth);
                $picAress=$parth.$filename;//数据库
                $this->mkdirm($path);//创建文件夹
                $upfile=$path.$filename; //全路径

                if(@move_uploaded_file($_FILES[$_filename]['tmp_name'],$upfile))
                {

                    $this->pictype=$_pictype;

                    return ['status'=>'success','result'=>["imgcode"=>$imgcode,
                        "path"=>$parth,
                        'pictype'=>$_pictype, //类型
                        'imgpath'=>$picAress, //存入数据库的地址
                        'abspath'=>$upfile,   //图片的绝对地址
                        "allpath"=>$path,     //图片的路径
                        "width"=>$width,
                        "height"=>$height,
                        "size"=>$_FILES[$_filename]['size']]];
                }
            }

        }
        return array('status'=>'fail','result'=>$result);

    }
    public function delPic($picadress){
        //删除图片
        $picroot=$this->getPicRoot();
        GDelFile($picroot.$picadress);

    }
    public function uploadFromUrl($url){

        //上传远程文件
        $url=trim($url);

        if(!preg_match("/^http.+$/i",$url)){

            //本站路径
            $picAress=$url;
            $picroot=$this->getPicRoot();  //图片根路径
            $upfile=$picroot.$picAress;


        }else{

            $filedot=$this->returndotfile($url);  //获得文件类型
            if(!in_array($filedot,array('gif','jpg','jpeg','png'))){
                $filedot='jpg';
            }
            $type='.'.$filedot;
            $imgcode=$this->getImgcode();
            $filename=$imgcode.$type;
            $picroot=$this->getPicRoot();  //图片根路径
            $childpath=$this->getChildPath(); //子路径
            $path=$picroot.$childpath;
            $parth=D_S.$childpath;
            $parth=str_replace("\\","/",$parth);
            $picAress=$parth.$filename;//数据库
            $this->mkdirm($path);//创建文件夹
            $upfile=$path.$filename; //全路径


            @set_time_limit(24*60*60); //限制最大的执行时间
            $file=@fopen($url,'rb');

            if($file){
                $newf=fopen($upfile,'wb');
                if($newf){
                    while(!feof($file)){
                        fwrite($newf,fread($file,1024*8),1024*8);
                    }
                }
                if($file){
                    fclose($file);
                }
                if($newf){
                    fclose($newf);
                }
            }
        }

        list($width,$height)=getimagesize($upfile);

        return ['status'=>'success','result'=>["imgpath"=>$picAress,"abspath"=>$upfile,"width"=>$width,"height"=>$height,"size"=>@filesize($upfile)]];

    }


}