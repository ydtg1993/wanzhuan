<?php

namespace App\Models;

use App\Models\InterFaces\Charge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GameStatus extends Model
{
    protected $table='game_status';
    public $timestamps = false;

    public static function getGoingOrder($user_id,$is_master = true)
    {
        if(!$is_master){
            return self::where('user_id',$user_id)
                ->whereIn('game_status', [0,1])
                ->where('order_status', 1)
                ->orderBy('id', 'desc')
                ->first();
        }
        return self::where('master_user_id',$user_id)
            ->whereIn('game_status', [0,1])
            ->where('order_status', 1)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * @param $order_id
     * @param $master_user_id
     * @return mixed
     */
    public static function getOrderInfo($order_id,$master_user_id)
    {
        return self::where('order_id', $order_id)
            ->where('master_user_id',$master_user_id)
            ->first();
    }

    public static function add($data)
    {
        return DB::table('game_status')->insert($data);
    }

    /**
     * @param $order_id
     * @param $data
     * @return mixed
     */
    public static function updateInfo($order_id,$data)
    {
        return self::where('order_id',$order_id)->update($data);
    }
}
