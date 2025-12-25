<?php

namespace Luoyue\WebmanMcp\DevMcp;

use Composer\InstalledVersions;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;

class Database
{
    #[McpTool(name: 'database_connections', description: '获取数据库redis配置信息列表')]
    public function databaseConnections(): array
    {
        if (!$this->isInstallDatabase()) {
            throw new ToolCallException('未安装数据库组件');
        }
        $connections = config('database.connections', []);
        return [
            'default' => config('database.default'),
            'connections' => array_map(function ($key, $connection) {
                return [
                    'connection_name' => $key,
                    'driver' => $connection['driver'] ?? null,
                    'database' => $connection['database'] ?? null,
                    'prefix' => $connection['prefix'] ?? null,
                    'pool' => $connection['pool'] ?? [],
                ];
            }, array_keys($connections), array_values($connections)),
        ];
    }

    protected function isInstallDatabase(): bool
    {
        return InstalledVersions::isInstalled('webman/database');
    }
}
