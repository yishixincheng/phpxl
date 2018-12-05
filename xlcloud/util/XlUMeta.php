<?php
namespace xl\util;


class XlUMeta{

    /**
     * 获取元信息
     * @param object|class $inst
     * @param boolean $record_doc 是否加载注释文本, 如果是
     * @param array $select, 只取选中的几个
     * @return array
     */

    private static $valid=array();

    static function get($inst, $record_doc=false, $select=null){
        $reflection = new \ReflectionClass($inst);
        $reader= new XlUAnnotationReader($reflection);
        $info = array();
        if($record_doc){
            if(false !== ($doc = $reflection->getDocComment())){
                $info['doc'] = $doc;
            }
        }
        if($select !== null){
            $select = array_flip($select);
        }
        foreach ($reader->getClassAnnotations($reflection, $record_doc) as $id =>$ann ){
            if($select !==null && !array_key_exists($id, $select)){
                continue;
            }
            $ann=$ann[0];//可能有多个重名的, 只取第一个
            $info[$id] = $ann;
        }
        foreach ($reflection->getMethods() as $method ){
            foreach ( $reader->getMethodAnnotations($method, $record_doc) as $id => $ann){
                if($select !==null && !array_key_exists($id, $select)){
                    continue;
                }
                $ann=$ann[0];//可能有多个重名的, 只取第一个
                $info += array($id=>array());
                $info[$id][$method->getName()] = $ann;
            }
        }
        foreach ($reflection->getProperties() as $property ){
            foreach ( $reader->getPropertyAnnotations($property, $record_doc) as $id => $ann){
                if($select !==null && !array_key_exists($id, $select)){
                    continue;
                }
                $ann = $ann[0];//可能有多个重名的, 只取第一个
                $info += array($id=>array());
                $info[$id][$property->getName()] = $ann;
            }
        }

        return $info;
    }


}