<?php

return [
    'mcp_file_log' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/mcp.log',
                    7, // $maxFiles
                    Monolog\Logger::NOTICE,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [null, 'Y-m-d H:i:s', true],
                ],
            ],
        ],
    ],
    'mcp_error_stderr' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\StreamHandler::class,
                'constructor' => [
                    STDERR,
                    Monolog\Logger::NOTICE,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [null, 'Y-m-d H:i:s', true],
                ],
            ],
        ],
    ],
];
