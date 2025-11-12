<?php

namespace Luoyue\WebmanMcp\Server;

use \Mcp\Server\Transport\StreamableHttpTransport as BaseStreamableHttpTransport;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Uid\Uuid;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\ServerSentEvents;

class StreamableHttpTransport extends BaseStreamableHttpTransport
{

    private readonly TcpConnection $connection;

    /**
     * @param array<string, string> $corsHeaders
     */
    public function __construct(
        private readonly ServerRequestInterface $request,
        ?ResponseFactoryInterface $responseFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        array $corsHeaders = [],
        LoggerInterface $logger = new NullLogger(),
    ) {
        $this->connection = $request->getAttribute(TcpConnection::class);
        parent::__construct($request, $responseFactory, $streamFactory, $corsHeaders, $logger);
    }

    protected function handleFiberTermination(): void
    {
        $finalResult = $this->sessionFiber->getReturn();

        if (null !== $finalResult) {
            try {
                $encoded = json_encode($finalResult, \JSON_THROW_ON_ERROR);
                $this->connection->send(new ServerSentEvents([
                    'event' => 'message',
                    'data' => $encoded,
                ]));
            } catch (\JsonException $e) {
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