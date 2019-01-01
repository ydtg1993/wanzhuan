<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/6 0006
 * Time: 下午 2:49
 */

namespace App\Http\AppointmentControllers;


use App\Models\Game;
use Illuminate\Http\Request;
use JunkMan\JunkMan;

class GameController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function GameList()
    {
        try {
            $data = Game::getAllList(3);
        }catch (\Exception $e){
            echo $e->getLine().$e->getMessage();exit;
        }
        return self::$RESPONSE_CODE->Code(0,$data);
    }

}