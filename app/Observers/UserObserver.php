<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * 监听用户创建事件.
     *
     * @param \App\Models\User;
     * @return void
     */
    public function created(User $user)
    {
        Log::channel('model')->info('new user');
    }

    /**
     * 监听用户创建事件.
     *
     * @param \App\Models\User;
     * @return void
     */
    public function updated(User $user)
    {
        //
    }
}