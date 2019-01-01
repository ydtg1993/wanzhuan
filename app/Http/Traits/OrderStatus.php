<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/21 0021
 * Time: 上午 11:53
 */

namespace App\Http\Traits;

trait OrderStatus
{
    protected static $order_status_map = [
        1000 => '成功',
        1001 => '你当前不可接单',
        1002 => '订单已被取消',
        1003 => '该订单已被抢',
        1004 => '订单已结束',
        1005 => '订单不能被取消',
        1006 => '你已经抢单成功,等待用户确定',
        1007 => '抢单人数已满',

        1008 => '你的余额不足,无法支付订单',
        1009 => '你正在服务中,无法接单',

        2000 => '订单支付成功',
        2001 => '订单未支付',
        2002 => '订单已取消',
        2003 => '订单已完成',
        2004 => '订单退款中',
        2005 => '订单退款完成',

        2010 => '查不到订单',
    ];

    /**
     * 订单错误配置表
     *
     * @param $code
     * @return array
     */
    protected static function orderStatusCode($code)
    {
        return ['status' => $code, 'message' => self::$order_status_map[$code]];
    }
}