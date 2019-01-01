<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\DB;

trait TicketTrait
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
