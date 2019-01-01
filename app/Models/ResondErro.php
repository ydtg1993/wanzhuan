<?php

namespace App\Models;

use App\Models\Traits\OrderTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ResondErro extends Model
{
    protected $table = 'resond_erro';
    public $timestamps = false;

    public static function add($order_id,$data,$message)
    {
        self::firstOrCreate(['order_id' => $order_id],['data'=>json_encode($data),'message'=>$message]);
    }
}
