<?php

return [
    'eloquent' => [
        /*
         * Strict mode aktif secara default hanya pada environment local.
         * Testing memakai test khusus agar regression suite lama tidak berubah
         * secara global, sedangkan production tetap menonaktifkannya.
         */
        'strict' => (bool) env(
            'LARAS_ELOQUENT_STRICT',
            env('APP_ENV') === 'local'
        ),
    ],

    'queries' => [
        /*
         * Query metrics aktif pada local dan testing. Header diagnostik tidak
         * dikirim pada production agar detail performa internal tidak terekspos.
         */
        'enabled' => (bool) env(
            'LARAS_QUERY_MONITORING',
            in_array(
                env('APP_ENV'),
                ['local', 'testing'],
                true
            )
        ),

        'response_headers' => (bool) env(
            'LARAS_QUERY_RESPONSE_HEADERS',
            in_array(
                env('APP_ENV'),
                ['local', 'testing'],
                true
            )
        ),

        'slow_query_threshold_ms' => (int) env(
            'LARAS_SLOW_QUERY_THRESHOLD_MS',
            250
        ),

        'cumulative_threshold_ms' => (int) env(
            'LARAS_CUMULATIVE_QUERY_THRESHOLD_MS',
            500
        ),

        'sql_preview_length' => (int) env(
            'LARAS_QUERY_SQL_PREVIEW_LENGTH',
            1000
        ),
    ],
];
