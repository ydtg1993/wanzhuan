<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/6 0006
 * Time: 下午 3:47
 */

namespace App\Models;

class AppointmentOrderModel extends BaseModel
{
    protected $table = 'appointment_order';
    public $timestamps = false;

    public static function findOrderListWhere(array $where = [],$page = 1,$limit = 30,$order_by = 'id',$sort = 'ASC')
    {
        $page = $page - 1;
        $start = $page * $limit;
        $data = self::where($where)
            ->where('game_status','>',0)
            ->offset($start)->limit($limit)->orderBy($order_by, $sort)->get();
        if($data){
            return $data->toArray();
        }

        return [];
    }
}