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
        // 服务日志，对应插件下的log配置文件
        'logger' => 'mcp_error_stderr',
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
            'store' => '', // 对应cache.php中的缓存配置名称, null为使用默认的内存缓存（不推荐）
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
];