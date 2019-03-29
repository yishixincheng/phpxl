<?php

namespace xl\base;

/**
 * Class XlModelStream
 * @package xl\base
 * 模型流,支持连缀函数
 */
final class XlModelStream extends XlMvcBase{

    private $_model=null;
    private $_streamlist=[];
    private $_modelfields=null;
    private $_fields=null;


    public function __construct($modelname,$config=null)
    {
        $this->_model=$this->Model($modelname,$config);
    }

    public function reInit(){

        $this->_streamlist=[];

    }

    public function getModel(){
        return $this->_model;
    }

    /**
     * 必传参数
     */
    public function must($mustkeys,$checkunset=false){

        array_push($this->_streamlist,['method'=>"must","position"=>"before","type"=>1,"params"=>[$mustkeys,$checkunset]]);

        return $this;

    }

    /**
     * 遍历
     */
    public function map($mapfunc){

        array_push($this->_streamlist,['method'=>"map","type"=>1,"params"=>[$mapfunc]]);

        return $this;

    }
    /**
     * 过滤
     */
    public function filter($filterfunc){

        array_push($this->_streamlist,['method'=>"filter","type"=>1,"params"=>[$filterfunc]]);

        return $this;

    }

    /**
     * 值
     */
    public function val(){

        array_push($this->_streamlist,['method'=>"val","position"=>"after","type"=>1]);

        return $this;

    }

    /**
     * before
     */
    public function before($func){

        array_push($this->_streamlist,['method'=>"before","position"=>"before","type"=>1,"params"=>[$func]]);

        return $this;
    }

    /**
     * after
     */
    public function after($func){

        array_push($this->_streamlist,['method'=>"after","position"=>"after","type"=>1,"params"=>[$func]]);

        return $this;
    }

    /**
     * 获取一行
     */
    public function getOne($columns="*",$condition,$debug=null){

        array_push($this->_streamlist,['method'=>"getOne","type"=>2,"params"=>[$columns,$condition,$debug]]);

        return $this;

    }

    /**
     * 获取多行
     */
    public function getRows($columns="*",$condition,$debug=null){

        array_push($this->_streamlist,['method'=>"getRows","type"=>2,"params"=>[$columns,$condition,$debug]]);

        return $this;
    }

    /**
     * 编辑字段
     */
    public function setColumn($columns,$condition,$debugorcleancache=null){


        array_push($this->_streamlist,['method'=>"setColumn","type"=>2,"params"=>[$columns,$condition,$debugorcleancache]]);

        return $this;

    }

    /**
     * 添加一行记录
     */
    public function insert(array $columns,$debugorcleancache=null){

        array_push($this->_streamlist,['method'=>"insert","type"=>2,"params"=>[$columns,$debugorcleancache]]);

        return $this;
    }

    /**
     * 插入多行
     */
    public function inserts($columns,array $values,$debugorcleancache=null){

        array_push($this->_streamlist,['method'=>"inserts","type"=>2,"params"=>[$columns,$values,$debugorcleancache]]);

        return $this;

    }

    /**
     * 获取个数
     */
    public function getRowNum($condition,$isgroup=false,$debug=null){

        array_push($this->_streamlist,['method'=>"getRowNum","type"=>2,"params"=>[$condition,$isgroup,$debug]]);

        return $this;

    }

    public function sumRow($column,$condition,$debug=null){

        array_push($this->_streamlist,['method'=>"sumRow","type"=>2,"params"=>[$column,$condition,$debug]]);

        return $this;

    }

    /**
     * 检索
     */
    public function search($pm,$debug=null){

        array_push($this->_streamlist,['method'=>"search","type"=>2,"params"=>[$pm,$debug]]);

        return $this;

    }

    /**
     * 添加
     */
    public function add($params,$dealcolumnlen=false,$debug=null){

        array_push($this->_streamlist,['method'=>"add","type"=>2,"params"=>[$params,$dealcolumnlen,$debug]]);

        return $this;

    }

    /**
     * 编辑
     */
    public function edit($params,$condition='',$dealcolumnlen=false,$debug=null){

        array_push($this->_streamlist,['method'=>"edit","type"=>2,"params"=>[$params,$condition,$dealcolumnlen,$debug]]);

        return $this;

    }

    /**
     * 删除
     */
    public function delete($condition,$debugorcleancache=null){

        array_push($this->_streamlist,['method'=>"delete","type"=>2,"params"=>[$condition,$debugorcleancache]]);

        return $this;

    }

