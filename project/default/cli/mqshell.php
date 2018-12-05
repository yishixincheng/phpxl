<?php
namespace lftsoft\cli;
use xl\XlLead;

include("../www/clipure.php");

/**
 * worker cli 脚本目录
 *
 */
\xl\api\XlApi::exec("OpenMQServer", [ 'config'=>config("mq"), 'logger'=>XlLead::logger("timingplan")]);