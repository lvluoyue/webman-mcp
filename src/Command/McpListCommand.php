<?php

namespace Luoyue\WebmanMcp\Command;

use Luoyue\WebmanMcp\McpServerManager;
use support\Container;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('mcp:list', 'List all MCP service')]
class McpListCommand extends Command
{

    public function __invoke(OutputInterface $output): int
    {
        $table = new Table($output);
        $table->setHeaders(['service', 'process', 'port', 'route', 'endpoint', 'discover_cache', 'discover_dirs', 'session_store', 'ttl', 'logger']);
        $table->setHeaderTitle('mcp service list');

        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        foreach ($mcpServerManager->getServiceNames() as $name) {
            $config = $mcpServerManager->getServiceConfig($name);
            $process = $config['process'];
            $router = $config['router'];
            $discover = $config['discover'];
            $session = $config['session'];
            $table->addRow([
                $name,
                $process['enable'] ? 'yes' : 'no',
                $process['port'] ?? 'none',
                $router['enable'] ? 'yes' : 'no',
                $router['endpoint'] ?? 'none',
                ($discover['cache'] ?? 'null') ?: 'default',
                json_encode($discover['scan_dirs'], JSON_UNESCAPED_SLASHES),
                ($session['store'] ?? 'null') ?: 'default',
                $session['ttl'] ?? 86400,
                $router['logger'] ?? 'none',
            ]);
        }

        $table->render();
        return Command::SUCCESS;
    }
}