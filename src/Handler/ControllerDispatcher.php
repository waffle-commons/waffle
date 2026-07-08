<?php

declare(strict_types=1);

namespace Waffle\Handler;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\EventDispatcher\EventDispatcherInterface;
use Waffle\Commons\Contracts\Handler\ArgumentResolverInterface;
use Waffle\Commons\Contracts\Handler\ResponseConverterInterface;
use Waffle\Commons\Contracts\Handler\ResponseFactoryAwareInterface;
use Waffle\Event\ControllerArgumentsResolvedEvent;

/**
 * The terminal handler of the framework.
 * It executes the controller identified by the request attributes.
 *
 * Leftover-purge §3: the dispatcher never instantiates its own dependencies.
 * The `ArgumentResolverInterface` is now required at construction time; callers
 * (AppKernelFactory + AbstractKernel default-handler registration) resolve it
 * from the container.
 */
final readonly class ControllerDispatcher implements RequestHandlerInterface
{
    public function __construct(
        private ContainerInterface $container,
        private ?EventDispatcherInterface $dispatcher,
        private ArgumentResolverInterface $argumentResolver,
        private ?ResponseConverterInterface $responseConverter = null,
    ) {}

    /**
     * @throws \JsonException
     */
    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var mixed $classname */
        $classname = $request->getAttribute('_classname');
        /** @var mixed $method */
        $method = $request->getAttribute('_method');
        /** @var array $routeParams */
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

        /** @var object $controller */
        $controller = $this->container->get($classname);

        // ARCH-05: inject the PSR-17 factory only into controllers that declare
        // the need through the formal ResponseFactoryAwareInterface contract —
        // an explicit interface check, never a loose method_exists() heuristic.
        if (
            $controller instanceof ResponseFactoryAwareInterface
            && $this->container->has(ResponseFactoryInterface::class)
        ) {
            $factory = $this->container->get(ResponseFactoryInterface::class);
            if ($factory instanceof ResponseFactoryInterface) {
                $controller->setResponseFactory($factory);
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

        // 5. Resolve Arguments (Auto-wiring for Controller Methods) via the
        // injected ArgumentResolverInterface — no in-handler instantiation.
        $args = $this->argumentResolver->resolve($controller, $method, $request, $routeParams);

        // 5b. Dispatch ControllerArgumentsResolvedEvent
        if ($this->dispatcher !== null) {
            $event = new ControllerArgumentsResolvedEvent($request, $classname, $method, $args);
            $event = $this->dispatcher->dispatch($event);
            if ($event instanceof ControllerArgumentsResolvedEvent) {
                $args = $event->arguments;
            }
        }

        // 6. Execute via a bound callable rather than a string member selector.
        // The method_exists() guard above proves the [object, method] pair is
        // callable, so the array-callable form keeps the dynamic dispatch typed
        // and lint-clean (no string-member-selector suppression).
        /** @var callable $action */
        $action = [$controller, $method];
        /** @var mixed $result */
        $result = $action(...$args);

        // 7. Auto-Response Conversion
        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if ($this->responseConverter !== null) {
            return $this->responseConverter->convert($result);
        }

        // No converter injected: build the framework default from the container's
        // PSR-17 factory. ControllerResponseConverter is a stateless `final
        // readonly` value object, so on-demand construction is worker-safe — not a
        // service-locator call for a stateful dependency. This stays in handle()
        // (rather than the constructor) because the default-handler path has no
        // guaranteed ResponseFactory until the app wires one.
        if ($this->container->has(ResponseFactoryInterface::class)) {
            /** @var ResponseFactoryInterface $factory */
            $factory = $this->container->get(ResponseFactoryInterface::class);
            $converter = new ControllerResponseConverter($factory);
            return $converter->convert($result);
        }

        throw new RuntimeException(sprintf(
            'Controller Error: Method "%s::%s" returned "%s", but no conversion strategy matched.',
            get_class($controller),
            $method,
            get_debug_type($result),
        ));
    }
}
