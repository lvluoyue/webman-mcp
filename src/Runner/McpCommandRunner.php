<?php

namespace Luoyue\WebmanMcp\Runner;

use Luoyue\WebmanMcp\Command\McpStdioCommand;
use support\Container;

final class McpCommandRunner implements McpRunnerInterface
{
    const COMMAND = [
        McpStdioCommand::class,
    ];

    public static function create(): array
    {
        return self::COMMAND;
    }
}
