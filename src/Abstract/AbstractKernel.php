<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionMethod;
use Throwable;
use Waffle\Commons\Contracts\Config\ConfigInterface;
use Waffle\Commons\Contracts\Constant\Constant;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\Core\KernelInterface;
use Waffle\Commons\Contracts\Routing\RouterInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Commons\Utils\Trait\ReflectionTrait;
use Waffle\Core\System;
use Waffle\Core\View;
use Waffle\Exception\Container\ContainerException;
use Waffle\Exception\Container\NotFoundException;
use Waffle\Exception\InvalidConfigurationException;
use Waffle\Exception\RouteNotFoundException;
use Waffle\Factory\ContainerFactory;

abstract class AbstractKernel implements KernelInterface
{
    use ReflectionTrait;

    private string $environment = Constant::ENV_PROD {
        get => $this->environment;
        set => $this->environment = $value;
    }

    public null|ConfigInterface $config = null {
        get => $this->config;
        set => $this->config = $value;
    }

    protected(set) null|System $system = null {
        get => $this->system;
        set => $this->system = $value;
    }

    public null|ContainerInterface $container = null {
        get => $this->container;
        set => $this->container = $value;
    }

    protected null|RouterInterface $router = null;

    protected null|SecurityInterface $security = null;

    // Holds the raw PSR-11 implementation injected by Runtime
    private null|PsrContainerInterface $innerContainer = null;

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

