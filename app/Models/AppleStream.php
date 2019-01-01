<?php

namespace App\Models;

use App\Models\Traits\OrderTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AppleStream extends Model
{
    protected $table = 'apple_stream';
    public $timestamps = false;

    public static function add($data)
    {
        return self::insertGetId($data);
    }

    public static function getInfoWhere(array $where)
    {
        return self::where($where)->first();
    }

    public static function upInfoWhere(array $data,array $where)
    {
        return self::where($where)->update($data);
    }
}
