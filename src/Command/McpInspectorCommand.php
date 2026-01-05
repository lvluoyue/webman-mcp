<?php

namespace Luoyue\WebmanMcp\Command;

use Luoyue\WebmanMcp\McpServerManager;
use support\Container;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('mcp:inspector', 'Start MCP inspector')]
final class McpInspectorCommand extends Command
{
    public function __invoke(InputInterface $input, OutputInterface $output, #[Argument('Service name')] ?string $service): int
    {
        $style = new SymfonyStyle($input, $output);
        /** @var McpServerManager $mcpServerManager */
        $mcpServerManager = Container::get(McpServerManager::class);
        $servers = iterator_to_array($mcpServerManager->getServiceNames());
        if ($service === null) {
            $service = QuestionHelper::handleQuestions([
                'service' => [
                    'question' => 'Please choice service name',
                    'choice' => $servers,
                ],
            ], $style)['service'];
        }
        $npxPath = $this->findExecutable(DIRECTORY_SEPARATOR === '\\' ? 'npx.cmd' : 'npx');
        if ($npxPath === null) {
            $style->error('npx not found. Please install Node.js to use the MCP Inspector.');
            $style->writeln('Visit: https://nodejs.org/');

            return Command::FAILURE;
        }

        $command = sprintf(
            '%s -y @modelcontextprotocol/inspector %s',
            escapeshellarg($npxPath),
            base_path('webman') . ' mcp:server ' . $service,
        );

        // Execute and return exit code
        passthru($command, $exitCode);

        return $exitCode === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function findExecutable(string $name): ?string
    {
        $nullDevice = DIRECTORY_SEPARATOR === '\\' ? 'nul' : '/dev/null';

        // Try which command first (Unix/Linux/macOS)
        $which = trim((string) shell_exec(sprintf('which %s 2>%s', escapeshellarg($name), $nullDevice)));
        if ($which !== '' && is_executable($which)) {
            return $which;
        }

        // Try where command (Windows)
        $where = trim((string) shell_exec(sprintf('where %s 2>%s', escapeshellarg($name), $nullDevice)));
        if ($where !== '') {
            $paths = explode("\n", $where);
            $firstPath = trim($paths[0]);
            if ($firstPath !== '' && (is_executable($firstPath) || DIRECTORY_SEPARATOR === '\\')) {
                return $firstPath;
            }
        }

        return null;
    }
}
