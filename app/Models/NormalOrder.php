<?php

namespace App\Models;

use App\Models\Traits\OrderTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NormalOrder extends Model
{
    use OrderTrait;

    protected $table = 'normal_orders';
    public $timestamps = false;
    public static $pageSize = 10;

    protected $fillable = [
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
        'money_sum',
        'back_sum',
        'back_type',
        'ticket_sum',
        'pay_at',
        'game_result',
        'comment'
    ];

    /**
     * 分页查询订单评论
     * @param $user_id
     * @return mixed
     */
    public static function findCommentList($user_id)
    {
        return self::where('normal_orders.master_user_id',$user_id)
            ->join('order_comment', 'order_comment.order_id', '=', 'normal_orders.order_id')
            ->join('users','users.id','=','normal_orders.user_id')
            ->selectRaw('normal_orders.game_name,
            normal_orders.server_name,
            normal_orders.user_id,
            normal_orders.master_user_id,
            normal_orders.unit,
            normal_orders.order_id,
            normal_orders.level_name,
            normal_orders.room_id,
            normal_orders.game_num,
            normal_orders.real_game_num,
            order_comment.*,
            FROM_UNIXTIME(order_comment.created_at,\'%Y-%m-%d %H:%i:%s\') as create_date,
            users.avatar,
            users.nickname')
            ->paginate(15);
    }

    /**
     * @param $data
     * @return int
     */
    public static function saveOrder($data)
    {
        return DB::table('normal_orders')->insertGetId($data);
    }

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
        $return = [];
        // 用户查看普通订单
        $return = DB::table('normal_orders')->where('user_id', $data['user_id'])->where('status', 2)
            ->orderBy('id', 'DESC')
            ->offset((intval($data['paginate']) - 1) * (self::$pageSize))->limit(self::$pageSize)
            ->get();
        foreach ($return as $key => &$val) {
            if($val->master_user_id){
                $val->master_user_info = DB::table('users')->where('id', $val->master_user_id)->first();
            }else{
                $val->master_user_info = (object)null;
            }

            if($val->level_type == 1){
                $val->level_type = '普通';
            }else if($val->level_type == 2){
                $val->level_type = '优选';
            }else if($val->level_type == 3){
                $val->level_type = '超级';
            }else{
                $val->level_type = '';
            }

            if($val->comment){
                $comment_info = DB::table('order_comment')->where('id', $val->comment)->where('type',1)->first();
                if($comment_info){
                    $val->comment_info = $comment_info;
                }else{
                    $val->comment_info = (object)null;
                }
            }else{
                $val->comment_info = (object)null;
            }
        }
        return $return;
    }


    /**
     * 获取普通订单
     *
     * @author AdamTyn
     *
     * @param string | int
     * @return mixed
     */
    public static function getYuewanOrder($data)
    {
        $return = [];
        // 用户查看普通订单
        $return = DB::table('yuewan_orders')->where('user_id', $data['user_id'])->where('status', 2)
            ->orderBy('id', 'DESC')
            ->offset((intval($data['paginate']) - 1) * (self::$pageSize))->limit(self::$pageSize)
            ->get();
        foreach ($return as $key => &$val) {
            if($val->match_user_id){
                $val->match_user_info = DB::table('users')->where('id', $val->match_user_id)->first();
            }else{
                $val->match_user_info = (object)null;
            }
        }
        return $return;
    }

    /**
     * @param $order_id
     * @return mixed
     */
    public static function getOrderById($order_id)
    {
        return self::where('order_id', $order_id)->first();
    }

    /**
     * @param $order_id
     * @param $data
     * @return mixed
     */
    public static function updateOrder($order_id, $data)
    {
        return self::where('order_id', $order_id)->update($data);
    }

    /**
     * 普通订单详情
     *
     * @author AdamTyn
     *
     * @param string | int
     * @return mixed
     */
    public static function getOrderInfo($data)
    {
        $orderInfo = DB::table('normal_orders')->where('order_id', $data['order_id'])->first();
        if($orderInfo->ticket_id){
            $ticket_info = DB::table('tickets')->where('id', $orderInfo->ticket_id)->first();
            if($ticket_info){
                $orderInfo->ticket_info = $ticket_info;
            }else{
                $orderInfo->ticket_info = (object)null;
            }
        }else{
            $orderInfo->ticket_info = (object)null;
        }

        if($orderInfo->master_user_id){
            $orderInfo->master_user_info = DB::table('users')->where('id', $orderInfo->master_user_id)->first();
        }else{
            $orderInfo->master_user_info = (object)null;
        }
            
        if($orderInfo->comment){
            $comment_info = DB::table('order_comment')->where('id', $orderInfo->comment)->where('type',1)->first();
            if($comment_info){
                $orderInfo->comment_info = $comment_info;
            }else{
                $orderInfo->comment_info = (object)null;
            }
        }else{
            $orderInfo->comment_info = (object)null;
        }



        if($orderInfo->game_result){
            $gameResult = json_decode($orderInfo->game_result, true);
            $game_result = implode('|', $gameResult);
            $orderInfo->game_result = $game_result;
        }
        return $orderInfo;
    }

    public static function setComment($user_id, $data)
    {
        $orderInfo = DB::table('normal_orders')->where('order_id', $data['order_id'])->first();
        $master_user_id = 0;
        if($orderInfo){
            $master_user_id = $orderInfo->master_user_id;
        }
        DB::table('order_comment')->where('order_id', $data['order_id'])->where('type',1)->delete();
        DB::table('order_comment')->insert([
            'user_id' => $user_id,
            'type' => 1,
            'order_id' => $data['order_id'],
            'master_user_id' => $master_user_id,
            'attitude_score' => $data['comprehensive_score'],
            'technology_score' => $data['comprehensive_score'],
            'efficiency_score' => $data['comprehensive_score'],
            'comprehensive_score' => $data['comprehensive_score'],
            'content' => $data['content'],
            'created_at' => time()
        ]);
        $id = DB::getPdo()->lastInsertId();
        DB::table('normal_orders')->where('order_id', $data['order_id'])->update(['comment'=>$id]);
        if($master_user_id){
            $arg_score = 0;
            $arg_score = DB::table('order_comment')->where('master_user_id', $master_user_id)->avg('comprehensive_score');
            $arg_score = round($arg_score,1);
            DB::table('masters')->where('user_id', $master_user_id)->update(['arg_score'=>$arg_score]);
            $game_comment_count = DB::table('normal_orders')->where('master_user_id', $orderInfo->master_user_id)->where('game_id', $orderInfo->game_id)->where('comment', '>', 0)->count();
            DB::table('skills')->where('master_user_id', $master_user_id)->where('game_id', $orderInfo->game_id)->update(['comment_count' => $game_comment_count]);
        }
        return true;
    }

    /**
     * 关联User
     *
     * @author AdamTyn
     */

    public function masterUser()
    {
        return $this->belongsTo('App\Models\User', 'master_user_id', 'id');
    }

}
