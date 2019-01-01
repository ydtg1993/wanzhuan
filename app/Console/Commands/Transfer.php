<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Yansongda\Pay\Pay;

class Transfer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transfer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'handle transfer';

    protected $aliapyConfig = [];

    protected $wechatPayConfig = [];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->aliapyConfig = [
            'app_id' => config('pay.alipay.app_id'),
            'notify_url' => config('pay.alipay.transfer_notify_url'),
            'ali_public_key' => config('pay.alipay.ali_public_key'),
            'private_key' => config('pay.alipay.private_key'),
        ];

        $this->wechatPayConfig = [

        ];
    }

    /**
     *
     * 待修改
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $records = DB::table('user_take_cash')
            ->where('status', '<', 1)
            ->where('money', '<', '10000')
            ->select('user_id', 'order_id', 'cash_account', 'type', 'money')
            ->get();

        if ($records->isEmpty()) {
            return;
        }
        foreach ($records as $record) {
            //支付宝
            try {
                DB::beginTransaction();
                if ($record->type == 1) {
                    $data = [
                        'out_biz_no' => $record->order_id,
                        'payee_type' => 'ALIPAY_LOGONID',
                        'payee_account' => $record->cash_account,
                        'amount' => ($record->amount / 100)
                    ];
                    $alipay = Pay::alipay($this->aliapyConfig)->transfer($data);

                    $update = ['status' => 3];
                    if ($alipay->code != 10000) {
                        $update['status'] = 4;
                        $update['reason'] = $alipay->sub_msg;
                        DB::table('wallets')->where('user_id', $record->user_id)->increment('cash', $record->money);
                        $transactionData = [];
                        $transactionData['user_id'] = $record->user_id;
                        $transactionData['order_id'] = $record->order_id;
                        $transactionData['money'] = $record->money;
                        $transactionData['title'] = '提现失败';
                        $transactionData['desc'] = '提现失败';
                        $transactionData['type'] = 4;
                        $transactionData['status'] = 1;
                        $transactionData['created_at'] = time();
                        DB::table('user_transaction')->insert($transactionData);
                    }
                    $record->update($update);
                    DB::commit();
                    continue;
                } else if ($record == 2) {
                    //Pay::wechat($this->wechatPayConfig)->transfer($data);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                info("{$record->order_id}提现处理失败");
            }
        }
    }
}
