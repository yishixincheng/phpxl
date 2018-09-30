<?php

namespace rpc\server\test;

trait ApiuserTrait{

    /**
     * @param $appkey
     * @return array|null
     * 如果当前类没有定义get
     */

    public function __getApiUser($appkey){

        if($appkey=="xxx"){

            return ['appsecret'=>'111'];

        }

        return null;

    }

}