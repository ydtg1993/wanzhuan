<?php

namespace App\Models;

use App\Models\InterFaces\Charge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WomanCharge extends Model implements Charge
{
    protected $table='game_woman_charge';

    public function getAllByGameIds(array $ids)
    {
        if(count($ids) == 1){
            $id = current($ids);
            return self::where('game_id',$id)->get()->toArray();
        }

        return self::whereIn('game_id',$ids)->get()->toArray();
    }

    public function getCharge($game_id,$level_id)
    {
        return self::where('game_id',$game_id)
            ->first();
    }
}
