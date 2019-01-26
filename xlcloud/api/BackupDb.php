<?php

namespace xl\api;

/**
 * Class BackupDb.php
 * @package xl\api
 * 备份数据表，支持mysql
 */

final class BackupDb extends XlApiBase{

    protected $database;  //备份表所在的数据库
    protected $username;  //数据库用户名
    protected $password;  //数据库密码
    protected $host="localhost";      //数据库所在主机
    protected $port="3306";            //数据库端口
    protected $tablename;       //要备份的数据表
    protected $splitline=5000;  //切割行数
    protected $backuppath="data/backup/";      //备份表所在的目录
    protected $backstarttime="";                //备份开始时间
    protected $route="backup";  //路由
    private $_dbObj=null;     //操作数据库对象

    public function run(){

        if(empty($this->database)){
            return $this->ErrorInf("未指定数据库参数！");
        }
        if(empty($this->username)){
            return $this->ErrorInf("数据库用户名不能为空！");
        }
        if(empty($this->password)){
            return $this->ErrorInf("数据库密码不能为空！");
        }
        if(empty($this->tablename)){
            return $this->ErrorInf("数据表名不能为空");
        }
        if(empty($this->backuppath)){
            return $this->ErrorInf("备份目录不能为空！");
        }
        if(substr($this->backuppath,0,1)!="/"&&substr($this->backuppath,1,1)!=":"){
            $this->backuppath=PROROOT_PATH.$this->backuppath;
        }
        if(substr($this->backuppath,-1,1)!="/"){
            $this->backuppath.=D_S;
        }
        if(empty($this->backstarttime)){
            return $this->ErrorInf("备份开始时间！");
        }
        $this->backstarttime=preg_replace("/:|-|\s/","_",$this->backstarttime);
        $this->backuppath.=$this->backstarttime.D_S;
        $this->backuppath.=$this->database.D_S.$this->tablename.D_S; //备份隔离

        switch ($this->route){

            case "backup":
                $rt=$this->_backup();
                break;
            case "restore":
                $rt=$this->_restore();
                break;

        }

        if(isset($rt)){
            return $rt;
        }

        return $this->ErrorInf("路由参数错误");

    }

    /**
     * 链接数据库
     */
    private function _connectdatabase(){

        $dbf=sysclass("dbfactory",0);

        $dbconfig=[
            'hostname'=>$this->host,
            'port'=>$this->port,
            'username'=>$this->username,
            'password'=>$this->password,
            'autoconnect'=>1,
            'type' => 'pdo',
            'charset' => 'utf8',
            'database'=>$this->database
        ];

        try{
            $this->_dbObj=$dbf::getInstance($dbconfig)->getDbObj($this->database);
        }catch (\Exception $e) {
            return $this->ErrorInf("数据库连接失败！");
        }

        return $this->SuccInf("数据库连接成功！");

    }

