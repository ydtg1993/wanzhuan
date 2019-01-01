<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DynamicCommentPraise extends Model
{
    protected $table = 'dynamic_comments_praises';

    protected $fillable = [
        'dynamic_comments_id',
        'user_id',
    ];


    /**
     * 关联动态
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dynamicComments()
    {
        return $this->belongsTo(DynamicComment::class);
    }
}