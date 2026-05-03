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
use Waffle\Event\ControllerArgumentsResolvedEvent;

/**
 * The terminal handler of the framework.
 * It executes the controller identified by the request attributes.
 */
final readonly class ControllerDispatcher implements RequestHandlerInterface
{
    public function __construct(
        private ContainerInterface $container,
        private ?EventDispatcherInterface $dispatcher = null,
        private ?ArgumentResolverInterface $argumentResolver = null,
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
        $resolver = $this->argumentResolver ?? new ControllerArgumentResolver($this->container);
        $args = $resolver->resolve($controller, $method, $request, $routeParams);

        // 5b. Dispatch ControllerArgumentsResolvedEvent
        if ($this->dispatcher !== null) {
            $event = new ControllerArgumentsResolvedEvent($request, $classname, $method, $args);
            $event = $this->dispatcher->dispatch($event);
            if ($event instanceof ControllerArgumentsResolvedEvent) {
                $args = $event->getArguments();
            }
        }

        // 6. Execute
        // @mago-ignore string-member-selector
        $result = $controller->$method(...$args);

        // 7. Auto-Response Conversion
        if ($this->responseConverter !== null) {
            return $this->responseConverter->convert($result);
        }

        if ($this->container->has(ResponseFactoryInterface::class)) {
            /** @var ResponseFactoryInterface $factory */
            $factory = $this->container->get(ResponseFactoryInterface::class);
            $converter = new ControllerResponseConverter($factory);
            return $converter->convert($result);
        }

        throw new RuntimeException(sprintf(
            'Controller Error: Method "%s::%s" returned "%s", but ResponseFactoryInterface is not available in container.',
            get_class($controller),
            $method,
            get_debug_type($result),
        ));
    }
}
