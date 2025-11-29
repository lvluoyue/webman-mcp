<?php

namespace Luoyue\WebmanMcp\Command;

use Luoyue\WebmanMcp\McpServerManager;
use support\Container;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('mcp:server', 'Starts an MCP server')]
final class McpStdioCommand extends Command
{
    public function __invoke(OutputInterface $output, #[Argument('Service name')] string $service): int
    {
        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        $config = $mcpServerManager->getServiceConfig($service);
        if (!$config['transport']['stdio']['enable'] ?? false) {
            $output->writeln("<error>MCP service: {$service} not enable stdio</error>");
            return Command::FAILURE;
        }
        /** @var ConsoleOutputInterface $output */
        $output->getErrorOutput()->writeln("<info>Starting MCP service: {$service}</info>");
        $mcpServerManager->start($service);
        return Command::SUCCESS;
    }
}
