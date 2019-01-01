<?php

$factory->define(App\Models\User::class, function (Faker\Generator $faker) {
    return [
        'nickname' => rand_name(),
        'mobile'=> rand_mobile(),
        'sexy'=>'男'
    ];
});

