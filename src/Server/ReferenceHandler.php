<?php

namespace Luoyue\WebmanMcp\Server;

use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerInterface;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use support\Context;
use Webman\Http\Request;

class ReferenceHandler extends \Mcp\Capability\Registry\ReferenceHandler
{
    public function __construct(
        private readonly ?ContainerInterface $container = null,
    )
    {
        parent::__construct($container);
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return array<int, mixed>
     */
    protected function prepareArguments(ReflectionFunctionAbstract $reflection, array $arguments): array
    {
        $injectMake = method_exists($this->container, 'make');
        foreach ($reflection->getParameters() as $parameter) {
            $paramName = $parameter->getName();
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                if (is_a($type->getName(), Request::class, true)) {
                    $arguments[$paramName] = Context::get(Request::class);
                } else if (is_a($type->getName(), Model::class, true)) {
                    $arguments[$paramName] = $injectMake ? $this->container->make($type->getName()) : new ($type->getName());
                } else if (is_a($type->getName(), ThinkModel::class, true)) {
                    $arguments[$paramName] = $injectMake ? $this->container->make($type->getName()) : new ($type->getName());
                }
            }
        }
        return parent::prepareArguments($reflection, $arguments);
    }
}
