<?php

namespace Luoyue\WebmanMcp;

use Mcp\Server;
use Mcp\Server\Session\Psr16StoreSession;
use Mcp\Server\Transport\CallbackStream;
use Mcp\Server\Transport\StdioTransport;
use Luoyue\WebmanMcp\Server\StreamableHttpTransport;
use Mcp\Server\Session\InMemorySessionStore;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use support\Cache;
use support\Container;
use support\Log;
use Webman\Http\Response;
use Workerman\Connection\TcpConnection;
use Workerman\Coroutine;
use Workerman\Worker;
use Generator;
use function request;

final class McpServerManager
{
    public static bool $isInit = false;

    private static array $config;

    private static string $pluginPrefix = 'plugin.luoyue.webman-mcp.';

    public function __construct()
    {
        self::$config = config(self::$pluginPrefix . 'mcp', []);
    }

    public static function loadConfig(): void
    {
        array_walk(self::$config, function (&$config, $serviceName) {
            if (!$config['logger'] instanceof LoggerInterface) {
                $config['logger'] = $config['logger'] ?
                    Log::channel(self::$pluginPrefix . $config['logger']) : Container::get(NullLogger::class);
            }

            if (isset($config['discover']['cache'])) {
                $config['discover']['cache'] = Cache::store($config['discover']['cache']);
            }
            $config['discover']['exclude_dirs'] ??= ['vendor'];

            if (!isset($config['session'])) {
                throw new \InvalidArgumentException("Mcp server [{$serviceName}] session store not found.");
            }

            $sessionConfig = $config['session'];
            $config['session'] = $sessionConfig['store'] === null ? Container::get(InMemorySessionStore::class) :
                new Psr16StoreSession(
                    Cache::store($sessionConfig['store']),
                    $sessionConfig['prefix'] ?? 'mcp-',
                    $sessionConfig['ttl'] ?? 3600
                );
        });
    }

    /**
     * @return Generator<String>
     */
    public function getServiceNames(): Generator
    {
        yield from array_keys(self::$config);
    }

    public function getServiceConfig(string $serviceName): array
    {
        $config = self::$config[$serviceName] ?? null;
        if (!$config) {
            throw new \InvalidArgumentException("Mcp server [{$serviceName}] not found.");
        }
        return $config;
    }

    public function start(string $serviceName): mixed
    {
        if (!self::$isInit) {
            self::loadConfig();
            self::$isInit = true;
        }

        $config = $this->getServiceConfig($serviceName);
        $discover = $config['discover'];

        $server = Server::builder()
            ->setDiscovery(base_path(), $discover['scan_dirs'], $discover['exclude_dirs'], $discover['cache'])
            ->setContainer(Container::instance())
            ->setSession($config['session'])
            ->setLogger($config['logger']);
        if (isset($config['configure']) && is_callable($config['configure'])) {
            ($config['configure'])($server);
        }

        $server = $server->build();

        return Worker::getAllWorkers() ? $this->handleHttpRequest($server, $serviceName) : $this->handleStdioMessage($server, $serviceName);
    }

    private function handleStdioMessage(Server $server, string $serviceName)
    {
        $config = $this->getServiceConfig($serviceName);

        $transport = new StdioTransport(logger: $config['logger']);
        $response = $server->run($transport);

        return $response;
    }

    private function handleHttpRequest(Server $server, string $serviceName): Response
    {
        $config = $this->getServiceConfig($serviceName);

        $request = new ServerRequest(
            request()->method(),
            request()->uri(),
            request()->header(),
            request()->rawBody(),
            request()->protocolVersion(),
            $_SERVER
        );
        $request = $request->withAttribute(TcpConnection::class, request()->connection);

        $transport = new StreamableHttpTransport(request: $request, corsHeaders: $config['headers'] ?? [], logger: $config['logger']);
        /** @var ResponseInterface $response */
        $response = $server->run($transport);

        return response($this->getResponseBody($response->getBody()), $response->getStatusCode(), array_map('current', $response->getHeaders()));
    }

    private function getResponseBody(StreamInterface $body): string
    {
        if($body instanceof CallbackStream) {
            Coroutine::defer($body->getContents(...));
            return "\r\n";
        }
        return $body->getContents();
    }

}