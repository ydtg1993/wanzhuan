<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DynamicPicture extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'dynamic_id',
        'url', //图片地址
        'sort'   //排序
    ];

    protected $hidden = [
        'id',
        'dynamic_id'
    ];

    /**
     * 替换url
     *
     * @return string|string[]|null
     */
    public function setUrlAttribute($url)
    {
        $this->attributes['url'] = preg_replace("/-[0-9]*\.cos.*\.com/", ".wanzhuanhuyu.cn", $url);
    }
}