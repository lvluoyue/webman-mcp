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
        private readonly string $prefix = 'mcp-',
        private readonly int $ttl = 3600
    )
    {
    }

    public function exists(Uuid $id): bool
    {
        try {
            return $this->cache->has($this->getKey($id));
        } catch (Throwable) {
            return false;
        }
    }

    public function read(Uuid $id): string|false
    {
        try {
            return $this->cache->get($this->getKey($id), false);
        } catch (Throwable) {
            return false;
        }
    }

    public function write(Uuid $id, string $data): bool
    {
        try {
            return $this->cache->set($this->getKey($id), $data, $this->ttl);
        } catch (Throwable) {
            return false;
        }
    }

    public function destroy(Uuid $id): bool
    {
        try {
            return $this->cache->delete($this->getKey($id));
        } catch (Throwable) {
            return false;
        }
    }

    public function gc(): array
    {
        return [];
    }

    private function getKey(Uuid $id): string
    {
        return $this->prefix . $id;
    }
}