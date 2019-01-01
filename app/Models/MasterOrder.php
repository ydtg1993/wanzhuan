<?php

namespace App\Models;

use App\Models\Traits\NormalOrderTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MasterOrder extends Model
{
    use NormalOrderTrait;

    protected $table = 'master_orders';
    public $timestamps = false;
    public static $pP = 10;
    protected $fillable = [
        'user_id',
        'user_id_1',
        'play_order_id',
        'pay_number',
        'status',
        'start_at',
        'end_at',
        'type',
        'score','fee','reduce','result'
    ];

    /**
     * 获取普通订单
     *
     * @author AdamTyn
     *
     * @param string | int
     * @return mixed
     */
    public static function getOrder($data)
    {
        if (DB::table('normal_orders')->where('user_id', $data['user_id'])->doesntExist())
            return null;

        $return = DB::table('normal_orders')
            ->join('normal_orders_temp', 'normal_orders_temp.id', '=', 'normal_orders.play_order_id')
            ->join('games', 'games.id', '=', 'normal_orders_temp.game_id')
            ->join('game_level', 'game_level.id', '=', 'normal_orders_temp.level_id')
            ->join('users', 'normal_orders.user_id_1', '=', 'users.id')
            ->selectRaw('concat(normal_orders.id,"") as id, users.nickname as master_nickname, users.avatar as master_avatar, users.sexy as master_sexy, games.name as game_name,game_level.level_name as game_level,concat(normal_orders_temp.game_num,normal_orders_temp.unit) as game_count, concat(normal_orders.fee,"") as fee, concat(normal_orders.status,"") as status, concat(normal_orders.type,"") as type, concat(normal_orders.score,"") as score, concat(normal_orders.end_at,"") as time')
            ->where('normal_orders.user_id', $data['user_id'])
            ->where('normal_orders_temp.status', '=',1)
            ->get();

        if (count($return) < 1)
            return null;

        return array_slice($return->toArray(), ((intval($data['paginate']) - 1) * (self::$pP)), self::$pP);
    }

    /**
     * 普通订单详情
     *
     * @author AdamTyn
     *
     * @param string | int
     * @return mixed
     */
    public static function getOrderInfo($normal_order_id)
    {
        $return = null;

        if (DB::table('normal_orders')->where('id', $normal_order_id)->doesntExist())
            return null;

        $order = self::where('id', $normal_order_id)->first();
//        $return['status']=strval($order->status);
//        $return['type']=$order->type;
//        $return['fee']=strval($order->fee);
        $return['reduce'] = strval($order->reduce);
        $return['result'] = $order->result;
        $return['game_data'] = DB::table('normal_orders')
            ->join('normal_orders_temp', 'normal_orders_temp.id', '=', 'normal_orders.play_order_id')
            ->join('games', 'games.id', '=', 'normal_orders_temp.game_id')
            ->join('game_server', 'game_server.id', '=', 'normal_orders_temp.server_id')
            ->join('game_level', 'game_level.id', '=', 'normal_orders_temp.level_id')
            ->join('tickets', 'ticket.id', '=', 'normal_orders_temp.ticket_id')
            ->selectRaw('games.name as name, game_level.level_name as level, game_server.server_name as server, concat(normal_orders_temp.game_num,"") as count, normal_orders_temp.unit as unit, concat(normal_orders_temp.price,"") as price')
            ->where('normal_orders.id', $normal_order_id)
            ->first();


        // 拼接订单导师信息
        $return['master']['info'] = DB::table('users')
            ->join('masters', 'users.id', '=', 'masters.user_id')
            ->selectRaw('users.nickname as nickname, concat(users.id,"") as id, users.avatar as avatar, users.sexy as sexy, concat(masters.arg_score,"") as arg_score, concat(masters.order_count,"") as order_count')
            ->where('users.id', $order->user_id_1)
            ->first();

        $return['master']['isFollow'] = DB::table('follows')->where('fan_id', $order->user_id)->where('star_id', $order->user_id_1)->exists();

        // 拼接评分信息
        if (empty($comment = $order->comment()->first()))
            $return['comment'] = null;
        $return['comment']['detail'] = $comment->detail;
        $return['comment']['score'] = strval($comment->score);

        return count($return) < 1 ? null : $return;
    }
}
