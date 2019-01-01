<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Models\Verify;

class CleanJob extends Job
{
    public function __construct()
    {
    }

    public function handle()
    {
//        Ticket::outAllTicket();
//        Verify::outAllVerify();
    }
}
