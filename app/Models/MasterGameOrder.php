<?php

namespace App\Models;

use App\Models\InterFaces\Charge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MasterGameOrder extends Model
{
    protected $table='master_gameorder_list';
    public $timestamps = false;
    public static $pageSize = 10;

    public static function add($data)
    {
        return self::insert($data);
    }

    public static function getInfoById($order_id)
    {
        return self::where('order_id',$order_id)->first();
    }

    /**
     * @param $user_id
     * @return mixed
     */
    public static function getList($user_id)
    {
        return (self::where('master_id', $user_id)->orderBy('id','DESC')
            ->get())->toArray();
    }

    /**
     * @param $user_id
     * @return mixed
     */
    public static function getOrderList($user_id)
    {
        return (self::where('master_id', $user_id)->where('order_status', 0)->orderBy('create_time','DESC')->get())->toArray();
    }

    /**
     * @param $user_id
     * @return mixed
     */
    public static function getOrderPageList($user_id,$page)
    {
        return self::where('master_id', $user_id)->where('order_status', 0)
            ->where('is_exclusive', 0)
            ->orderBy('create_time','DESC')
            ->offset(($page - 1) * (self::$pageSize))->limit(self::$pageSize)->get();
    }

    /**
     * @param $user_id
     * @return mixed
     */
    public static function getPersonalOrderList($user_id)
    {
        return self::where('master_id', $user_id)->where('order_status', 0)
            ->where('is_exclusive', 1)
            ->orderBy('create_time','DESC')
            ->get();
    }

    /**
     * @param $user_id
     * @return mixed
     */
    public static function findList($user_id)
    {
        return self::where('master_id',$user_id)
            ->paginate(15);
    }

    /**
     * @param $order_id
     * @param $status
     * @return mixed
     */
    protected static function updataOrder($order_id,$status)
    {
        return self::where('order_id', $order_id)
            ->update(['order_status' => $status]);
    }

}
