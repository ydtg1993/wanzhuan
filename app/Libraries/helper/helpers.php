<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/19
 * Time: 17:41
 */

if (!function_exists('andorid_notice_push')) {
    function andorid_notice_push($accounts, $title, $content)
    {
        return app('notice')->andorid()->push($accounts, $title, $content);
    }
}

if (!function_exists('ios_notice_push')) {
    function ios_notice_push($accounts, $title, $content)
    {
        return app('notice')->ios()->push($accounts, $title, $content);
    }
}