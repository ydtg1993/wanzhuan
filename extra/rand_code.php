<?php

/**
 * 随机验证码
 *
 * @author AdamTyn
 *
 * @param int
 * @return string
 */

function rand_code(int $length = 6): string
{
    return mt_rand(100000, 999999);
}