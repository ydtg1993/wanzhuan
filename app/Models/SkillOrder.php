<?php

namespace App\Models;

use App\Models\Traits\OrderTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SkillOrder extends Model
{
    use SoftDeletes,OrderTrait;

    protected $table='skill_orders';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'user_id_1',
        'skill_id',
        'pay_number',
        'status',
        'start_at',
        'end_at',
        'comment',
        'result',
        'reduce',
        'type'
    ];

    /**
     * 关联User
     *
     * @author AdamTyn
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }
}
