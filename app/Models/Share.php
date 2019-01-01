<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Share extends Model
{
    protected $table = 'share';
    public $timestamps = false;

    public static function getInfo($loc)
    {
        return self::where('loc',$loc)->first();
    }
}
