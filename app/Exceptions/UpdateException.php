<?php

namespace App\Exceptions;

class UpdateException extends \Exception
{
    function __construct($msg='',$code='4000')
    {
        parent::__construct($msg,$code);
    }
}