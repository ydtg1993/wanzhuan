<?php

namespace App\Models;

use App\Http\Common\RedisDriver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Game extends Model
{

    protected $table = 'games';
    protected $fillable = [];
    public $timestamps = false;

    public static function getGameServerLevel($game_id,$server_id,$level_id = 0)
    {
        if($level_id == 0){
            return self::where('games.id',$game_id)
                ->where('game_server.id',$server_id)
                ->join('game_server','game_server.game_id','=','games.id')
                ->first();
        }

        return self::where('games.id',$game_id)
            ->where('game_server.id',$server_id)
            ->where('game_level.id',$level_id)
            ->join('game_server','game_server.game_id','=','games.id')
            ->join('game_level','game_level.game_id','=','games.id')
            ->first();
    }

    public static function getAllWhere(array $where = [])
    {
        return self::where($where)->get();
    }

    /**
     * 获取游戏列表
     *
     * @param int $type
     * @return mixed
     */
    public static function getAll(int $type)
    {
        $games = Game::where('game_type', $type)->where('status', 1)->with('gameServer')->with('gameLevel')->get();
        return $games;
    }

    /**
     * @param $id
     * @return mixed
     */
    public static function getGameInfo($id)
    {
        return self::where('id',$id)->first();
    }

    /**
     * @param int $type
     * @return array
     * @throws \Exception
     */
    public static function getAllList(int $type)
    {
        $games = (Game::where('game_type', $type)->orderBy('sort','asc')->where('status', 1)->with('gameServer')->with('gameLevel')->get())->toArray();
        return $games;
    }

    public static function getDateGameList()
    {
        return (self::where('game_type', 3)->orderBy('sort','DESC')->with('gameServer')->get())->toArray();
    }

    /**
     * 获得游戏区服
     */
    public function gameServer()
    {
        return $this->hasMany('App\Models\GameServer', 'game_id', 'id');
    }

    /**
     * 获得游戏段位
     */
    public function gameLevel()
    {
        return $this->hasMany('App\Models\GameLevel', 'game_id', 'id');
    }

    public static function getPlayGameMoney($sexy)
    {
        $data = DB::table('game_yuewan_charge')->where('player_sex', $sexy)->first();
        return $data->price;
    }
}
