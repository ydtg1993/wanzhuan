<?php

namespace App\Models;

use App\Models\Traits\OrderTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class YueOrder extends Model
{
    protected $table = 'yuewan_orders';
    public $timestamps = false;

    /**
     * @param $order_id
     * @return mixed
     */
    public static function getOrderInfo($order_id)
    {
        return self::where('order_id',$order_id)->first();
    }

    /**
     * @param $data
     * @return mixed
     */
    public static function updateOrder($data){
        return self::where('order_id', $data['order_id'])->update($data);
    }
}
