<?php

use Mcp\Schema\Enum\ProtocolVersion;
use Mcp\Schema\ServerCapabilities;
use Mcp\Server\Builder;

return [
    'mcp' => [
        // MCP功能配置
        'configure' => function (Builder $server) {
            // 设置服务信息
            $server->setServerInfo('MCP Server', '0.0.1', 'MCP Server');
            // 设置协议版本
            $server->setProtocolVersion(ProtocolVersion::V2025_06_18);
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
                completions: true,
                experimental: null,
            ));
        },
        // 服务日志，对应插件下的log配置文件，为空则不记录日志
        'logger' => null,
        // 服务注册配置
        'discover' => [
            // 注解扫描路径
            'scan_dirs' => [
                'app/mcp',
            ],
            // 排除扫描路径
            'exclude_dirs' => [
            ],
            // 缓存扫描结果，cache.php中的缓存配置名称，对于webman常驻内存框架无提升并且无法及时清理缓存，建议关闭。
            'cache' => null,
        ],
        // session设置
        'session' => [
            'store' => '', // 对应cache.php中的缓存配置名称, null为使用默认的内存缓存（多进程模式下不适用）
            'prefix' => 'mcp-',
            'ttl' => 86400,
        ],
        'transport' => [
            'stdio' => [
                'enable' => true,
            ],
            'streamable_http' => [
                // mcp端点
                'endpoint' => '/mcp',
                // 额外响应头，可配置CORS跨域
                'headers' => [

                ],
                // 启用后将mcp端点注入到您的路由中
                'router' => [
                    'enable' => true
                ],
                // 额外的自定义进程配置（与process.php配置相同）使用port代替listen
                'process' => [
                    'enable' => false,
                    'port' => 8080,
                    'count' => 1,
                    'eventloop' => ''
                ]
            ]
        ]
    ]
];