<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Blacklist extends Model
{

    protected $table = 'blacklists';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'list'
    ];

    /**
     * 更新黑名单
     *
     * @author AdamTyn
     *
     * @param string
     * @param string
     * @return void
     */
    public static function setBlacklist($user_id, $user_id_1)
    {
        $temp = self::firstOrCreate(['user_id' => $user_id]);

        $lists = empty($temp->list) ? [] : explode('@', $temp->list);

        if (in_array($user_id_1, $lists)) {
            $temp->list = substr_count($temp->list, '@') > 0 ? str_replace('@' . $user_id_1, '', $temp->list) : str_replace($user_id_1, '', $temp->list);
        } else {
            $temp->list = trim(($temp->list . '@' . $user_id_1), '@');
        }

        $temp->save();
    }

    /**
     * 查看黑名单列表
     *
     * @author AdamTyn
     *
     * @param string
     * @param string
     * @return mixed
     */
    public static function getBlacklist($user_id, $paginate)
    {
        $temp = self::firstOrCreate(['user_id' => $user_id]);

        $lists = empty($temp->list) ? [] : explode('@', $temp->list);

        $return = DB::table('users')
            ->selectRaw('concat(id,"") as id ,nickname,avatar,level,about,sexy,level')
            ->whereIn('id', $lists)
            ->orderBy('id')
            ->offset(intval($paginate) * 10 - 10)
            ->limit(10)
            ->get();

        return count($return) < 1 ? null : $return;
    }

    /**
     * 关联User
     *
     * @author AdamTyn
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }
}
