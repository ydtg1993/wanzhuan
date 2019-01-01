<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Play extends Model
{
    protected $table = 'play_man_to_woman';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'user_id',
        'status',
        'created_at'
    ];

    public static function man2man($data)
    {
        DB::table('play_man_to_man')->insert($data);
    }

    public static function man2woman($data)
    {
        DB::table('play_man_to_woman')->insert($data);
    }

    public static function woman2man($data)
    {
        DB::table('play_woman_to_man')->insert($data);
    }

    public static function woman2woman($data)
    {
        DB::table('play_woman_to_woman')->insert($data);
    }
}
