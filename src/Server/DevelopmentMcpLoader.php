<?php

namespace Luoyue\WebmanMcp\Server;

use Mcp\Capability\Registry\Loader\DiscoveryLoader;
use Mcp\Capability\Registry\Loader\LoaderInterface;
use Mcp\Capability\RegistryInterface;
use Psr\Log\NullLogger;

class DevelopmentMcpLoader implements LoaderInterface
{

    public function load(RegistryInterface $registry): void
    {
        $discoveryLoader = new DiscoveryLoader(base_path('vendor/luoyue/webman-mcp/src'), ['DevMcp'], [], new NullLogger());
        $discoveryLoader->load($registry);
    }
}