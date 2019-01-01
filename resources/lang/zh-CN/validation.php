<?php
/**
 * Created by PhpStorm.
 * User: donggege
 * Date: 2018/11/5
 * Time: 17:22
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | such as the size rules. Feel free to tweak each of these messages.
    |
    */
    "required" => "请求出错，缺少`:attribute`参数或参数为空",
    "in" => "属性 :attribute 非法",
    "size" => [
        "numeric" => ":attribute 大小必须为 :size",
        "file" => ":attribute 大小必须为 :size kb",
        "string" => ":attribute 必须是 :size 个字符",
        "array" => ":attribute 必须为 :size 个单元",
    ],
    "between" => [
        "numeric" => ":attribute 必须介于 :min - :max 之间。",
        "file" => ":attribute 必须介于 :min - :max kb 之间。",
        "string" => ":attribute 必须介于 :min - :max 个字符之间。",
        "array" => ":attribute 必须只有 :min - :max 个单元。",
    ],
    "max" => [
        "numeric" => ":attribute 不能大于 :max。",
        "file" => ":attribute 不能大于 :max kb。",
        "string" => ":attribute 不能大于 :max 个字符。",
        "array" => ":attribute 最多能有 :max 个单元。",
    ],
    "min" => [
        "numeric" => ":attribute 必须大于等于 :min。",
        "file" => ":attribute 大小不能小于 :min kb。",
        "string" => ":attribute 至少为 :min 个字符。",
        "array" => ":attribute 至少有 :min 个单元。",
    ],
    "unique" => ":attribute 已经存在",
    "present" => ":attribute 可以为空但必须存在",


    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */
    'custom' => [

    ],
    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */
    'attributes' => [
        'js_code' => 'js_code'
    ],
];