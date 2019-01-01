<?php

namespace App\Models;

use App\Models\Traits\OrderTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NormalOrderTmp extends Model
{
    protected $table = 'normal_orders_temp';
    public $timestamps = false;

    public static function getInfoWhere(array $where)
    {
        return self::where($where)->first();
    }

    public static function upInfoWhere(array $data,array $where)
    {
        return self::where($where)->update($data);
    }
}
