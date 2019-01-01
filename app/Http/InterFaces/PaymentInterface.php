<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/17 0017
 * Time: 下午 3:43
 */

namespace App\Http\InterFaces;

interface PaymentInterface
{
    public function ahead($data);
    public function pay();
    public function behind();
}