    /**
     * 备份数据表
     */
    private function _backup(){

        //连接数据库

        //数据表存在，开始备份
        $opfilecls=sysclass("opfile",0);
        $opfile=new $opfilecls; //重新生成对象，非单例模式

        $opfile->setParam($this->backuppath.'0.properties',false);
        $page=$opfile->readProp("backup_page");

        $isover=$opfile->readProp("backup_isover");
        if($isover){
            return $this->SuccInf("已经备份结束",['code'=>1]);
        }

        if(!$this->superIsOK($rt=$this->_connectdatabase())){
            return $rt;
        }
        if(!$this->_dbObj->tableExists($this->tablename)){
            return $this->ErrorInf("抱歉，数据表不存在！");
        }

        if(empty($page)){

            //创建数据结构
            $sqlstr='SHOW CREATE TABLE '.$this->tablename;
            $sql=$this->_dbObj->execute($sqlstr);
            $rt=$this->_dbObj->getQueryResult($sql);
            $tablecreatestr=$rt['Create Table'];
            if($tablecreatestr){

                $str="DROP TABLE IF EXISTS {$this->tablename};\n";
                $str.=$tablecreatestr.";\n";

                $opfile->setParam($this->backuppath."0.sql",false);

                if(!$opfile->Write($str)){
                    return $this->ErrorInf("创建数据表结构失败！");
                }
            }

            $opfile->setParam($this->backuppath.'0.properties',false);

            if($opfile->writeProp("backup_page",1)){

                return $this->_backup(); //递归调用

            }else{
                return $this->ErrorInf("写入页数文件失败，请检查有无权限操作");
            }

        }
        //根据页数读取数据
        $page=intval($page)?:1;
        $offset=($page-1)*$this->splitline;
        $sqlstr="select * from ".$this->tablename." limit {$offset},{$this->splitline}";
        $sql=$this->_dbObj->execute($sqlstr);

        $str='';
        $affectcount=0;
        while($rt=$this->_dbObj->getQueryResult($sql)) {
            $insert = [];
            if ($affectcount == 0) {
                $keys = [];
                foreach ($rt as $r_k => $r_v) {
                    $keys[] = "`" . $r_k . "`";
                    $insert[] = $this->_dbObj->getSqlObj()->quote($r_v);
                }

                $str = "INSERT INTO " . $this->tablename . " (" . implode(',', $keys) . ") VALUES \n";
                $str .= "(" . implode(",", $insert) . "),\n";

            } else {
                foreach ($rt as $r_k => $r_v) {
                    $insert[] = $this->_dbObj->getSqlObj()->quote($r_v);
                }
                $str .= "(" . implode(",", $insert) . "),\n";
            }

            $affectcount=1;

        }
        if(empty($affectcount)){
            $opfile->setParam($this->backuppath.'0.properties',false);
            $opfile->writeProp("backup_isover",1); //设置备份结束标识
            return $this->SuccInf("备份已执行结束",['code'=>1]);
        }
        $opfile->setParam($this->backuppath."{$page}.sql",false);
        $str=trim($str,",\n");
        $str.=";\n";
        if(!$opfile->Write($str)){
            return $this->ErrorInf("写入文件失败当前页数：{$page}！");
        }

        $page++;
        $opfile->setParam($this->backuppath.'0.properties',false);

        if($opfile->writeProp("backup_page",$page)){

            return $this->SuccInf("备份成功{$page}页",['code'=>0]); //交给调用者，不断调用
            //return $this->_backup(); //递归调用

        }else{
            return $this->ErrorInf("写入页数文件失败，请检查有无权限操作");
        }

    }

    /**
     * 还原数据库
     */
    private function _restore(){

        $opfilecls=sysclass("opfile",0);
        $opfile=new $opfilecls; //重新生成对象，非单例模式
        $opfile->setParam($this->backuppath.'0.properties',false);
        $backup_page=intval($opfile->readProp("backup_page"));
        $backup_isover=$opfile->readProp("backup_isover");
        if(!$backup_isover){
            return $this->ErrorInf("备份文件不存在或备份没结束不能还原");
        }
        $restore_page=intval($opfile->readProp("restore_page"));
        $restore_isover=$opfile->readProp("restore_isover");
        if($restore_isover){
            return $this->SuccInf("已经还原结束",['code'=>1]);
        }


        if(!$this->superIsOK($rt=$this->_connectdatabase())){
            return $rt;
        }

        if($restore_page==0){

            //创建数据库
            $opfile->setParam($this->backuppath."0.sql",false);
            $sqlstatement=$opfile->Read();
            if($sqlstatement){
                $sqlarr=explode(";\n",trim($sqlstatement));

                $isexec=false;

                foreach ($sqlarr as $sqlstr){
                    if($sqlstr){
                        $isexec=$this->_dbObj->execute($sqlstr);
                    }
                }

                if($isexec){

                    //执行成功
                    $opfile->setParam($this->backuppath.'0.properties',false);
                    if($opfile->writeProp("restore_page",1)){
                        return $this->_restore(); //递归调用
                    }else{
                        return $this->ErrorInf("写入页数文件失败，请检查有无权限操作");
                    }
                }

            }

            return $this->ErrorInf("创建数据表文件失败！");

        }

        $opfile->setParam($this->backuppath."{$restore_page}.sql",false);

        $sqlstatement=$opfile->Read();

        if($sqlstatement){
            $sqlarr=explode(";\n",trim($sqlstatement));

            foreach ($sqlarr as $sqlstr){
                if($sqlstr){
                    $this->_dbObj->execute($sqlstr);
                }
            }
            //执行成功
            $opfile->setParam($this->backuppath.'0.properties',false);
            $restore_page++;
            if($restore_page>=$backup_page){
                $opfile->writeProp("restore_isover",1); //设置备份结束标识
                return $this->SuccInf("还原已执行结束",['code'=>1]);
            }
            if($opfile->writeProp("restore_page",$restore_page)){
                return $this->SuccInf("还原成功{$restore_page}",['code'=>0]);
            }else{
                return $this->ErrorInf("写入页数文件失败，请检查有无权限操作");
            }

        }else{
            return $this->ErrorInf("备份文件{$restore_page}内容为空！");
        }

    }

}