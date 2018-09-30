<?php

namespace xl\classs;

use xl\util\XlUAnnotationCleaner;
use xl\base\XlClassBase;


class JsonClass extends XlClassBase {

    public function __construct()
    {
        $opfilecls=sysclass("opfile",0);

        $this->_ifile=new $opfilecls;
    }
    public function setParam($filepath,$islocal=true){

        $this->_ifile->setParam($filepath,$islocal);
    }
    public function getArray(){
        $jsonstr=$this->_ifile->Read(true);

        if(empty($jsonstr)){
            return null;
        }
        $jsonstr=XlUAnnotationCleaner::clean($jsonstr);
        $jsonstr=trim($jsonstr);
        $jsonstr=preg_replace("/(\r)|(\n)|(\t)/","",$jsonstr);

        $jsonstr=str_replace('\\','\\\\',$jsonstr);
        return json_decode($jsonstr, true);
    }
    public function read($filepath,$islocal=true){

        $this->setParam($filepath,$islocal);
        return $this->getArray();
    }

}