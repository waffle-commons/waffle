<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use IgorPhp\IgorBundle\Attribute\WorkerSafe;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Waffle\Commons\Contracts\Config\ConfigInterface;
use Waffle\Commons\Contracts\Constant\Constant;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\Core\KernelInterface;
use Waffle\Commons\Contracts\Core\TerminableInterface;
use Waffle\Commons\Contracts\EventDispatcher\EventDispatcherInterface;
use Waffle\Commons\Contracts\Handler\ArgumentResolverInterface;
use Waffle\Commons\Contracts\Pipeline\MiddlewareStackInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Commons\Contracts\Service\ReflectionServiceInterface;
use Waffle\Commons\Contracts\Service\ResettableInterface;
use Waffle\Core\System;
use Waffle\Event\RequestReceivedEvent;
use Waffle\Event\ResponseGeneratedEvent;
use Waffle\Event\TerminateEvent;
use Waffle\Exception\Container\ContainerException;
use Waffle\Exception\WaffleException;
use Waffle\Factory\ContainerFactory;
use Waffle\Handler\ControllerArgumentResolver;
use Waffle\Handler\ControllerDispatcher;
use Waffle\Service\ReflectionService;

/**
 * Base HTTP kernel: turns a PSR-7 request into a PSR-7 response and exposes the
 * framework's request lifecycle as three optional, dispatcher-gated hooks.
 *
 * Lifecycle (per request, in order):
 *  1. boot() + configure() — once per worker; wires the container, middleware
 *     stack and default terminal handler.
 *  2. {@see RequestReceivedEvent} — dispatched before the PSR-15 pipeline runs.
 *     A listener may return the event carrying a replaced `request`, which the
 *     kernel then uses for the remainder of the request.
 *  3. The PSR-15 middleware pipeline executes, terminating in the
 *     RequestHandlerInterface resolved from the container.
 *  4. {@see ResponseGeneratedEvent} — dispatched after the pipeline. A listener
 *     may return the event carrying a replaced `response`, which becomes the
 *     kernel's return value.
 *  5. {@see TerminateEvent} — dispatched by terminate() after the response has
 *     been emitted, for deferred/async work (independent of the PSR-15 stack).
 *
 * Every hook is a no-op when no EventDispatcher is set (the default), so the
 * lifecycle imposes zero cost on apps that never subscribe to it. The event
 * objects live in this component (Waffle\Event\*) by design — they are
 * framework-internal lifecycle signals, not part of the cross-component
 * contracts surface.
 *
 * ARCH-03: every required collaborator is injected at construction (mandatory
 * constructor parameters), so a half-built kernel is unrepresentable. The previous
 * nullable fields + `set*()` setters + `validateState()` temporal-coupling
 * machinery are gone.
 */
abstract class AbstractKernel implements KernelInterface, TerminableInterface
{
    protected string $environment = Constant::ENV_PROD;

    protected bool $booted = false;

    protected(set) ?System $system = null;

    protected ?EventDispatcherInterface $dispatcher = null;

    public function __construct(
        public protected(set) ConfigInterface $config,
        public protected(set) ContainerInterface $container,
        protected SecurityInterface $security,
        protected(set) MiddlewareStackInterface $middlewareStack,
        protected LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Optionally wire the lifecycle event dispatcher — the ONE optional
     * collaborator (the kernel's hooks no-op without it). Kept as a boot-time
     * setter so the required-collaborator constructor stays within the 5-parameter
     * bound; a missing dispatcher never leaves the kernel half-built (ARCH-03).
     */
    #[WorkerSafe(
        scope: 'boot-time',
        reason: 'optional dispatcher wired once before requests; persists for the worker lifetime',
    )]
    public function setEventDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     * @throws WaffleException|ContainerException
     */
    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // PERFORMANCE: Only initialize if not already booted
        if (!$this->booted) {
            $this->boot()->configure();
        }

        // Dispatch RequestReceivedEvent (allows pre-pipeline modification)
        $requestReceivedEvent = new RequestReceivedEvent($request);
        $requestReceivedEvent = $this->dispatch($requestReceivedEvent);
        if ($requestReceivedEvent instanceof RequestReceivedEvent) {
            $request = $requestReceivedEvent->request;
        }

        // Beta-1 hardening (Roadmap §1.1 — Découplage du Kernel): the terminal
        // handler comes exclusively from the container under PSR-15's
        // RequestHandlerInterface. configure() registers a ControllerDispatcher
        // by default; downstream apps swap it by pre-registering their own
        // handler before configure() runs.
        $fallbackHandler = $this->container->get(RequestHandlerInterface::class);
        if (!$fallbackHandler instanceof RequestHandlerInterface) {
            $this->logAndThrow(
                ContainerException::class,
                'Kernel Error: container returned a non-RequestHandlerInterface instance for the terminal handler.',
                $request->getMethod(),
            );
        }

        $response = $this->middlewareStack->createHandler($fallbackHandler)->handle($request);

        // Dispatch ResponseGeneratedEvent (allows post-pipeline modification)
        $responseGeneratedEvent = new ResponseGeneratedEvent($response);
        $responseGeneratedEvent = $this->dispatch($responseGeneratedEvent);
        if ($responseGeneratedEvent instanceof ResponseGeneratedEvent) {
            $response = $responseGeneratedEvent->response;
        }

