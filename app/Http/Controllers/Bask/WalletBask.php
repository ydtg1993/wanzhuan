<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/20 0020
 * Time: 上午 11:33
 */

namespace App\Http\Controllers\Bask;

use App\Http\Controllers\Controller;
use App\Http\InterFaces\PaymentInterface;
use App\Models\BaskStream;
use App\Models\NormalOrder;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Wallet;

require_once PROGECT_ROOT_PATH.'/../public/xinge-api-php/XingeApp.php';

class WalletBask extends Controller implements PaymentInterface
{
    private $order_data;

    /**
     * @param $data
     * @throws \Exception
     */
    public function ahead($data)
    {
        $this->order_data = $data;
        if ($this->order_data['pay_sum'] > 0) {
            $wallet = Wallet::getWallet($this->order_data['user_id']);
            if ($wallet['cash'] < $this->order_data['pay_sum']) {
                throw new \Exception('钱包余额不足');
            }
        }
    }

    public function pay()
    {
        \DB::beginTransaction();
        try {
            Wallet::addUserTransaction([
                'user_id' => $this->order_data['user_id'],
                'order_id' => $this->order_data['order_id'],
                'money' => 0 - $this->order_data['pay_sum'],
                'title' => '消费',
                'desc' => '打赏消费',
                'type' => 8,
                'status' => 1,
                'created_at' => TIME
            ]);

            $this->order_data['status'] = 1;
            $id = BaskStream::add($this->order_data);

            //被打赏人加钱
            Wallet::addUserTransaction([
                'user_id' => $this->order_data['bask_user_id'],
                'order_id' => $this->order_data['order_id'],
                'money' => $this->order_data['pay_sum'] * $this->order_data['proportions'],
                'title' => '收入',
                'desc' => '打赏收入',
                'type' => 7,
                'status' => 1,
                'created_at' => TIME
            ]);
        }catch (\Exception $e){
            \DB::rollBack();
            throw new \Exception('服务器错误，请稍后重试');
        }

        if ($id) {
            $data = [
                'order_id' => $this->order_data['order_id'],
                'pay_sum' => $this->order_data['pay_sum']
            ];
        } else {
            throw new \Exception('服务器错误，请稍后重试');
        }
        \DB::commit();
        return $data;
    }

    public function behind()
    {
        $user_info = User::getBasic($this->order_data['user_id']);
        $bask_user_info = User::getBasic($this->order_data['bask_user_id']);

        //环信推送
        $options['client_id'] = config('easemob.client_id');
        $options['client_secret'] = config('easemob.client_secret');
        $options['org_name'] = config('easemob.org_name');
        $options['app_name'] = config('easemob.app_name');
        $easemob = new \Easemob($options);

        //消息推送
        $target_type = 'users';
        $target = array($bask_user_info->hx_id);
        $from = 'system';
        $pay_sum = $this->order_data['pay_sum'] / 10;
        $flatform_divide = ($this->order_data['pay_sum'] * (1 - $this->order_data['proportions'])) / 10;

        $len = mb_strlen($user_info->nickname);
        if($len > 7){
            $name = mb_substr($user_info->nickname,0,7).'...';
        }else{
            $name = $user_info->nickname;
        }
        $gains = $pay_sum - $flatform_divide;
        $content = <<<EOF
{$name} 打赏{$pay_sum}玩币，扣除平台服务费{$flatform_divide}玩币，获得{$gains}玩币
EOF;

        $ext['title'] = '打赏';
        $ext['type'] = '3';
        $ext['orderInfo'] = json_encode($this->order_data);
        $ext['redirectInfo'] = 'bask';
        $ext['nickname'] = '系统消息';
        $ext['avatar'] = 'http://image.wanzhuanhuyu.cn/game-icon/system.png';
        $easemob->sendText($from, $target_type, $target, $content, $ext);

        //会话消息
        if($this->order_data['source_page'] == 2 || $this->order_data['source_page'] == 3) {
            $from = $user_info->hx_id;
            $content = $this->order_data['content'];

            $ext = [];
            $ext['title'] = $this->order_data['content'];
            $ext['type'] = '4';
            $ext['orderInfo'] = json_encode($this->order_data);
            $ext['redirectInfo'] = 'bask';
            $ext['nickname'] = $user_info->nickname;
            $ext['avatar'] = $user_info->avatar;
            if($this->order_data['source_page'] == 2) {
                $easemob->sendText($from, $target_type, $target, $content, $ext);
            }

            $content = <<<EOF
玩家{$user_info->nickname}打赏了你:{$pay_sum}玩币
EOF;
            $ext['tip'] = 1;
            $easemob->sendText($from, $target_type, $target, $content, $ext);

            //给自己一个消息提示
            $content = <<<EOF
你打赏了{$bask_user_info->nickname}:{$pay_sum}玩币
EOF;
            $target = array($user_info->hx_id);
            $from = $bask_user_info->hx_id;
            $ext['nickname'] = $bask_user_info->nickname;
            $ext['avatar'] = $bask_user_info->avatar;
            $easemob->sendText($from, $target_type, $target, $content, $ext);
        }
    }
}