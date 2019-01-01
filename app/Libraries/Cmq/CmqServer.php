<?php

namespace App\Libraries\Cmq;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/13
 * Time: 15:25
 */
class CmqServer
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

}