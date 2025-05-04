<?php

return [
    'unpaid_order' => [
        'limit' => 2,
        'minutes' => 60,
        'levels' => [1 => 180, 2 => 720, 3 => null],
    ],
    'cancel' => [
        'limit' => 2,
        'minutes' => 60,
        'levels' => [1 => 180, 2 => 720, 3 => null],
    ],
    'order_spam' => [
        'limit' => 3,
        'minutes' => 10,
        'levels' => [1 => 180, 2 => 720, 3 => null],
    ],
];
