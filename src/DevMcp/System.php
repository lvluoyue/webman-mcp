<?php

namespace Luoyue\WebmanMcp\DevMcp;

use Closure;
use Composer\InstalledVersions;
use function config;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Webman\Route;
use Webman\Route\Route as RouteObject;
use Workerman\Coroutine;
use Workerman\Events\Fiber;
use Workerman\Events\Swoole;
use Workerman\Events\Swow;
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
            'workerman_version' => InstalledVersions::getPrettyVersion('workerman/workerman'),
            'webman_version' => InstalledVersions::getPrettyVersion('workerman/webman-framework'),
            'event_loop' => $event_loop,
            // mcp执行时自带fiber导致误判，所以需要额外判断
            'is_coroutine' => in_array($event_loop, [Swoole::class, Swow::class, Fiber::class]) && Coroutine::isCoroutine(),
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
    ): mixed
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
        eval($code);
        return ob_get_contents();
    }
}
