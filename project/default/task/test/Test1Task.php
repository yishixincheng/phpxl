<?php

namespace lftsoft\task\test;

use xl\base\XlTaskBase;

class Test1Task extends XlTaskBase{


    public function run($params)
    {
        // TODO: Implement run() method.

        if(empty($params)){
            return $this->next($this->ErrorInf("参数不能为空！"));
        }
        return $this->next(['result'=>['返回流程1结果'],'params'=>['msg'=>'我进入了下个流程携带参数'.print_r($params,true)]]);

    }

}
