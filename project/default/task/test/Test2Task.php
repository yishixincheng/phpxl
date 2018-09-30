<?php

namespace lftsoft\task\test;

use xl\base\XlTaskBase;

class Test2Task extends XlTaskBase{


    public function run($params)
    {
        // TODO: Implement run() method.

        return $this->next(['result'=>['tip'=>'返回流程2结果','preparams'=>$params],'params'=>['msg'=>'我进入了下个流程']]);

    }

}
