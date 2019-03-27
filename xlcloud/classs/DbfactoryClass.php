<?php

namespace xl\classs;
use xl\base\XlClassBase;
use xl\classs\db\{Dbtrait,MysqliClass,PdoClass,SqlsrvPdoClass};

class DbfactoryClass extends XlClassBase{

    use Dbtrait;
    /**
     * 当前数据库工厂类静态实例
     */
    private static $db_factory;

    /**
     * 数据库配置列表
     */
    protected $db_config = array();

    /**
     * 数据库操作实例化列表
     */
    protected $db_list = array();


    public static function getInstance($dbconfig=null){

        $dbconfig=$dbconfig?:config("database");
        if(!static::$db_factory){
            static::$db_factory = new DbfactoryClass();
        }
        if(empty(static::$db_factory->db_config)){
            static::$db_factory->db_config =$dbconfig;
        }else{
            static::$db_factory->db_config = array_merge(static::$db_factory->db_config,$dbconfig);
        }
        return static::$db_factory;

    }
    public function getDbObj($dbname='default'){

        //不同的数据库，包括跨域，维持不同的数据库操作对象
        $linkkey=$this->getLinkKey($this->db_config);

        $dbkey=md5($linkkey."/".$dbname);

        if(!isset($this->db_list[$dbkey]) || !is_object($this->db_list[$dbkey])||(defined("ISCLI")&&ISCLI)) {

            if($this->db_config['database']!=$dbname){
                $this->db_config['database']=$dbname;
            }
            $this->db_list[$dbkey] = $this->_connect();
        }

        return $this->db_list[$dbkey];

    }
    private function _connect() {
        $object = null;

        if($this->db_config['type']=="mysqli"){
            $object=new MysqliClass();
        }else{
            if(!isset($this->db_config['driver'])){
                $this->db_config['driver']="mysql";
            }
            switch ($this->db_config['driver']){
                case "mysql":
                    $object=new PdoClass();
                    break;
                case "sqlsrv":
                    $object=new SqlsrvPdoClass();
                    break;
                default:
                    $object=new PdoClass();
            }
        }

        $object->open($this->db_config); //每个数据库创建独立对象

        return $object;
    }

    /**
     * 关闭数据库连接
     * @return void
     */
    protected function close() {
        foreach($this->db_list as $db) {
            $db->close();
        }
    }

    /**
     * 析构函数
     */
    public function __destruct() {
        $this->close();
    }
}
