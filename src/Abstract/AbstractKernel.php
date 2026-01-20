<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Waffle\Commons\Contracts\Config\ConfigInterface;
use Waffle\Commons\Contracts\Constant\Constant;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\Core\KernelInterface;
use Waffle\Commons\Contracts\Pipeline\MiddlewareStackInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Commons\Utils\Trait\ReflectionTrait;
use Waffle\Core\System;
use Waffle\Exception\Container\ContainerException;
use Waffle\Exception\Container\NotFoundException;
use Waffle\Exception\InvalidConfigurationException;
use Waffle\Exception\WaffleException;
use Waffle\Factory\ContainerFactory;
use Waffle\Handler\ControllerDispatcher;

abstract class AbstractKernel implements KernelInterface
{
    use ReflectionTrait;

    protected string $environment = Constant::ENV_PROD;

    protected bool $booted = false;

    public ?ConfigInterface $config = null;

    protected(set) ?System $system = null;

    public ?ContainerInterface $container = null;

    protected ?SecurityInterface $security = null;

    // Holds the raw PSR-11 implementation injected by Runtime
    private ?PsrContainerInterface $innerContainer = null;

    protected(set) ?MiddlewareStackInterface $middlewareStack = null;

    /**
     * Allows injecting a specific PSR-11 container implementation (e.g., from waffle-commons/container).
     */
    public function setContainerImplementation(PsrContainerInterface $container): void
    {
        $this->innerContainer = $container;
    }

    /**
     * Allows injecting Configuration (e.g., from waffle-commons/config).
     */
    public function setConfiguration(ConfigInterface $config): void
    {
        $this->config = $config;
    }

    public function setSecurity(SecurityInterface $security): void
    {
        $this->security = $security;
    }

    public function setMiddlewareStack(MiddlewareStackInterface $stack): void
    {
        $this->middlewareStack = $stack;
    }

    public function __construct(
        protected LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * {@inheritdoc}
     * @throws WaffleException|InvalidConfigurationException|ContainerException
     */
    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // PERFORMANCE: Only initialize if not already booted
        if (!$this->booted) {
            $this->boot()->configure();
        }

        $this->validateState(request: $request);

        $fallbackHandler = new ControllerDispatcher($this->container);

        return $this->middlewareStack->createHandler($fallbackHandler)->handle($request);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function boot(): self
    {
        if ($this->booted) {
            return $this;
        }

        $appEnv = Constant::ENV_PROD;
        if (getenv(Constant::APP_ENV) === false) {
            putenv($appEnv);
        }
        $this->environment = $appEnv;

        return $this;
    }

    /**
     * {@inheritdoc}
     * @throws WaffleException
     */
    #[\Override]
    public function configure(): self
    {
        if ($this->booted) {
            return $this;
        }

        /** @var string $root */
        $root = APP_ROOT;
        if ($this->config === null) {
            $messageConfig = 'Configuration not initialized. Please inject a ConfigInterface implementation into the Kernel.';
            $this->logAndThrow(InvalidConfigurationException::class, $messageConfig, 'configure');
        }

        if ($this->security === null) {
            $messageSecurity = 'Security implementation not provided. Please inject a SecurityInterface implementation via setSecurity().';
            $this->logAndThrow(ContainerException::class, $messageSecurity, 'configure');
        }

        // Check if an implementation was provided via setContainerImplementation
        if ($this->innerContainer === null) {
            $messageContainer = 'No Container implementation provided. Please ensure the Runtime injects a PSR-11 container via setContainerImplementation().';
            $this->logAndThrow(ContainerException::class, $messageContainer, 'configure');
        }

        // The container is now expected to be fully configured (and potentially decorated) by the Runtime/Factory.
        // We cast it to our interface to support set() operations if available.
        if ($this->innerContainer instanceof ContainerInterface) {
            $this->container = $this->innerContainer;
        } else {
            // Let's assume the user injects Waffle\Commons\Contracts\Container\ContainerInterface.
            $messageInnerContainer = 'The injected container must implement Waffle\Commons\Contracts\Container\ContainerInterface.';
            $this->logAndThrow(ContainerException::class, $messageInnerContainer, 'configure');
        }

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
        $this->booted = true;

        return $this;
    }

    private function validateState(ServerRequestInterface $request): void
    {
        if ($this->middlewareStack === null) {
            $messageMiddlewareStack = 'Kernel Error: MiddlewareStack not initialized. Did you call setMiddlewareStack()?';
            $this->logAndThrow(RuntimeException::class, $messageMiddlewareStack, $request->getMethod());
        }

        if ($this->container === null) {
            $messageContainer = 'Container not initialized.';
            $this->logAndThrow(ContainerException::class, $messageContainer, $request->getMethod());
        }

        if ($this->system === null) {
            $messageSystem = 'System not initialized.';
            $this->logAndThrow(NotFoundException::class, $messageSystem, $request->getMethod());
        }
    }

    /**
     * Centralized error reporting for the handle loop.
     * @param string $exceptionClass The class of the exception to throw.
     * @param string $message The error message.
     * @param string $method The current method.
     * @param int $code The HTTP status code (must be 100-599).
     */
    private function logAndThrow(
        string $exceptionClass,
        string $message,
        string $method,
        int $code = 500,
    ): void {
        $this->logger->critical($message, [
            'exception' => static::class,
            'method' => $method,
            'code' => $code,
        ]);

        // We pass the code to the constructor to ensure ErrorHandler receives a valid HTTP status.
        throw new $exceptionClass($message, $code);
    }
}
