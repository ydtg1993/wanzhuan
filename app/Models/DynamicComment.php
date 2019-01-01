<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class DynamicComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'dynamic_id',
        'from_id',
        'content',
        'to_id',
        'praise',
        'type',
    ];

    protected $hidden = [
        'updated_at',
        'like'
    ];

    protected $appends = ['is_praise'];

    /**
     * 关联动态
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dynamic()
    {
        return $this->belongsTo(Dynamic::class);
    }

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
     * 关联赞
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function like()
    {
        return $this->hasMany(DynamicCommentPraise::class, 'dynamic_comments_id');
    }

    /**
     * 是否已经点过赞
     * @return mixed
     */
    public function getIsPraiseAttribute()
    {
        return $this->like->where('user_id', app('auth')->id())->count();
    }
}