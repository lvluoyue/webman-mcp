<?php

namespace Luoyue\WebmanMcp;

use Luoyue\WebmanMcp\Server\Session\Psr16StoreSession;
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
use support\Cache;
use support\Container;
use support\Log;
use Webman\Http\Response;

final class McpServerManager
{

    private array $config;

    private static array $configs;

    private static string $pluginPrefix = 'plugin.luoyue.webman-mcp.';

    private function __construct(string $serviceName)
    {
        $this->config = self::$configs['services'][$serviceName];
        if (!$this->config['logger'] instanceof LoggerInterface) {
            $this->config['logger'] = $this->config['logger'] ?
                Log::channel($this->config['logger']) : Container::get(NullLogger::class);
        }

        if (isset($this->config['discover']['cache'])) {
            $this->config['discover']['cache'] = Cache::store($this->config['discover']['cache']);
        }

        if (!isset($this->config['session'])) {
            throw new \InvalidArgumentException("Mcp server [{$serviceName}] session store not found.");
        }

    }

    public static function service(string $serviceName): static
    {
        self::$configs ??= config(self::$pluginPrefix . 'app', []);
        if (!isset(self::$configs['services'][$serviceName])) {
            throw new \InvalidArgumentException("Mcp server [{$serviceName}] not found.");
        }

        if (!self::$configs['logger'] instanceof LoggerInterface) {
            self::$configs['logger'] = self::$configs['logger'] === null ? Container::get(NullLogger::class) :
                Log::channel(self::$configs['logger']);
        }

        return new McpServerManager($serviceName);
    }

    public function run(McpTransportEnum $type): mixed
    {
        $sessionConfig = $this->config['session'];
        $sessionStore = $sessionConfig['store'] === null ? Container::get(InMemorySessionStore::class) :
            new Psr16StoreSession(
                Cache::store($sessionConfig['store']),
                $sessionConfig['prefix'] ?? 'mcp-',
                $sessionConfig['ttl'] ?? 3600
            );

        $server = Server::builder()
            ->setServerInfo($this->config['name'], $this->config['version'], $this->config['description'] ?? null)
            ->setDiscovery(
                base_path(),
                $this->config['discover']['scan_dirs'],
                $this->config['discover']['exclude_dirs'] ?? ['vendor'],
                $this->config['discover']['cache']
            )
            ->setContainer(Container::instance())
            ->setPaginationLimit($this->config['pagination_limit'] ?? 50)
            ->setInstructions($this->config['instructions'] ?? null)
            ->setSession($sessionStore)
            ->setCapabilities($this->config['capabilities'] ??= Container::get(ServerCapabilities::class))
            ->setLogger(self::$configs['logger'])
            ->addRequestHandlers($this->config['request_handlers'] ?? [])
            ->addNotificationHandlers($this->config['notification_handlers'] ?? []);
        foreach ($this->config['tool'] ?? [] as $tool) {
            $server->addTool(...$tool);
        }
        foreach ($this->config['prompt'] ?? [] as $prompt) {
            $server->addPrompt(...$prompt);
        }
        foreach ($this->config['resource'] ?? [] as $resource) {
            $server->addResource(...$resource);
        }
        foreach ($this->config['resource_template'] ?? [] as $resourceTemplate) {
            $server->addResourceTemplate(...$resourceTemplate);
        }

        $server = $server->build();

        return match ($type) {
            McpTransportEnum::STDOUT => $this->handleStdioMessage($server),
            McpTransportEnum::STREAMABLE_HTTP => $this->handleHttpRequest($server),
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