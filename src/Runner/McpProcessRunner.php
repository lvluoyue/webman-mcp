<?php

namespace Luoyue\WebmanMcp\Runner;

use Luoyue\WebmanMcp\Enum\McpTransportEnum;
use Luoyue\WebmanMcp\McpServerManager;
use support\Context;
use Workerman\Connection\TcpConnection;
use Webman\Http\Request;

final class McpProcessRunner implements McpRunnerInterface
{
    private static array $endpoint = [];

    public static function create(): array
    {
        $process = [];
        foreach (config('plugin.luoyue.webman-mcp.app.services', []) as $name => $service) {
            $processConfig = $service['process'] ?? [];
            if($processConfig['enable'] ?? false) {
                $process[$name] = array_merge($processConfig, [
                    'handler' => static::class,
                    'listen' => 'http://0.0.0.0:' . $processConfig['port'],
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

    public function onMessage(TcpConnection $connection, Request $request): void
    {
        Context::reset(new \ArrayObject([Request::class => $request]));
        try {
            if($service = self::$endpoint[$request->getLocalPort()][$request->path()] ?? null) {
                $connection->send(McpServerManager::service($service)->run(McpTransportEnum::STREAMABLEHTTP));
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
        }
    }
}