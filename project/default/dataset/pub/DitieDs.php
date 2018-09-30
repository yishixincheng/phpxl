<?php

class DitieDs{

    public $fields=[
        'id'=>['type'=>'int', 'comment'=>'ID'],
        'name'=>['type'=>'string', 'comment'=>'名称'],
        'torder'=>['type'=>'int', 'comment'=>'排序']
    ];
    public $datalist=[
        ['id'=>1, 'name'=>'是', 'torder'=>0],
        ['id'=>2, 'name'=>'否', 'torder'=>0],
        ['id'=>3,'name'=>'测试']
    ];
}