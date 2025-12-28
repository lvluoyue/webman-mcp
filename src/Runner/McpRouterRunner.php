<?php

namespace Luoyue\WebmanMcp\Runner;

use Luoyue\WebmanMcp\McpServerManager;
use support\Container;
use Webman\Route;

final class McpRouterRunner implements McpRunnerInterface
{
    public static function create(): array
    {
        $routes = [];
        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        foreach ($mcpServerManager->getServiceNames() as $name) {
            $config = $mcpServerManager->getServiceConfig($name);
            $httpConfig = $config['transport']['streamable_http'];
            if ($httpConfig['router']['enable'] ?? false) {
                $routes[] = Route::any($httpConfig['endpoint'], fn () => $mcpServerManager->start($name));
            }
        }
        return $routes;
    }
}
