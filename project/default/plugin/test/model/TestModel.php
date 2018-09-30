<?php
class TestModel{

    public $database="";
    public $isneedcreate=true; //自动创建

    public $fields=[
        'id'=>['type'=>'bigint','size'=>20,'primarykey'=>true],
        'name'=>['type'=>'varchar','size'=>10,'name'=>'经纪人名字']
    ];
}