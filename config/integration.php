<?php

return [

    // ─────────────────────────────────────────────────────────────
    // NEO LMS (CYPHER Learning) API v3
    // ─────────────────────────────────────────────────────────────
    'neo_lms' => [
        'base_url'    => env('NEO_LMS_BASE_URL'),
        'api_key'     => env('NEO_LMS_API_KEY'),
        'timeout'     => env('NEO_LMS_TIMEOUT', 30),
        'page_size'   => env('NEO_LMS_PAGE_SIZE', 100),
        'retry_times' => env('NEO_LMS_RETRY_TIMES', 3),
        'retry_sleep' => env('NEO_LMS_RETRY_SLEEP_MS', 2000),
    ],

    // ─────────────────────────────────────────────────────────────
    // CONTROL ESCOLAR
    // driver: 'db' | 'api'
    // ─────────────────────────────────────────────────────────────
    'control_escolar' => [
        'driver' => env('CONTROL_ESCOLAR_DRIVER', 'db'),

        'db' => [
            'connection' => env('CE_DB_CONNECTION', 'pgsql'),
            'host'       => env('CE_DB_HOST', '127.0.0.1'),
            'port'       => env('CE_DB_PORT', 5432),
            'database'   => env('CE_DB_DATABASE', 'control_escolar'),
            'username'   => env('CE_DB_USERNAME'),
            'password'   => env('CE_DB_PASSWORD'),
        ],

        'api' => [
            'base_url'    => env('CE_API_BASE_URL'),
            'api_key'     => env('CE_API_KEY'),
            'timeout'     => env('CE_API_TIMEOUT', 30),
            'retry_times' => env('CE_API_RETRY_TIMES', 3),
            'retry_sleep' => env('CE_API_RETRY_SLEEP_MS', 1000),
        ],
    ],

    // ─────────────────────────────────────────────────────────────
    // QUEUES
    // ─────────────────────────────────────────────────────────────
    'queues' => [
        'high'    => env('QUEUE_HIGH',    'neo-sync-high'),
        'default' => env('QUEUE_DEFAULT', 'neo-sync-default'),
        'low'     => env('QUEUE_LOW',     'neo-sync-low'),
    ],

    // ─────────────────────────────────────────────────────────────
    // SCHEDULER — frecuencias por defecto (sobreescribibles desde BackOffice)
    // ─────────────────────────────────────────────────────────────
    'schedules' => [
        'sync_students'     => env('SCHEDULE_SYNC_STUDENTS',    '0 2 * * *'),
        'sync_teachers'     => env('SCHEDULE_SYNC_TEACHERS',    '15 2 * * *'),
        'sync_study_programs' => env('SCHEDULE_SYNC_PROGRAMS',  '0 3 * * *'),
        'sync_enrollments'  => env('SCHEDULE_SYNC_ENROLLMENTS', '30 2 * * *'),
        'sync_grades'       => env('SCHEDULE_SYNC_GRADES',      '*/30 * * * *'),
        'poll_neo_grades'   => env('SCHEDULE_POLL_NEO',         '0 */4 * * *'),
    ],

];
