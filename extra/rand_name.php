<?php

/**
 * 获得随机昵称
 *
 * @author AdamTyn
 *
 * @param string
 * @return string
 */


function rand_name(string $str=''): string
{
    return '玩转'.mt_rand(100, 999).$str;
}