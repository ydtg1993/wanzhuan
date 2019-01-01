<?php
namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;;

class TicketController extends Controller
{
    /**
     * 用户优惠券过期
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return void
     */
    public function __construct(Request $request)
    {
//        Ticket::outUserTicket($request->input('user_id'));
    }

    /**
     * 兑换优惠券
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     *
     * @throws \App\Exceptions\TicketException;
     */
    public function newTicket(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        if (!($request->has('ticket_code'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`ticket_code`参数';
        } else {
            try {
                $response['data'] = Ticket::newTicket($request->only([
                    'ticket_code',
                    'user_id',
                    'status'
                ]));
            } catch (QueryException $queryException) {
                $response['code'] = '5002';
                $response['msg'] = '无法响应请求，服务端异常';
            }
        }

        return response()->json($response);
    }

    /**
     * 获取用户优惠券
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithPage;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function showTicket(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );

        if (!($request->has('status'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`status`参数';
        } else {
            try {
                $response['data'] = Ticket::getTicket($request->only('user_id', 'status', 'paginate'));
            } catch (QueryException $queryException) {
                $response['code'] = '5002';
                $response['msg'] = '无法响应请求，服务端异常';
            }
        }

        return response()->json($response);
    }

    /**
     * 获取用户优惠券
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithPage;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function getGameTicket(Request $request)
    {
        $response = array(
            'code' => '0',
            'msg' => 'success'
        );
        if (!($request->has('game_id'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`game_id`参数';
        } else {
            try {
                $response['data'] = Ticket::getGameTicket($request->only('user_id', 'game_id', 'play_type'));
            } catch (QueryException $queryException) {
                $response['code'] = '5002';
                $response['msg'] = '无法响应请求，服务端异常';
            }
        }

        return response()->json($response);
    }

    /**
     * 用户使用优惠券（停用）
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     *
     * @throws \App\Exceptions\TicketException;
     */
    public function useTicket(Request $request)
    {
        $response = array('code' => '0');

        if (!($request->has('ticket_id'))) {
            $response['code'] = '4000';
            $response['msg'] = '请求出错，缺少`ticket_id`参数';
        } else {
            try {
                Ticket::useTicket($request->only('user_id', 'ticket_id'));
            } catch (QueryException $queryException) {
                $response['code'] = '5002';
                $response['msg'] = '无法响应请求，服务端异常';
            }
        }

        return response()->json($response);
    }
}