<?php

namespace xl\base;

/**
 * Class XlElasticBase
 * elasticsearch接口
 */
import("@xl.vendor.autoload");

define("DATA_ELASTIC_PATH",DATA_PATH.'elastic'.DIRECTORY_SEPARATOR);

class XlElasticBase extends XlMvcBase {

    public static $clientcache;
    public $client=null;
    public $config=null;

    public function __construct($indextype=null)
    {
        $this->getClient();

        if($indextype){

            //解析当前索引和类型是否存在不存在则创建
            $this->createIndexType($indextype);

        }

    }
    public function getClient($config=null,$isneednew=false){

        $this->config=$config?:config("elastic");
        if(!static::$clientcache||$isneednew){

            $clientBuilder=\Elasticsearch\ClientBuilder::create();
            if($this->config['hosts']){
                $clientBuilder->setHosts($this->config['hosts']);
            }
            if($this->config['retries']){
                $clientBuilder->setRetries($this->config['retries']);
            }
            static::$clientcache=$clientBuilder->build();
        }
        $this->client=static::$clientcache;
        return $this->client;

    }
    public function createIndexType($indextype){

        $indextypearr=multiexplode([',','|','/'],$indextype);
        $this->_index=$indextypearr[0];
        $this->_type=$indextypearr[1];

        $indextypepath=implode('.',$indextypearr);
        $indextypepath.=".php";
        $indextypepath=DATA_ELASTIC_PATH.$indextypepath;
        if(!file_exists($indextypepath)){
            return $this->ErrorInf("配置文件不存在");
        }
        $param=include $indextypepath;

        $rt=$this->client->indices()->exists(['index'=>$this->_index]);

        if($rt!=1){
            //不存在则创建
            $this->client->indices()->create($param);
            //$this->client->indices()->get(['index'=>$this->_index,'ignore_unavailable'=>true]);

        }else{

            echo $this->_index;

        }

    }



}