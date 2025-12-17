<?php

namespace Luoyue\WebmanMcp\Server;

use Mcp\Capability\Registry\Loader\DiscoveryLoader;
use Mcp\Capability\Registry\Loader\LoaderInterface;
use Mcp\Capability\RegistryInterface;
use Psr\Log\NullLogger;

class DevelopmentMcpLoader implements LoaderInterface
{
    /**
     * @param string[] $path
     */
    public function __construct(private readonly array $path = [])
    {
    }

    public function load(RegistryInterface $registry): void
    {
        $discoveryLoader = new DiscoveryLoader(base_path(), ['vendor/luoyue/webman-mcp/src/DevMcp', ...$this->path], [], new NullLogger());
        $discoveryLoader->load($registry);
    }
}
