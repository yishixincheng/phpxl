<?php
namespace rpc\server;

class Response{

    public $fields='status,code,msg';
    public $fiedsallow=array('status'=>array('level'=>10),
        'code'=>array('level'=>10),
        'msg'=>array('level'=>10)); //fields允许的值有
    public $appkey=0;
    public $sessionkey='';
    public $params=array();
    private $_error=null;
    public $postallow=null;

    function __construct($methodname=''){

    }

    public function getApiResult(){

    }

    public function getApiUser($appkey=null){

        return [];

    }

    public function seterror($msg,$code=0){
        $this->_error=array('status'=>'fail','msg'=>$msg,'code'=>$code);
    }
    public function geterror(){

        if($this->_error){
            return $this->_error;
        }
        return null;
    }
    public function check($callerlevel=0){
        //验证参数的有效性,如果有错误则进入用户错误流程里
        if(empty($this->fields)){
            //这里必须有参数
            $this->seterror('传入的fields参数为空');
            return false;
        }
        //组织成数组
        if($this->fields=='*'){
            $this->fields=array();
            foreach($this->fiedsallow as $k=>$v){
                if($v){
                    $this->fields[]=is_numeric($k)?$v:$k;
                }
            }
        }else{
            $this->fields=explode(',',$this->fields);
        }
        foreach($this->fields as $field){
            if($this->fiedsallow[$field]||in_array($field,$this->fiedsallow)){
                if(is_array($this->fiedsallow[$field])&&$this->fiedsallow[$field]['level']<$callerlevel){
                    //权限不足
                    $this->seterror("权限不足，不能传递fields中".$field."参数",1);
                    return false;
                }
            }else{
                $this->seterror("参数无效，不能传递fields中".$field."参数",2);
                return false;
            }
        }
        return $this->mycheck($callerlevel);
        //初步验证成功
    }
    public function mycheck($callerlevel=0){
        return true;
    }
    public function autoGetParams($postparams){

        if(!is_array($this->params)){
            $this->params=array();
        }
        if($postparams['fields']){
            $this->fields=$postparams['fields'];
        }

        if($this->postallow){
            //限制传参，增加安全性
            foreach($this->postallow as $key=>$pn){

                if(is_string($pn)){
                    if(isset($postparams[$pn])){
                        $this->params[$pn]=$postparams[$pn];
                    }
                }else if(is_array($pn)){
                    if(isset($postparams[$key])){
                        $this->params[$key]=$postparams[$key];
                    }
                    else if(isset($pn['default'])){
                        $this->params[$key]=$pn['default'];
                    }
                }
            }

        }else{
            $this->params=$postparams;
        }

    }
    public function _dealresult($r,$fd=null,$gradation=1){

        if($r&&$fd&&is_array($r)&&is_array($fd)){
            //过滤数据
            $td=array();
            if($gradation==1){
                foreach($r as $k=>$v){
                    if(in_array($k,$fd)){
                        $td[$k]=$v;
                    }
                }
            }else if($gradation==2){
                $fd[]='allcount'; //增加所有的数hack
                foreach($r as $k=>$v){
                    if(is_array($v)){
                        foreach($v as $kk=>$vv){
                            if(!in_array($kk,$fd)){
                                unset($v[$kk]);
                            }
                        }
                    }
                    $td[$k]=$v;
                }
            }
            $r=$td;
        }

        if(isset($r['__'])&&$r['status']){
            return $r;
        }else{
            return array('status'=>'success','msg'=>'成功获得数据','code'=>0,'data'=>$r);
        }

    }
    private function _unifiedSuccErrorInf($error,$text,$attach=0){

        $code=$attach;
        $rt=array();
        if(is_array($attach)){
            foreach($attach as $k=>$v){
                if(is_numeric($k)||in_array($k,["error","result","status","msg","code"])){
                    continue;
                }
                $rt[$k]=$v;
            }
        }else{
            $rt['code']=$code;
        }
        if($error){
            $rt['status']="fail";
        }else{
            $rt['status']="success";
        }
        $rt['msg']=$text;

        $rt['__']=1;

        return $rt;
    }
    final public function SuccInf($text,$attach=0)
    {
        return $this->_unifiedSuccErrorInf(false,$text,$attach);
    }
    final public function ErrorInf($text,$attach=0)
    {
        return $this->_unifiedSuccErrorInf(true,$text,$attach);

    }

}