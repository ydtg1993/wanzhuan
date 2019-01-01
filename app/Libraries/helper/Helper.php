<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/17 0017
 * Time: 下午 4:14
 */

namespace App\Libraries\helper;


class Helper
{

    /**
     * 多条件查询二维数组
     *
     * @param $array
     * @param array $params
     * @return array
     */
    public static function multiQuery2Array($array, array $params)
    {
        $data = [];
        foreach ($array as $item) {
            $add = true;
            foreach ($params as $field => $value) {
                if ($item[$field] != $value) {
                    $add = false;
                }
            }
            if ($add) {
                $data[] = $item;
            }
        }

        return $data;
    }

    /**
     * 根据值返回所有键
     * @param $array
     * @param $value
     * @return array
     */
    public static function keysQueryByValue($array, $value)
    {
        $keys = [];
        foreach ($array as $k => $v) {
            if ($v == $value) {
                $keys[] = $k;
            }
        }

        return $keys;
    }

    /**
     * @param $longitude
     * @param $latitude
     */
    public static function randomLbs(&$longitude, &$latitude)
    {
        $rand_long = mt_rand(-10000, 10000);
        $rand_lat = mt_rand(-10000, 10000);

        $longitude = $longitude + $rand_long * 0.000001;
        $latitude = $latitude + $rand_lat * 0.000001;
    }

    /**
     * @param $differ
     * @return string
     */
    static function differTime($differ)
    {
        if ($differ < 60) {
            return $differ . ' 秒前';
        } elseif ($differ < 3600) {
            $minute = floor($differ / 60);
            return $minute . ' 分钟前';
        } elseif ($differ < 86400) {
            $hour = floor($differ / 3600);
            return $hour . ' 小时前';
        } elseif ($differ < (86400 * 7)) {
            $day = floor($differ / 86400);
            return $day . ' 天前';
        } elseif ($differ < (86400 * 30)) {
            $week = floor($differ / (86400 * 7));
            return $week . ' 周前';
        } elseif ($differ < (86400 * 365)){
            $months = floor($differ / (86400 * 30));
            return $months . ' 月前';
        }
        $year = floor($differ / (86400 * 365));
        return $year . ' 年前';
    }

    /**
     * @param $url
     * @param array $vars
     * @param string $method
     * @param int $timeout
     * @param bool $CA
     * @param string $cacert
     * @return int|mixed|string
     */
    static function curlRequest($url, $vars = array(), $method = 'POST', $timeout = 10, $CA = false, $cacert = '')
    {
        $method = strtoupper($method);
        $SSL = substr($url, 0, 8) == "https://" ? true : false;
        if ($method == 'GET' && !empty($vars)) {
            $params = is_array($vars) ? http_build_query($vars) : $vars;
            $url = rtrim($url, '?');
            if (false === strpos($url . $params, '?')) {
                $url = $url . '?' . ltrim($params, '&');
            } else {
                $url = $url . $params;
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout - 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-HTTP-Method-Override: {$method}"));

        if ($SSL && $CA && $cacert) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_CAINFO, $cacert);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else if ($SSL && !$CA) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        if ($method == 'POST' || $method == 'PUT') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
            //curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); //避免data数据过长
        }
        $result = curl_exec($ch);
        $error_no = curl_errno($ch);
        if (!$error_no) {
            $result = trim($result);
        } else {
            $result = $error_no;
        }

        curl_close($ch);
        return $result;
    }


    /**
     * 计算两点地理坐标之间的距离
     * @param  Decimal $longitude1 起点经度
     * @param  Decimal $latitude1 起点纬度
     * @param  Decimal $longitude2 终点经度
     * @param  Decimal $latitude2 终点纬度
     * @param  Int $unit 单位 1:米 2:公里
     * @param  Int $decimal 精度 保留小数位数
     * @return Decimal
     */
    static function getDistance($longitude1, $latitude1, $longitude2, $latitude2, $unit = 2, $decimal = 2)
    {
        $EARTH_RADIUS = 6370.996; // 地球半径系数
        $PI = 3.1415926;

        $radLat1 = $latitude1 * $PI / 180.0;
        $radLat2 = $latitude2 * $PI / 180.0;

        $radLng1 = $longitude1 * $PI / 180.0;
        $radLng2 = $longitude2 * $PI / 180.0;

        $a = $radLat1 - $radLat2;
        $b = $radLng1 - $radLng2;

        $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
        $distance = $distance * $EARTH_RADIUS * 1000;

        if ($unit == 2) {
            $distance = $distance / 1000;
        }

        return round($distance, $decimal);
    }
}