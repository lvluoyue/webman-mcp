<?php

namespace Luoyue\WebmanMcp;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Webman\Console\Command;
use Symfony\Component\Console\Command\Command as Commands;
use Workerman\Coroutine;
use Workerman\Events\Fiber;
use Workerman\Events\Swoole;
use Workerman\Events\Swow;
use Workerman\Worker;

class McpHelper
{
    /**
     * mcp执行时自带fiber导致误判，所以需要额外判断.
     */
    public static function is_coroutine(): bool
    {
        $event_loop = Worker::getEventLoop()::class;
        return in_array($event_loop, [Swoole::class, Swow::class, Fiber::class]) && Coroutine::isCoroutine();
    }

    /**
     * 运行console命令.
     * @param class-string<Commands> $command 命令类名.
     * @param array<string, string> $args 执行参数.
     * @return string 输出结果
     */
    public static function fetch_console(string $command, array $args = []): string
    {
        $application = new Command();
        /** @var Commands $commandInstance */
        $commandInstance = $application->createCommandInstance($command);
        $application->setAutoExit(false);

        $input = new ArrayInput(['command' => $commandInstance->getName(), ...$args]);
        $output = new BufferedOutput();

        $application->run($input, $output);
        return $output->fetch();
    }
}
