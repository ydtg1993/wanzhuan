<?php
/**
 * Mobile Validator
 *
 * @author AdamTyn
 *
 * @param string
 * @return bool
 */

function mobile_validator(string $mobile):bool
{
    if (strlen($mobile)!=11){
        return false;
    }

    $rule = '/^(13[0-9]|14[579]|15[0-3,5-9]|16[6]|17[0135678]|18[0-9]|19[89])\d{8}$/';

    if (preg_match($rule,$mobile))
    {
        return true;
    }else{
        return false;
    }
}