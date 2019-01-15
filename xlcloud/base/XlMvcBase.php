<?php

namespace xl\base;


class XlMvcBase extends XlHookBase {

    protected $super_imem=null;

    protected static $_cachelist=[];
    protected static $_modellist=[];
    protected static $_sqlviewlist=[];
    protected static $_datasetlist=[];
    protected static $_isopenmq=false;

    public function __construct() {

    }
    final public function superGetCacheObj($type=''){
        //启动缓存机制,缓存优先级获取

        if(empty($type)){
            if(static::$_cachelist["*"]){
                return static::$_cachelist["*"];
            }
        }else{
            if(static::$_cachelist[$type]){
                return static::$_cachelist[$type];
            }
        }
        $cachetypes=['redis','memcache','file']; //缓存优先级
        if($type){
            $keys=array_keys($cachetypes,$type);
            if(count($keys)==0){
                array_unshift($cachetypes,$type);
            }else{
                $index=$keys[0];
                array_splice($cachetypes,$index,1); //移除
                array_unshift($cachetypes,$type);   //添加
            }
        }
        $cls = sysclass("cachefactory", 0);
        $hittype="";
        $cache = $cls::priority($cachetypes,$hittype);  //获得cache
        static::$_cachelist[$hittype]=$cache;
        if(empty($type)){
            static::$_cachelist["*"]=$cache;
        }else{
            if($hittype!=$type){
                static::$_cachelist[$type]=$cache;
            }
        }

        return $cache;

    }
    final public function superGetMemData($key,$type=null,$format=null){
        //从内存调取用户数据

        if(!$cache=$this->superGetCacheObj($type)){
            return null;
        }
        if($format){
            $cache->setting($format);
        }
        $dataarr=$cache->get($key);
        return $dataarr;

    }
    final public function superSetMemData($key,$value,$expire=0,$type=null,$format=null){

        if(!$cache=$this->superGetCacheObj($type)){
            return null;
        }
        if($format){
            $cache->setting($format);
        }
        $cache->set($key,$value,$expire);

    }
    final public function superDelMemData($key,$type=null){

        if(!$cache=$this->superGetCacheObj($type)){
            return null;
        }
        $cache->delete($key);

    }

