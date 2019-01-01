<?php
/**
 * Get Random Code
 *
 * @author AdamTyn
 *
 * @param string
 * @param string
 * @param string
 * @param int
 * @return string
 */

function sms_sign(string $mobile, string $app_key, string $random, int $time): string
{
    return hash('sha256', 'appkey=' . $app_key . '&random=' . $random . '&time=' . strval($time) . '&mobile=' . $mobile);
}