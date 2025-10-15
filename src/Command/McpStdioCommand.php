<?php

namespace Luoyue\WebmanMcp\Command;

use Luoyue\WebmanMcp\Enum\McpTransportEnum;
use Luoyue\WebmanMcp\McpServerManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('mcp:server', 'Starts an MCP server')]
final class McpStdioCommand extends Command
{
    public function __invoke(OutputInterface $output, #[Argument('Service name')] string $service): int
    {
        $output->writeln("Starting MCP server for service $service...");
        McpServerManager::service($service)->run(McpTransportEnum::STDOUT);
        return Command::SUCCESS;
    }
}
