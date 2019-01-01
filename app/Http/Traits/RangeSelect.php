<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/21 0021
 * Time: 上午 11:53
 */

namespace App\Http\Traits;

use App\Libraries\helper\Helper;

/**
 * 导师范围
 *
 * Trait RangeCharge
 * @package App\Http\Traits
 */
trait RangeSelect
{
    public static $MALE = '男';//男
    public static $FA_MALE = '女';//女

    public static $MALE_DEFAULT_LEVEL = 1;
    public static $FAMALE_DEFAULT_LEVEL = 4;

    protected static $RANGE_SELECT_FILTER = false;
    protected static $RANGE_SELECT_CHARGE = false;
    protected static $MASTER_GENDER = '男';
    protected static $MASTER_CHARGE_LIST = [];

    protected static $SELECT_GAME_ID;

    /**
     * @param $list
     * @param bool $unite
     */
    protected static function refactorList(&$list, $unite = false)
    {
        foreach ($list as &$game) {
            $game_level = [];
            if (isset($game['game_level'])) {
                $game_level = $game['game_level'];
                unset($game['game_level']);
                if (self::$MASTER_GENDER == self::$MALE && is_array($game_level) && false == empty($game_level) && $unite) {
                    //联合构造
                    $ids = join(',', array_column($game_level, 'id'));
                    array_unshift($game_level, ['id' => $ids, 'game_id' => $game['id'], 'level_name' => '不限', 'status' => 1]);
                }
            }

            if (isset($game['game_server']) && false == empty($game['game_server'])) {
                foreach ($game['game_server'] as $name => &$game_server) {
                    $game_server['game_level'] = $game_level;
                }
            } else {
                $game['game_server'] = ['game_level' => $game_level];
            }
        }
    }

    protected static function refactorMasterInfo(&$master_info, $game_id)
    {
        $this_game_range_info = current(Helper::multiQuery2Array($master_info['range'], ['game_id' => $game_id]));
        if (empty($this_game_range_info)) {
            $master_info['master_level'] = 0;
            $master_info['master_strength'] = 0;
            $master_info['master_probability'] = 0;
            $master_info['service_time'] = 0;
        } else {
            $master_info['master_level'] = $this_game_range_info['master_level'];
            $master_info['master_strength'] = $this_game_range_info['master_strength'];
            $master_info['master_probability'] = $this_game_range_info['master_probability'];
            $master_info['service_time'] = $this_game_range_info['service_time'];
        }
        $master_info['order_count'] = $this_game_range_info['order_count'];
    }

    /**
     * @param $ranges
     * @return array
     */
    protected static function rangeContentToArray($ranges)
    {
        $master_range = [];
        foreach ($ranges as $range) {
            $game_id = $range['game_id'];
            $range_content = json_decode($range['range_content'], true);
            if (empty($range_content)) {
                continue;
            }
            foreach ($range_content['game_server'] as &$server_unit) {
                $game_level = explode(',', $server_unit['game_level']);
                $server_unit = ['game_level' => $game_level];
            }
            $master_range[$game_id] = $range_content;
            $master_range[$game_id]['master_level'] = $range['master_level'];
        }
        return $master_range;
    }