    /**
     * 获得缓存时间
     */
    final public function superGetCacheTime($key,$type=null){

        if(!$cache=$this->superGetCacheObj($type)){
            return null;
        }
        return $cache->getcachetime($key);

    }
    private function _unifiedSuccErrorInf($error,$text,$attach=0){

        $code=$attach;
        $rt=array();
        if(is_array($attach)){
            foreach($attach as $k=>$v){
                if(is_numeric($k)){
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
    final public function superIsOK($rt){

        if($rt['status']=="success"){
            return true;
        }
        return false;
    }
    final public function superIsEmpty($param,$maps){

        foreach ($maps as $kv){

            if(empty($param[$kv['key']])){

                if($kv['name']){
                    return $this->ErrorInf("抱歉，参数".$kv['name'].'缺失');
                }else if($kv['tip']){
                    return $this->ErrorInf($kv['tip']);
                }else{
                    return $this->ErrorInf("抱歉，参数".$kv['key'].'缺失');
                }

            }

        }

        return $this->SuccInf("验证通过");

    }
    final public function superLock($key='',$sec=2){

        //开启上锁机制
        $key=$key?:get_class($this);
        $key="superlock_".$key;
        $islock=$this->superGetMemData($key);

        if(empty($islock)){
            $this->superSetMemData($key,1);//上锁
            return null;
        }
        //如果上锁则执行等待
        $t1=microtime(true);
        while(1){
            $islock=$this->superGetMemData($key);
            $t2=microtime(true);
            if(empty($islock)){
                $this->superSetMemData($key,1);//上锁
                break;
            }
            if($t2-$t1>$sec){
                //等待2秒后自动解锁
                $this->superDelMemData($key);
                break;
            }
            usleep(10);
        }

    }
    final public function superUnlock($key=''){

        $key=$key?:get_class($this);
        $key="superlock_".$key;

        $this->superDelMemData($key); //删除上锁机制

    }

    /**
     * @param $tbname
     * @param null $config
     * @return mixed
     * model原型
     */

    final public function Model($tbname,$config=null){


        //根据表名返回结构,不同的模型创建不同的对象
        $key=$tbname;
        if($config){
            $key.="_".serialize($config);
        }
        $key=md5($key);
        if(isset(static::$_modellist[$key])){
            if (is_callable(static::$_modellist[$key])) {
                (static::$_modellist[$key])();
            }
            return static::$_modellist[$key];
        }

        $proxy=$this->factory->bind("properties",['_Isplugin'=>$this->_Isplugin,'_Ns'=>$this->_Ns])->bind("construct_args",[$tbname,$config])->getInstance("xl\\base\\XlModelProxy");//构造基类

        static::$_modellist[$key]=$proxy->getModelObject();

        return static::$_modellist[$key];

    }

    /**
     * @param $tbname
     * @param null $config
     * @return mixed
     * 无缓存model
     */
    final public function noCacheModel($tbname,$config=null){

        $proxy=$this->factory->bind("properties",['_Isplugin'=>$this->_Isplugin,'_Ns'=>$this->_Ns])->bind("construct_args",[$tbname,$config])->getInstance("xl\\base\\XlModelProxy");//构造基类

        return $proxy->getModelObject();

    }

    final public function SqlView($tbname,$config=null,$modelsconfig=null){

        //根据表名返回结构,不同的模型创建不同的对象
        $key=$tbname;
        if($config){
            $key.="_".serialize($config);
        }
        if($modelsconfig){
            $key.="_".serialize($modelsconfig);
        }
        $key=md5($key);

        if(static::$_sqlviewlist[$key]){
            return static::$_sqlviewlist[$key];
        }
        $proxy=$this->factory->bind("properties",['_Isplugin'=>$this->_Isplugin,'_Ns'=>$this->_Ns])->bind("construct_args",[$tbname,$config,$modelsconfig])->getInstance("xl\\base\\XlSqlviewProxy");//构造基类

        static::$_sqlviewlist[$key]=$proxy->getModelObject();

        return static::$_sqlviewlist[$key];

    }

    /**
     * @param $tbname
     * @param null $config
     * @param null $modelsconfig
     * @return mixed
     */
    final public function noCacheSqlView($tbname,$config=null,$modelsconfig=null){

        $proxy=$this->factory->bind("properties",['_Isplugin'=>$this->_Isplugin,'_Ns'=>$this->_Ns])->bind("construct_args",[$tbname,$config,$modelsconfig])->getInstance("xl\\base\\XlSqlviewProxy");//构造基类

        return $proxy->getModelObject();

    }

    final public function Dataset($dsname,$config=null){

        $key=$dsname;
        if($config){
            $key.="_".serialize($config);
        }
        $key=md5($key);

        if(static::$_datasetlist[$key]){
            return static::$_datasetlist[$key];
        }
        static::$_datasetlist[$key]=$this->factory->bind("properties",['_Isplugin'=>$this->_Isplugin,'_Ns'=>$this->_Ns])->bind("construct_args",[$dsname,$config])->getInstance("xl\\base\\XlDatasetFactory");//构造基类

        return static::$_datasetlist[$key];

    }

    /**
     * 返回逻辑层对象
     */
    final public function Logic($clsname,$binds=null,$iscache=1){

        if(!$binds){
            if($this->_Isplugin){
                $binds=['properties'=>['_Isplugin'=>$this->_Isplugin,'_Ns'=>$this->_Ns]];
            }
        }

        return __autocreaterunobject("logic",$clsname,$iscache,$binds,$this->_Isplugin?$this->_Ns:null);

    }

    /**
     * @param $methodname
     * @param null $config
     * 模型流
     */
    final public function MS($methodname,$config=null){

        return MS($methodname,$config,$this->_Isplugin,$this->_Ns);

    }

    /**
     * @param $name
     * @param null $params
     * 任务流
     */
    final public function TS($name,$params=null){

        return TS($name,$params,$this->_Isplugin,$this->_Ns);

    }

    /**
     * 添加到消息队列
     * 结构plugin:folder/taskname
     */
    final public function MQ($task,$params=null,$queuename=null,$settime=null,$debug=false){

        $config=null;
        if(!static::$_isopenmq){
            static::$_isopenmq=true;
            $config=config("mq");
        }
        if(substr($task,0,1)==":"){
            if($this->_Isplugin){
                $task=$this->_Ns.$task;
            }else{
                $task=substr($task,1);
            }
        }
        //添加到消息队列中
        if($debug){
            $ns=null;
            if(($pos=strpos($task,":"))===false){
                //全局方法
                $ns=defined("ROOT_NS")?ROOT_NS:'';
                $methodname=$task;
                $isplugin=false;
            }else{
                //插件
                $ns=substr($task,0,$pos);
                $methodname=substr($task,$pos+1);
                $isplugin=true;
            }
            //添加失败
            return TS("消息队列任务",$params,$isplugin,$ns)->task($methodname)->done(); //调用task任务
        }

        return \xl\api\XlApi::exec("AddMQMessage", ['queuename'=>$queuename,
                                                                'task'=>$task,
                                                                'params'=>$params,
                                                                'settime'=>$settime,
                                                                'config'=>$config]);

    }

    /**
     * 注入工厂实例
     * @inject("xlinjector")
     */
    protected $factory;


}
