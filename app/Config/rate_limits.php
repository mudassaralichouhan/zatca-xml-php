<?php

use App\Config\Config;

return [
    'default' => [
        'id'       => 'default',
        'policy'   => 'fixed_window',
        'limit'    => Config::DEFAULT_RATE_LIMIT(10),
        'interval' => '1 minute',
    ],
    'health' => [
        'id'       => 'health',
        'policy'   => 'fixed_window',
        'limit'    => 60,
        'interval' => '30 minutes',
    ],
    'login' => [
        'id'       => 'login',
        'policy'   => 'fixed_window',
        'limit'    => 5,
        'interval' => '15 minutes',
    ],
    'resend_register_confirmation' => [
        'id'       => 'resend_register_confirmation',
        'policy'   => 'fixed_window',
        'limit'    => 3,
        'interval' => '6 hours',
    ],
    'register' => [
        'id'       => 'register',
        'policy'   => 'fixed_window',
        'limit'    => 5,
        'interval' => '10 minutes',
    ],
];
