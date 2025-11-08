<?php

namespace Luoyue\WebmanMcp\Runner;

use support\Container;
use Webman\Route;
use Luoyue\WebmanMcp\McpServerManager;

final class McpRouterRunner implements McpRunnerInterface
{
    public static function create(): array
    {
        $routes = [];
        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        foreach ($mcpServerManager->getServiceNames() as $name) {
            $config = $mcpServerManager->getServiceConfig($name);
            $routerConfig = $config['router'] ?? [];
            if($routerConfig['enable'] ?? false) {
                $routes[] = Route::any($routerConfig['endpoint'], fn() => $mcpServerManager->start($name));
            }
        }
        return $routes;
    }

}