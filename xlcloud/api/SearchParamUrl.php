<?php

namespace xl\api;

final class SearchParamUrl extends XlApiBase{


    protected $params;
    protected $url='';


    public function run(){


        $this->params=$this->params?:[];

        /**
         * 返回一个值，
         */
        return ["urlfunc"=>function($key,$value) {

            $sosoParam = $this->params;

            if (empty($value)) {
                if (isset($sosoParam[$key])) {
                    unset($sosoParam[$key]);
                }
            }else{
                $sosoParam[$key] = $value;
            }

            if (empty($sosoParam)) {
                return $this->url;
            }

            ksort($sosoParam);

            $httpquerystr = http_build_query($sosoParam);

            return $this->url . "?" . $httpquerystr;

        },"setparamfunc"=>function($key,$value){

            if($value===null){
                unset($this->params[$key]);
            }else{
                $this->params[$key]=$value;
            }

        }];

    }

}