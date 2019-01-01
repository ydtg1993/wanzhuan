<?php

/**
 * 随机账单号
 *
 * @author AdamTyn
 *
 * @param string
 * @param int
 * @return string
 */

function rand_number(string $mobile,int $length=37):string
{
    $temp=mt_rand(100000,999999);
    $temp1=date('YmdHis',time());

    return substr($temp1.$temp.$temp.$mobile,0,$length);
}