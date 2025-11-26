<?php

namespace Luoyue\WebmanMcp\Command;

use Luoyue\WebmanMcp\McpServerManager;
use support\Container;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

#[AsCommand('mcp:server', 'Starts an MCP server')]
final class McpStdioCommand extends Command
{
    public function __invoke(#[Argument('Service name')] string $service): int
    {
        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        /** @var ConsoleOutputInterface $output */
        $output->getErrorOutput()->writeln("<info>Starting MCP service: {$service}</info>");
        $mcpServerManager->start($service);
        return Command::SUCCESS;
    }
}
