<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Skill extends Model
{
    protected $table = 'skills';
    public $timestamps = false;

    /**
     * 获取标准技能
     *
     * @author AdamTyn
     *
     * @param string
     * @return mixed
     */
    public static function getNormal($user_id)
    {
        $skill = DB::table('skills')
            ->select('id', 'game_name', 'game_server', 'game_level', 'unit', 'price', 'accumulation', 'now_count', 'team_count', 'status')
            ->where('user_id', $user_id)
            ->orderBy('id')
            ->get();

        return count($skill) < 1 ? null : $skill;
    }
}