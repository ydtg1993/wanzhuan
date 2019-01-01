<?php

namespace App\Exceptions;

class AuthException extends \Exception
{
    function __construct($msg='',$code='4000')
    {
        parent::__construct($msg,$code);
    }
}