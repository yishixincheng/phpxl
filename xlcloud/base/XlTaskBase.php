<?php

namespace xl\base;


/**
 * Class XlTaskBase
 * @package xl\base
 * 任务节点抽象基类
 */
abstract class XlTaskBase extends XlMvcBase
{

    private $_taskParam=null;
    private $_params=null;
    private $_nextTaskParam=null;

    public function __construct(){
        parent::__construct();
    }

    public function enter($taskParam){
        $this->_taskParam=$taskParam;
        if($taskParam){
            $this->_params=isset($taskParam['params'])?$taskParam['params']:[];
        }
    }

    abstract public function run($params); //抽象方法由子类实现，必须实现

    public function getParams(){
        return $this->_params;
    }
    public function next($rt){

        if(!is_array($rt)){
            throw new XlException("next参数错误！");
        }
        if(!isset($rt['result'])){
            $rt['result']=null;
        }
        $result=$rt['result'];
        $isbreak=false;
        if(is_array($rt)){
            if($rt['__']==1){
                if($rt['status']=="fail"){
                    $isbreak=true;
                }
                if($rt['result']===null){
                    unset($rt['result']);
                }
                $result=$rt;
            }
        }
        $this->_nextTaskParam=[
            'params'=>$rt['params']?:null,
            'result'=>$result,
            'isbreak'=>$isbreak?:null
        ];
    }
    public function leave(){
        if(empty($this->_nextTaskParam)){
            throw new XlException("任务未调用next方法");
        }
        return $this->_nextTaskParam;
    }

}