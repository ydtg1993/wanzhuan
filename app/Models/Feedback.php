<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Feedback extends Model
{
    protected $table = 'feedbacks';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'contact',
        'detail',
        'created_at',
        'updated_at',
        'status',
        'file_paths'
    ];

    /**
     * 新增反馈建议
     *
     * @author AdamTyn
     *
     * @param array
     * @return void
     */
    public static function addOne($data)
    {
        self::create([
            'user_id'=>$data['user_id'],
            'contact'=>$data['data']['contact'],
            'detail'=>$data['data']['detail'],
            'file_paths'=>implode('@',$data['data']['file_paths']),
            'status'=>0,
            'created_at'=>time()
        ]);
    }

    /**
     * 举报建议
     *
     * @author AdamTyn
     *
     * @param array
     * @return void
     */
    public static function report($data)
    {
        DB::table('report')->insert([
            'user_id'=>$data['user_id'],
            'report_user_id'=>$data['data']['report_user_id'],
            'report_reason'=>$data['data']['report_reason'],
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
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }
}