<?php

namespace Luoyue\WebmanMcp;

use Generator;
use InvalidArgumentException;
use Luoyue\WebmanMcp\Server\StreamableHttpTransport;
use Mcp\Server;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Psr16StoreSession;
use Mcp\Server\Transport\CallbackStream;
use Mcp\Server\Transport\StdioTransport;
use Mcp\Server\Transport\TransportInterface;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use support\Cache;
use support\Container;
use support\Log;
use WeakMap;
use Webman\Http\Response;
use Workerman\Connection\TcpConnection;
use Workerman\Coroutine;
use Workerman\Timer;
use Workerman\Worker;
use function request;

final class McpServerManager
{
    public static bool $isInit = false;

    private static array $config;

    /** @var array<Server> */
    private static array $server = [];

    /** @var WeakMap<TransportInterface, int> */
    private static WeakMap $transports;

    public const PLUGIN_REWFIX = 'plugin.luoyue.webman-mcp.';

    public function __construct()
    {
        self::$config = config(self::PLUGIN_REWFIX . 'mcp', []);
        self::$transports ??= new WeakMap();
    }

    public static function loadConfig(): void
    {
        array_walk(self::$config, function (&$config, $serviceName) {
            if (!$config['logger'] instanceof LoggerInterface) {
                $config['logger'] = $config['logger'] ?
                    Log::channel(self::PLUGIN_REWFIX . $config['logger']) : Container::get(NullLogger::class);
            }

            if (isset($config['discover']['cache'])) {
                $config['discover']['cache'] = Cache::store($config['discover']['cache']);
            }
            $config['discover']['exclude_dirs'] ??= ['vendor'];

            if (!isset($config['session'])) {
                throw new InvalidArgumentException("Mcp server [{$serviceName}] session store not found.");
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
     * @return Generator<string>
     */
    public function getServiceNames(): Generator
    {
        yield from array_keys(self::$config);
    }

    public function getServiceConfig(string $serviceName): array
    {
        $config = self::$config[$serviceName] ?? null;
        if (!$config) {
            throw new InvalidArgumentException("Mcp server [{$serviceName}] not found.");
        }
        return $config;
    }

    /**
     * @return WeakMap<TransportInterface, int>
     */
    public function getTransports(): WeakMap
    {
        return self::$transports;
    }

    public function start(string $serviceName): mixed
    {
        if (!self::$isInit) {
            self::loadConfig();
            self::$isInit = true;
        }

        $config = $this->getServiceConfig($serviceName);
        if (!isset(self::$server[$serviceName])) {
            $discover = $config['discover'];
            $server = Server::builder()
                ->setDiscovery(base_path(), $discover['scan_dirs'], $discover['exclude_dirs'], $discover['cache'])
                ->setContainer(Container::instance())
                ->setSession($config['session'])
                ->setLogger($config['logger']);
            if (isset($config['configure']) && is_callable($config['configure'])) {
                ($config['configure'])($server);
            }

            self::$server[$serviceName] = $server->build();
        }
        $server = self::$server[$serviceName];

        return Worker::getAllWorkers() ? $this->handleHttpRequest($server, $serviceName) : $this->handleStdioMessage($server, $serviceName);
    }

    private function handleStdioMessage(Server $server, string $serviceName)
    {
        $config = $this->getServiceConfig($serviceName);

        $transport = new StdioTransport(logger: $config['logger']);
        self::$transports[$transport] = time();
        $response = $server->run($transport);

        return $response;
    }

    private function handleHttpRequest(Server $server, string $serviceName): Response
    {
        $config = $this->getServiceConfig($serviceName);
        $headers = $config['transport']['streamable_http']['headers'] ?? [];

        $request = new ServerRequest(
            request()->method(),
            request()->uri(),
            request()->header(),
            request()->rawBody(),
            request()->protocolVersion(),
            $_SERVER
        );
        $request = $request->withAttribute(TcpConnection::class, request()->connection);

        $transport = new StreamableHttpTransport(request: $request, corsHeaders: $headers, logger: $config['logger']);
        self::$transports[$transport] = time();

        /** @var ResponseInterface $response */
        $response = $server->run($transport);

        return response($this->getResponseBody($response->getBody()), $response->getStatusCode(), array_map('current', $response->getHeaders()));
    }

    private function getResponseBody(StreamInterface $body): string
    {
        if ($body instanceof CallbackStream) {
            $callback = $body->getContents(...);
            Coroutine::isCoroutine() ? Coroutine::defer($callback) : Timer::delay(0.000001, $callback);
            return "\r\n";
        }
        return $body->getContents();
    }

}