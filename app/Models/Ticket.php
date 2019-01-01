<?php

namespace App\Models;

use App\Exceptions\TicketException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Traits\TicketTrait;

class Ticket extends Model
{
    use TicketTrait;

    protected $table = 'tickets';
    public $timestamps = false;
    public static $pP = 10;

    protected $fillable = [
        'play_type',
        'limits',
        'desc',
        'type',
        'rule',
        'period',
        'status',
        'user_id',
        'get_at',
        'used_at'
    ];

    /**
     * @param $data
     * @return mixed
     */
    public static function addTicket($data)
    {
        return self::insert($data);
    }

    /**
     * 兑换优惠券（停用）
     *
     * @author AdamTyn
     *
     * @param array
     * @return mixed
     *
     * @throws \App\Exceptions\TicketException;
     */
    public static function newTicket($data)
    {
        $return = null;

        $ticket = self::where('code', $data['ticket_code'])
            ->where('status', 0)
            ->first();

        if (empty($ticket))
            throw new TicketException('优惠券不存在或已经被兑换', '1002');

        $ticket->user_id = $data['user_id'];
        $ticket->status = 1;
        $ticket->got_at = time();
        $ticket->save();

        foreach (['1', '2', '3'] as $value) {
            $return['count'][$value . '_count'] = strval(DB::table('tickets')
                ->where('user_id', $data['user_id'])
                ->where('status', $value)
                ->count());
        }

        $return['ticket'] = DB::table('tickets')
            ->selectRaw('concat(id,"") as id,concat(status,"") as status,concat(type,"") as type,limits,concat(period,"") as period,rule')
            ->where('code', $data['ticket_code'])
            ->where('status', 1)
            ->first();

        return $return;
    }

    /**
     * 获取用户优惠券
     *
     * @author AdamTyn
     *
     * @param array
     * @param array
     * @return mixed
     */
    public static function getTicket($data)
    {
        $return = [];

        if ((self::where('user_id', $data['user_id'])->count() < 1)) {
            $return = [];
        }

        $return['tickets'] = DB::table('tickets')
            ->where('user_id', $data['user_id'])
            ->where('status', $data['status'])
            ->offset((intval($data['paginate']) - 1) * (self::$pP))
            ->limit((self::$pP))
            ->get();

        foreach ($return['tickets'] as $key => &$val) {
            if(!$val->limits){
                $val->desc = '适用于导师所有游戏';
                if($val->play_type == 2){
                    $val->desc = '适用于部落所有游戏';
                }
                
            }else{
                $gameIdArray = explode(",", $val->limits);
                $gameName = DB::table('games')->whereIn('id', $gameIdArray)->get();
                $gameNameTemp = '';
                foreach ($gameName as $k => $v) {
                    $gameNameTemp .= $v->name . ',';
                }
                if($gameNameTemp){
                    $gameNameTemp = trim($gameNameTemp, ',');
                    $val->desc = '仅限 ' . $gameNameTemp;
                }
            }
        }

        foreach (['1', '2', '3'] as $value) {
            $return['statistics']['count_' . $value] = strval(DB::table('tickets')
                ->where('user_id', $data['user_id'])
                ->where('status', $value)
                ->count());
        }
        return $return;
    }

    public static function getUnusedTicketById($user_id, $id)
    {
        return DB::table('tickets')->where('id', $id)
            ->where('user_id', $user_id)
            ->where('status', 1)->first();
    }

    /**
     * 游戏优惠券接口
     *
     * @author AdamTyn
     *
     * @param array
     * @param array
     * @return mixed
     */
    public static function getGameTicket($data)
    {
        $return = [];
        $gameList = DB::table('tickets')
            ->where('user_id', $data['user_id'])
            ->where('play_type', $data['play_type'])
            ->where('status', 1)
            ->get();
        foreach ($gameList as $key => $val) {
            if(!$val->limits){
                $return[] = $val;
            }else{
                $tempGame = explode(",", $val->limits);
                if(in_array($data['game_id'],$tempGame)){
                    $return[] = $val;
                }
            }
        }
        return $return;
    }

    /**
     * 使用优惠券（停用）
     *
     * @author AdamTyn
     *
     * @param array
     * @return void
     *
     * @throws \App\Exceptions\TicketException;
     */
    public static function useTicket($data)
    {
        $ticket = self::where('id', $data['ticket_id'])
            ->where('status', 1)
            ->first();

        if (empty($ticket))
            throw new TicketException('优惠券不存在或已经被使用', '1003');

        $ticket->status = 2;
        $ticket->used_at = time();
        $ticket->save();
    }

    /**
     * 用户优惠券过期
     *
     * @author AdamTyn
     *
     * @param string
     * @return void
     */
    public static function outUserTicket($user_id)
    {
        $tickets = self::where('user_id', $user_id)->where('status', 1)->get();

        if (!empty($tickets)) {
            $period = null;
            foreach ($tickets as $ticket) {
                $period = explode('@', $ticket->period);

                if (count($period) > 0 && intval($period[1]) < time()) {
                    $ticket->status = 3;
                    $ticket->save();
                }
            }
        }
    }

    /**
     * 发放优惠券（停用）
     *
     * @author AdamTyn
     *
     * @param array
     * @return mixed
     */
    public static function pushTickets($user_id)
    {
        return null;
    }

    /**
     * 所有用户优惠券过期
     *
     * @author AdamTyn
     *
     * @return void
     */
    public static function outAllTicket()
    {
        $tickets = self::where('status', 1)->get();

        if (isset($tickets)) {
            $period = null;
            foreach ($tickets as $ticket) {
                $period = explode('@', $ticket->period);

                if (count($period) > 0 && (intval($period[1]) < time())) {
                    $ticket->status = 3;
                    $ticket->save();
                }
            }
        }
    }
}
