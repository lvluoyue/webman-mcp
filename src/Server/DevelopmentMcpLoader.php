<?php

namespace Luoyue\WebmanMcp\Server;

use Mcp\Capability\Discovery\Discoverer;
use Mcp\Capability\Registry\Loader\LoaderInterface;
use Mcp\Capability\RegistryInterface;
use support\Log;

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
        $discoverer = new Discoverer(Log::channel('plugin.luoyue.webman-mcp.mcp_error_stderr'));

        $state = $discoverer->discover(base_path(), ['vendor/luoyue/webman-mcp/src/DevMcp', ...$this->path], []);
        foreach ($state->getTools() as $tool) {
            $registry->registerTool($tool->tool, $tool->handler, true);
        }
        foreach ($state->getPrompts() as $prompt) {
            $registry->registerPrompt($prompt->prompt, $prompt->handler, $prompt->completionProviders, true);
        }
        foreach ($state->getResources() as $resource) {
            $registry->registerResource($resource->resource, $resource->handler, true);
        }
        foreach ($state->getResourceTemplates() as $resource) {
            $registry->registerResourceTemplate($resource->resourceTemplate, $resource->handler, $resource->completionProviders, true);
        }
    }
}
