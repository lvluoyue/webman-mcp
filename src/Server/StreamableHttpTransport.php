<?php

namespace Luoyue\WebmanMcp\Server;

use Http\Discovery\Psr17FactoryDiscovery;
use const JSON_THROW_ON_ERROR;
use JsonException;
use Luoyue\WebmanMcp\McpHelper;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Server\Transport\StreamableHttpTransport as BaseStreamableHttpTransport;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use support\Context;
use Symfony\Component\Uid\Uuid;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\ServerSentEvents;
use Workerman\Timer;

class StreamableHttpTransport extends BaseStreamableHttpTransport
{
    private readonly TcpConnection $connection;

    /**
     * @param array<string, string> $corsHeaders
     */
    public function __construct(
        public readonly ServerRequestInterface $request,
        private ?ResponseFactoryInterface $responseFactory = null,
        private ?StreamFactoryInterface $streamFactory = null,
        array $corsHeaders = [],
        ?LoggerInterface $logger = null,
    ) {
        $this->connection = $request->getAttribute(TcpConnection::class);
        $this->responseFactory = $responseFactory ?? Psr17FactoryDiscovery::findResponseFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        parent::__construct($request, $responseFactory, $streamFactory, $corsHeaders, $logger);
    }

    protected function createStreamedResponse(): ResponseInterface
    {
        $context = clone Context::get();
        $callback = function (bool $loop = false) use (&$callback, $context): void {
            try {
                Context::reset($context);

                !$loop && $this->logger->info('SSE: Starting request processing loop');

                while ($this->sessionFiber->isSuspended()) {
                    $this->flushOutgoingMessages($this->sessionId);

                    $pendingRequests = $this->getPendingRequests($this->sessionId);

                    if (empty($pendingRequests)) {
                        $yielded = $this->sessionFiber->resume();
                        $this->handleFiberYield($yielded, $this->sessionId);
                        continue;
                    }

                    $resumed = false;
                    foreach ($pendingRequests as $pending) {
                        $requestId = $pending['request_id'];
                        $timestamp = $pending['timestamp'];
                        $timeout = $pending['timeout'] ?? 120;

                        $response = $this->checkForResponse($requestId, $this->sessionId);

                        if (null !== $response) {
                            $yielded = $this->sessionFiber->resume($response);
                            $this->handleFiberYield($yielded, $this->sessionId);
                            $resumed = true;
                            break;
                        }

                        if (time() - $timestamp >= $timeout) {
                            $error = Error::forInternalError('Request timed out', $requestId);
                            $yielded = $this->sessionFiber->resume($error);
                            $this->handleFiberYield($yielded, $this->sessionId);
                            $resumed = true;
                            break;
                        }
                    }

                    if (!$resumed) {
                        Timer::delay(0.1, $callback, [true]);
                        return;
                    } // Prevent tight loop
                }

                $this->handleFiberTermination();
            } finally {
                isset($resumed) && $resumed && $this->sessionFiber = null;
            }
        };

        McpHelper::coroutine_defer($callback);
        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withHeader('X-Accel-Buffering', 'no')
            ->withBody($this->streamFactory->createStream("\r\n"));

        if ($this->sessionId) {
            $response = $response->withHeader('Mcp-Session-Id', $this->sessionId->toRfc4122());
        }

        return $this->withCorsHeaders($response);
    }

    protected function handleFiberTermination(): void
    {
        $finalResult = $this->sessionFiber->getReturn();

        if (null !== $finalResult) {
            try {
                $encoded = json_encode($finalResult, JSON_THROW_ON_ERROR);
                $this->connection->send(new ServerSentEvents([
                    'event' => 'message',
                    'data' => $encoded,
                ]));
            } catch (JsonException $e) {
                $this->logger->error('SSE: Failed to encode final Fiber result.', ['exception' => $e]);
            }
        }

        $this->sessionFiber = null;
    }

    protected function flushOutgoingMessages(?Uuid $sessionId): void
    {
        $messages = $this->getOutgoingMessages($sessionId);

        foreach ($messages as $message) {
            $this->connection->send(new ServerSentEvents([
                'event' => 'message',
                'data' => $message['message'],
            ]));
        }
    }
}
