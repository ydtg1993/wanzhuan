<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DynamicReport extends Model
{
    protected $fillable = [
        'userid',
        'report_id',
        'type',
        'describe',
    ];
}