    /**
     * 执行动作并获取执行结果
     */
    public function done(){
        $streamlist=[];
        $type_2_num=0;
        $i=0;
        $result_call_index=null;
        foreach ($this->_streamlist as $item){
            if($item['type']==2){
                $result_call_index=$i;
                $type_2_num++;
            }
            if($type_2_num>1){
                throw new XlException("抱歉，结果函数只能出现一次！");
            }
            $streamlist[$i]=$item;
            $i++;
        }
        if($result_call_index===null){

            throw new XlException("抱歉，缺少结果函数调用！");

        }
        foreach ($streamlist as $i=>$item){

            if($i<$result_call_index){
                //过程函数处理参数
                if(isset($item['position'])&&$item['position']=="after"){
                    throw new XlException("抱歉，".$item['method']."只能用于后置调用");
                }
                $return=$this->_processDealParam($i,$result_call_index,$streamlist);
                if($return){
                    $this->reInit();
                    return $return;
                }
            }elseif($i>$result_call_index){
                //过程函数处理结果
                if(isset($item['position'])&&$item['position']=="before"){
                    throw new XlException("抱歉，".$item['method']."只能用于前置调用");
                }
                $this->_processDealResult($i,$result_call_index,$streamlist);
            }else{
                //调用
                $method=$item['method'];
                $streamlist[$result_call_index]['result']=call_user_func_array([$this->_model,$method],$streamlist[$i]['params']);
            }
        }

        $this->reInit(); //执行结束，重新初始化，用于复用

        return $streamlist[$result_call_index]['result'];   //返回执行结果


    }
    private function _processDealParam($i,$result_call_index,&$streamlist){

        $item=$streamlist[$i];
        $resultitem=$streamlist[$result_call_index];

        $method=$item['method'];
        $method.="_deal";
        if(in_array(strtolower($resultitem['method']),['getone','getrows','setColumn','search','add','edit'])){

            //处理第一个参数
            if($item['method']=="must"){
                return $this->{$method}(count($item['params'])>1?$item['params']:$item['params'][0],$resultitem['params'][0]);
            }else{
                $streamlist[$result_call_index]['params'][0]=$this->{$method}(count($item['params'])>1?$item['params']:$item['params'][0],$resultitem['params'][0]);
            }
        }

        return null;

    }
    private function _processDealResult($i,$result_call_index,&$streamlist){

        $item=$streamlist[$i];
        $resultitem=$streamlist[$result_call_index];
        $method=$item['method'];
        $method.="_deal";
        $result_method=strtolower($resultitem['method']);
        if($result_method=='getone'){
            //处理返回值
            $streamlist[$result_call_index]['result']=$this->{$method}($item['params'][0],$resultitem['result']);
        }else if($result_method=="getrows"){
            if(is_array($streamlist[$result_call_index]['result'])){
                $streamlist[$result_call_index]['result']=$this->{$method}($item['params'][0],$streamlist[$result_call_index]['result']);
            }

        }else if($result_method=="search"){
            if($resultitem['params'][0]['needallcount']){
                if(is_array($streamlist[$result_call_index]['result']['datalist'])){
                    $streamlist[$result_call_index]['result']['datalist']=$this->{$method}($item['params'][0],$streamlist[$result_call_index]['result']['datalist']);
                }
            }else{
                if(is_array($streamlist[$result_call_index]['result'])){
                    $streamlist[$result_call_index]['result']=$this->{$method}($item['params'][0],$streamlist[$result_call_index]['result']);

                }
            }
        }else{
            $streamlist[$result_call_index]['result']=$this->{$method}($item['params'][0],$streamlist[$result_call_index]['result']);
        }

    }
    private function _getmodelfields(){

        if($this->_modelfields){
            return $this->_modelfields;
        }
        $fields=$this->_model->getFields();

        if(!is_array($fields)){
            throw new XlException("非法列！");
        }
        $this->_modelfields=array_keys($fields);

        return $this->_modelfields;

    }
    private function _getfields(){

        if($this->_fields){
            return $this->_fields;
        }
        $fields=$this->_model->getFields();

        if(!is_array($fields)){
            throw new XlException("非法列！");
        }
        $this->_fields=$fields;

        return $this->_fields;

    }
    /**
     * @param $func
     * @param $params
     * 遍历数组
     */
    protected function map_deal($func,$params){

        if($params=="*"){
            $params=$this->_getmodelfields();
        }else if(is_string($params)){
            $params=explode(',',$params);
        }
        if(!is_array($params)){
            return null;
        }
        foreach ($params as $k=>&$item){
            $item=$func($item,$k);
        }

        return $params;

    }
    /**
     * @param $func
     * @param $params
     * 过滤数组
     */
    protected function filter_deal($func,$params){

        if($params=="*"){
            $params=$this->_getmodelfields();
        }else if(is_string($params)){
            $params=explode(',',$params);
        }
        if(!is_array($params)){
            return null;
        }
        foreach ($params as $k=>$item){
            if(!$func($item,$k)){
                unset($params[$k]);
            }
        }
        return $params;

    }

    protected function val_deal($func=null,$params){

        if(!$params){
            return null;
        }
        if(is_array($params)&&count($params)==1){
            return array_pop($params);
        }
        return $params;

    }

    /**
     * @param $func
     * @param $params
     * 判断必填项
     */
    protected function must_deal($mustParam,$params){

        $mustkeys=$mustParam[0];
        $checkunset=$mustParam[1];
        $fields=$this->_getfields();

        if(is_string($mustkeys)){
            $mustkeys=multiexplode([",","|"],$mustkeys);
        }
        if(empty($mustkeys)||!is_array($mustkeys)){
            return null;
        }
        $indexarr=false;
        if(array_values($params)==$params){
            $indexarr=true;
        }
        foreach($mustkeys as $key){
            $field=$fields[$key];
            if($field){
                $name=$field['name']?:$key;
            }else{
                $name=$key;
            }
            if($checkunset){
                if($indexarr){
                    if(!in_array($key,$params)){
                        return $this->ErrorInf("参数：".$name."缺失！");
                    }
                }else{
                    if(!isset($params[$key])){
                        return $this->ErrorInf("参数：".$name."缺失！");
                    }
                }
            }else{
                if($indexarr){
                    if(!in_array($key,$params)){
                        return $this->ErrorInf("参数：".$name."缺失！");
                    }
                }else{
                    if(empty($params[$key])){
                        return $this->ErrorInf("参数：".$name."不能为空！");
                    }
                }

            }
        }
        return null;

    }

    public function before_deal($func,$params=null){

        if(is_callable($func)){
            $params=$func($params);
        }
        return $params;

    }
    public function after_deal($func,$params=null){

        if(is_callable($func)){
            $params=$func($params);
        }
        return $params;
    }

}