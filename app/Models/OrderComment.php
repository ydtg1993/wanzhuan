<?php

namespace App\Models;

use App\Models\Traits\OrderTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderComment extends Model
{
    protected $table = 'order_comment';

}
