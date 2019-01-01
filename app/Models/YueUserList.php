<?php

namespace App\Models;

use App\Models\Traits\OrderTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class YueUserList extends Model
{
    protected $table = 'yuewan_user_list';
    public $timestamps = false;

    /**
     * @param $data
     * @return mixed
     */
    public static function add($data)
    {
        return self::insertGetId(
            [
                'user_id' => $data['user_id'],
                'game_id' => $data['game_id'],
                'server_id' => $data['server_id'],
                'order_id' => $data['order_id'],
                'sexy' => $data['sexy'],
                'search_sexy' => $data['search_sexy']
            ]);
    }
}
