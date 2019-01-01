<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GameServer extends BaseModel
{
    protected $table='game_server';

    public static function getServers($game_id)
    {
        return self::where('game_id',$game_id)->get();
    }
}
