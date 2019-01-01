<?php

namespace App\Models;

use App\Http\Common\RedisDriver;
use Carbon\Carbon;
use function GuzzleHttp\Psr7\str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\Types\Self_;

class MasterRange extends Model
{
    protected $table = 'master_game_range';
    public $timestamps = false;
    protected $fillable = [
        'master_id',
        'game_id',
        'range_content',
        'master_strength',
        'master_probability',
        'service_time',
        'order_count',
        'created_at',
        'updated_at'
    ];

    public static function upInfoWhere(array $data,array $where)
    {
        return self::where($where)->update($data);
    }

    /**
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public static function addOrUpRange($data)
    {
        $params = [
            'master_id' => $data['user_id'],
            'game_id' => $data['game_id'],
        ];
        $temp = self::firstOrNew($params);

        $range_content = [];
        $get_range_content = $data['range_content'];
        if(!isset($get_range_content['game_server']['game_level'])){
            throw new \Exception('数据格式错误');
        }

        if(!isset($get_range_content['game_server']['server_id'])){
            throw new \Exception('数据格式错误');
        }
        $get_game_level = $get_range_content['game_server']['game_level'];
        $get_game_server_id = $get_range_content['game_server']['server_id'];

        if (empty($temp->original)) {
            //新建
            $temp->attributes['created_at'] = TIME;
            $had_range_content['game_server'][$get_game_server_id] = ['game_level'=>$get_game_level];
            $range_content = $had_range_content;
        } elseif (isset($temp->original['range_content'])) {
            //删改
            $had_range_content = json_decode($temp->original['range_content'], true);

            if (empty($had_range_content) && $get_game_level != '') {
                //为空直接覆盖
                $range_content = $had_range_content['game_server'][$get_game_server_id] = ['game_level'=>$get_game_level];
            }

            if(false == empty($had_range_content)){
                $had_range_content_server_ids = array_keys($had_range_content['game_server']);
                if(in_array($get_game_server_id,$had_range_content_server_ids)){
                    if($get_game_level == ''){
                        //删除
                        unset($had_range_content['game_server'][$get_game_server_id]);
                        if(empty($had_range_content['game_server'])){
                            $had_range_content = [];
                        }
                    }else{
                        //修改
                        $had_range_content['game_server'][$get_game_server_id] = ['game_level'=>$get_game_level];
                    }
                }else{
                    //当等级范围不为空 添加
                    if($get_game_level != ''){
                        $had_range_content['game_server'][$get_game_server_id] = ['game_level'=>$get_game_level];
                    }
                }

                $range_content = $had_range_content;
            }
        }
        $temp->attributes['updated_at'] = TIME;
        $temp->attributes['range_content'] = json_encode($range_content);
        $temp->save();
        self::setSearchRange($data['user_id'], $data['game_id'], $range_content);
        //清除缓存
        $cache_key = RedisDriver::getInstance()->getCacheKey('hash.masterRange');
        RedisDriver::getInstance()->redis->hDel($cache_key,$data['user_id']);

        return $temp->id;
    }

    /**
     * @param $master_id
     * @param $game_id
     * @param $range_content
     */
    public static function setSearchRange($master_id, $game_id, $range_content){
        $search_range = DB::table('master_game_range')->where('master_id',$master_id)->where('game_id',$game_id)->first();
        $range_content = [];
        $range_content = json_decode($search_range->range_content, true);
        $searchRangeList = [];
        if(count($range_content)){
            foreach($range_content['game_server'] as $k=>&$v){
                $v['game_level'] = explode(',',$v['game_level']);
            }
            foreach($range_content['game_server'] as $key=>$val){
                foreach($val['game_level'] as $kk=>$vv){
                    $temp = [];
                    $temp['master_id'] = $master_id;
                    $temp['game_id'] = $game_id;
                    $temp['server_id'] = $key;
                    $temp['level_id'] = $vv;
                    $temp['master_level'] = $search_range->master_level;
                    $searchRangeList[] = $temp;
                }
            }
        }
        DB::table('master_game_search_range')->where('master_id',$master_id)->where('game_id',$game_id)->delete();
        if(count($searchRangeList)){
            DB::table('master_game_search_range')->insert($searchRangeList);
        }
        
    }


    /**
     * @param $master_id
     * @param $game_id
     * @return mixed
     */
    public static function getRange($master_id,$game_id)
    {
        return self::where('master_id', $master_id)
            ->where('game_id', $game_id)->first();
    }
}