<?php

return [
    'id' => env('PAYNOW_ID'),
    'key' => env('PAYNOW_KEY'),
    'email' => env('PAYNOW_EMAIL', env('COMPANY_EMAIL', 'billing@chindeka.test')),
    'simulate' => filter_var(env('PAYNOW_SIMULATE', true), FILTER_VALIDATE_BOOL),
    'default_phone' => env('ECOCASH_NUMBER', '0776528965'),
    'test_numbers' => [
        '0776528965' => 'paid',
        '0771111111' => 'paid',
        '0772222222' => 'delayed',
        '0773333333' => 'cancelled',
        '0774444444' => 'failed',
    ],
];