    public function setRouter(RouterInterface $router): void
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function __construct(
        protected LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->boot()->configure();

            if ($this->container === null) {
                throw new ContainerException('Container not initialized.');
            }

            if ($this->system === null) {
                throw new NotFoundException('System not initialized.');
            }

            // --- Routing Logic (Bridge) ---
            if ($this->router === null) {
                throw new ContainerException('Router not initialized. Please inject RouterInterface.');
            }

            $matchedRoute = $this->router->matchRequest($request);

            if ($matchedRoute === null) {
                throw new RouteNotFoundException('No route found for path: ' . $request->getUri()->getPath());
            }

            // --- Dispatching Logic ---
            $controllerClass = $matchedRoute[Constant::CLASSNAME];
            $method = $matchedRoute[Constant::METHOD];

            if (!$this->container->has($controllerClass)) {
                $this->container->set($controllerClass, $controllerClass);
            }

            /** @var object $controller */
            $controller = $this->container->get($controllerClass);

            $refMethod = new ReflectionMethod($controller, $method);
            $args = [];

            foreach ($refMethod->getParameters() as $param) {
                $type = $param->getType();
                if ($type && !$type->isBuiltin() && $this->container->has($type->getName())) {
                    $args[] = $this->container->get($type->getName());
                } elseif (isset($matchedRoute['params'][$param->getName()])) {
                    $val = $matchedRoute['params'][$param->getName()];
                    if ($type && $type->getName() === 'int') {
                        $val = (int) $val;
                    }
                    $args[] = $val;
                }
            }

            /** @var View $view */
            $view = $refMethod->invokeArgs($controller, $args);

            // --- Response Creation ---
            $json = json_encode($view->data, JSON_THROW_ON_ERROR);

            $response = $this->createResponse(200);
            $response->getBody()->write($json);
            $response->getBody()->rewind();

            return $response->withHeader('Content-Type', 'application/json');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    private function createResponse(int $code = 200): ResponseInterface
    {
        if ($this->container && $this->container->has(ResponseFactoryInterface::class)) {
            /** @var ResponseFactoryInterface $factory */
            $factory = $this->container->get(ResponseFactoryInterface::class);
            return $factory->createResponse($code);
        }

        $waffleResponseClass = 'Waffle\\Commons\\Http\\Response';
        if (class_exists($waffleResponseClass)) {
            /** @var ResponseInterface */
            return new $waffleResponseClass($code);
        }

        throw new \RuntimeException(
            'No Response implementation found. Please install waffle-commons/http or a PSR-17 factory.',
        );
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function boot(): self
    {
        /** @var string $root */
        $root = APP_ROOT;

        $envFiles = [
            $root . '/.env',
            $root . '/.env.local',
        ];

        foreach ($envFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines) {
                foreach ($lines as $line) {
                    if (!str_contains($line, '=') || str_starts_with(trim($line), '#')) {
                        continue;
                    }
                    [$key, $value] = explode('=', $line, 2);
                    // Only set if not already set (OS env vars take precedence)
                    if (getenv($key) === false && !isset($_ENV[$key])) {
                        putenv($line);
                        $_ENV[$key] = $value;
                        $_SERVER[$key] = $value;
                    }
                }
            }
        }

        $appEnv = $_ENV['APP_ENV'] ?? 'prod';
        if (!isset($_ENV[Constant::APP_ENV])) {
            $_ENV[Constant::APP_ENV] = $appEnv;
        }
        $this->environment = $appEnv;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function configure(): self
    {
        /** @var string $root */
        $root = APP_ROOT;
        if ($this->config === null) {
            throw new InvalidConfigurationException(
                'Configuration not initialized. Please inject a ConfigInterface implementation into the Kernel.',
            );
        }

        if ($this->security === null) {
            throw new ContainerException(
                'Security implementation not provided. Please inject a SecurityInterface implementation via setSecurity().',
            );
        }

        // Check if an implementation was provided via setContainerImplementation
        if ($this->innerContainer === null) {
            throw new ContainerException(
                'No Container implementation provided. Please ensure the Runtime injects a PSR-11 container via setContainerImplementation().',
            );
        }

        // The container is now expected to be fully configured (and potentially decorated) by the Runtime/Factory.
        // We cast it to our interface to support set() operations if available.
        if ($this->innerContainer instanceof ContainerInterface) {
            $this->container = $this->innerContainer;
        } else {
            // Fallback for raw PSR-11 containers that might not implement our interface but have methods we need?
            // Ideally, the user should inject a compatible container.
            // For now, we can't easily wrap it without a concrete class.
            // We assume the user knows what they are doing.
            // If they inject a read-only container, set() calls will fail, which is expected behavior for read-only.
            // But strict typing requires ContainerInterface.
            // We can't satisfy strict typing without a wrapper.
            // BUT, we are removing the wrapper to be a micro-core.
            // So we must change the property type or rely on the user injecting the right type.
            // Let's assume the user injects Waffle\Commons\Contracts\Container\ContainerInterface.
            if (!$this->innerContainer instanceof ContainerInterface) {
                throw new ContainerException(
                    'The injected container must implement Waffle\Commons\Contracts\Container\ContainerInterface.',
                );
            }
            $this->container = $this->innerContainer;
        }

        $containerFactory = new ContainerFactory();
        $services = $this->config->getString(key: 'waffle.paths.services');
        if (is_string($services)) {
            $containerFactory->create(
                container: $this->container,
                directory: $root . DIRECTORY_SEPARATOR . $services,
            );
        }
        $controllers = $this->config->getString(key: 'waffle.paths.controllers');
        if (is_string($controllers)) {
            $containerFactory->create(
                container: $this->container,
                directory: $root . DIRECTORY_SEPARATOR . $controllers,
            );
        }

        $this->system = new System(security: $this->security)->boot(kernel: $this);

        return $this;
    }

    private function handleException(Throwable $e): ResponseInterface
    {
        $this->logger->error($e->getMessage(), ['exception' => $e]);

        $data = [
            'error' => true,
            'message' => $e->getMessage(),
        ];

        if ($this->environment === Constant::ENV_DEV) {
            $data['trace'] = $e->getTraceAsString();
            $data['file'] = $e->getFile();
            $data['line'] = $e->getLine();
        }

        $code = $e instanceof RouteNotFoundException ? 404 : 500;

        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR);
            $response = $this->createResponse($code);
            $response->getBody()->write($json);
            $response->getBody()->rewind();
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Throwable $critical) {
            $response = $this->createResponse(500);
            $response->getBody()->write(json_encode([
                'error' => 'Critical System Error',
                'details' => 'Exception handling failed.',
            ], JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }
}
