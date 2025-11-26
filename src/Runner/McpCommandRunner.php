<?php

namespace Luoyue\WebmanMcp\Runner;

use Luoyue\WebmanMcp\Command\McpInspectorCommand;
use Luoyue\WebmanMcp\Command\McpListCommand;
use Luoyue\WebmanMcp\Command\McpMakeCommand;
use Luoyue\WebmanMcp\Command\McpStdioCommand;

final class McpCommandRunner implements McpRunnerInterface
{
    const COMMAND = [
        McpStdioCommand::class,
        McpListCommand::class,
        McpMakeCommand::class,
        McpInspectorCommand::class,
    ];

    public static function create(): array
    {
        return self::COMMAND;
    }
}