    /**
     * @param $list
     * @param $master_range
     * @param $master_auth
     */
    protected static function selectGameRange(&$list, $master_range, $master_auth)
    {
        foreach ($list as $game_key => &$game) {
            $game['auth'] = 0;//导师游戏是否已验证
            $game['isset'] = 0; //导师范围是否设置
            $game_id = $game['id'];
            if (self::$SELECT_GAME_ID && $game_id != self::$SELECT_GAME_ID) {
                unset($list[$game_key]);
                continue;
            }

            if (in_array($game_id, $master_auth)) {
                $game['auth'] = 1;
            } elseif (self::$RANGE_SELECT_FILTER) {
                unset($list[$game_key]);
            }

            $master_range_this_game = isset($master_range[$game_id]) ? $master_range[$game_id] : [];//导师此游戏范围
            $master_range_server_ids_this_game = [];//导师此游戏选区范围
            if (false == empty($master_range_this_game && isset($master_range_this_game['game_server']))) {
                $master_range_server_ids_this_game = array_keys($master_range_this_game['game_server']);
            }

            $game_servers = &$game['game_server'];
            foreach ($game_servers as $game_server_key => &$game_server) {
                $game_server['accept'] = 0;
                $master_range_level_ids_this_server = [];//导师此选区等级范围

                $master_range_server_index = array_search($game_server['id'], $master_range_server_ids_this_game);
                if ($master_range_server_index !== false) {
                    $game_server['accept'] = 1;
                    $master_range_server_id_this_game = $master_range_server_ids_this_game[$master_range_server_index];

                    if (false == empty($master_range_this_game) &&
                        isset($master_range_this_game['game_server']) &&
                        isset($master_range_this_game['game_server'][$master_range_server_id_this_game]) &&
                        isset($master_range_this_game['game_server'][$master_range_server_id_this_game]['game_level'])
                    ) {
                        $master_range_level_ids_this_server = $master_range_this_game['game_server'][$master_range_server_id_this_game]['game_level'];
                    }
                } elseif (self::$RANGE_SELECT_FILTER) {
                    unset($game_servers[$game_server_key]);
                    if (empty($game_servers)) {
                        unset($list[$game_key]);
                    }
                }
              
                foreach ($game_server['game_level'] as $game_level_key => &$game_level) {
                    $game_level['accept'] = 0;
                    if (self::$RANGE_SELECT_CHARGE) {//加入单价默认
                        $game_level['price'] = 0;
                        $game_level['old_price'] = 0;
                        $game_level['unit'] = '';
                    }

                    if (in_array($game_level['id'], $master_range_level_ids_this_server)) {
                        $game_level['accept'] = 1;
                        $game['isset'] = 1; //导师已设置
                        if (self::$RANGE_SELECT_CHARGE) {
                            //加入单价
                            $charge_list_this = self::getPrice($game_id, $game_server['id'], $game_level['id'], $master_range_this_game['master_level']);
                            $game_level['price'] = $charge_list_this['price'];
                            $game_level['old_price'] = $charge_list_this['old_price'];
                            $game_level['unit'] = $charge_list_this['unit'];

                            if ($charge_list_this['price'] == 0) {
                                //删除
                                unset($game_server['game_level'][$game_level_key]);
                            }
                        }
                    } elseif (self::$RANGE_SELECT_FILTER) {
                        unset($game_server['game_level'][$game_level_key]);
                    }
                }
                sort($game_server['game_level']);
            }
            sort($game_servers);
        }
        sort($list);
    }

    /**
     * @param $game_id
     * @param $server_id
     * @param string $level_id
     * @param string $master_level
     * @return array
     */
    protected static function getPrice($game_id, $server_id, $level_id = '', $master_level = '')
    {
        if (self::$MASTER_GENDER == self::$MALE) {
            $charge_list_this = current(Helper::multiQuery2Array(self::$MASTER_CHARGE_LIST,
                ['game_id' => $game_id,
                    'level_id' => $level_id]));
        } else {
            $charge_list_this = current(Helper::multiQuery2Array(self::$MASTER_CHARGE_LIST,
                ['game_id' => $game_id]));
        }
        $price = self::selectSinglePrice($charge_list_this, $master_level);
        $unit = isset($charge_list_this['unit']) ? $charge_list_this['unit'] : '';
        return ['price' => $price['price'], 'old_price' => $price['old_price'], 'unit' => $unit];
    }

    /**
     * @param $charge_list_this
     * @param $master_level
     * @return array
     */
    protected static function selectSinglePrice($charge_list_this, $master_level)
    {
        $price = 0;
        $old_price = 0;
        if (!empty($charge_list_this)) {
                switch ($master_level) {
                    case 1://普通
                        $price = $charge_list_this['normal_price'];
                        $old_price = $charge_list_this['normal_old_price'];
                        break;
                    case 2://优质
                        $price = $charge_list_this['better_price'];
                        $old_price = $charge_list_this['better_old_price'];
                        break;
                    case 3://超级
                        $price = $charge_list_this['super_price'];
                        $old_price = $charge_list_this['super_old_price'];
                        break;
                }
        }

        return ['price' => $price, 'old_price' => $old_price];
    }
}