<?php
/**
 * Get Random Code
 *
 * @author AdamTyn
 *
 * @param string
 * @param string
 * @param string
 * @return string
 */

function ai_auth(string $cloud_app_id, string $cloud_secret_id, string $cloud_secret_key): string
{
    $current = time();
    $expired = $current + 2592000;
    $random = rand();
    $userid = "0";

    $src = 'a=' . $cloud_app_id . '&b=' . '&k=' . $cloud_secret_id . '&e=' . $expired . '&t=' . $current . '&r=' . $random . '&u='
        . $userid . '&f=';

    return base64_encode(hash_hmac('SHA1', $src, $cloud_secret_key, true) . $src);
}