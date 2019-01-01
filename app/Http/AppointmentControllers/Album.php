<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/10 0010
 * Time: 下午 2:49
 */

namespace App\Http\AppointmentControllers;


use App\Libraries\helper\Helper;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\Request;
use Mockery\Exception;

class Album extends Controller
{
    public function getList(Request $request)
    {
        if (!$request->has('u_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        $user_id = $request->input('u_id');
        $list = Resource::getAllWhere(['user_id'=>$user_id,'kind'=>1,'status'=>0],'sort');
        return self::$RESPONSE_CODE->Code(0,$list);
    }


    public function upload(Request $request)
    {
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        if (!$request->has('file_paths')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        try {
            $user_id = $request->input('user_id');
            $list = Resource::getAllWhere(['user_id'=>$user_id,'kind'=>1,'status'=>0]);
            if($list == null){
                $list = [];
            }

            $temp = [];
            foreach ($request->input('file_paths') as $sort=>$path) {
                $sort = $sort + 1;
                $data = Helper::multiQuery2Array($list,['ori_path'=>$path]);
                if(!empty($data)){
                    $data = current($data);
                    $temp[] = $data['id'];
                    //update
                    Resource::upInfoWhere(['sort'=>$sort],['id'=>$data['id']]);
                    continue;
                }
                //add
                Resource::addAlbum($user_id, $path,$sort);
            }

            foreach ($list as $l){
                if(!in_array($l['id'],$temp)){
                    //delete
                    Resource::upInfoWhere(['status'=>1],['id'=>$l['id']]);
                }
            }

            $list = Resource::getAllWhere(['user_id'=>$user_id,'kind'=>1,'status'=>0],'sort');
            $avatar = current($list)['path'];

            User::upInfoWhere(['avatar'=>$avatar],['id'=>$user_id]);
        }catch (Exception $e){
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0,$list);
    }
}