<?php

namespace xl\classs;

use xl\base\XlClassBase;

class DownloadClass extends XlClassBase {

    private $_filepath='';
    private $_exts=[];
    private $_savefilename='';
    private $_memorylimit='1024M';
    private $_isabs=false;
    public function __construct()
    {
        parent::__construct();
    }
    public function setFilePath($filepath,$isabs=false){
        if(strncmp($filepath,'/',1)!=0){
            $filepath='/'.$filepath;
        }
        $this->_filepath=$filepath;
        $this->_isabs=$isabs;
    }
    public function getFilePath(){
        if($this->_isabs){
            return $this->_filepath;
        }
        return DOC_ROOT.$this->_filepath;
    }
    public function setSaveFileName($filename){

        //设置保存的文件名
        $this->_savefilename=$filename;
    }
    public function getSaveFileName(){

        if($this->_savefilename){
            return $this->_savefilename;
        }
        $filearr=explode('/',$this->_filepath);
        $filename=array_pop($filearr);

        return $filename;

    }
    public function setPromiseExt($ext){

        //设置扩展名
        if(is_array($ext)){
            $this->_exts=$ext;
        }else{
            $this->_exts[]=$ext;
        }

    }
    public function setMemoryLimit($ml){
        $this->_memorylimit=$ml;
    }

