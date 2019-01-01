<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\DB;
use App\Exceptions\SetException;

trait OrderTrait
{
    /**
     * 关联Team
     *
     * @author AdamTyn
     *
     * @param string | int
     * @return boolean
     */
    public static function isMaster($user_id)
    {
        return DB::table('masters')->where('user_id', $user_id)->exists();
    }

    /**
     * 用户评论订单
     *
     * @author AdamTyn
     *
     * @param string
     * @param array
     * @return void
     *
     * @throws \App\Exceptions\SetException;
     */
    public static function setComment($where,$data)
    {
        if (doubleval($data['data']['score'])>5 || doubleval($data['data']['score'])<0)
            throw new SetException('`score`只能0~5分','4000');

        DB::table($where.'_order_comments')->insert([
            'user_id' => intval($data['user_id']),
            'order_id' => intval($data['order_id']),
            'detail' => $data['data']['detail'],
            'score'=>$data['data']['score'],
            'created_at' => time()
        ]);
    }
}