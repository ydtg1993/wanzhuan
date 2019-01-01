<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/17 0017
 * Time: 下午 3:43
 */

namespace App\Models\InterFaces;


interface Charge
{
    public function getAllByGameIds(array $ids);
    public function getCharge($game_id,$level_id);
}