    public function exec(){

        //执行
        set_time_limit(0);
        ini_set('memory_limit',$this->_memorylimit);
        $filepath=$this->getFilePath();
        $fileext=$this->_getExt($filepath);
        if($this->_exts){
            if(!in_array($fileext,$this->_exts)){
                return ['status'=>'fail','msg'=>'不支持文件格式'];
            }
        }
        if(!is_file($filepath)){
            return ['status'=>'fail','msg'=>'文件不存在'];
        }
        $filesize=filesize($filepath); //文件大小
        //输出头部，准备下载
        header("Cache-Control:public");
        $mime=$this->_getMime($filepath);
        //设置输出浏览器格式
        header("Content-Type:$mime");
        header("Content-Disposition:attachment;filename=".$this->getSaveFileName());
        $seek=0;
        $size=$filesize;
        //支持断点续传
        header("Accept-Ranges:bytes");
        if(isset($_SERVER['HTTP_RANGE'])) {

            $ranges=$this->_getRange($filesize);
            $surlen=$ranges['end']-$ranges['start'];
            $seek=$ranges['start'];
            $size=$surlen;

        }else{
            //一次链接
            header("Content-Range:bytes 0-$size/$filesize");
            header("Content-Length:".$size);
        }

        //打开文件
        $fp = fopen($filepath,"rb+");
        fseek($fp,$seek);
        $_seeksize=$size;
        $_linesize=1024*8;

        while(!feof($fp)){
            //设置文件最长执行时间

            if($_seeksize<=0){
                break;
            }
            if($_seeksize<=$_linesize){
                print(fread($fp,$_seeksize));
                flush(); //输出缓冲
                ob_flush();
                break;
            }else{
                print(fread($fp,$_linesize));
                $_seeksize-=$_linesize;
                flush(); //输出缓冲
                ob_flush();
            }
        }

        fclose($fp);

        exit;

    }
    private function _getRange($file_size){

        $range = $_SERVER['HTTP_RANGE'];
        $range = explode('-', $range);
        if(count($range)<2) {
            $range[1] =$file_size;
        }
        if($range[1]>$file_size){
            $range[1]=$file_size;
        }
        $range = array_combine(array('start','end'), $range);
        if(empty($range['start'])){
            $range['start'] = 0;
        }
        if(empty($range['end'])){
            $range['end'] = $file_size;
        }
        return $range;

    }
    private function _getExt($file){

        //获取文件的扩展名
        return  strtolower(trim(substr(strrchr($file, '.'), 1, 10)));
    }
    private function _getMime($file) {

        $ext = $this->_getExt($file);
        if($ext == '') return '';
        $mime_types = array (
            'acx' => 'application/internet-property-stream',
            'ai' => 'application/postscript',
            'aif' => 'audio/x-aiff',
            'aifc' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff',
            'asp' => 'text/plain',
            'aspx' => 'text/plain',
            'asf' => 'video/x-ms-asf',
            'asr' => 'video/x-ms-asf',
            'asx' => 'video/x-ms-asf',
            'au' => 'audio/basic',
            'avi' => 'video/x-msvideo',
            'axs' => 'application/olescript',
            'bas' => 'text/plain',
            'bcpio' => 'application/x-bcpio',
            'bin' => 'application/octet-stream',
            'bmp' => 'image/bmp',
            'c' => 'text/plain',
            'cat' => 'application/vnd.ms-pkiseccat',
            'cdf' => 'application/x-cdf',
            'cer' => 'application/x-x509-ca-cert',
            'class' => 'application/octet-stream',
            'clp' => 'application/x-msclip',
            'cmx' => 'image/x-cmx',
            'cod' => 'image/cis-cod',
            'cpio' => 'application/x-cpio',
            'crd' => 'application/x-mscardfile',
            'crl' => 'application/pkix-crl',
            'crt' => 'application/x-x509-ca-cert',
            'csh' => 'application/x-csh',
            'css' => 'text/css',
            'dcr' => 'application/x-director',
            'der' => 'application/x-x509-ca-cert',
            'dir' => 'application/x-director',
            'dll' => 'application/x-msdownload',
            'dms' => 'application/octet-stream',
            'doc' => 'application/msword',
            'dot' => 'application/msword',
            'dvi' => 'application/x-dvi',
            'dxr' => 'application/x-director',
            'eps' => 'application/postscript',
            'etx' => 'text/x-setext',
            'evy' => 'application/envoy',
            'exe' => 'application/octet-stream',
            'fif' => 'application/fractals',
            'flr' => 'x-world/x-vrml',
            'flv' => 'video/x-flv',
            'gif' => 'image/gif',
            'gtar' => 'application/x-gtar',
            'gz' => 'application/x-gzip',
            'h' => 'text/plain',
            'hdf' => 'application/x-hdf',
            'hlp' => 'application/winhlp',
            'hqx' => 'application/mac-binhex40',
            'hta' => 'application/hta',
            'htc' => 'text/x-component',
            'htm' => 'text/html',
            'html' => 'text/html',
            'htt' => 'text/webviewhtml',
            'ico' => 'image/x-icon',
            'ief' => 'image/ief',
            'iii' => 'application/x-iphone',
            'ins' => 'application/x-internet-signup',
            'isp' => 'application/x-internet-signup',
            'jfif' => 'image/pipeg',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'application/x-javascript',
            'latex' => 'application/x-latex',
            'lha' => 'application/octet-stream',
            'lsf' => 'video/x-la-asf',
            'lsx' => 'video/x-la-asf',
            'lzh' => 'application/octet-stream',
            'm13' => 'application/x-msmediaview',
            'm14' => 'application/x-msmediaview',
            'm3u' => 'audio/x-mpegurl',
            'man' => 'application/x-troff-man',
            'mdb' => 'application/x-msaccess',
            'me' => 'application/x-troff-me',
            'mht' => 'message/rfc822',
            'mhtml' => 'message/rfc822',
            'mid' => 'audio/mid',
            'mny' => 'application/x-msmoney',
            'mov' => 'video/quicktime',
            'movie' => 'video/x-sgi-movie',
            'mp2' => 'video/mpeg',
            'mp3' => 'audio/mpeg',
            'mpa' => 'video/mpeg',
            'mpe' => 'video/mpeg',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpp' => 'application/vnd.ms-project',
            'mpv2' => 'video/mpeg',
            'ms' => 'application/x-troff-ms',
            'mvb' => 'application/x-msmediaview',
            'nws' => 'message/rfc822',
            'oda' => 'application/oda',
            'p10' => 'application/pkcs10',
            'p12' => 'application/x-pkcs12',
            'p7b' => 'application/x-pkcs7-certificates',
            'p7c' => 'application/x-pkcs7-mime',
            'p7m' => 'application/x-pkcs7-mime',
            'p7r' => 'application/x-pkcs7-certreqresp',
            'p7s' => 'application/x-pkcs7-signature',
            'pbm' => 'image/x-portable-bitmap',
            'pdf' => 'application/pdf',
            'pfx' => 'application/x-pkcs12',
            'pgm' => 'image/x-portable-graymap',
            'php' => 'text/plain',
            'pko' => 'application/ynd.ms-pkipko',
            'pma' => 'application/x-perfmon',
            'pmc' => 'application/x-perfmon',
            'pml' => 'application/x-perfmon',
            'pmr' => 'application/x-perfmon',
            'pmw' => 'application/x-perfmon',
            'png' => 'image/png',
            'pnm' => 'image/x-portable-anymap',
            'pot,' => 'application/vnd.ms-powerpoint',
            'ppm' => 'image/x-portable-pixmap',
            'pps' => 'application/vnd.ms-powerpoint',
            'ppt' => 'application/vnd.ms-powerpoint',
            'prf' => 'application/pics-rules',
            'ps' => 'application/postscript',
            'pub' => 'application/x-mspublisher',
            'qt' => 'video/quicktime',
            'ra' => 'audio/x-pn-realaudio',
            'ram' => 'audio/x-pn-realaudio',
            'ras' => 'image/x-cmu-raster',
            'rgb' => 'image/x-rgb',
            'rmi' => 'audio/mid',
            'roff' => 'application/x-troff',
            'rtf' => 'application/rtf',
            'rtx' => 'text/richtext',
            'scd' => 'application/x-msschedule',
            'sct' => 'text/scriptlet',
            'setpay' => 'application/set-payment-initiation',
            'setreg' => 'application/set-registration-initiation',
            'sh' => 'application/x-sh',
            'shar' => 'application/x-shar',
            'sit' => 'application/x-stuffit',
            'snd' => 'audio/basic',
            'spc' => 'application/x-pkcs7-certificates',
            'spl' => 'application/futuresplash',
            'src' => 'application/x-wais-source',
            'sst' => 'application/vnd.ms-pkicertstore',
            'stl' => 'application/vnd.ms-pkistl',
            'stm' => 'text/html',
            'svg' => 'image/svg+xml',
            'sv4cpio' => 'application/x-sv4cpio',
            'sv4crc' => 'application/x-sv4crc',
            'swf' => 'application/x-shockwave-flash',
            't' => 'application/x-troff',
            'tar' => 'application/x-tar',
            'tcl' => 'application/x-tcl',
            'tex' => 'application/x-tex',
            'texi' => 'application/x-texinfo',
            'texinfo' => 'application/x-texinfo',
            'tgz' => 'application/x-compressed',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'tr' => 'application/x-troff',
            'trm' => 'application/x-msterminal',
            'tsv' => 'text/tab-separated-values',
            'txt' => 'text/plain',
            'uls' => 'text/iuls',
            'ustar' => 'application/x-ustar',
            'vcf' => 'text/x-vcard',
            'vrml' => 'x-world/x-vrml',
            'wav' => 'audio/x-wav',
            'wcm' => 'application/vnd.ms-works',
            'wdb' => 'application/vnd.ms-works',
            'wks' => 'application/vnd.ms-works',
            'wmf' => 'application/x-msmetafile',
            'wmv' => 'video/x-ms-wmv',
            'wps' => 'application/vnd.ms-works',
            'wri' => 'application/x-mswrite',
            'wrl' => 'x-world/x-vrml',
            'wrz' => 'x-world/x-vrml',
            'xaf' => 'x-world/x-vrml',
            'xbm' => 'image/x-xbitmap',
            'xla' => 'application/vnd.ms-excel',
            'xlc' => 'application/vnd.ms-excel',
            'xlm' => 'application/vnd.ms-excel',
            'xls' => 'application/vnd.ms-excel',
            'xlt' => 'application/vnd.ms-excel',
            'xlw' => 'application/vnd.ms-excel',
            'xof' => 'x-world/x-vrml',
            'xpm' => 'image/x-xpixmap',
            'xwd' => 'image/x-xwindowdump',
            'z' => 'application/x-compress',
            'zip' => 'application/zip',
        );
        return isset($mime_types[$ext]) ? $mime_types[$ext] : '';
    }



}