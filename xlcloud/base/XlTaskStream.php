<?php

namespace xl\base;

/**
 * Class XlAffairStream
 * @package xl\base
 * 业务链条
 *
 * taskParam,数据结构 ['params'=>'','result'=>'',isbreak=>false,'index'=>,'stepnum'=>]
 *
 */

class XlTaskStream extends XlMvcBase{

    private $_startParam=null;
    private $_name=null; //业务名称
    private $_tasklist=[];
    private $_tasknum=0;

    public function __construct($name="",$param=null)
    {
        $this->_startParam=$param;
        $this->_name=$name;
    }

    public function reInit(){
        $this->_tasklist=[];
        $this->_tasknum=0;
    }

    public function task($taskname,$param=null,$issingleton=true){

        //业务流程-任务节点
        $this->_tasklist[]=['type'=>1,'taskname'=>$taskname,'param'=>$param,'issingleton'=>$issingleton];
        $this->_tasknum++;
        return $this;
    }
    public function filter($callback){

        $this->_tasklist[]=['type'=>2,'method'=>'filter','callback'=>$callback];
        return $this;

    }
    public function map($callback){

        $this->_tasklist[]=['type'=>2,'method'=>'map','callback'=>$callback];
        return $this;

    }
    public function done(){

        //执行业务流程
        if($this->_tasknum==0){
            throw new XlException("抱歉，请添加任务节点！");
        }
        $stepnum=$this->_tasknum;
        $index=0;
        $taskParam=[
            'params'=>$this->_startParam,
            'result'=>null,
            'isbreak'=>false,
            'index'=>$index,
            'stepnum'=>$stepnum
        ];
        $streamnodenum=count($this->_tasklist);
        $lasttasknodeindex=0;
        for($i=$streamnodenum-1;$i>=0;$i--){
            $tasknode=$this->_tasklist[$i];
            if($tasknode['type']==1){
                $lasttasknodeindex=$i;
                break;
            }
        }
        $j=0;
        for($i=0;$i<$streamnodenum;$i++){

            $tasknode=$this->_tasklist[$i];
            if($tasknode['type']==1){
                //任务流节点
                $taskParam['index']=$j;
                $taskname=$tasknode['taskname'];

                $taskObject=$this->_getTaskObject($taskname,$tasknode['issingleton']); //获得task对象

                $taskObject->enter($taskParam);
                $runparams=$taskObject->getParams();

                if($tasknode['param']&&is_array($tasknode['param'])){
                    if(is_array($runparams)){
                        $runparams=array_merge($runparams,$tasknode['param']);
                    }else if($runparams==null){
                        $runparams=$tasknode['param'];
                    }
                }

                $taskObject->run($runparams);
                $taskParam=$taskObject->leave();

                if($taskParam['isbreak']){
                    break;
                }
                $j++;
            }else{
                //过程函数
                $method=$tasknode['method'];
                $method.="_deal";  //处理函数
                $callback=$tasknode['callback'];
                if($i>$lasttasknodeindex){
                    //过程处理函数处理结果
                    $taskParam['result']=$this->{$method}($callback,$taskParam['result'],true);
                }else{
                    //过程处理函数处理过程
                    $taskParam['params']=$this->{$method}($callback,$taskParam['params'],null);
                }
            }

        }

        $this->reInit();

        return $taskParam['result']; //直接返回结果
    }

    /**
     * @param $func
     * @param $params
     * 遍历数组
     */
    protected function map_deal($func,$params,$dealisresult=null){

        if(!is_array($params)){
            return $params; //原值返回
        }

        if(is_callable($func)){
            foreach ($params as $k=>&$item) {
                $item = $func($item, $k);
            }
        }

        return $params;

    }
    /**
     * @param $func
     * @param $params
     * 过滤数组
     */
    protected function filter_deal($func,$params,$dealisresult=null){

        if(!is_array($params)){
            return $params;
        }
        if(is_callable($func)){
            foreach ($params as $k=>$item){
                if(!$func($item,$k)){
                    unset($params[$k]);
                }
            }
        }else{
            if(is_string($func)){
                $func=explode(",",$func);
            }
            if(!is_array($func)){
                return $params;
            }
            $retainkeys=[];
            $excludekeys=[];
            foreach ($func as $it){
                if(substr($it,0,1)=="!"){
                    $retainkeys[]=substr($it,1);
                }else{
                    $excludekeys[]=$it;
                }
            }

            unset($func);
            $keys=array_keys($params);
            if($excludekeys){
                $keys=array_unique(array_merge($retainkeys,array_diff($keys,$excludekeys)));
            }else{
                $keys=$retainkeys;
            }

            $params=array_filter($params,function($k) use($keys){
                if(in_array($k,$keys)){
                    return true;
                }
                return false;
            },ARRAY_FILTER_USE_KEY);

            if($dealisresult){
                if(count($params)==1){
                    $params=array_values($params)[0];
                }
            }
        }

        return $params;

    }
    private function _getTaskObject($taskname,$issingleton=true){

        $_Isplugin=$this->_Isplugin;
        $_Ns=$this->_Ns;
        if(($pos=strpos($taskname,":"))!==false){
            if($pos==0){
                $_Isplugin=false;
                $_Ns=defined("ROOT_NS")?ROOT_NS:'';
            }else{
                if($this->_Isplugin){
                    throw new XlException("非法调用");
                }
            }
            $taskname=substr($taskname,$pos+1);
        }
        if(!$_Isplugin){
            $_Ns=defined("ROOT_NS")?ROOT_NS:'';
        }
        $taskname=trim($taskname);
        $cachekey=$_Ns.":".$taskname;
        if($issingleton){
            if($obj=$this->staticCacheGet("taskobjs",$cachekey)){
                return $obj;
            }
        }
        if(strpos($taskname,".")===false){
            $taskname=ucfirst($taskname);
        }
        $cls=$_Ns."\\task\\".str_replace(".","\\",$taskname)."Task";
        $obj=\xl\XlLead::$factroy->bind("properties",['_Isplugin'=>$this->_Isplugin,'_Ns'=>$this->_Ns])->getInstance($cls);
        if($issingleton){
            $this->staticCacheSet("taskobjs",$cachekey,$obj);
        }
        return $obj;
    }

}