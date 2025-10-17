<?php

use Luoyue\WebmanMcp\Enum\McpClientRegisterEnum;
use Mcp\Schema\ServerCapabilities;
use Mcp\Server\Session\InMemorySessionStore;

return [
    'enable' => true,
    // 自动注册MCP服务到ide中
    'auto_register_client' => McpClientRegisterEnum::CURSOR_IDE,
    // mcp系统日志名称，对应插件的log配置文件
    'logger' => null,
    'services' => [
        'mcp' => [
            // 服务名称
            'name' => 'MCP Server',
            // 服务版本
            'version' => '0.0.1',
            // 服务描述
            'description' => 'MCP Server',
            // 使用说明
            'instructions' => '',
            // 服务连接日志名称，对应插件的log配置文件
            'logger' => 'mcp',
            // 服务注册配置
            'discover' => [
                // 注解扫描路径
                'scan_dirs' => [
                    'app/mcp',
                ],
                // 排除扫描路径
                'exclude_dirs' => [
                ],
                // Psr-16缓存实例，用于缓存扫描结果，加快启动速度  Cache::store()
                'cache' => null,
            ],
            // 分页限制
            'pagination_limit' => 50,
            // session设置  InMemorySessionStore内存存储  FileSessionStore文件持久存储
            'session' => new InMemorySessionStore(3600),
            // 设置需要开启的功能
            'capabilities' => new ServerCapabilities(
                tools: true,
                toolsListChanged: false,
                resources: true,
                resourcesSubscribe: false,
                resourcesListChanged: false,
                prompts: true,
                promptsListChanged: false,
                logging: false,
                completions: false,
                experimental: null,
            ),
            'tool' => [
//                [
//                    'handler' => fn() => null,
//                    'name' => null,
//                    'description' => null,
//                    'annotations' => null,
//                    'inputSchema' => null
//                ]
            ],
            'prompt' => [
//                [
//                    'handler' => fn() => null,
//                    'name' => null,
//                    'description' => null
//                ]
            ],
            'resource' => [
//                [
//                    'handler' => fn() => null,
//                    'uri' => '',
//                    'name' => null,
//                    'description' => null,
//                    'mimeType' => null,
//                    'size' => null,
//                    'annotations' => null
//                ]
            ],
            'resource_template' => [
//                [
//                    'handler' => fn() => null,
//                    'uri' => '',
//                    'name' => null,
//                    'description' => null,
//                    'mimeType' => null,
//                    'size' => null,
//                    'annotations' => null
//                ]
            ],
            // 路由配置（此配置将注入至webman进程中）
            'router' => [
                'enable' => true,
                'endpoint' => '/mcp',
            ],
            // 额外自定义进程配置（与process.php配置相同）使用port代替listen
            'process' => [
                'enable' => false,
                'port' => 8080,
                'count' => 1,
                'eventloop' => ''
            ]
        ]
    ]
];
