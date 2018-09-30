<?php 
return [
    'master'=>'host=localhost;port=3306;username=root;password=test;',
    'slaves'=>[
        0=>'host=localhost;port=3306;username=root;password=test;',
    ],
    'database'=>'xl_demo',
    'tablepre'=>'xl_',
    'charset'=>'utf8',
    'type'=>'pdo',
    'debug'=>true,
    'pconnect'=>0,
    'autoconnect'=>0
];