<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirContract extends Model
{

    protected $table='vir_contracts';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'number',
        'money',
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
        return $this->belongsTo('App\Models\User','user_id','id');
    }
}
