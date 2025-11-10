<?php

namespace Luoyue\WebmanMcp\Command;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('mcp:make', 'Create MCP service or template')]
class McpMakeCommand
{

    public function __invoke(
        OutputInterface $output,
        #[Argument('type name', suggestedValues: ['config', 'template'])] string $type,
        #[Argument('Service name')] string $service
    ): int
    {
        switch ($type) {
            case 'config':
                return $this->makeConfig($service);
            case 'template':
                return $this->makeTemplate($service);
            default:
                $output->writeln('<error>Please specify a type name</error>');
                return Command::INVALID;
        }
    }

    public function makeConfig(string $service): int
    {
        return Command::SUCCESS;
    }

    public function makeTemplate(string $service): int
    {
        return Command::SUCCESS;
    }
}