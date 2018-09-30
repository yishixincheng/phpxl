<?php
namespace lftsoft\hook;

use xl\base\XlHookBase;
use xl\XlLead;

/**
 * 系统自动调用
 */
class InitHook extends XlHookBase{

    public function init(){
        logger("system_init_hook")->write("执行时间：".date("Y-m-d H:i:s"), false);
        // 系统自动调用，在这里注册路由钩子
        $this->registRequestHook("*", function($param){
//            $this->preventAttacks($param);
            // 在这里可以防采集，ip黑白名单验证功能
            return true;

        });
        // 系统路由拦截 $param=>['request'=>[], 'route'=>'', 'regparam'=>'']
        $this->registRequestHook("*", function($param){
            return true;
        });

        $this->registRequestHook("_notfoundroute", function ($param){
            $path=$param['path'];
            $request=$param['request'];
            if($path[0]=="rpcgateway"){
                $this->hookRpcRoute($request);
                return "__exit";
            }
            return true;
        });

        $this->registResponseHook("*", function ($param){
            // 输出响应钩子
            return true;
        });
    }

    public function hookRpcRoute($request){
        $postParam=$request['_POST'];
        rpcserver($postParam);
    }

    public function preventAttacks($param){
        $access=config("access")?:[];
        $cls = sysclass("cachefactory", 0);
        $cache = $cls::priority(['memcache','redis','file']);
        $access['logger']=XlLead::logger("accessforbidden_".SYS_CURR_DAY_INT);
        $access['cache']=$cache;
        $access['request']=$param['request'];
        $ip=ip();
        $access['ip']=$ip;
        $access['hostid']=$ip; //主机唯一识别码
        $obj=XlLead::$factroy->bind("properties", $access)->getInstance("xl\\util\\XlUAccessfirewall");
        $obj->run();
    }
}

