<?php

require __DIR__ . '/../../../xlcloud/XlLead.php';
define('IS_DEBUG', true);
define('TIMEOUT_METACACHE', 50);
define('TIMEOUT_ROUTETIME', 3600);
\xl\XlLead::run(__FILE__, ['namespace'=>'lftsoft']);
