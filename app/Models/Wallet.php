<?php

namespace App\Models;

use App\Exceptions\AuthException;
use App\Models\Traits\WalletTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    protected $table = 'wallets';
    public $timestamps = false;
    public static $pP = 10;
    protected static $rate = 0.1;
    protected $fillable = [
        'vir_money',
        'cash',
        'user_id'
    ];

    /**
     * 查看交易记录
     *
     * @author AdamTyn
     *
     * @param array
     * @return mixed
     */
    public static function getContract(array $data)
    {
        $return = DB::table('user_transaction')
            ->where('user_id', $data['user_id'])
            ->orderBy('id','desc')
            ->offset((intval($data['paginate'] - 1)) * self::$pP)
            ->limit(self::$pP)
            ->get();
        return $return;
    }

    /**
     * 查看用户钱包
     *
     * @author AdamTyn
     *
     * @param string
     * @return array
     */
    public static function getWallet($user_id)
    {
        $wallet = self::firstOrCreate(['user_id' => $user_id]);
        $wallet->vir_money = (int)$wallet->vir_money;
        $wallet->cash = (int)$wallet->cash;
        return [
            'vir_money' => strval($wallet->vir_money),
            'cash' => strval($wallet->cash)
        ];
    }

    /**
     * 充值钱包接口
     *
     * @author AdamTyn
     *
     * @param array
     * @return array
     */
    public static function recharge($data)
    {
        $wallet = self::firstOrCreate(['user_id' => $data['user_id']]);

        $order_id_main = 'recharge_' . date('YmdHis');
        $random = rand(10000000, 99999999);
        $id = (100 - $random % 100) % 100;
        $order_id = $order_id_main . str_pad($id, 6, '0', STR_PAD_LEFT);

        $rechargeData = [];
        $rechargeData['user_id'] = $data['user_id'];
        $rechargeData['order_id'] = $order_id;
        $rechargeData['money'] = $data['money'];
        $rechargeData['pay_type'] = $data['pay_type'];
        $rechargeData['status'] = 0;
        $rechargeData['created_at'] = time();
        $rechargeData['updated_at'] = time();
        $res = DB::table('recharge_orders')->insert($rechargeData);
        if (!$res) {
            throw new AuthException('创建订单失败', '2099');
        }
        return $rechargeData;
    }

    /**
     * 充值钱包接口
     *
     * @author AdamTyn
     *
     * @param array
     * @return array
     */
    public static function bond($data)
    {
        $authorizesData = DB::table('authorizes')->where('user_id', $data['user_id'])->select('id', 'user_id', 'game_id', 'status')->orderBy('id', 'desc')->first();
        if (!$authorizesData) {
            throw new AuthException('此游戏导师认证不存在', '2096');
        }

        if ($authorizesData->status >= 3) {
            throw new AuthException('此游戏已交保证金', '2097');
        }

        $order_id_main = 'bond_' . date('YmdHis');
        $random = rand(10000000, 99999999);
        $id = (100 - $random % 100) % 100;
        $order_id = $order_id_main . str_pad($id, 6, '0', STR_PAD_LEFT);

        $rechargeData = [];
        $rechargeData['user_id'] = $data['user_id'];
        $rechargeData['order_id'] = $order_id;
        $rechargeData['game_id'] = $authorizesData->game_id;
        $rechargeData['money'] = $data['money'];
        //$rechargeData['money'] = 1;
        $rechargeData['pay_type'] = $data['pay_type'];
        $rechargeData['status'] = 0;
        $rechargeData['created_at'] = time();
        $rechargeData['updated_at'] = time();

        $res = DB::table('bond_orders')->insert($rechargeData);
        if (!$res) {
            throw new AuthException('创建订单失败', '2099');
        }
        return $rechargeData;
    }

    /**
     *
     * @author AdamTyn
     *
     * @param array
     * @return array
     */
    public static function bondWallet($data)
    {
        try {
            $skillData = $transactionData = [];
            $transactionData['user_id'] = $data['user_id'];
            $transactionData['order_id'] = $data['order_id'];
            $transactionData['money'] = $data['money'] * -1;
            $transactionData['title'] = '缴纳保证金';
            $transactionData['desc'] = '余额缴纳保证金';
            $transactionData['type'] = 5;
            $transactionData['status'] = 1;
            $transactionData['created_at'] = time();
            DB::table('user_transaction')->insert($transactionData);
            DB::table('wallets')->where('user_id', $data['user_id'])->decrement('cash', $data['money']);
            $user = DB::table('users')->where('id', $data['user_id'])->first();

            $authorizesInfo = DB::table('authorizes')->where('user_id', $data['user_id'])->where('status', 2)->orderBy('id', 'desc')->first();
            $skillData['master_user_id'] = $authorizesInfo->user_id;
            $skillData['game_id'] = $authorizesInfo->game_id;
            $skillData['game_name'] = $authorizesInfo->game_name;
            $skillData['server_id'] = $authorizesInfo->server_id;
            $skillData['game_server'] = $authorizesInfo->game_server;
            $skillData['level_id'] = $authorizesInfo->level_id;
            $skillData['game_level'] = $authorizesInfo->game_level;
            $skillData['unit'] = '小时';
            $skillData['created_at'] = time();
            $skillData['status'] = 1;

            $level_type = 1;
            if($user->sexy == '男'){
                $priceInfo = DB::table('game_man_charge')->where('game_id', $authorizesInfo->game_id)->where('server_id', $authorizesInfo->server_id)->where('level_id', $authorizesInfo->level_id)->first();
                if($priceInfo){
                    $skillData['unit'] = $priceInfo->unit;
                    if($user->user_level == 1){
                        $level_type = 1;
                        $skillData['price'] = $priceInfo->normal_price;
                    }
                    if($user->user_level == 2){
                        $level_type = 2;
                        $skillData['price'] = $priceInfo->better_price;
                    }
                    if($user->user_level == 3){
                        $level_type = 3;
                        $skillData['price'] = $priceInfo->super_price;
                    }
                }
            }
            if($user->sexy == '女'){
                $priceInfo = DB::table('game_woman_charge')->where('game_id', $authorizesInfo->game_id)->where('server_id', $authorizesInfo->server_id)->first();
                if($priceInfo){
                    $level_type = 1;
                    $skillData['unit'] = $priceInfo->unit;
                    $skillData['price'] = $priceInfo->normal_price;
                }
            }
            DB::table('skills')->insert($skillData);
            DB::table('bond_orders')->where('order_id', $data['order_id'])->update(['status'=>1]);
            DB::table('authorizes')->where('user_id', $data['user_id'])->where('status', 2)->update(['status'=>4]);
            DB::table('users')->where('id', $data['user_id'])->update(['isMaster'=>1]);

            $master = DB::table('masters')->where('user_id', $data['user_id'])->first();
            if(!$master){
                DB::table('masters')->insert(['user_id'=>$user->id,'sex'=>$user->sexy,'order_count'=>0,'arg_score'=>0,'level_type'=>$level_type,'status'=>1]);
            }

        } catch (QueryException $queryException) {
            throw new AuthException('缴纳保证金失败', '2096');
        }
    }

    /**
     * @param $data
     */
    public static function addUserTransaction($data)
    {
        DB::table('user_transaction')->insert($data);
        if ($data['money'] != 0) {
            DB::table('wallets')->where('user_id', $data['user_id'])->increment('cash', $data['money']);
        }
    }

    /**
     * 用户兑换虚拟币
     *
     * @author AdamTyn
     *
     * @param array
     * @return array
     */
    public static function exchange($data)
    {
        $much = intval($data['much']);
        $wallet = self::firstOrCreate(['user_id' => $data['user_id']]);
        $wallet->cash -= (($much) * self::$rate);
        $wallet->setVir($much);

        return [
            'vir_money' => strval($wallet->vir_money),
            'cash' => strval($wallet->cash)
        ];
    }

    /**
     * 虚拟币提现到余额
     *
     * @author AdamTyn
     *
     * @param array
     * @return array
     */
    public static function reExchange($data)
    {
        $much = intval($data['much']);
        $wallet = self::firstOrCreate(['user_id' => $data['user_id']]);
        $wallet->cash -= (($much) * self::$rate);
        $wallet->setVir($much);

        return [
            'vir_money' => strval($wallet->vir_money),
            'cash' => strval($wallet->cash)
        ];
    }

    /**
     * 虚拟币打赏
     *
     * @author AdamTyn
     *
     * @param array
     * @return array
     */
    public static function reward($data)
    {
        $much = intval($data['much']);
        $wallet = self::firstOrCreate(['user_id' => $data['user_id_1']]);
        $wallet->setVir($much);
        $wallet = self::firstOrCreate(['user_id' => $data['user_id']]);
        $wallet->setVir(0 - ($much));

        return [
            'vir_money' => strval($wallet->vir_money)
        ];
    }

    /**
     * @param $user_id
     * @param $add_cash
     * @return mixed
     */
    public static function addCash($user_id,$add_cash)
    {
        $wallet = self::where('user_id',$user_id)->first();
        if($wallet == null){
            $cash = $add_cash;
            return self::insert([
                'user_id'=>$user_id,
                'cash'=>$cash
            ]);
        }

        $cash = $wallet->cash + $add_cash;
        return self::where('user_id',$user_id)->update(['cash'=>$cash]);
    }

    /**
     *
     * @author AdamTyn
     *
     * @param array
     * @return mixed
     */
    public static function getRechargeInfo($order_id)
    {
        $recharge = DB::table('recharge_orders')->where('order_id', $order_id)->first();
        if($recharge){
            return $recharge;
        }
        return false;
    }

    /**
     *
     * @author AdamTyn
     *
     * @param array
     * @return mixed
     */
    public static function getBondInfo($order_id)
    {
        $recharge = DB::table('bond_orders')->where('order_id', $order_id)->first();
        if($recharge){
            return $recharge;
        }
        return false;
    }

}
