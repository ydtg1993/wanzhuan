<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Complaint extends Model
{
    protected $table='complaints';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'master_user_id',
        'complaints_reason',
        'complaints_type',
        'detail',
        'created_at',
        'status',
        'play_order_id',
        'file_paths',
        'updated_at'
    ];

    /**
     * 新增投诉
     *
     * @author AdamTyn
     * @param array
     * @return void
     */
    public static function addOne($data)
    {
        self::create([
            'user_id'=>$data['user_id'],
            'master_user_id'=>$data['data']['master_user_id'],
            'complaints_reason'=>$data['data']['complaints_reason'],
            'complaints_type'=>$data['data']['complaints_type'],
            'detail'=>$data['data']['detail'],
            'file_paths'=>implode('@',$data['data']['file_paths']),
            'status'=> 0,
            'play_order_id'=>$data['data']['play_order_id'],
            'created_at'=>time()
        ]);
    }

    /**
     * 关联函数
     *
     * @author AdamTyn
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id','id');
    }
}