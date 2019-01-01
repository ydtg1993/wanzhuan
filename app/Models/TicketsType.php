<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mockery\Exception;

class TicketsType extends Model
{
    protected $table = 'tickets_type';
    public $timestamps = false;

    /**
     * @param $id
     * @return mixed
     */
    public static function getTicketInfo($id)
    {
        return self::where('id',$id)
            ->first();
    }

    /**
     * @param array $ids
     * @return mixed
     */
    public static function getTicketInfos(array $ids)
    {
        return self::whereIn('id',$ids)->get();
    }
}
