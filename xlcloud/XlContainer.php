<?php

namespace xl;

use xl\util\{XlUAnnotationReader,XlUVerify};

/**
 * 类容器
 * @param string $class 类名
 * @param string $method 方法名, 如果为空, 则加载此类的所有方法
 */

class XlContainer{

    public $routes=[]; //['GET'=>[],'POST',....];
    public $class;

    public $path;

    public function __construct($class, $method = null)
    {
        $this->load($class,$method);
    }
    /**
     *
     * @param string $class 类名
     * @param string $method ==null时load所有方法, !==null时load指定方法
     */
    public function load($class,$method){

        $this->class=$class;
        $reflClass=new \ReflectionClass($class);

        $reader= new XlUAnnotationReader($reflClass);
        $class_ann = $reader->getClassAnnotations($reflClass);

        if(empty($class_ann['path'])){
            //没有设置path的module直接跳过
            return null;
        }
        $path = $class_ann['path'][0]['value']; //只取第一个path

        $this->path=$path;
        $specified = $method;

        foreach ($reflClass->getMethods() as $method){

            $methodName=$method->getName();
            if($specified !== null && $specified !== $methodName){
                continue;
            }
            $anns = $reader->getMethodAnnotations($method, false);
            if(!isset($anns['path'])){
                continue;
            }

            $routeArr=$anns['path'];

            foreach ($routeArr as $ann){

                $route=$ann['value'];
                XlUVerify::isTrue(is_array($route) && (count($route)==2 || count($route)==3),
                    "$class::{$method->getName()} syntax error @path, example: @path({\"routes\",\"GET\"})"
                );
                list($uri,$http_method,$strict) = $route+[null,null,null];
                $this->routes[$http_method][]=["class"=>$class, //对应的类
                                                "method"=>$methodName, //对应的方法
                                                "path"=>str_replace("//","/",$path."/".$uri),
                                                "strict"=>$strict]; //是否是严格模式

            }

        }

    }

}