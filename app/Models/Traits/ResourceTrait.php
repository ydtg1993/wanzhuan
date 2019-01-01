<?php

namespace App\Models\Traits;

trait ResourceTrait
{
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
