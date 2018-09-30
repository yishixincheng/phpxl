<?php
namespace lftsoft\cli;
use xl\XlLead;

include("../www/clipure.php");

/**
 * worker cli 脚本目录
 * 用户消息推送，调用接口执行定时计划
 */
\xl\api\XlApi::exec("Timingplan", ['processnum'=>1, 'plans'=>config("plans"), 'logger'=>XlLead::logger("timingplan")]);