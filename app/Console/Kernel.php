<?php

namespace App\Console;

use App\Jobs\CleanJob;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // 'App\Console\Commands\MatchingMaster'
        'App\Console\Commands\Transfer',
        'App\Console\Commands\Socket',
        'App\Console\Commands\AppointmentEndGame',
        'App\Console\Commands\Statistic',
        'App\Console\Commands\IdentifyImages'
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->job(new CleanJob)->daily();
    }
}
