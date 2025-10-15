<?php

namespace Luoyue\WebmanMcp;

use Mcp\Schema\ServerCapabilities;
use Mcp\Server;
use Luoyue\WebmanMcp\Enum\McpTransportEnum;
use Nyholm\Psr7\Factory\Psr17Factory;
use Mcp\Server\Transport\StdioTransport;
use Mcp\Server\Transport\StreamableHttpTransport;
use Mcp\Server\Session\InMemorySessionStore;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use support\Container;
use support\Log;
use Webman\Http\Response;
use Workerman\Coroutine\Locker;

final class McpServerManager
{

    private array $config;

    private static array $configs;

    private static string $pluginPrefix = 'plugin.luoyue.webman-mcp.';

    private function __construct(string $serviceName)
    {
        $this->config = self::$configs['services'][$serviceName];
        if(!$this->config['logger'] instanceof LoggerInterface) {
            $this->config['logger'] = $this->config['logger'] ?
                Log::channel(self::$pluginPrefix . $this->config['logger']) : Container::get(NullLogger::class);
        }
    }

    public static function service(string $serviceName): static
    {
        self::$configs ??= config(self::$pluginPrefix . 'app', []);
        if (!isset(self::$configs['services'][$serviceName])) {
            throw new \InvalidArgumentException("Mcp server [{$serviceName}] not found.");
        }

        if(!self::$configs['logger'] instanceof LoggerInterface) {
            self::$configs['logger'] = self::$configs['logger'] ?
                Log::channel(self::$pluginPrefix . self::$configs['logger']) : Container::get(NullLogger::class);
        }

        return new McpServerManager($serviceName);
    }

    public function run(McpTransportEnum $type): mixed
    {
        $server = Server::builder()
            ->setServerInfo($this->config['name'], $this->config['version'], $this->config['description'] ?? null)
            ->setDiscovery(
                base_path(),
                $this->config['discover']['scan_dirs'],
                $this->config['discover']['exclude_dirs'] ?? ['vendor'],
                $this->config['discover']['cache'] ?? null
            )
            ->setContainer(Container::instance())
            ->setPaginationLimit($this->config['pagination_limit'] ?? 50)
            ->setInstructions($this->config['instructions'] ?? null)
            ->setSession($this->config['session'] ??= Container::get(InMemorySessionStore::class))
            ->setCapabilities($this->config['capabilities'] ??= Container::get(ServerCapabilities::class))
            ->setLogger(self::$configs['logger'])
            ->build();

        return match ($type) {
            McpTransportEnum::STDOUT => $this->handleStdioMessage($server),
            McpTransportEnum::STREAMABLEHTTP => $this->handleHttpRequest($server),
        };
    }

    private function handleStdioMessage(Server $server)
    {
        $transport = new StdioTransport(logger: $this->config['logger']);
        $response = $server->run($transport);

        return $response;
    }

    private function handleHttpRequest(Server $server): Response
    {
        $transport = new StreamableHttpTransport(
            $this->getRequest(),
            $this->getPsrFactory(),
            $this->getPsrFactory(),
            $this->config['logger']
        );
        /** @var ResponseInterface $response */
        $response = $server->run($transport);
        return \response($response->getBody()->getContents(), $response->getStatusCode(), $response->getHeaders());
    }

    private function getPsrFactory(): Psr17Factory
    {
        return Container::get(Psr17Factory::class);
    }

    private function getRequest(): ServerRequestInterface
    {
        $psr17Factory = self::getPsrFactory();
        $headers = \request()->header();
        $serverRequest = $psr17Factory->createServerRequest(\request()->method(), request()->uri())
            ->withBody($psr17Factory->createStream(\request()->rawBody()))
            ->withCookieParams((array)\request()->cookie())
            ->withQueryParams((array)\request()->get())
            ->withParsedBody((array)\request()->post());
        return array_reduce(
            array_keys($headers),
            fn($request, $key) => $request->withHeader($key, $headers[$key]),
            $serverRequest
        );
    }

}