<?php

namespace App\Console\Commands;

use App\Http\Common\RedisDriver;
use App\Libraries\Swoole\WebSocktHandler;
use App\Models\AppointmentGrabOrderModel;
use App\Models\AppointmentOrderModel;
use App\Models\AppointmentStatusModel;
use App\Models\From;
use App\Models\Sociaty;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

date_default_timezone_set('PRC');

class Statistic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statistic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '统计';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
       $from = From::getAllWhere();
       foreach ($from as $f){
           $id = $f['id'];

           $total = User::countAllWhere(['from'=>$id]);
           \App\Models\Statistic::upInfoWhere(['total'=>$total],['id'=>$id,'system'=>0]);
       }
    }
}
