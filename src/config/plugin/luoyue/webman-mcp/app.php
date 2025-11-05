<?php

use Luoyue\WebmanMcp\Enum\McpClientRegisterEnum;
use Mcp\Schema\ServerCapabilities;
use Mcp\Server\Builder;

return [
    'enable' => true,
    // 自动注册MCP服务到ide中
    'auto_register_client' => McpClientRegisterEnum::CURSOR_IDE,
    // mcp系统日志名称，对应log配置文件
    'logger' => 'default',
    'services' => [
        'mcp' => [
            // MCP功能配置
            'configure' => function (Builder $server) {
                // 设置服务信息
                $server->setServerInfo('MCP Server', '0.0.1', 'MCP Server');
                // 设置使用说明
                $server->setInstructions('MCP Server');
                // 设置分页大小
                $server->setPaginationLimit(50);
                //设置需要开启的功能
                $server->setCapabilities(new ServerCapabilities(
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
                ));
            },
            // 服务连接日志名称，对应log配置文件
            'logger' => 'plugin.luoyue.webman-mcp.mcp',
            // 服务注册配置
            'discover' => [
                // 注解扫描路径
                'scan_dirs' => [
                    'app/mcp',
                ],
                // 排除扫描路径
                'exclude_dirs' => [
                ],
                // cache.php中的缓存配置名称，用于缓存扫描结果，加快启动速度
                'cache' => null,
            ],
            // session设置
            'session' => [
                'store' => null, // 对应cache.php中的缓存配置名称, null为使用默认的内存缓存
                'prefix' => 'mcp-',
                'ttl' => 86400,
            ],
            // http传输模式下的请求头
            'headers' => [
                
            ],
            // 路由配置（此配置将注入至webman进程中）
            'router' => [
                'enable' => true,
                'endpoint' => '/mcp', // 路由地址，与process共享此配置
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