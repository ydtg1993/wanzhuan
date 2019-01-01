<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

class ShareTask extends Model
{
    protected $table = 'share_task';
    public $timestamps = false;
    protected $fillable = ['user_id','achieve_num','reward_num'];

    public static function usersAdd($ids)
    {
        self::whereIn('user_id',$ids)->increment('achieve_num');
        foreach ($ids as $id) {
            self::firstOrCreate(['user_id' => $id],['achieve_num'=>1]);
        }
    }
}
