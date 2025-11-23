<?php

namespace Luoyue\WebmanMcp\Runner;

use Luoyue\WebmanMcp\McpServerManager;
use support\Container;
use support\Context;
use Workerman\Connection\TcpConnection;
use Webman\Http\Request;

final class McpProcessRunner implements McpRunnerInterface
{
    private static array $endpoint = [];

    public static function create(): array
    {
        $process = [];
        $mcpServerManager = new McpServerManager();
        foreach ($mcpServerManager->getServiceNames() as $name) {
            $config = $mcpServerManager->getServiceConfig($name);
            $processConfig = $config['process'] ?? [];
            if($processConfig['enable'] ?? false) {
                $process[$name] = array_merge($processConfig, [
                    'handler' => McpProcessRunner::class,
                    'listen' => self::getSocketName($processConfig['port']),
                    'constructor' => [
                        'requestClass'  => Request::class,
                    ]
                ]);
                if($endpoint = $service['router']['endpoint'] ?? null) {
                    self::$endpoint[$processConfig['port']][$endpoint] = $name;
                }
            }
        }
        return $process;
    }

    public static function getSocketName(int $port): string
    {
        return 'http://0.0.0.0:' . $port;
    }

    public function onMessage(TcpConnection $connection, Request $request): void
    {
        Context::reset(new \ArrayObject([Request::class => $request]));
        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        try {
            if($service = self::$endpoint[$request->getLocalPort()][$request->path()] ?? null) {
                $connection->send($mcpServerManager->start($service));
            } else {
                $connection->send(response(json_encode([
                    'error' => 'Not Found',
                    'message' => 'Not Found',
                ]), 404));
            }
        } catch (\Throwable $e) {
            $connection->send(response(json_encode([
                'error' => 'Server Error',
                'message' => $e->getMessage(),
            ]), 500));
        } finally {
            Context::destroy();
        }
    }
}