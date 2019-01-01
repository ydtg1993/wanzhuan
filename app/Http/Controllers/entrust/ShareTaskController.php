<?php
/**
 * Created by PhpStorm.
 * User: carlyle
 * Date: 2018/8/28
 * Time: 11:19 AM
 */

namespace App\Http\Controllers\entrust;

use App\Http\Controllers\Controller;
use App\Models\ShareStream;
use App\Models\ShareTask;
use App\Models\User;

class ShareTaskController extends Controller
{
    public function index($user_id)
    {
        $user_info = User::getBasic($user_id);
        $share_users = ShareStream::getAllShareUser($user_info->mobile);

        if(null == $share_users){
            return;
        }

        $share_user_ids_hash = [];
        foreach ($share_users as $share_user_info){
            if(!isset($share_user_ids_hash[$share_user_info['share_user_id']])) {
                $share_user_ids_hash[$share_user_info['share_user_id']] = true;
            }
        }

        if(empty($share_user_ids_hash)){
            return;
        }

        $share_user_ids = array_keys($share_user_ids_hash);
        ShareTask::usersAdd($share_user_ids);
        ShareStream::upPlayed($user_info->mobile);
    }
}