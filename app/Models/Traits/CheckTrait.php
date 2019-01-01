<?php

namespace App\Models\Traits;

trait CheckTrait
{
    /**
     * 用户签到信息
     *
     * @author AdamTyn
     *
     * @param string
     * @return array
     */
    public function getCheck()
    {
        return array(
            'contain' => strval($this->contain),
            'last_time' => strval($this->last_time),
            'recent_time' => strval($this->recent_time)
        );

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
