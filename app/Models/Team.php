<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Team extends Model
{
    protected $table = 'codes';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'masters',
        'result',
        'name',
        'game_name_id',
        'game_server_id',
        'game_level_id',
        'unit',
        'score',
        'count',
        'price',
        'type',
        'status'
    ];

    /**
     * 获取订单导师
     *
     * @author AdamTyn
     */
    public function leader()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }

    /**
     * 关联TeamOrder
     *
     * @author AdamTyn
     */
    public function teamOrders()
    {
        return $this->hasMany('App\Models\TeamOrder', 'team_id', 'id');
    }
}