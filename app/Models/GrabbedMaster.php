<?php

namespace App\Models;

use App\Models\InterFaces\Charge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GrabbedMaster extends Model
{
    protected $table='grabbed_master';

    /**
     * @param $order_id
     * @return mixed
     */
    public static function checkGrabbedTotal($order_id)
    {
        return self::where('order_id', $order_id)
            ->count();
    }

    /**
     * @param $order_id
     * @return mixed
     */
    public static function getOrdersById($order_id)
    {
        return self::where('order_id',$order_id)->get();
    }

    /**
     * @param $order_id
     * @param $user_id
     * @return mixed
     */
    public static function getOrder($order_id,$user_id)
    {
        return self::where('order_id', $order_id)
            ->where('user_id', $user_id)
            ->orderBy('user_id', 'ASC')
            ->get();
    }
}
