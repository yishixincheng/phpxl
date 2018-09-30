<?php

namespace xl\classs\db;

/**
 * php version 5.6
 */


Interface DbInterface{

    /**
     * 连接数据库
     */

    public function open($dbconfig);

    public function connect($dbconfig);

    /**
     * @return mixed
     * 获得数据库驱动对象
     */
    public function getSqlObj();


    /**
     * 功能：获取一行
     */

    public function getOne($tablename,$columns="*",$condition,$debug=null,$aop=null);

    /**
     * 获取多行
     */
    public function getRows($tablename,$columns="*",$condition,$debug=null,$aop=null);

    /**
     * 设置列字段
     */

    public function setColumn($tablename,array $columns,$condition,$debug=null,$aop=null);

    /**
     * 插入
     */

    public function insert($tablename,array $columns,$debug=null,$aop=null);

    /**
     * 多行插入
     */
    public function inserts($tablename,array $columns,array $values,$debug=null,$aop=null);


    /**
     * 删除
     */
    public function delete($tablename,$condition,$debug=null,$aop=null);



    /*
     * unoin查询
     */

    public function unionAll($tables,$columns,$conditions,$debug=null);

    /**
     * 个数
     */

    public function getRowNum($tablename,$condition,$isgroup=false,$debug=null,$aop=null);


    /**
     * query 自定义查询，{db}{tb}
     */

    public function query($query);


    public function getQueryResult($sql);


    public function insert_id();



    /**
     * 关闭数据库连接
    */

    public function close();

}