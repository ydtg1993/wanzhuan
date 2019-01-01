<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/20
 * Time: 16:11
 */

namespace Service\Notice\Xinge;

class TagTokenPair {

    public function __construct($tag, $token) {
        $this->tag = strval($tag);
        $this->token = strval($token);
    }

    public function __destruct() {
    }

    public $tag;
    public $token;
}