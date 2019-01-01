<?php
/**
 * Created by PhpStorm.
 * User: carlyle
 * Date: 2018/8/28
 * Time: 11:19 AM
 */

namespace App\Http\Controllers\entrust;

use App\Http\Controllers\Controller;
use App\Libraries\helper\Helper;
use App\Models\ShareStream;
use App\Models\TicketsType;
use App\Models\Ticket;
use Mockery\Exception;

class PresentTicketController extends Controller
{
    /**
     * 分享赠送券
     * @param $mobile
     * @param $user_id
     * @return mixed
     */
    public function sharePresent($user_id, $mobile)
    {
        $share_stream = ShareStream::getAllByStatus($mobile);
        if ($share_stream == null) {
            return;
        }

        $tickets = [];
        $limit_day = 0;
        foreach ($share_stream as $stream) {
            $stream_tickets = (array)json_decode($stream->tickets);
            $limit_day = (int)$stream->ticket_period;
            foreach ($stream_tickets as $stream_ticket_id=>$num) {
                if(isset($tickets[$stream_ticket_id])){
                    $tickets[$stream_ticket_id] = $tickets[$stream_ticket_id] + $num;
                }else{
                    $tickets[$stream_ticket_id] = $num;
                }
            }
        }

        if (empty($tickets)) {
            return;
        }
        ShareStream::upStatus($mobile);

        $tickets_conf_ids = array_keys($tickets);
        $ticket_infos = TicketsType::getTicketInfos($tickets_conf_ids);
        if ($ticket_infos == null) {
            return;
        }
        $ticket_infos = $ticket_infos->toArray();

        $data = [];
        $now = time();
        $limit_time = date('Y-m-d',$now + 86400 * $limit_day);
        foreach ($tickets as $ticket_id => $num) {
            $ticket = current(Helper::multiQuery2Array($ticket_infos, ['id' => $ticket_id]));
            if (empty($ticket)) {
                continue;
            }

            for ($i = 1; $i <= $num; $i++) {
                $data[] = [
                    'user_id' => $user_id,
                    'play_type' => $ticket['play_type'],
                    'limits' => $ticket['limits'],
                    'desc' => $ticket['desc'],
                    'type' => $ticket['type'],
                    'rule' => $ticket['rule'],
                    'period' => $limit_time,//签到优惠券期限
                    'status' => 1,
                    'get_at' => $now,
                ];
            }
        }

        return Ticket::addTicket($data);
    }
}