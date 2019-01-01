<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DynamicRemind extends Model
{
    protected $table = 'dynamic_remind';

    protected $fillable = [
        'dynamic_id',
        'from_id',
        'content',
        'to_id',
        'praise',
        'read'
    ];

    protected $hidden = [
        'updated_at'
    ];

    /**
     * 关联form用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_id');
    }

    /**
     * 关联to用户
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_id');
    }

    /**
     * 所属动态
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dynamic()
    {
        return $this->belongsTo(Dynamic::class,'dynamic_id');
    }
}