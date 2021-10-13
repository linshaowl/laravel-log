<?php

return [
    'driver' => 'custom',
    'via' => \Lswl\Log\LswlLogger::class,
    'debug' => env('LSWL_LOG_DEBUG', false),
    'max_size' => env('LSWL_LOG_MAX_SIZE', 0),
    'max_files' => env('LSWL_LOG_MAX_FILES', 0),
    'bubble' => env('LSWL_LOG_BUBBLE', true),
    'permission' => env('LSWL_LOG_PERMISSION', null),
    'locking' => env('LSWL_LOG_LOCKING', false),
];
