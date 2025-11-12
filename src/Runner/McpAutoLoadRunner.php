<?php

namespace Luoyue\WebmanMcp\Runner;

use Luoyue\WebmanMcp\Enum\McpClientRegisterEnum;
use Luoyue\WebmanMcp\McpServerManager;
use support\Container;
use Webman\Bootstrap;
use Workerman\Worker;

final class McpAutoLoadRunner implements McpRunnerInterface, Bootstrap
{
    public static function create(): array
    {
        return [self::class];
    }

    public static function start(?Worker $worker): void
    {
        /** @var ?McpClientRegisterEnum $editor */
        $editor = config('plugin.luoyue.webman-mcp.app.auto_register_client', null);
        if (PHP_OS_FAMILY !== 'Windows' || is_phar() || !$editor) {
            return;
        }

        if ($editor && !$editor instanceof McpClientRegisterEnum) {
            throw new \RuntimeException('editor must be instanceof McpClientDirectoryEnum');
        }

        $lockFile = base_path('windows.php');
        if (time() - filemtime($lockFile) <= 3) {
            return;
        }
        touch($lockFile);

        $editorPath = $editor->getPath();
        if (!file_exists($editorPath)) {
            @mkdir(dirname($editorPath));
            $mcpServers = [];
        } else {
            $mcpServers = json_decode(file_get_contents($editorPath), true);
        }
        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        foreach ($mcpServerManager->getServiceNames() as $name) {
            $mcpServers[$editor->getKey()][$name] = [
                'type' => 'stdio',
                'command' => 'php',
                'args' => [base_path('webman'), 'mcp:server', $name]
            ];
        }
        file_put_contents($editor->getPath(), json_encode($mcpServers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
