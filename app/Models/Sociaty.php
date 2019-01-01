<?php

namespace App\Models;

use App\Models\Traits\OrderTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Sociaty extends Model
{
    protected $table = 'sociaty';
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
