<?php

namespace App\Http\Controllers;

use App\Http\Traits\ShareCode;
use App\Models\Share;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    use ShareCode;
    
    public function index(Request $request)
    {
        if (!$request->has('loc') || !$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        $data = Share::getInfo($request->input('loc'));
        if($data == null){
            return self::$RESPONSE_CODE->Code(5004);
        }
        $code = ShareCode::encodeCode([
            'user_id'=>$request->input('user_id'),
            'share_date'=>date('Ymd')
        ]);

        //$data->link = $data->link .'?code='.$code;

        return self::$RESPONSE_CODE->Code(0,$data);
    }
}