<?php

namespace App\Models;

use App\Events\UserCreated;
use App\Events\UserUpdated;
use App\Exceptions\AuthException;
use App\Exceptions\UpdateException;
use App\Models\Traits\UserTrait;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserTransaction extends Model
{
    protected $table = 'user_transaction';
    public $timestamps = false;

    public static function add($data)
    {
        return self::insert($data);
    }
}
