<?php

/*自定义函数库*/

function is_idcard( $id )
{
    $id = strtoupper($id);
    $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
    $arr_split = array();
    if(!preg_match($regx, $id))
    {
        return FALSE;
    }
    if(15==strlen($id)) //检查15位
    {
        $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";

        @preg_match($regx, $id, $arr_split);
        //检查生日日期是否正确
        $dtm_birth = "19".$arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
        if(!strtotime($dtm_birth))
        {
            return FALSE;
        } else {
            return TRUE;
        }
    }
    else      //检查18位
    {
        $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
        @preg_match($regx, $id, $arr_split);
        $dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
        if(!strtotime($dtm_birth)) //检查生日日期是否正确
        {
            return FALSE;
        }
        else
        {
            //检验18位身份证的校验码是否正确。
            //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
            $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
            $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
            $sign = 0;
            for ( $i = 0; $i < 17; $i++ )
            {
                $b = (int) $id{$i};
                $w = $arr_int[$i];
                $sign += $b * $w;
            }
            $n = $sign % 11;
            $val_num = $arr_ch[$n];
            if ($val_num != substr($id,17, 1))
            {
                return FALSE;
            }
            else
            {
                return TRUE;
            }
        }
    }

}

function mydebug($test, $debug=false){
    echo '<hr><pre>';
    if($debug){
        var_dump($test);
    }else{
        if(is_string($test)){
            echo $test;
        }else if(is_array($test)){
            print_r($test);
        }else{
            var_dump($test);
        }
    }
    echo '<hr></pre>';
}

/**
 * 获得当前时间（毫秒级）
 * @return int $time 当前时间
 */
