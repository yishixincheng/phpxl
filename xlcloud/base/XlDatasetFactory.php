<?php

namespace xl\base;

/**
 * Class XlDatasetFactory
 * @package xl\base
 * 不能继承，Dataset工厂类
 */

final class XlDatasetFactory{

    private $_dataset=null;
    private $_fields=null;
    private $_datalist=null;
    private $_plugin_dateset_path=null;

    public function __construct($dsname,$config=null)
    {
        if($this->_Isplugin){
            $this->_plugin_dateset_path=PLUGIN_PATH.$this->_Ns.D_S."dataset".D_S;
        }

        $this->parseDatasetName($dsname,$config);
    }
    public function parseDatasetName($dsname,$config=null){

        //只支持2层目录
        if(($pos=strpos($dsname,'.'))){
            $folder=substr($dsname,0,$pos);
            $dsname=substr($dsname,$pos+1);
            $classname=ucfirst($dsname).'Ds';

            if($this->_plugin_dateset_path){
                $path=$this->_plugin_dateset_path.$folder.D_S.$classname.'.php'; //文件路径
            }

            if(!isset($path)||!is_file($path)){
                $path=DATASET_PATH.$folder.D_S.$classname.'.php'; //文件路径
                if(!is_file($path)){
                    $path=false;
                }
            }

        }else{
            //查找
            $classname=ucfirst($dsname).'Ds';
            if($this->_plugin_dateset_path){
                $path=findfile($this->_plugin_dateset_path,$classname.'.php');
            }
            if(!isset($path)||!$path){
                $path=findfile(DATASET_PATH,$classname.'.php');
            }
        }
        if(!$path){
            throw new XlException($classname." file is not exist!");
        }
        //包含文件
        include($path);
        $this->_dataset = new $classname; //实例化Model
        if (!$this->_dataset) {
            throw new XlException($classname . " is not defined");
        }

        $this->_fields=$this->_dataset->fields?:['id'=>['type'=>'int','name'=>'ID'],'name'=>['type'=>'varchar','name'=>'名称']];
        $this->_datalist=$this->_dataset->datalist?:[];

        $this->_adJustData();


    }
    private function _adJustData(){
        //规范化数据
        $dd=array();$i=0;
        foreach($this->_fields as $k=>$_ds){

            foreach($this->_datalist as $index=>$_dd){

                if(!is_array($dd[$index])){
                    $dd[$index]=[];
                }
                if(isset($_dd[$k])){
                    $dd[$index][$k]=$_dd[$k];
                }else{
                    $dd[$index][$k]=$_dd[$i];
                }
            }
            $i++;
        }
        $this->_datalist=$dd;
    }
    public function getDataList(){

        //获取列表
        return $this->_datalist;

    }
    public function appendGetDataList($attach,$islast=false,$dim=1){

        $datalist=$this->_datalist;
        if($islast){
            if($dim==1){
                array_push($datalist,$attach);
            }else{
                $datalist=array_merge($datalist,$attach);
            }
        }else{

            if($dim==1){
                array_unshift($datalist,$attach);
            }else{
                $datalist=array_merge($attach,$datalist);
            }

        }

        return $datalist;

    }
    public function getData($id,$key="id"){

        $datas=$this->getDataList();
        return getfromarraysbysomekeyvalue($datas,$key,$id);

    }
    public function getNameById($id){

        //根据id获得对应的名字
        $data=$this->getData($id);

        if($data){
            return $data['name']?:'';
        }
        return '';

    }
    public function getIdByName($name){

        $data=$this->getData($name,"name");

        if($data){
            return $data['id']?:0;
        }

        return 0;
    }
    public function order($c,$o=1){
        //c代表按照某一个列进行排序
        //o=1代表降序，2代表升序
        $i=0;
        $os = $index = array();
        foreach ($this->_datalist as $dd) {
            $os[] = $dd[$c];
            $index[]=$i++;
        }
        array_multisort($os,$o==1?SORT_DESC:SORT_ASC,$index,SORT_ASC,$this->_datalist);
    }


    /**
     * 注入工厂实例
     * @inject("xlinjector")
     */
    protected $factory;

    /**
     * 注入的变量
     */
    public $_Ns;
    public $_Isplugin;

}