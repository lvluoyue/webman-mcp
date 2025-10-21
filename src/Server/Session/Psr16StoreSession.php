<?php

namespace Luoyue\WebmanMcp\Server\Session;

use Mcp\Server\Session\SessionStoreInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Uid\Uuid;
use Throwable;

class Psr16StoreSession implements SessionStoreInterface
{

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $ttl = 3600
    )
    {
    }

    public function exists(Uuid $id): bool
    {
        try {
            return $this->cache->has($id->toRfc4122());
        } catch (Throwable) {
            return false;
        }
    }

    public function read(Uuid $id): string|false
    {
        try {
            return $this->cache->get($id->toRfc4122(), false);
        } catch (Throwable) {
            return false;
        }
    }

    public function write(Uuid $id, string $data): bool
    {
        try {
            return $this->cache->set($id->toRfc4122(), $data, $this->ttl);
        } catch (Throwable) {
            return false;
        }
    }

    public function destroy(Uuid $id): bool
    {
        try {
            return $this->cache->delete($id->toRfc4122());
        } catch (Throwable) {
            return false;
        }
    }

    public function gc(): array
    {
        return [];
    }
}