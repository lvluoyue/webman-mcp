<?php

namespace Luoyue\WebmanMcp\DevMcp;

use Composer\InstalledVersions;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use support\Db;
use Throwable;

class Database
{
    #[McpTool(name: 'database_connections', description: '获取数据库redis配置信息列表')]
    public function databaseConnections(): array
    {
        $this->checkInstallDatabase();
        $connections = config('database.connections', []);
        return [
            'default' => config('database.default'),
            'connections' => array_map(function ($key, $connection) {
                return [
                    'connection_name' => $key,
                    'driver' => $connection['driver'] ?? null,
                    'database' => $connection['database'] ?? null,
                    'prefix' => $connection['prefix'] ?? null,
                    'schema' => $connection['schema'] ?? null,
                    'pool' => $connection['pool'] ?? [],
                ];
            }, array_keys($connections), array_values($connections)),
        ];
    }

    #[McpTool(name: 'database_execute_sql', description: '执行原始sql脚本')]
    public function databaseExecuteSql(
        #[Schema(description: 'sql脚本')]
        string $sql,
        #[Schema(description: 'sql参数绑定')]
        array $bindings,
        #[Schema(description: 'database连接名称')]
        ?string $connection = null,
    ): array
    {
        $this->checkInstallDatabase();
        try {
            return [
                'result' => array_map(fn ($item) => (array) $item, Db::connection($connection)->select($sql, $bindings)),
            ];
        } catch (Throwable $e) {
            throw new ToolCallException('执行sql失败: ' . $e->getMessage());
        }
    }

    protected function checkInstallDatabase(): void
    {
        !InstalledVersions::isInstalled('webman/database') && throw new ToolCallException('未安装数据库组件');
    }
}
