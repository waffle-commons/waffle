<?php

declare(strict_types=1);

namespace Waffle\Handler;

use Psr\Http\Message\ResponseFactoryInterface;use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use Waffle\Commons\Contracts\Container\ContainerInterface;

/**
 * The terminal handler of the framework.
 * It executes the controller identified by the request attributes.
 */
final readonly class ControllerDispatcher implements RequestHandlerInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // 1. Retrieve Controller Info from PSR-7 Attributes
        $classname = $request->getAttribute('_classname');
        $method = $request->getAttribute('_method');
        $routeParams = $request->getAttribute('_params', []);

        // 2. Validate Pipeline Integrity
        if (!$classname || !$method) {
            throw new RuntimeException('Pipeline Error: No controller defined in request attributes. Did the RoutingMiddleware run?');
        }

        if (!is_string($classname) || !is_string($method)) {
            throw new RuntimeException('Pipeline Error: Invalid controller attributes.');
        }

        // 3. Lazy Load Controller from Container
        if (!$this->container->has($classname)) {
            $this->container->set($classname, $classname);
        }

        $controller = $this->container->get($classname);

        // We inject the ResponseFactory if the controller needs it (extends AbstractController)
        if (method_exists($controller, 'setResponseFactory')) {
            if ($this->container->has(ResponseFactoryInterface::class)) {
                $factory = $this->container->get(ResponseFactoryInterface::class);

                // Ensure we got an object before injecting
                if (is_object($factory) && $factory instanceof ResponseFactoryInterface) {
                    $controller->setResponseFactory($factory);
                }
            } else {
                // Warning: Controller needs factory but container doesn't have it.
                // We don't throw here to allow execution if jsonResponse is not called.
            }
        }

        // 4. Check Callable
        if (!method_exists($controller, $method)) {
            throw new RuntimeException(sprintf('Dispatcher Error: Method "%s" not found in "%s".', $method, $classname));
        }

        // 5. Resolve Arguments (Auto-wiring for Controller Methods)
        $args = $this->resolveArguments($controller, $method, $request, $routeParams);

        // 6. Execute
        return $controller->$method(...$args);
    }

    /**
     * Resolves dependencies for the controller method using Reflection.
     */
    private function resolveArguments(string|object $controller, string $method, ServerRequestInterface $request, array $routeParams): array
    {
        $reflection = new ReflectionMethod($controller, $method);
        $args = [];

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            $typeName = $type?->getName();
            $name = $parameter->getName();

            // A. Handle Route Parameters (by name)
            // We check if the parameter name exists in the extracted route params
            if (array_key_exists($name, $routeParams)) {
                $args[] = $routeParams[$name];
                continue;
            }

            // B. Handle Typed Dependencies (Services & Request)
            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {

                // 1. Inject Request if requested
                if ($typeName === ServerRequestInterface::class || is_subclass_of($typeName, ServerRequestInterface::class)) {
                    $args[] = $request;
                    continue;
                }

                // 2. Inject Service from Container
                if ($this->container->has($type->getName())) {
                    $args[] = $this->container->get($type->getName());
                    continue;
                }
            } else {
                $val = match ($typeName) {
                    'int' => (int) $name,
                    'bool' => (bool) $name,
                    default => $name,
                };
                $args[] = $val;
                continue;
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
                get_class($controller),
                $method
            ));
        }

        return $args;
    }

    /**
     * Safe resolution wrapper.
     * Ensures we get an OBJECT, even if the container returns a class string.
     */
    private function resolveService(string $id): object
    {
        $service = $this->container->get($id);

        if (is_object($service)) {
            return $service;
        }

        if (is_string($service) && class_exists($service)) {
            return new $service();
        }

        throw new RuntimeException(sprintf('Container Error: Service "%s" resolved to a non-object type.', $id));
    }
}
