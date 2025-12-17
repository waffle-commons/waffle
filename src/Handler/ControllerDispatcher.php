<?php

declare(strict_types=1);

namespace Waffle\Handler;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\View\ViewInterface;

/**
 * The terminal handler of the framework.
 * It executes the controller identified by the request attributes.
 */
final readonly class ControllerDispatcher implements RequestHandlerInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // 1. Retrieve Controller Info from PSR-7 Attributes
        $classname = $request->getAttribute('_classname');
        $method = $request->getAttribute('_method');
        $routeParams = $request->getAttribute('_params', []);

        if (!$classname || !$method) {
            $attrs = var_export($request->getAttributes(), true);
            throw new RuntimeException('Pipeline Error: No controller defined in request attributes. Did the RoutingMiddleware run? Attributes: '
            . $attrs);
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
            throw new RuntimeException(sprintf(
                'Dispatcher Error: Method "%s" not found in "%s".',
                $method,
                $classname,
            ));
        }

        // 5. Resolve Arguments (Auto-wiring for Controller Methods)
        $args = $this->resolveArguments($controller, $method, $request, $routeParams);

        // 6. Execute
        $result = $controller->$method(...$args);

        if ($result instanceof ResponseInterface) {
            return $result;
        }

        // 7. Auto-Response Conversion
        if ($this->container->has(ResponseFactoryInterface::class)) {
            /** @var ResponseFactoryInterface $factory */
            $factory = $this->container->get(ResponseFactoryInterface::class);

            if ($result === null) {
                return $factory->createResponse(204);
            }

            if (is_array($result) || $result instanceof \JsonSerializable) {
                $response = $factory->createResponse(200)->withHeader('Content-Type', 'application/json');
                $response->getBody()->write(json_encode($result));
                return $response;
            }

            if (is_string($result)) {
                $response = $factory->createResponse(200)->withHeader('Content-Type', 'text/html');
                $response->getBody()->write($result);
                return $response;
            }

            // If it is a View, we likely need to render it?
            // Assuming Waffle\Core\View exists or similar.
            // For now, if it's an object with __toString?
            if (is_object($result) && method_exists($result, '__toString')) {
                $response = $factory->createResponse(200);
                $response->getBody()->write((string) $result);
                return $response;
            }

            if ($result instanceof ViewInterface) {
                $response = $factory->createResponse(200)->withHeader('Content-Type', 'application/json');
                $response->getBody()->write(json_encode($result->data));
                return $response;
            }
        }

        throw new RuntimeException(sprintf(
            'Controller Error: Method "%s::%s" returned "%s", but ResponseInterface was expected and no conversion strategy matched.',
            get_class($controller),
            $method,
            get_debug_type($result),
        ));
    }

    /**
     * Resolves dependencies for the controller method using Reflection.
     */
    private function resolveArguments(
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
                get_class($controller),
                $method,
            ));
        }

        return $args;
    }
}
