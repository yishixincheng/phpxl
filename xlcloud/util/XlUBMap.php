<?php

namespace xl\util;

/**
 * Class XlUBMap
 * @package xl\run\util
 * 百度地图坐标转换
 */



class XlUBMap{


     const MCBAND=[12890594.86, 8362377.87, 5591021, 3481989.83, 1678043.12, 0];
     const MC2LL=[
			[1.410526172116255e-8, 0.00000898305509648872, -1.9939833816331, 200.9824383106796, -187.2403703815547, 91.6087516669843, -23.38765649603339, 2.57121317296198, -0.03801003308653, 17337981.2],
			[-7.435856389565537e-9, 0.000008983055097726239, -0.78625201886289, 96.32687599759846, -1.85204757529826, -59.36935905485877, 47.40033549296737, -16.50741931063887, 2.28786674699375, 10260144.86],
			[-3.030883460898826e-8, 0.00000898305509983578, 0.30071316287616, 59.74293618442277, 7.357984074871, -25.38371002664745, 13.45380521110908, -3.29883767235584, 0.32710905363475, 6856817.37],
			[-1.981981304930552e-8, 0.000008983055099779535, 0.03278182852591, 40.31678527705744, 0.65659298677277, -4.44255534477492, 0.85341911805263, 0.12923347998204, -0.04625736007561, 4482777.06],
			[3.09191371068437e-9, 0.000008983055096812155, 0.00006995724062, 23.10934304144901, -0.00023663490511, -0.6321817810242, -0.00663494467273, 0.03430082397953, -0.00466043876332, 2555164.4],
			[2.890871144776878e-9, 0.000008983055095805407, -3.068298e-8, 7.47137025468032, -0.00000353937994, -0.02145144861037, -0.00001234426596, 0.00010322952773, -0.00000323890364, 826088.5]
     ];

     const LLBAND=[75,60,45,30,15,0];
     const LL2MC=[
           [-0.0015702102444, 111320.7020616939, 1704480524535203, -10338987376042340, 26112667856603880, -35149669176653700, 26595700718403920, -10725012454188240, 1800819912950474, 82.5],
           [0.0008277824516172526, 111320.7020463578, 647795574.6671607, -4082003173.641316, 10774905663.51142, -15171875531.51559, 12053065338.62167, -5124939663.577472, 913311935.9512032, 67.5],
           [0.00337398766765, 111320.7020202162, 4481351.045890365, -23393751.19931662, 79682215.47186455, -115964993.2797253, 97236711.15602145, -43661946.33752821, 8477230.501135234, 52.5],
           [0.00220636496208, 111320.7020209128, 51751.86112841131, 3796837.749470245, 992013.7397791013, -1221952.21711287, 1340652.697009075, -620943.6990984312, 144416.9293806241, 37.5],
           [-0.0003441963504368392, 111320.7020576856, 278.2353980772752, 2485758.690035394, 6070.750963243378, 54821.18345352118, 9540.606633304236, -2710.55326746645, 1405.483844121726, 22.5],
           [-0.0003218135878613132, 111320.7020701615, 0.00369383431289, 823725.6402795718, 0.46104986909093, 2351.343141331292, 1.58060784298199, 8.77738589078284, 0.37238884252424, 7.45]
     ];



    public static function isInRange($latlngarr){

        if($latlngarr){

            $lng= $latlngarr['lng'];
            $lat= $latlngarr['lat'];

            if($lng>=-180&&$lng<=180&&$lat>=-74&&$lat<=74){
                return true;
            }

        }

        return false;

    }
    public static function xyToLatLng($x,$y){


        $cC=new b4($x,$y);

        return static::convertMC2LL($cC);

    }



    public static function convertor($cD, $cE) {
        if(!$cD || !$cE) {
            return null;
        }
        $T = $cE[0] + $cE[1] * abs($cD->lng);
        $cC = abs($cD->lat) / $cE[9];
        $cF = $cE[2] + $cE[3] * $cC + $cE[4] * $cC * $cC + $cE[5] * $cC * $cC * $cC + $cE[6] * $cC * $cC * $cC * $cC + $cE[7] * $cC * $cC * $cC * $cC * $cC + $cE[8] * $cC * $cC * $cC * $cC * $cC * $cC;
        $T *= ($cD->lng < 0 ? -1 : 1);
        $cF *= ($cD->lat < 0 ? -1 : 1);
        return new b4($T, $cF);
    }
    public static function toTixed($num,$len){

        return number_format($num,$len,".","");

    }
    public static function convertMC2LL($cC){

        $cF=null;
        $cD=new b4(abs($cC->lng),abs($cC->lat));

        for($cE=0;$cE<count(static::MCBAND);$cE++){

            if($cD->lat>=static::MCBAND[$cE]){
                $cF=static::MC2LL[$cE];
                break;
            }

        }

        $T= static::convertor($cC,$cF);

        $cC=new b4(static::toTixed($T->lng,6),static::toTixed($T->lat,6));

        return ['lng'=>$cC->lng,'lat'=>$cC->lat];
    }

