<?php

namespace Luoyue\WebmanMcp\DevMcp;

use Closure;
use Composer\InstalledVersions;
use function config;
use FastRoute\Dispatcher;
use Luoyue\WebmanMcp\McpHelper;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use ReflectionMethod;
use Webman\App;
use Webman\Console\Commands\BuildBinCommand;
use Webman\Console\Commands\BuildPharCommand;
use Webman\Event\Event;
use Webman\Route;
use Webman\Route\Route as RouteObject;
use Workerman\Worker;

class System
{
    #[McpTool(name: 'system_info', description: '获取webman框架信息，php版本信息，系统信息，是否使用协程等')]
    public function sequentialThinking(): array
    {
        $event_loop = Worker::getEventLoop()::class;
        return [
            'server_os' => PHP_OS,
            'server_uname' => php_uname(),
            'php_version' => PHP_VERSION,
            'php_binary' => PHP_BINARY,
            'php_sapi_name' => php_sapi_name(),
            'workerman_version' => InstalledVersions::getPrettyVersion('workerman/workerman'),
            'webman_version' => InstalledVersions::getPrettyVersion('workerman/webman-framework'),
            'event_loop_class' => $event_loop,
            'is_coroutine' => McpHelper::is_coroutine(),
            'default_temp_dir' => sys_get_temp_dir(),
        ];
    }

    #[McpTool(name: 'list_dependence', description: '获取当前项目已安装依赖列表')]
    public function listDependence(): array
    {
        return InstalledVersions::getAllRawData();
    }

    /**
     * @return string[]
     */
    #[McpTool('list_extensions', '获取当前环境已加载的php扩展')]
    public function extensions(): array
    {
        return get_loaded_extensions();
    }

    /**
     * @return string[]
     */
    #[McpTool(name: 'get_extension_funcs', description: '获取扩展已加载的函数')]
    public function getExtensionFuncs(
        #[Schema(description: '扩展名')]
        string $extension,
    ): array
    {
        return get_extension_funcs($extension);
    }

    #[McpTool(name: 'get_php_ini', description: '获取应用程序配置')]
    public function getPhpIni(
        #[Schema(description: '扩展名')]
        ?string $extension = null,
    ): array|bool
    {
        return ini_get_all($extension);
    }

    #[McpTool(name: 'get_config', description: '获取应用程序配置')]
    public function getConfig(
        #[Schema(description: '配置文件名')]
        string $path,
    ): mixed
    {
        return config($path);
    }

    #[McpTool(name: 'list_routes', description: '获取路由列表')]
    public function listRoutes(): array
    {
        $callback = function (RouteObject $route) {
            $cb = $route->getCallback();
            $cb = $cb instanceof Closure ? 'Closure' : (is_array($cb) ? json_encode($cb) : var_export($cb, 1));
            return [
                'name' => $route->getName(),
                'uri' => $route->getPath(),
                'methods' => $route->getMethods(),
                'callback' => $cb,
                'param' => $route->param(),
                'middleware' => json_encode($route->getMiddleware()),
            ];
        };
        return array_map($callback, Route::getRoutes());
    }

    #[McpTool(name: 'match_routes', description: '匹配url对应的路由信息')]
    public function matchRoutes(
        #[Schema(description: 'url路径，不包含域名')]
        string $path,
        #[Schema(description: '请求方法')]
        ?string $method = '',
    ): array
    {
        $method = strtoupper($method);
        $getAppByController = new ReflectionMethod(App::class, 'getAppByController');
        $getRealMethod = new ReflectionMethod(App::class, 'getRealMethod');
        $routeInfo = Route::dispatch($method, $path);
        if ($routeInfo[0] === Dispatcher::FOUND) {
            $callback = $routeInfo[1]['callback'];
            /** @var RouteObject $route */
            $route = clone $routeInfo[1]['route'];
            $app = $controller = $action = '';
            $args = !empty($routeInfo[2]) ? $routeInfo[2] : [];
            if ($args) {
                $route->setParams($args);
            }
            $args = array_merge($route->param(), $args);

            if (is_array($callback)) {
                $controller = $callback[0];
                $plugin = App::getPluginByClass($controller);
                $app = $getAppByController->invokeArgs(null, [$controller]);
                $action = $getRealMethod->invokeArgs(null, [$controller, $callback[1]]) ?? '';
            } else {
                $plugin = App::getPluginByPath($path);
            }
            return [
                'plugin' => $plugin,
                'app' => $app,
                'controller' => $controller ?: '',
                'action' => $action,
                'route' => [
                    'name' => $route->getName(),
                    'uri' => $route->getPath(),
                    'methods' => $route->getMethods(),
                    'callback' => $callback,
                    'param' => $route->param(),
                    'args' => $args,
                    'middleware' => $route->getMiddleware(),
                ],
            ];
        } else {
            if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
                throw new ToolCallException('Method Not Allowed. Supported methods: ' . implode(', ', $routeInfo[1]));
            }
            throw new ToolCallException('Not Found');
        }
    }

    #[McpTool(name: 'list_events', description: '获取事件列表')]
    public function listEvents(): array
    {
        if (!InstalledVersions::isInstalled('webman/event')) {
            throw new ToolCallException('请先安装webman/event扩展');
        }
        $callback = function ($item) {
            $event_name = $item[0];
            $callback = $item[1];
            if (is_array($callback) && is_object($callback[0])) {
                $callback[0] = get_class($callback[0]);
            }
            $cb = $callback instanceof Closure ? 'Closure' : (is_array($callback) ? json_encode($callback) : var_export($callback, 1));
            return [
                'event_name' => $event_name,
                'callback' => $cb,
            ];
        };
        return array_map($callback, Event::list());
    }

    #[McpTool(name: 'get_env', description: '获取应用程序环境变量')]
    public function getEnv(
        #[Schema(description: '环境变量名')]
        ?string $key = null,
    ): array|false|string
    {
        return getenv($key);
    }

    #[McpTool(name: 'eval_code', description: '在当前进程中执行php代码')]
    public function evalCode(
        #[Schema(description: 'php代码')]
        string $code,
    ): string
    {
        $code = str_replace(['<?php', '?>'], '', $code);
        ob_start();
        try {
            eval($code);
            $output = ob_get_contents();
        } finally {
            ob_end_clean(); // 确保无论是否出错都会清理缓冲区
        }
        return $output;
    }

    #[McpTool(name: 'build_phar', description: '将项目代码打包为phar文件')]
    public function buildPhar(): string
    {
        if (!class_exists(BuildPharCommand::class)) {
            throw new ToolCallException('当前环境暂不支持执行此tool');
        }
        return McpHelper::fetch_console(BuildPharCommand::class);
    }

    #[McpTool(name: 'build_bin', description: '将项目代码打包为linux二进制可执行文件')]
    public function buildBin(): string
    {
        if (!class_exists(BuildBinCommand::class)) {
            throw new ToolCallException('当前环境暂不支持执行此tool');
        }
        return McpHelper::fetch_console(BuildBinCommand::class);
    }

}