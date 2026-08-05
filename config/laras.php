<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Feedback
    |--------------------------------------------------------------------------
    */

    'authentication' => [
        'detailed_errors' => (bool) env(
            'LARAS_DETAILED_AUTH_ERRORS',
            false
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Personal Account
    |--------------------------------------------------------------------------
    */

    'user' => [
        'name' => env('LARAS_USER_NAME', 'Pengguna Laras'),
        'email' => env('LARAS_USER_EMAIL'),
        'password' => env('LARAS_USER_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Preferences
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'locale' => env('LARAS_DEFAULT_LOCALE', 'id'),
        'currency_code' => env('LARAS_DEFAULT_CURRENCY', 'IDR'),
        'timezone' => env('LARAS_DEFAULT_TIMEZONE', 'Asia/Jakarta'),
        'date_format' => env('LARAS_DEFAULT_DATE_FORMAT', 'd/m/Y'),
        'time_format' => env('LARAS_DEFAULT_TIME_FORMAT', 'H:i'),
        'week_starts_on' => (int) env('LARAS_WEEK_STARTS_ON', 1),
    ],

    'account_presets' => [
        [
            'name' => 'BCA Utama',
            'type' => 'bank',
            'institution' => 'BCA',
            'initial_balance' => '0',
            'account_number_last_four' => null,
            'color' => '#2563EB',
        ],
        [
            'name' => 'Mandiri',
            'type' => 'bank',
            'institution' => 'Mandiri',
            'initial_balance' => '0',
            'account_number_last_four' => null,
            'color' => '#1D4ED8',
        ],
        [
            'name' => 'BNI Pribadi',
            'type' => 'bank',
            'institution' => 'BNI',
            'initial_balance' => '0',
            'account_number_last_four' => null,
            'color' => '#EA580C',
        ],
        [
            'name' => 'BNI Mahasiswa/Kampus',
            'type' => 'bank',
            'institution' => 'BNI',
            'initial_balance' => '0',
            'account_number_last_four' => null,
            'color' => '#F97316',
        ],
        [
            'name' => 'SeaBank',
            'type' => 'bank',
            'institution' => 'SeaBank',
            'initial_balance' => '0',
            'account_number_last_four' => null,
            'color' => '#0EA5E9',
        ],
        [
            'name' => 'Uang Tunai',
            'type' => 'cash',
            'institution' => null,
            'initial_balance' => '0',
            'account_number_last_four' => null,
            'color' => '#16A34A',
        ],
    ],
];