    public static function getBdCityIdByCityCode($citycode){

        $citymap=config("baiducity");

        if(!$citymap){
            return 127;
        }
        if(!is_array($citymap)){
            return 127;
        }
        if($citymap[$citycode]){
            return $citymap[$citycode];
        }
        return 127;

    }

    /**
     * @param $lat
     * @param $lng
     * 经纬坐标转换为墨卡托坐标
     */
    public static function lngLatToXy($lng,$lat){

        //经纬坐标转xy

        return static::convertLL2MC($lng,$lat);

    }

    public static function convertLL2MC($lng,$lat){

        $cE=null;
        $lat=static::getLoop($lat,-74,74);
        $lng=static::getLoop($lng,-180,180);

        for($i=0;count(static::LLBAND);$i++){

            if($lat>=static::LLBAND[$i]){
                $cE=static::LL2MC[$i];
                break;
            }
        }
        if($cE!=null){

            for($i=count(static::LLBAND)-1;$i>=0;$i--){

                if($lat<=-static::LLBAND[$i]){
                    $cE=static::LL2MC[$i];
                    break;
                }
            }

        }

        return static::convertor2($lng,$lat,$cE);

    }

    public static function convertor2($x,$y,$cE){

        $xTemp=$cE[0]+$cE[1]*abs($x);
        $cC=abs($y)/$cE[9];
        $yTemp=$cE[2]+$cE[3]*$cC+$cE[4]*pow($cC,2)+$cE[5]*pow($cC,3)+$cE[6]*pow($cC,4)+$cE[7]*pow($cC,5)+$cE[8]*pow($cC,6);
        $xTemp *=($x<0?-1:1);
        $yTemp *=($y<0?-1:1);
        return ['x'=>$xTemp,'y'=>$yTemp];
    }

    public static function getLoop($lnglat,$min,$max){

        while($lnglat>$max){
            $lnglat-=$max-$min;
        }
        while($lnglat<$min){
            $lnglat+=$max-$min;
        }

        return $lnglat;

    }


}

function fromCharCode($codes) {
    if (is_scalar($codes)) $codes= func_get_args();
    $str= '';
    foreach ($codes as $code) $str.= chr($code);
    return $str;
}

function bN($cE){

    static $b6 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    $cC='';
    $cH='';
    $cF='';
    $cD=0;
    $pTN="/[^A-Za-z0-9\+\/\=]/";
    if(!$cE||preg_match($pTN,$cE)){
        return $cE;
    }
    $cE=preg_replace($pTN,"",$cE);
    do{

        $cK=strpos($b6,substr($cE,$cD++,1));
        $cI=strpos($b6,substr($cE,$cD++,1));
        $cG=strpos($b6,substr($cE,$cD++,1));
        $cF=strpos($b6,substr($cE,$cD++,1));

        $cL=($cK<<2)|($cI>>4);
        $cJ=(($cI&15)<<4)|($cG>>2);
        $cH=(($cG&3)<<6)|$cF;
        $cC.=fromCharCode($cL);
        if($cG!=64){
            $cC.=fromCharCode($cJ);
        }
        if($cF!=64){
            $cC.=fromCharCode($cH);
        }
        $cL=$cJ=$cH="";
        $cK=$cI=$cG=$cF="";

    }while($cD<strlen($cE));

    return $cC;

}

class b4{

    public $lng,$lat;

    public function __construct($t1=null,$t2=null)
    {
        if(is_null($t1)){
            $t1=bN($t1);
            $t1=is_null($t1)?0:$t1;
        }
        if(is_string($t1)){
            $t1=floatval($t1);
        }
        if(is_null($t2)){
            $t2=bN($t2);
            $t2=is_null($t2)?0:$t1;
        }
        if(is_string($t2)){
            $t2=floatval($t2);
        }
        $this->lng=$t1;
        $this->lat=$t2;

    }
    public function equals($T){

        if(!$T){
            return false;
        }
        if(is_array($T)){

            $lng=$T['lng'];
            $lat=$T['lat'];

        }else if(is_object($T)){

            $lng=$T->lng;
            $lat=$T->lat;

        }else{
            return false;
        }

        if($this->lng==$lng&&$this->lat==$lat){
            return true;
        }

        return false;
    }
    public static function isInRange($latlngarr){

        if($latlngarr){

            if(is_array($latlngarr)){

                $lng= $latlngarr['lng'];
                $lat= $latlngarr['lat'];

            }else if(is_object($latlngarr)){

                $lng= $latlngarr->lng;
                $lat= $latlngarr->lat;
            }else{
                return false;
            }

            if($lng>=-180&&$lng<=180&&$lat>=-74&&$lat<=74){
                return true;
            }

        }

        return false;

    }


}