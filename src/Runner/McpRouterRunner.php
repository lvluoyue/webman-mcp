<?php

namespace Luoyue\WebmanMcp\Runner;

use Luoyue\WebmanMcp\Enum\McpTransportEnum;
use Webman\Route;
use Luoyue\WebmanMcp\McpServerManager;

final class McpRouterRunner implements McpRunnerInterface
{
    public static function create(): array
    {
        $routes = [];
        foreach (config('plugin.luoyue.webman-mcp.app.services', []) as $name => $service) {
            $routerConfig = $service['router'] ?? [];
            if($routerConfig['enable'] ?? false) {
                $routes[] = Route::any(
                    $routerConfig['endpoint'],
                    fn() => McpServerManager::service($name)->run(McpTransportEnum::STREAMABLE_HTTP)
                );
            }
        }
        return $routes;
    }

}