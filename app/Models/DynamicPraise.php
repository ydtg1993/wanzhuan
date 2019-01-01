<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DynamicPraise extends Model
{
    protected $fillable = [
        'dynamic_id',
        'user_id',
    ];

    /**
     * 关联动态
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dynamic()
    {
        return $this->belongsTo(Dynamic::class);
    }
}