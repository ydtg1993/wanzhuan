<?php

namespace App\Models\Traits;

trait WalletTrait
{
    /**
     * 更新现金
     *
     * @author AdamTyn
     *
     * @param double
     * @return void
     */
    public function setCash($money)
    {
        $this->cash+=$money;
        $this->save();
    }

    /**
     * 更新虚拟币
     *
     * @author AdamTyn
     *
     * @param double
     * @return void
     */
    public function setVir($much)
    {
        $this->vir_money+=$much;
        $this->save();
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