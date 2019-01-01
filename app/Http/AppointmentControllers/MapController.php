<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/6 0006
 * Time: 下午 2:58
 */

namespace App\Http\AppointmentControllers;

use App\Libraries\helper\Helper;
use App\Models\Follow;
use App\Models\Geo;
use App\Models\HotCityModel;
use App\Models\Resource;
use Illuminate\Http\Request;

class MapController extends Controller
{
    public function getUserList(Request $request)
    {
        if(!$request->has('center_longitude')){
            return self::$RESPONSE_CODE->Code(4000);
        }
        if(!$request->has('center_latitude')){
            return self::$RESPONSE_CODE->Code(4000);
        }
        if(!$request->has('edge_longitude')){
            return self::$RESPONSE_CODE->Code(4000);
        }
        if(!$request->has('edge_latitude')){
            return self::$RESPONSE_CODE->Code(4000);
        }
        if(!$request->has('game_id')){
            return self::$RESPONSE_CODE->Code(4000);
        }
        if(!$request->has('user_id')){
            return self::$RESPONSE_CODE->Code(4000);
        }

        $distance = Helper::getDistance(
            $request->input('center_longitude'),
            $request->input('center_latitude'),
            $request->input('edge_longitude'),
            $request->input('edge_latitude'),
            2
        );

        $users = Geo::getNear(
            $request->input('user_id'),
            $request->input('game_id'),
            $request->input('center_longitude'),
            $request->input('center_latitude'),
            $distance
        );

        return self::$RESPONSE_CODE->Code(0,$users);
    }

    public function getUserInfo(Request $request)
    {
        if(!$request->has('user_id')){
            return self::$RESPONSE_CODE->Code(4000);
        }
        if(!$request->has('other_user_id')){
            return self::$RESPONSE_CODE->Code(4000);
        }

        $user_id = $request->input('user_id');
        $other_user_id = $request->input('other_user_id');

        $data = [];
        try {
            $data['distance'] = Geo::getDistance($user_id, $other_user_id );
            $data['user_info'] = Resource::getImageAndAudio($other_user_id );
            $data['user_info']['user_info']['is_follow'] = 0;

            if(Follow::getInfoWhere(['star_id'=>$other_user_id ,'fan_id'=>$user_id])){
                $data['user_info']['user_info']['is_follow'] = 1;
                if(Follow::getInfoWhere(['star_id'=>$user_id ,'fan_id'=>$other_user_id])){
                    $data['user_info']['user_info']['is_follow'] = 2;
                }
            }
        }catch (\Exception $e){
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0,$data);
    }

    public function getHotCity()
    {
        $data = HotCityModel::getAllWhere();
        return self::$RESPONSE_CODE->Code(0,$data);
    }
}