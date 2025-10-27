<?php

namespace Luoyue\WebmanMcp;

use Mcp\Server;
use Luoyue\WebmanMcp\Enum\McpTransportEnum;
use Mcp\Server\Session\Psr16StoreSession;
use Mcp\Server\Transport\StdioTransport;
use Mcp\Server\Transport\StreamableHttpTransport;
use Mcp\Server\Session\InMemorySessionStore;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use support\Cache;
use support\Container;
use support\Log;
use Webman\Http\Response;
use function request;

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

        $sessionConfig = $this->config['session'];
        $this->config['session'] = $sessionConfig['store'] === null ? Container::get(InMemorySessionStore::class) :
            new Psr16StoreSession(
                Cache::store($sessionConfig['store']),
                $sessionConfig['prefix'] ?? 'mcp-',
                $sessionConfig['ttl'] ?? 3600
            );
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
        $server = Server::builder()
            ->setDiscovery(
                base_path(),
                $this->config['discover']['scan_dirs'],
                $this->config['discover']['exclude_dirs'] ?? ['vendor'],
                $this->config['discover']['cache']
            )
            ->setContainer(Container::instance())
            ->setSession($this->config['session'])
            ->setLogger(self::$configs['logger']);

        if (isset($this->config['configure']) && is_callable($this->config['configure'])) {
            ($this->config['configure'])($server);
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
            request: new ServerRequest(request()->method(), request()->uri(), request()->header(), request()->rawBody()),
            corsHeaders: $this->config['headers'] ?? [],
            logger: $this->config['logger']);
        /** @var ResponseInterface $response */
        $response = $server->run($transport);
        return response($response->getBody()->getContents(), $response->getStatusCode(), $response->getHeaders());
    }

}