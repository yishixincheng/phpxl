<?php

namespace xl\classs;

use xl\base\XlClassBase;

/**
 * Class TreeClass
 * @package xl\classs
 * 树节点工具类,树形数据结构
 * ['data'=>datanode,'children'=>[]]
 * 作者：一世心城2017-06-21
 */

class TreeClass extends XlClassBase{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 创建一个节点
     */
    public function createNode($data){

        return new TreeNode($data);

    }

    /**
     * 根据树形数据结构创建可以操作的对象
     * $datastruct=[
     *     'data'=>'',
     *     'children'=>..
     * ]
     */
    public function fromDataStruct($datastruct){

        if(empty($datastruct)||!is_array($datastruct)){
            return null;
        }

        $treeObj=$this->createNode($datastruct['data']);
        $this->recuCreateNode($datastruct,$treeObj);

        return $treeObj;
    }

    /**
     * @param $org
     * @param $treenode
     * 递归创建树形对象
     */
    public function recuCreateNode($org,$treenode){

        if(empty($org['children'])){
            return;
        }
        foreach ($org['children'] as $orgNode){
            $childrenTreeNode=$treenode->insertNode($orgNode['data']);
            $this->recuCreateNode($orgNode,$childrenTreeNode);
        }

    }

    /**
     * 遍历树节点对象
     */
    public function eachNode($treenode,callable $callback=null){

        if($callback) {
            $rt = $callback($treenode);
            if($rt==="__break"){
                //是否跳出遍历
                return $rt;
            }
        }

        $childtreenodes=$treenode->getChildrenNode(null);

        if(empty($childtreenodes)){
            return null;
        }

        foreach ($childtreenodes as $childtreenode){
            $this->eachNode($childtreenode,$callback);
        }

        return true;

    }

    /**
     * 遍历树数据结构节点
     */
    public function each(&$treenode,callable $callback=null){

        if($treenode['data']){

            if($callback){
                $rt=$callback($treenode['data']);
                if($rt==="__break"){
                    //是否跳出遍历
                    return $rt;
                }
            }

        }
        if(empty($treenode['children'])||!is_array($treenode['children'])){
            return null;
        }

        //子节点是二维数组
        foreach ($treenode['children'] as &$treenodeItem){
            $rt=$this->each($treenodeItem,$callback);
            if($rt&&$rt==="__break"){
                break;
            }
        }

        return true;

    }


}

/**
 * Class TreeNode
 * @package xl\classs
 * 树节点对象
 */
class TreeNode{

    private $_treedatastruct=null;
    private $_childnodeobj=[];
    private $_i=0;
    private $inSiblingIndex=0; //在兄弟节点的索引位置
    private $parentNode=null; //父节点

    public function __construct($data)
    {
        $this->_treedatastruct=['data'=>$data,'children'=>null]; //创建根节点

    }

    /*
     * 插入孩子节点
     * 返回孩子节点对象
     */

    public function insertNode($data){

        $childTreeNode=new TreeNode($data);

        $childTreeNode->parentNode=$this;
        $childTreeNode->inSiblingIndex=$this->_i;

        $this->_childnodeobj[$this->_i]=$childTreeNode;

        $this->_i++;

        return $childTreeNode;

    }

    public function getChildNode($i=null){

        if($i===null){
            return $this->_childnodeobj;
        }

        return $this->_childnodeobj[$i];

    }

    /**
     * @return array|null
     * 获得节点数据
     */
    public function getTreeData(){

        $treestruct=$this->_treedatastruct;

        $children=[];

        $childrenCount=count($this->_childnodeobj);

        if($childrenCount){

            for($i=0;$i<$childrenCount;$i++){
                $children[]=$this->_childnodeobj[$i]->getTreeData();
            }
        }

        if($children){
            $treestruct['children']=$children;
        }

        return $treestruct;

    }
    /**
     * @return null
     * 获取父节点对象
     */
    public function getParentNode(){
        return $this->parentNode;
    }
    /**
     * 获取孩子节点对象
     */
    public function getChildrenNode($index=null){

        if($index===null){
            return $this->_childnodeobj;
        }
        return $this->_childnodeobj[$index];

    }
    /**
     * 移除自身对象
     */
    public function remove(){

        if($this->parentNode===null){

            //移除的是根节点
            $this->_treedatastruct=null;
            $this->_childnodeobj=[];
            $this->_i=0;

        }else{

            if($this->parentNode instanceof TreeNode){
                $this->parentNode->removeNode($this->inSiblingIndex);
            }

        }

    }
    /**
     * 根据子节点索引的位置移除子节点
     */
    public function removeNode($index){

        $childcount=count($this->_childnodeobj); //子节点个数
        for($i=$index;$i<$childcount-1;$i++){
            $this->_childnodeobj[$i]=$this->_childnodeobj[$i+1];
            $this->_childnodeobj[$i]->inSiblingIndex=$i; //改变在兄弟节点顺序
        }
        array_pop($this->_childnodeobj); //移除最后一个节点

    }

    /**
     * 设置节点数据
     */
    public function setData($data){

        $this->_treedatastruct['data']=$data;

    }

    /**
     * 获得节点数据
     */
    public function getData(){
        return $this->_treedatastruct['data'];
    }

}