<?php
/**
 * Identity Validator
 *
 * @author AdamTyn
 *
 * @param string
 * @return bool
 */

function identity_validator(string $id):bool
{
    if (strlen($id)!=18){
        return false;
    }

    $rule = '/^\d{6}(18|19|20)?\d{2}(0[1-9]|1[012])(0[1-9]|[12]\d|3[01])\d{3}(\d|[xX])$/';

    if (preg_match($rule,$id))
    {
        return true;
    }else{
        return false;
    }
}