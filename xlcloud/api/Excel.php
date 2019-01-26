<?php

namespace xl\api;

final class Excel extends XlApiBase{

    protected $iapiname=""; //内部接口方法的名
    protected $oper="";  //导入还是导出
    protected $postparams=[]; //传递的参数

    public function run(){

        if($this->oper=="import"){
            //导入
            return $this->import();
        }else{
            //导出
            return $this->export();
        }

    }
    /**
     * 导入
     */
    public function import(){

        $iapiObj=$this->getIapiObject();

        $rt=sysclass("opexcel")->uploadGetData();
        if($rt['status']=="fail"){
            return $rt;
        }
        $datalist=$rt['result'];
        $config=$iapiObj->getImConfig();
        $datalist=$this->_getformatdatalist($datalist,$config);
        $rt=$iapiObj->import($this->postparams,$datalist);

        return $rt;

    }

    /**
     * 导出
     */
    public function export(){

        $iapiObj=$this->getIapiObject();

        $config=$iapiObj->getExConfig(); //导出配置
        $columnmap=$this->_getdealcolumnmap([],$config['columnmap']);
        $rt=$iapiObj->export($this->postparams);
        if($rt['title']){
            $config['title']=$rt['title'];
        }
        if($rt['columnmap']){
            $columnmap=$this->_getdealcolumnmap([],$rt['columnmap']);
        }
        if($rt['status']=="fail"){
            return $rt;
        }
        $datalist=$rt['datalist'];
        $this->exportDataToExcel($datalist, $columnmap, $config['title']); //导出

        return ['status'=>'success'];

    }

    //根据接口获取iapi对象
    private function getIapiObject(){

        $methodname=$this->iapiname;
        $ns=null;
        if(($pos=strpos($methodname,":"))===false){
            //全局方法
            $ns=defined("ROOT_NS")?ROOT_NS:'';
            $isplugin=false;
        }else{
            //插件
            $ns=substr($methodname,0,$pos);
            $methodname=substr($methodname,$pos+1);
            $isplugin=true;

        }

        $methodname=trim($methodname);
        if(strpos($methodname,".")===false){
            $methodname=ucfirst($methodname);
        }
        $cls=$ns."\\iapi\\".str_replace(".","\\",$methodname)."Iapi";

        $obj=\xl\XlLead::$factroy->bind("properties",['_Isplugin'=>$isplugin,'_Ns'=>$isplugin?$ns:null])->getInstance($cls);

        return $obj;

    }

    private function _getdealcolumnmap($columns,$columnmap){
        if($columns){
            if(is_string($columns)){
                $columns=explode(',',$columns);
            }
            if(is_array($columns)){
                foreach($columnmap as $index=>$cn){
                    if(!in_array($cn['key'],$columns)){
                        unset($columnmap[$index]);
                    }
                }
                $columnmap=array_values($columnmap);
            }
        }
        return $columnmap;
    }

    private function _getformatdatalist($datalist,$config){
        $row=$config['start'][0];
        $column=$config['start'][1];
        $row=$row?:0;
        $column=$column?:0;
        $columnmap=$config['columnmap'];
        $rownum=count($datalist);
        $flist=[];
        for($i=$row; $i<$rownum; $i++){
            $dl=$datalist[$i]; //每一列都是二维数组
            if(is_array($dl)){
                $columnnum=count($dl);
                $fdl=[];
                for($j=$column; $j<$columnnum; $j++){
                    if($columnmap[$j] && is_array($columnmap[$j])){
                        $key=$columnmap[$j]['key'];
                        $fdl[$key]=trim($dl[$j]);
                    }
                }
                $flist[]=$fdl;
            }
        }
        return $flist;
    }

    public function exportDataToExcel($datalist,$columnmap,$title){

        $abcindex=[
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL',
            'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'
        ];
        $header=[];
        $list=[];
        $abcmap=[];
        foreach($columnmap as $k=>$v){
            $header[$abcindex[$k]]=$v;
            $abcmap[$v['key']]=$abcindex[$k];
        }
        foreach($datalist as $dl){
            if(is_array($dl)){
                $listnode=[];
                foreach($dl as $key=>$val){
                    if($abcmap[$key]){
                        $listnode[$abcmap[$key]]=$val;
                    }
                }
                $list[]=$listnode;
            }
        }
        sysclass("opexcel")->exportExcel(['header'=>$header, 'list'=>$list, 'title'=>$title]);
    }

}