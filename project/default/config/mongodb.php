<?php

/**
 * 格式=>(“mongodb://用户名:密码 @地址:端口/默认指定数据库”,参数)
 */

return [
    'hostdsn'=>['host=localhost;port=27017;'],
    'database' => 'xl_test',
    'tablepre' => 'xl_',
    'charset' => 'utf8',
    'type' => 'mongodb', //pdo默认，支持mysqli
    'debug' => true,
    'pconnect' => 0,
    'autoconnect' => 0
];
