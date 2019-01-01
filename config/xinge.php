<?php

return [
    'android_app_id' => '6030fdd0c21e9',
    'andorid_secret_key' => '96774184c1d236fe0cfb37744b9c0515',
    'andorid_access_id' => 2100300435,
    'andorid_access_key' => 'ANXI3888CF8V',

    'ios_app_id' => '897e18c5d2cb1',
    'ios_secret_key' => '82477f2c9b7956386583dc094ddef0a1',
    'ios_access_id' => 2200302271,
    'ios_access_key' => '82477f2c9b7956386583dc094ddef0a1',


    'andorid' => [
        'app_id' => '6030fdd0c21e9',
        'secret_key' => '96774184c1d236fe0cfb37744b9c0515',
        'access_id' => 2100300435,
        'access_key' => 'ANXI3888CF8V',
    ],
    'ios' => [
        'app_id' => '897e18c5d2cb1',
        'secret_key' => '82477f2c9b7956386583dc094ddef0a1',
        'access_id' => 2200302271,
        'access_key' => '82477f2c9b7956386583dc094ddef0a1',
    ],

    'environment' => env('APP_ENV') == 'local' ? 'dev' : 'product'
];