        return $response;
    }

    /**
     * Dispatches a terminate event for heavy async tasks after response emission.
     * Call this from Runtime after emit() and before reset().
     */
    #[\Override]
    public function terminate(ServerRequestInterface $request, ResponseInterface $response): void
    {
        if ($this->dispatcher === null) {
            return;
        }

        $this->dispatch(new TerminateEvent($request, $response));
    }

    /**
     * Dispatches an event through the event dispatcher if available.
     *
     * @template T of object
     * @param T $event
     * @return T
     */
    protected function dispatch(object $event): object
    {
        if ($this->dispatcher === null) {
            return $event;
        }

        return $this->dispatcher->dispatch($event);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    #[WorkerSafe(
        scope: 'boot-time',
        reason: 'one-time APP_ENV read; persists for the worker lifetime, not per request',
    )]
    public function boot(): static
    {
        if ($this->booted) {
            return $this;
        }

        // Beta-1 hardening: read APP_ENV from the process environment without
        // mutating global state (the prior `putenv($appEnv)` was both a latent
        // bug — missing '=' sentinel — and a worker-mode safety hazard).
        $envVar = getenv(Constant::APP_ENV);
        $this->environment = is_string($envVar) && $envVar !== '' ? $envVar : Constant::ENV_PROD;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @throws WaffleException
     */
    #[\Override]
    #[WorkerSafe(
        scope: 'boot-time',
        reason: 'one-time boot wiring guarded by $booted; persists for the worker lifetime',
    )]
    public function configure(): void
    {
        if ($this->booted) {
            return;
        }

        /** @var string $root */
        $root = APP_ROOT;

        $containerFactory = new ContainerFactory();
        $services = $this->config->getString(key: 'waffle.paths.services');
        if (is_string($services)) {
            $containerFactory->create(container: $this->container, directory: $root . DIRECTORY_SEPARATOR . $services);
        }
        $controllers = $this->config->getString(key: 'waffle.paths.controllers');
        if (is_string($controllers)) {
            $containerFactory->create(
                container: $this->container,
                directory: $root . DIRECTORY_SEPARATOR . $controllers,
            );
        }

        $this->system = new System(security: $this->security)->boot(kernel: $this);

        $this->registerDefaultTerminalHandler();

        // Lock the container to prevent service override after boot
        if (method_exists($this->container, 'lock')) {
            $this->container->lock();
        }

        $this->booted = true;
    }

    /**
     * Registers the default terminal PSR-15 handler in the container under
     * `RequestHandlerInterface`. Idempotent: pre-registered handlers (e.g. an
     * app supplying its own dispatcher) win — only the empty-slot case is
     * filled here.
     *
     * Called from {@see self::configure()} during the normal boot path. Test
     * fixtures that override `configure()` to bypass the standard setup must
     * call this method themselves, otherwise {@see self::handle()} cannot
     * resolve the terminal handler.
     */
    protected function registerDefaultTerminalHandler(): void
    {
        // Fast path: if the terminal handler is already wired (the AppKernelFactory
        // composes ReflectionService + ArgumentResolver + ControllerDispatcher and
        // binds RequestHandlerInterface), do nothing. Returning here BEFORE touching
        // the infra-service slots is deliberate — resolving them through a
        // SecureContainer would run controller-grade security analysis over framework
        // plumbing (and ReflectionService's `string|object` union params trip the
        // analyzer). Apps own the wiring; the kernel only fills the empty-slot case.
        if ($this->container->has(RequestHandlerInterface::class)) {
            return;
        }

        // Sensible defaults for library/test consumers that did NOT pre-wire a
        // handler. Each lookup is gated by container.has() + a type check, so a
        // permissive blanket-has() test double cannot starve the kernel of its deps.
        $reflectionService = $this->resolveOrDefault(
            ReflectionServiceInterface::class,
            ReflectionServiceInterface::class,
            static fn(): ReflectionServiceInterface => new ReflectionService(),
        );

        $argumentResolver = $this->resolveOrDefault(
            ArgumentResolverInterface::class,
            ArgumentResolverInterface::class,
            fn(): ArgumentResolverInterface => new ControllerArgumentResolver($this->container, $reflectionService),
        );

        $this->container->set(
            RequestHandlerInterface::class,
            new ControllerDispatcher($this->container, $this->dispatcher, $argumentResolver),
        );
    }

    /**
     * Returns the container-bound instance of `$interface` if it exists and is
     * the expected type; otherwise constructs a default via `$factory`, binds
     * it to the container, and returns it.
     *
     * Defensive against test doubles whose `has()` returns true for unknown IDs
     * but whose `get()` returns null — without this guard, those doubles would
     * crash the kernel boot with a TypeError on the first dependency lookup.
     *
     * @template TService of object
     * @param class-string<TService> $interface
     * @param class-string<TService> $expectedType
     * @param callable(): TService   $factory
     * @return TService
     */
    private function resolveOrDefault(string $interface, string $expectedType, callable $factory): object
    {
        if ($this->container->has($interface)) {
            $candidate = $this->container->get($interface);
            if ($candidate instanceof $expectedType) {
                return $candidate;
            }
        }

        $instance = $factory();
        $this->container->set($interface, $instance);
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function reset(): void
    {
        $this->container->reset();

        // Drain any buffered logger so log entries never bleed across requests in
        // worker mode. Decoupled: only loggers that opt into ResettableInterface.
        if ($this->logger instanceof ResettableInterface) {
            $this->logger->reset();
        }
    }

    /**
     * Centralized error reporting for the handle loop.
     * @param string $exceptionClass The class of the exception to throw.
     * @param string $message The error message.
     * @param string $method The current method.
     * @param int $code The HTTP status code (must be 100-599).
     */
    private function logAndThrow(string $exceptionClass, string $message, string $method, int $code = 500): never
    {
        $this->logger->critical($message, [
            'exception' => static::class,
            'method' => $method,
            'code' => $code,
        ]);

        // We pass the code to the constructor to ensure ErrorHandler receives a valid HTTP status.
        throw new $exceptionClass($message, $code);
    }
}
