<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayOrder extends Model
{
    protected $table = 'normal_orders_temp';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'order_id',
        'order_type',
        'order_status',
        'master_type',
        'user_id',
        'master_user_id',
        'pay_number',
        'game_id',
        'level_type',
        'server_id',
        'level_id',
        'game_name',
        'server_name',
        'level_name',
        'ticket_id',
        'unit_price',
        'unit',
        'game_num',
        'real_game_num',
        'room_id',
        'room_details',
        'status',
        'pay_status',
        'pay_type',
        'pay_sum',
        'created_at',
        'updated_at'
    ];

    public static function getOne(int $id)
    {
        $games = PlayOrder::where('id', $id)->with('gameServer')->with('gameLevel')->first();
        return $games;
    }

    /**
     * 获得游戏区服
     */
    public function gameServer()
    {
        return $this->hasOne('App\Models\GameServer', 'id', 'server_id');
    }

    /**
     * 获得游戏段位
     */
    public function gameLevel()
    {
        return $this->hasOne('App\Models\GameLevel', 'id', 'level_id');
    }

    /**
     * @param $order_id
     * @return mixed
     */
    public static function getOrderById($order_id)
    {
        return self::where('order_id', $order_id)->first();
    }
}
