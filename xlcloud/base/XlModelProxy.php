<?php

namespace xl\base;

/**
 * Class XlModelProxy
 * @package xl\base
 * 代理兼容单库版模式
 */

final class XlModelProxy
{

    private $_modelname;
    private $_config;
    //数据库配置
    public function __construct($modelname, $config = null)
    {
        $this->_modelname=$modelname;
        $this->_config=$config;
    }

    public function getModelObject(){

        if($this->_Isplugin){
            //插件模式
            $model_path=PLUGIN_PATH.$this->_Ns.D_S."model".D_S;
        }else{
            $model_path=MODEL_PATH;
        }
        $modelname='';
        if(($pos=strrpos($this->_modelname,'.'))){
            $folder=substr($this->_modelname,0,$pos);
            $folder=str_replace(".",D_S,$folder);
            $modelname=substr($this->_modelname,$pos+1);
            $classname=ucfirst($modelname).'Model';
            $path=$model_path.$folder.D_S.$classname.'.php'; //文件路径
            if(!is_file($path)){
                $path=false;
            }
        }else{
            $classname=ucfirst($this->_modelname).'Model';
            $path=findfile($model_path,$classname.'.php');
        }
        if(!$path){
            throw new XlException($classname." file is not exist!");
        }
        //包含文件
        include_once($path);
        $model = new $classname; //实例化Model
        if (!$model) {
            throw new XlException($classname . " is not defined!");
        }

        //路由驱动
        $dbhostconf=sysclass("globalconf")->getDbHostConf($model->database); //
        if(!$dbhostconf){
            throw new XlException("database host no found!");
        }

        $driver=$dbhostconf['masterhost']['driver'];


        if($driver=="sqlsrv"){

            return $this->factory->bind("properties",['_Isplugin'=>$this->_Isplugin,'_Ns'=>$this->_Ns])->bind("construct_args",[$this->_modelname,$this->_config,$model,$modelname,$dbhostconf])->getInstance("xl\\base\\db\\XlSqlsrvModelFactory");

        }else{

            return $this->factory->bind("properties",['_Isplugin'=>$this->_Isplugin,'_Ns'=>$this->_Ns])->bind("construct_args",[$this->_modelname,$this->_config,$model,$modelname,$dbhostconf])->getInstance("xl\\base\\db\\XlMysqlModelFactory");

        }



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