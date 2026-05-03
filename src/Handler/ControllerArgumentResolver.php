<?php

declare(strict_types=1);

namespace Waffle\Handler;

use Psr\Http\Message\ServerRequestInterface;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\Handler\ArgumentResolverInterface;

final readonly class ControllerArgumentResolver implements ArgumentResolverInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    #[\Override]
    public function resolve(
        string|object $controller,
        string $method,
        ServerRequestInterface $request,
        array $routeParams,
    ): array {
        $reflection = new ReflectionMethod($controller, $method);
        $args = [];

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            $typeName = $type?->getName();
            $name = $parameter->getName();

            // A. Handle Route Parameters (by name)
            if (array_key_exists($name, $routeParams)) {
                $val = $routeParams[$name];

                // Auto-cast if type matches
                if ($type instanceof ReflectionNamedType && $type->isBuiltin()) {
                    $val = match ($type->getName()) {
                        'int' => (int) $val,
                        'float' => (float) $val,
                        'bool' => filter_var($val, FILTER_VALIDATE_BOOLEAN),
                        default => $val,
                    };
                }

                $args[] = $val;
                continue;
            }

            // B. Handle Typed Dependencies (Services & Request)
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                // 1. Inject Request if requested
                if (
                    $typeName === ServerRequestInterface::class
                    || is_subclass_of($typeName, ServerRequestInterface::class)
                ) {
                    $args[] = $request;
                    continue;
                }

                // 2. Inject Service from Container
                if ($this->container->has($type->getName())) {
                    $args[] = $this->container->get($type->getName());
                    continue;
                }
            }

            // C. Handle Default Value
            if ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
                continue;
            }

            // D. Handle Nullable
            if ($parameter->allowsNull()) {
                $args[] = null;
                continue;
            }

            throw new RuntimeException(sprintf(
                'Controller Error: Argument "$%s" in "%s::%s" cannot be resolved. Check type hints or route parameters.',
                $name,
                is_object($controller) ? get_class($controller) : $controller,
                $method,
            ));
        }

        return $args;
    }
}
