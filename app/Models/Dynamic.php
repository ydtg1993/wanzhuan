<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Dynamic extends Model
{

    protected $fillable = [
        'user_id',
        'content', //内容
        'praise',   //赞
        'comments_num',//评论数
    ];

    protected $hidden = [
        'updated_at',
        'like'
    ];

    protected $appends = ['is_praise'];

    public static function boot()
    {
        parent::boot();

        static::deleted(function ($model) {
            $model->comments()->delete();
            $model->pictures()->delete();
        });
    }

    /**
     * 动态配图
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pictures()
    {
        return $this->hasMany(DynamicPicture::class)->orderBy('sort', 'ASC');
    }

    /**
     * 动态评论
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(DynamicComment::class)->orderBy('created_at', 'DESC');
    }

    /**
     * 关联赞
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function like()
    {
        return $this->hasMany(DynamicPraise::class, 'dynamic_id');
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