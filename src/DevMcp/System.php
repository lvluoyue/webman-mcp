<?php

namespace Luoyue\WebmanMcp\DevMcp;

use Composer\InstalledVersions;
use function config;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Workerman\Coroutine;
use Workerman\Worker;

class System
{
    #[McpTool(name: 'system_info')]
    public function sequentialThinking(): array
    {
        return [
            'server_os' => PHP_OS,
            'php_version' => PHP_VERSION,
            'workerman_version' => InstalledVersions::getPrettyVersion('workerman/workerman'),
            'webman_version' => InstalledVersions::getPrettyVersion('webman/webman'),
            'event_loop' => Worker::getEventLoop()::class,
            'is_coroutine' => Coroutine::isCoroutine(),
        ];
    }

    #[McpTool(name: 'list_dependence', description: '获取当前项目已安装依赖列表.')]
    public function listDependence(): array
    {
        return InstalledVersions::getInstalledPackages();
    }

    #[McpTool(name: 'get_config', description: '获取应用程序配置.')]
    public function getConfig(
        #[Schema(description: '配置文件名')]
        ?string $path = null,
    ): ?array {
        return config($path);
    }

    #[McpTool(name: 'get_env', description: '获取应用程序环境变量.')]
    public function getEnv(
        #[Schema(description: '环境变量名')]
        ?string $key = null,
    ): array|false|string {
        return getenv($key);
    }
}
