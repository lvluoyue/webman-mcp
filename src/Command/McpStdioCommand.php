<?php

namespace Luoyue\WebmanMcp\Command;

use Luoyue\WebmanMcp\McpServerManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('mcp:server', 'Starts an MCP server')]
final class McpStdioCommand extends Command
{
    public function __invoke(OutputInterface $output, #[Argument('Service name')] string $service): int
    {
        McpServerManager::service($service)->run();
        return Command::SUCCESS;
    }
}
