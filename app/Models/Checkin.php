<?php

namespace App\Models;

use App\Models\Traits\CheckTrait;
use App\Exceptions\UpdateException;
use Illuminate\Database\Eloquent\Model;

class Checkin extends Model
{
    use CheckTrait;
    protected $table = 'checkins';
    protected $fillable = [
        'user_id',
        'contain',
        'last_time',
        'recent_time'
    ];
    public $timestamps = false;

    /**
     * 每日签到
     *
     * @author AdamTyn
     *
     * @param string
     * @return mixed
     *
     * @throws \App\Exceptions\UpdateException;
     */
    public static function newCheck($user_id)
    {
        $now_time = time();
        $checkin = self::firstOrCreate([
            'user_id' => $user_id
        ]);

        $now = getdate($now_time)['mday'];
        $old = (($checkin->last_time) == 0) ? ($now - 2) : getdate($checkin->last_time)['mday'];

        if ($now == $old) {
            throw new UpdateException('一天只能签到一次','1003');
        } else {
            if (($now - 1) > $old) {
                $checkin->contain = 1;
            } else {
                $checkin->contain += 1;
            }
            $checkin->recent_time = $checkin->last_time;
            $checkin->last_time = $now_time;
        }

        $checkin->save();

        return Ticket::pushTickets($user_id);
    }
}
