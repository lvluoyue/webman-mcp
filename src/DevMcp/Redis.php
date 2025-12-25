<?php

namespace Luoyue\WebmanMcp\DevMcp;

use Composer\InstalledVersions;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;

class Redis
{
    #[McpTool(name: 'redis_connections', description: '获取数据库连接配置信息')]
    public function databaseConnections(): array
    {
        if (!$this->isInstallRedis()) {
            throw new ToolCallException('未安装Redis组件');
        }
        $connections = config('redis.connections', []);
        return [
            'default' => 'default',
            'connections' => array_map(function ($key, $connection) {
                return [
                    'connection_name' => $key,
                    'database' => $connection['database'] ?? null,
                    'prefix' => $connection['prefix'] ?? null,
                    'pool' => $connection['pool'] ?? [],
                ];
            }, array_keys($connections), array_values($connections)),
        ];
    }

    protected function isInstallRedis(): bool
    {
        return InstalledVersions::isInstalled('webman/redis');
    }
}