function getTime(){
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

/**
 * 中文数字转阿拉伯数字
 * @param string $chinesenum  传递的中文数字
 * @return number $data 返回的阿拉伯数字
 */
function chineseToNum($chinesenum){
    $numberarr=[
        '一' => '1',
        '二' => '2',
        '两' => '2',
        '三' => '3',
        '四' => '4',
        '五' => '5',
        '六' => '6',
        '七' => '7',
        '八' => '8',
        '九' => '9',
        '零' => '0',
    ];
    if(is_numeric($chinesenum)){
        $number=$chinesenum;
    }else{
        $chinesenum=trim($chinesenum);
        $number=$numberarr[$chinesenum];
    }
    return $number;
}

function getRecent12Mouth(){
    $current_year=date('Y', SYS_TIME);
    $current_month=date('m', SYS_TIME);
    $recent12mouth=[];
    for($i=1; $i<=12; $i++){
        if($i<=(int)$current_month){
            if($i>=10){
                $recent12mouth[]=$current_year.'-'.$i;
            }else{
                $recent12mouth[]=$current_year.'-0'.$i;
            }
        }else{
            if($i>=10){
                $recent12mouth[]=(int)($current_year-1).'-'.$i;
            }else{
                $recent12mouth[]=(int)($current_year-1).'-0'.$i;
            }
        }
    }
    return $recent12mouth;
}

/**
 * 简单curl请求
 * @param string $url 请求地址
 * @param array $header 请求头（确保理解http协议、头必须传）<p>
 *      [
 *        'Origin: http://map.baidu.com',
 *        'Referer: http://map.baidu.com/?newmap=1&ie=utf-8',
 *        'Host: map.baidu.com'
 *      ]
 * </p>
 * @param array $postdata post请求数组（get请求直接拼接字符串）
 * @return array
 */
function simpleCurl($url, $header, $postdata=[]){
    if(empty($header)){
        return [];
    }
    import("@third.classes.SimpleCurlClass");
    $curl=new \SimpleCurlClass();
    $default_header=[
        'Cache-Control: no-cache',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8'
    ];
    array_merge($default_header, $header);
    $curl->setOpt(CURLOPT_HTTPHEADER, $default_header);
    if(empty($postdata)){
        $curl->get($url);
    }else{
        $curl->post($url, $postdata);
    }
    $res=[];
    $res['request_head']=$curl->getRequestHeader();
    $res['response_header']=$curl->getResponseHeader();
    $res['response_cookie']=$curl->getResponseCookie();
    $res['response_body']=$curl->getResponseBody();
    $res['response_jumpurl']=$curl->getLastUrl();
    $curl->close();
//    logger('bmap/nampcurl')->write(print_r($res, true));
    return $res;
}

/**
 * 根据百度地图两点经纬度坐标获取其距离
 * @param string $lng1 纬度1
 * @param string $lng2 维度2
 * @param string $lat1 经度1
 * @param string $lat2 经度2
 * @return string
 */
function getDistanceByGeo($lat1, $lng1, $lat2, $lng2){
    if((abs($lat1)>90) || (abs($lat2)>90)){
        return 0;
    }
    if((abs($lng1)>180) || (abs($lng2)>180)){
        return 0;
    }
    $radLat1=rad($lat1);
    $radLat2=rad($lat2);
    $a=$radLat1-$radLat2;
    $b=rad($lng1)-rad($lng2);
    $s=2*asin(sqrt(pow(sin($a/2), 2) + cos($radLat1)*cos($radLat2)*pow(sin($b/2), 2)));
    $s=$s*6370996.81;
    // EARTH_RADIUS; 单位Km
    $s=round($s*10000)/10000;
    $result=intval($s);
    $result.='米';
//    if($s<1000){
//        $result=(intval($s/100)+1)*100;
//        $result.="米以内";
//    }else if($s < 20000){
//        $result=(intval($s/1000)+1);
//        $result.="公里以内";
//    }else{
//        $result="20公里以外";
//    }
    return $result;
}

/**
 * 地球半径
 * private const double EARTH_RADIUS = 6378.137;
 */
function rad($d) {
    return $d*pi()/180.0;
}

function distanceOfTwoPoints($lat1, $lng1, $lat2, $lng2) {
    $radLat1 = rad($lat1);
    $radLat2 = rad($lat2);
    $a = $radLat1 - $radLat2;
    $b = rad($lng1) - rad($lng2);
    $s = 2 * asin(sqrt(pow(sin($a / 2), 2)
            + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
    $s = $s * 6370996.81;
    $s = round($s * 10000) / 10000;
    $ss = $s * 1.0936132983377;
    mydebug("两点间的距离是：" . $s ."米" . "," .  $ss . "码");
}

/**
 * 通过范围 获取最大坐标
 * @param $lat
 * @param $lng
 * @param $raidus
 * @return array
 */
function getAround($lat,$lng,$raidus){
    $PI = 3.14159265;

    $latitude = $lat;
    $longitude = $lng;

    $degree = (24901*1609)/360.0;
    $raidusMile = $raidus;

    $dpmLat = 1/$degree;
    $radiusLat = $dpmLat*$raidusMile;
    $minLat = $latitude - $radiusLat;
    $maxLat = $latitude + $radiusLat;

    $mpdLng = $degree*cos($latitude * ($PI/180));
    $dpmLng = 1 / $mpdLng;
    $radiusLng = $dpmLng*$raidusMile;
    $minLng = $longitude - $radiusLng;
    $maxLng = $longitude + $radiusLng;


    return [$minLat,$maxLat,$minLng,$maxLng];
}

/**
 * 经纬度转换为 x，y 坐标
 * @param $latitude
 * @param $longitude
 * @return array
 */
function BLtoXY($latitude, $longitude)
{
    $_a = 6378245.0;
    $_f = 1.0 / 298.3;
    $zoneWide = 6;
    $PI = 3.14159265353846;
    $iPI = 0.0174532925199433; //3.1415926535898/180.0;
    //ZoneWide = 6; //6度带宽
    $ProjNo = floor($longitude / $zoneWide);
    $longitude0 = $ProjNo * $zoneWide + $zoneWide / 2;
    $longitude0 = $longitude0 * $iPI;
    $latitude0 = 0;
    $longitude1 = $longitude * $iPI; //经度转换为弧度
    $latitude1 = $latitude * $iPI; //纬度转换为弧度
    $e2 = 2 * $_f - $_f * $_f;
    $ee = $e2 * (1.0 - $e2);
    $NN = $_a / sqrt(1.0 - $e2 * sin($latitude1) * sin($latitude1));
    $T = tan($latitude1) * tan($latitude1);
    $C = $ee * cos($latitude1) * cos($latitude1);
    $A = ($longitude1 - $longitude0) * cos($latitude1);
    $M = $_a * ((1 - $e2 / 4 - 3 * $e2 * $e2 / 64 - 5 * $e2 * $e2 * $e2 / 256) * $latitude1 - (3 * $e2 / 8 + 3 * $e2 * $e2 / 32 + 45 * $e2 * $e2 * $e2 / 1024) * sin(2 * $latitude1) + (15 * $e2 * $e2 / 256 + 45 * $e2 * $e2 * $e2 / 1024) * sin(4 * $latitude1) - (35 * $e2 * $e2 * $e2 / 3072) * sin(6 * $latitude1));
    $xval = $NN * ($A + (1 - $T + $C) * $A * $A * $A / 6 + (5 - 18 * $T + $T * $T + 72 * $C - 58 * $ee) * $A * $A * $A * $A * $A / 120);
    $yval = $M + $NN * tan($latitude1) * ($A * $A / 2 + (5 - $T + 9 * $C + 4 * $C * $C) * $A * $A * $A * $A / 24 + (61 - 58 * $T + $T * $T + 600 * $C - 330 * $ee) * $A * $A * $A * $A * $A * $A / 720);
    $X0 = 1000000 * ($ProjNo + 1) + 500000;
    $Y0 = 0;
    $X = round(($xval + $X0) * 100) / 100.0;
    $Y = round(($yval + $Y0) * 100) / 100.0;
    return array($X, $Y);
}


//GCJ-02(火星，高德)坐标转换成BD-09(百度)坐标
//@param bd_lon 百度经度
//@param bd_lat 百度纬度
function bd_encrypt($gg_lon,$gg_lat){
    $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
    $x = $gg_lon;
    $y = $gg_lat;
    $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
    $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
    $bd_lon = $z * cos($theta) + 0.0065;
    $bd_lat = $z * sin($theta) + 0.006;
    // 保留小数点后六位
    $data['bd_lon'] = round($bd_lon, 6);
    $data['bd_lat'] = round($bd_lat, 6);
    return $data;
}



/**
 *计算某个经纬度的周围某段距离的正方形的四个点
 *
 *@param lng float 经度
 *@param lat float 纬度
 *@param distance float 该点所在圆的半径，该圆与此正方形内切，默认值为0.5千米
 *@return array 正方形的四个点的经纬度坐标
 */
function returnSquarePoint($lng, $lat, $distance = 0.8){
    $distance = $distance?$distance:5;
    $earthRadius = 6371;
    $dlng =  2 * asin(sin($distance / (2 * $earthRadius)) / cos(deg2rad($lat)));
    $dlng = rad2deg($dlng);
    $dlat = $distance/$earthRadius;
    $dlat = rad2deg($dlat);
    return array(
        'left-top'=>array('lat'=>$lat + $dlat,'lng'=>$lng-$dlng),
        'right-top'=>array('lat'=>$lat + $dlat, 'lng'=>$lng + $dlng),
        'left-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng - $dlng),
        'right-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng + $dlng)
    );
}


/**
 * 获取范围坐标数据
 * @param $lng
 * @param $lat
 * @param int $distance
 * @return string
 */
function getLngStr($lng,$lat,$distance=2000){
    $lngArr =getAround($lat,$lng,$distance);
    $left = \xl\util\XlUBMap::lngLatToXy($lngArr[2],$lngArr[0]);//转换左下角坐标
    $right = \xl\util\XlUBMap::lngLatToXy($lngArr[3],$lngArr[1]);
    $leftstr = implode(',',$left);
    $rightstr = implode(',',$right);
    $lnstr = $leftstr.';'.$rightstr;
    return $lnstr;
}


//二维数组转化为字符串，中间用,隔开
function toString($arr){
    $str = '';
    foreach ($arr as $value){
        $value = join(",",$value);

        $temp[] = $value;
    }
    foreach($temp as $v){

        $str.=$v.",";
    }
    $str = substr($str,0,-1);  //利用字符串截取函数消除最后一个逗号
    return $str;
}
