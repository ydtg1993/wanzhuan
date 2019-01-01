<?php

namespace App\Http\Controllers;

use App\Models\Blacklist;
use App\Models\Friend;
use App\Models\Follow;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;


class UserSocialController extends Controller
{

    /**
     * 添加关注接口
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithOtherUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     *
     * @throws \Exception
     */
    public function optFollow(Request $request)
    {
        try {
            Follow::optFollow($request->input('user_id'), $request->input('other_user_id'));
        } catch (QueryException $queryException) {
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0);
    }

    /**
     * 好友列表接口
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithPaginate;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function friends(Request $request)
    {
        try {
            $data = Follow::showFriends($request->input('user_id'), $request->input('paginate'));
        } catch (QueryException $queryException) {
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0,$data);
    }

    /**
     * 关注列表接口
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithPaginate;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function follows(Request $request)
    {
        try {
            $data = Follow::showFollows($request->input('user_id'), $request->input('paginate'));
        } catch (QueryException $queryException) {
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0,$data);
    }

    /**
     * 粉丝列表接口
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithPaginate;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function fans(Request $request)
    {
        try {
            $data= Follow::showFans($request->input('user_id'), $request->input('paginate'));
        } catch (QueryException $queryException) {
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0,$data);
    }

    /**
     * 更新黑名单
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithOtherUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function setBlacklist(Request $request)
    {
        try {
            Blacklist::setBlacklist($request->input('user_id'), $request->input('user_id_1'));
        } catch (QueryException $queryException) {
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0);
    }

    /**
     * 查看黑名单列表
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     * @middleware \App\Http\Middleware\WithPage;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function getBlacklist(Request $request)
    {
        try {
            $data = Blacklist::getBlacklist($request->input('user_id'), $request->input('paginate'));
        } catch (QueryException $queryException) {
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0,$data);
    }
}