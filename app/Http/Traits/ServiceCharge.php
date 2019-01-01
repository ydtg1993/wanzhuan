<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/21 0021
 * Time: 上午 11:53
 */

namespace App\Http\Traits;

trait ServiceCharge
{
    public static function calculate($price)
    {
        return $price * 0.1;
    }
}