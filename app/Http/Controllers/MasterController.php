<?php

namespace App\Http\Controllers;

use App\Http\Traits\RangeSelect;
use App\Libraries\helper\Helper;
use App\Models\Game;
use App\Models\GameStatus;
use App\Models\ManCharge;
use App\Models\MasterRange;
use App\Models\NormalOrder;
use App\Models\OrderComment;
use App\Models\Skill;
use App\Models\Master;
use App\Models\WomanCharge;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class MasterController extends Controller
{
    use RangeSelect;
    /**
     * @Master
     */
    protected $MASTER;

    public function __construct()
    {
        parent::__construct();
        $this->MASTER = new Master();
    }

    /**
     * 用户标准技能
     *
     * @author AdamTyn
     *
     * @middleware \App\Http\Middleware\WithUserID;
     *
     * @param \Illuminate\Http\Request;
     * @return \Illuminate\Http\Response;
     */
    public function normalSkill(Request $request)
    {
        try {
            $data = Skill::getNormal($request->input('user_id'));
        } catch (QueryException $queryException) {
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0, $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function masterInfo(Request $request)
    {
        try {
            if (!$request->has('order_id')) {
                return self::$RESPONSE_CODE->Code(4000);
            }

            $order_id = $request->get('order_id');
            $order_info = (new NormalOrder())->getOrderById($order_id);
            if (empty($order_info)) {
                return self::$RESPONSE_CODE->Code(5004);
            }
            $order_info = $order_info->toArray();

            $master_user_id = (int)$order_info['master_user_id'];
            $game_id = (int)$order_info['game_id'];
            $master_info = $this->MASTER->getMasterRange($master_user_id);
            if (!$master_info) {
                return self::$RESPONSE_CODE->Code(5004);
            }

            self::refactorMasterInfo($master_info, $game_id);

            $data = [];
            if ($request->get('state')) {
                $order_info = NormalOrder::getOrderById($order_id);
                $data['order_info'] = $order_info;
            } else {
                $list = self::getGameLevelList($master_info, $game_id);
                $data['list'] = $list;
            }

            //调整结构
            unset($master_info['range']);
            unset($master_info['authorizes']);
            if (!empty($master_info['user'])) {
                $master_info['nickname'] = $master_info['user']['nickname'];
                $master_info['avatar'] = $master_info['user']['avatar'];
                $master_info['about'] = $master_info['user']['about'];
                $master_info['location'] = $master_info['user']['location'];
                $master_info['hx_id'] = $master_info['user']['hx_id'];
                $master_info['xg_id'] = $master_info['user']['xg_id'];

                $master_conf = config('master');
                $master_level_conf = $master_conf['level'];
                $master_strength_conf = $master_conf['strength'];

                $master_info['master_level'] = isset($master_level_conf[$master_info['master_level']]) ?
                    $master_level_conf[$master_info['master_level']] : $master_level_conf[1];

                $master_info['master_strength'] = isset($master_strength_conf[$master_info['master_strength']]) ?
                    $master_strength_conf[$master_info['master_strength']] : $master_strength_conf[0];
                unset($master_info['user']);
            }
            $masterInfo = DB::table('masters')->where('user_id', $master_user_id)->first();
            $master_info['order_count'] = $masterInfo->order_count;
            $data['master_info'] = $master_info;
        } catch (QueryException $queryException) {
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0, $data);
    }

    /**
     * @param $master_info
     * @param $game_id
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    protected static function getGameLevelList($master_info, $game_id)
    {
        $gender = $master_info['sex'];
        $CHARGE = null;//付费模型
        if ($gender == self::$FA_MALE) {
            $CHARGE = new WomanCharge();
            $game_type = 2;
        } else {
            $game_type = 1;
            $CHARGE = new ManCharge();
        }
        self::$MASTER_GENDER = $gender;
        $list = Game::getAllList($game_type);
        self::refactorList($list);

        //价格查询
        $charge_list = $CHARGE->getAllByGameIds([$game_id]);

        if (empty($master_info)) {
            return self::$RESPONSE_CODE->Code(5004);
        }

        $master_range = self::rangeContentToArray($master_info['range']);
        $master_auth = array_column($master_info['skill'], 'game_id');

        self::$RANGE_SELECT_FILTER = true;
        self::$RANGE_SELECT_CHARGE = true;
        self::$MASTER_CHARGE_LIST = $charge_list;
        self::$SELECT_GAME_ID = $game_id;
        self::selectGameRange($list, $master_range, $master_auth);

        if (!empty($list)) {
            $list = current($list);
            $list['game_server'] = current($list['game_server']);
            $list['game_level'] = $list['game_server']['game_level'];
            unset($list['game_server']);
        } else {
            $list = [
                "id" => '',
                "name" => '',
                "game_type" => '',
                "img_url" => '',
                "game_icon" => '',
                "game_icon_yes" => '',
                "game_icon_no" => '',
                "activity_title" => '',
                "desc" => '',
                "status" => '',
                "auth" => '',
                "isset" => '',
                "game_level" => []
            ];
        }
        return $list;
    }

    /**
     * 设置接单状态
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setMasterStatus(Request $request)
    {
        try {
            $response['data'] = Master::updateInfo($request->only('user_id', 'status'));
        } catch (QueryException $queryException) {
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0);
    }

    /**
     * 获取导师接单范围
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function getMasterRange(Request $request)
    {
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }
        try {
            $master_info = $this->MASTER->getMasterRange($request->get('user_id'));
            if($master_info == null){
                return self::$RESPONSE_CODE->Code(5004);
            }
            $gender = $master_info['sex'];

            if ($gender == self::$FA_MALE) {
                $game_type = 2;
            } else {
                $game_type = 1;
            }
            self::$MASTER_GENDER = $gender;

            $list = Game::getAllList($game_type);
            self::refactorList($list, true);

            $master_range = self::rangeContentToArray($master_info['range']);
            $master_auth = array_column($master_info['skill'], 'game_id');

            self::selectGameRange($list, $master_range, $master_auth);

        } catch (QueryException $queryException) {
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0, $list);
    }

    /**
     * 设置导师接单范围
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function setMasterGameRange(Request $request)
    {
        try {
            $data = $request->only('user_id', 'game_id', 'range_content');
            $result =  MasterRange::addOrUpRange($data);
            if (!$result) {
                return self::$RESPONSE_CODE->Code(5005);
            }
        } catch (QueryException $queryException) {
            return self::$RESPONSE_CODE->Code(5002);
        }

        return self::$RESPONSE_CODE->Code(0);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkMasterGameStauts(Request $request)
    {
        if (!$request->has('user_id')) {
            return self::$RESPONSE_CODE->Code(4000);
        }

        $master_id = $request->input('user_id');
        
        $master = $this->MASTER->masterInfo($master_id);
        if (!$master) {
            return self::$RESPONSE_CODE->Code(5004,(object)[]);
        }

        $msg = [1 => '关闭接单', 2 => '开启接单', 3 => '接单中'];
        $data = ['status' => $master->status, 'msg' => $msg[$master->status], 'order_id' => ''];
        if ($master->status == 3) {
            $order_info = GameStatus::getGoingOrder($master_id);
            if ($order_info) {
                $data['order_id'] = $order_info->order_id;
            }
        }
        return self::$RESPONSE_CODE->Code(0, $data);
    }

}