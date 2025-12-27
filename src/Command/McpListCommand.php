<?php

namespace Luoyue\WebmanMcp\Command;

use Luoyue\WebmanMcp\McpServerManager;
use support\Container;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('mcp:list', 'List all MCP service')]
final class McpListCommand extends Command
{
    public function __invoke(OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders(['service', 'stdio', 'process_port', 'route', 'endpoint', 'discover_cache', 'discover_dirs', 'session_store', 'ttl', 'logger']);
        $table->setHeaderTitle('mcp service list');

        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        foreach ($mcpServerManager->getServiceNames() as $name) {
            $config = $mcpServerManager->getServiceConfig($name);
            $discover = $config['discover'];
            $session = $config['session'];
            $transport = $config['transport'];
            $httpConfig = $transport['streamable_http'];
            $process = $httpConfig['process'];
            $router = $httpConfig['router'];
            $table->addRow([
                $name,
                $transport['stdio']['enable'] ? 'yes' : 'no',
                $process['enable'] ? $process['port'] ?? '(null)' : '(null)',
                $router['enable'] ? 'yes' : 'no',
                $httpConfig['endpoint'] ?? '(null)',
                ($discover['cache'] ?? '(null)') ?: config('cache.default', '(null)'),
                json_encode($discover['scan_dirs'], JSON_UNESCAPED_SLASHES),
                ($session['store'] ?? '(null)') ?: config('cache.default', '(null)'),
                $session['ttl'] ?? 86400,
                $router['logger'] ?? '(null)',
            ]);
        }

        $table->render();
        return Command::SUCCESS;
    }
}
