<?php

namespace App\Models;

use App\Models\Traits\OrderTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class YueMatch extends Model
{
    protected $table = 'yuewan_match_list';
    public $timestamps = false;

    /**
     * @param $order_id
     * @return mixed
     */
    public static function getMatching($order_id)
    {
        return self::where('order_id',$order_id)
            ->where('status',0)
            ->orderBy('id','DESC')
            ->first();
    }

    /**
     * @param $data
     * @return mixed
     */
    public static function updateOrder($data){
        return self::where('order_id', $data['order_id'])->update($data);
    }
}
