<?php

namespace xl\util;

class XlUAnnotationReader{

    public function __construct(){
        $this->parser= new XlUDocParser();
    }
    public function getClassAnnotations(\ReflectionClass $class, $record_doc=false)
    {
        $cn = $class->getName();
        if(isset($this->cache[$cn]['class'])){
            return $this->cache[$cn]['class'];
        }
        $this->cache[$cn]['class'] = array();

        $annots = $this->parser->parse($class->getDocComment(), 'class '.$cn, $record_doc);
        foreach ($annots as $annot){
            $key = $annot[0];
            $annot = $annot[1];
            $this->cache[$cn]['class'][$key][]=$annot;
        }
        return $this->cache[$cn]['class'];
    }

    /**
     * {@inheritDoc}
     */
    public function getMethodAnnotations(\ReflectionMethod $method, $record_doc=false)
    {
        $cn = $method->getDeclaringClass()->getName();

        $id = $method->getName();
        if(isset($this->cache[$cn]['method'][$id])){
            return $this->cache[$cn]['method'][$id];
        }
        $this->cache[$cn]['method'][$id] = array();
        $annots =  $this->parser->parse($method->getDocComment(), 'method '.$cn.'::'.$id.'()', $record_doc);
        foreach ($annots as $annot){
            $key = $annot[0];
            $annot = $annot[1];

            $this->cache[$cn]['method'][$id][$key][]=$annot;
        }
        return $this->cache[$cn]['method'][$id];
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyAnnotations(\ReflectionProperty $property, $record_doc=false)
    {
        $cn = $property->getDeclaringClass()->getName();

        $id = $property->getName();
        if(isset($this->cache[$cn]['property'][$id])){
            return $this->cache[$cn]['property'][$id];
        }
        $this->cache[$cn]['property'][$id] = array();
        $annots =  $this->parser->parse($property->getDocComment(), 'property '.$cn.'::$'.$id, $record_doc);
        foreach ($annots as $annot){
            $key= $annot[0];
            $annot= $annot[1];

            $this->cache[$cn]['property'][$id][$key][]=$annot;
        }
        return $this->cache[$cn]['property'][$id];
    }
    private $cache=array() ;
    private $parser ;
}