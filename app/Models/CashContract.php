<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashContract extends Model
{

    protected $table='cash_contracts';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'order_id',
        'number',
        'fee',
        'description',
        'type',
        'status',
        'created_at'
    ];

    /**
     * 关联User
     *
     * @author AdamTyn
     */
    public function user()
    {
        return $this->belongsTo('App\Models\Wallet','user_id','id');
    }
}
