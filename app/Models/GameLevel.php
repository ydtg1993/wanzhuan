<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\Self_;

class GameLevel extends Model
{
    protected $table='game_level';

    public static function getFirstLevel($game_id)
    {
        return self::where('game_id',$game_id)
            ->first();
    }

    public static function getInfoWhere(array $where)
    {
        return self::where($where)->first();
    }
}
