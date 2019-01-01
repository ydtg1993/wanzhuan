<?php

namespace App\Models;

use App\Models\Traits\OrderTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TeamOrder extends Model
{
    use OrderTrait;

    public static $pP = 10;
    public $timestamps = false;
    protected $table = 'team_orders';
    protected $fillable = [
        'user_id',
        'count',
        'team_id',
        'pay_number',
        'status',
        'start_at',
        'end_at',
        'type',
        'real_fee',
        'reduce',
        'comment',
        'method',
        'back_fee'
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
        $return = null;

        if (DB::table('team_orders')->where('user_id', $data['user_id'])->doesntExist())
            return null;

        if (self::isMaster($data['user_id'])) {
            return null;
        } else {
            $order = self::where('user_id', $data['user_id'])->first();
            // 用户查看普通订单
            $return = DB::table('users')
                ->join('team_orders', 'team_orders.user_id', '=', 'users.id')
                ->join('teams', 'teams.id', '=', 'team_orders.team_id')
                ->selectRaw('concat(team_orders.id,"") as id, users.nickname as leader_nickname, users.avatar as leader_avatar, users.sexy as leader_sexy, teams.name as game_name,teams.level as game_level,concat(team_orders.count,teams.unit) as game_count, concat(team_orders.fee,"") as fee, concat(team_orders.status,"") as status, concat(team_orders.type,"") as type, concat(team_orders.end_at,"") as time, JSON_UNQUOTE(JSON_EXTRACT(team_orders.comment,"$.score")) as score, group_concat(users.avatar Separator "@") as mates')
                ->where('teams.id', $order->team_id)
                ->where('team_orders.status', 1)
                ->groupBy('teams.id')
                ->orderBy('team_orders.end_at', 'DESC')
                ->offset((intval($data['paginate']) - 1) * (self::$pP))->limit(self::$pP)
                ->get();
        }

        return count($return) < 1 ? null : $return;
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
        $return = null;

        if (DB::table('team_orders')->where('id', $data['order_id'])->doesntExist())
            return null;

        $order = self::where('id', $data['order_id'])->first();
        $team = DB::table('teams')->where('id', $order->team_id)->first();

        if (self::isMaster($data['user_id'])) {
            return null;
        } else {
            // 拼接订单数据
            $return['order_data'] = [
                'result' => $team->result,
                'price' => strval($team->price),
                'unit' => $team->unit,
                'name' => $team->name,
                'game' => $team->game,
                'server' => $team->server,
                'level' => $team->level,
                'count' => strval($team->count),
                'score' => strval($team->score)
            ];

            // 拼接成员数据
            $mates = DB::table('users')
                ->join('team_orders', 'team_orders.user_id', '=', 'users.id')
                ->join('teams', 'team_orders.team_id', '=', 'teams.id')
                ->selectRaw('users.nickname as nickname, concat(users.id,"") as id, users.avatar as avatar, users.sexy as sexy, concat(team_orders.count,"") as count, JSON_UNQUOTE(JSON_EXTRACT(team_orders.comment,"$.detail")) as comment_detail, JSON_UNQUOTE(JSON_EXTRACT(team_orders.comment,"$.score")) as comment_score, JSON_UNQUOTE(JSON_EXTRACT(team_orders.comment,"$.time")) as comment_time')
                ->where('team_orders.team_id', $team->id)
                ->orderBy('team_orders.end_at')
                ->get();

            foreach ($mates as $index => $mate) {
                $mate->isFollow = DB::table('follows')->where('fan_id', $data['user_id'])->where('star_id', $mate->id)->exists();
                $return['mate_data'][$index] = new \stdClass;
                $return['mate_data'][$index] = $mate;
            }

            // 拼接导师数据
            $masters = empty($team->masters) ? [] : explode('@', $team->masters);
            $masters[] = $team->user_id;
            $temp = null;
            $index = 0;
            foreach ($masters as $index => $master) {
                $temp = DB::table('users')->join('masters', 'masters.user_id', '=', 'users.id')
                    ->selectRaw('users.nickname as nickname, concat(users.id,"") as id, users.avatar as avatar, users.sexy as sexy, concat(masters.arg_score,"") as arg_score, concat(masters.order_count,"") as order_count')
                    ->where('users.id', '=', $master)->first();
                $temp->isFollow = DB::table('follows')->where('fan_id', $data['user_id'])->where('star_id', $master)->exists();

                if ($master == $team->user_id)
                    $temp->isLeader = true;

                $return['master_data'][$index] = new \stdClass;
                $return['master_data'][$index] = $temp;
            }
//            // 拼接评分信息
//
//            $comment=$order->comment;
//            $return['comment_data'] = null;
//            $temp=null;
//            if(DB::table('team_order_comments')->where('order_id',$data['order_id'])->exists()){
//                DB::table('team_order_comments')->join('users','users.id','=','team_order_comments.user_id')
//                    ->selectRaw('users.sexy as sexy, users.nickname as nickname, users.avatar as avatar, concat(users.id,"") as id, team_order_comments.detail as detail, concat(team_order_comments.score,"") as score, concat(team_order_comments.created_at,"") as time')
//                    ->where('order_id',$data['order_id'])
//                    ->orderBy('team_order_comments.created_at','DESC')->get();
//            }
        }

        return $return;
    }

    /**
     * 关联User
     *
     * @author AdamTyn
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }
}
