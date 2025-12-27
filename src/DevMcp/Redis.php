<?php

namespace Luoyue\WebmanMcp\DevMcp;

use Composer\InstalledVersions;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use support\Redis as RedisInstance;
use Throwable;

class Redis
{
    #[McpTool(name: 'redis_connections', description: '获取数据库连接配置信息')]
    public function databaseConnections(): array
    {
        $this->checkInstallRedis();
        $connections = config('redis', []);
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

    #[McpTool(name: 'redis_execute_raw', description: '执行原始Redis命令')]
    public function executeRaw(
        #[Schema(description: '命令参数数组')]
        array $parameters,
        #[Schema(description: 'Redis连接名称')]
        string $connection = 'default',
    ): array
    {
        $this->checkInstallRedis();
        try {
            return [
                'success' => true,
                'result' => RedisInstance::connection($connection)->executeRaw($parameters),
            ];
        } catch (Throwable $e) {
            throw new ToolCallException('执行原始命令失败: ' . $e->getMessage());
        }
    }

    #[McpTool(name: 'redis_execute_lua', description: '执行Redis Lua脚本')]
    public function executeLua(
        #[Schema(description: 'Lua脚本内容')]
        string $script,
        #[Schema(description: 'Redis连接名称')]
        string $connection = 'default',
        #[Schema(description: '键数量')]
        int $numKeys = 0,
        #[Schema(description: '参数列表')]
        array $args = [],
    ): array
    {
        $this->checkInstallRedis();
        try {
            return [
                'result' => RedisInstance::connection($connection)->eval($script, $numKeys, ...$args),
            ];
        } catch (Throwable $e) {
            throw new ToolCallException('执行Lua脚本失败: ' . $e->getMessage());
        }
    }

    #[McpTool(name: 'redis_execute_lua_sha', description: '使用sha1执行Redis Lua脚本')]
    public function executeLuaSha(
        #[Schema(description: 'Lua脚本内容')]
        string $script,
        #[Schema(description: 'Redis连接名称')]
        string $connection = 'default',
        #[Schema(description: '键数量')]
        int $numKeys = 0,
        #[Schema(description: '参数列表')]
        array $args = [],
    ): array
    {
        $this->checkInstallRedis();
        try {
            return [
                'success' => true,
                'result' => RedisInstance::connection($connection)->evalsha($script, $numKeys, ...$args),
            ];
        } catch (Throwable $e) {
            throw new ToolCallException('执行Lua脚本SHA失败: ' . $e->getMessage());
        }
    }

    protected function checkInstallRedis(): void
    {
        !InstalledVersions::isInstalled('webman/redis') && throw new ToolCallException('未安装Redis组件');
    }
}
