<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Personal Account
    |--------------------------------------------------------------------------
    |
    | Akun tunggal yang digunakan untuk mengakses Laras.
    | Nilainya diambil dari environment agar password tidak masuk repository.
    |
    */

    'user' => [
        'name' => env('LARAS_USER_NAME', 'Rizki Ramadhan'),
        'email' => env('LARAS_USER_EMAIL'),
        'password' => env('LARAS_USER_PASSWORD'),
    ],
];
