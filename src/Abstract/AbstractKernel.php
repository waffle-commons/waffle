<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Psr\Container\ContainerInterface as PsrContainerInterface;use Psr\Http\Message\ResponseFactoryInterface;use Psr\Http\Message\ResponseInterface;use Psr\Http\Message\ServerRequestInterface;use Psr\Log\LoggerInterface;use Psr\Log\NullLogger;use ReflectionMethod;use Throwable;use Waffle\Commons\Contracts\Constant\Constant;use Waffle\Commons\Contracts\Container\ContainerInterface;use Waffle\Commons\Contracts\Core\KernelInterface;use Waffle\Commons\Contracts\Enum\Failsafe;use Waffle\Core\Config;use Waffle\Core\Container;use Waffle\Core\Security;use Waffle\Core\System;use Waffle\Core\View;use Waffle\Exception\Container\ContainerException;use Waffle\Exception\Container\NotFoundException;use Waffle\Exception\RouteNotFoundException;use Waffle\Factory\ContainerFactory;use Waffle\Trait\ReflectionTrait;

abstract class AbstractKernel implements KernelInterface
{
    use ReflectionTrait;

    private string $environment = Constant::ENV_PROD {
        get => $this->environment;
        set => $this->environment = $value;
    }

    public null|Config $config = null {
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
            $path = $request->getUri()->getPath();
            $routes = $this->system->getRouter()?->getRoutes() ?? [];
            $matchedRoute = null;
            $routeParams = [];

            foreach ($routes as $route) {
                $routePath = $route[Constant::PATH];
                $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', $routePath);
                $pattern = '#^' . str_replace('/', '\/', $pattern) . '$#';

                if (preg_match($pattern, $path, $matches)) {
                    $matchedRoute = $route;
                    array_shift($matches);
                    $routeParams = $matches;
                    break;
                }
            }

            if ($matchedRoute === null) {
                throw new RouteNotFoundException("No route found for path: $path");
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
            $paramIndex = 0;

            foreach ($refMethod->getParameters() as $param) {
                $type = $param->getType();
                if ($type && !$type->isBuiltin() && $this->container->has($type->getName())) {
                    $args[] = $this->container->get($type->getName());
                } elseif (isset($routeParams[$paramIndex])) {
                    $val = $routeParams[$paramIndex];
                    if ($type && $type->getName() === 'int') {
                        $val = (int) $val;
                    }
                    $args[] = $val;
                    $paramIndex++;
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
                    putenv($line);
                    [$key, $value] = explode('=', $line, 2);
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
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
            $rootConfig = $root . DIRECTORY_SEPARATOR . APP_CONFIG;
            $this->config = new Config(
                configDir: $rootConfig,
                environment: $this->environment,
            );
        }

        $security = new Security(cfg: $this->config);

        // Check if an implementation was provided via setContainerImplementation
        if ($this->innerContainer === null) {
            throw new ContainerException(
                'No Container implementation provided. Please ensure the Runtime injects a PSR-11 container via setContainerImplementation().',
            );
        }

        // Wrap the provided container with the Core Security Decorator
        $this->container = new Container($this->innerContainer, $security);

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

        $this->system = new System(security: $security)->boot(kernel: $this);

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

    private function createFailsafeContainer(): ContainerInterface
    {
        /** @var string $root */
        $root = APP_ROOT;
        $config = new Config(
            configDir: $root . '/app',
            environment: 'prod',
            failsafe: Failsafe::ENABLED,
        );
        $security = new Security(cfg: $config);

        // Fix: We must provide an inner container even in failsafe mode
        // Assuming CommonsContainer is available via autoload if waffle-commons/container is installed
        $inner = class_exists(CommonsContainer::class)
            ? new CommonsContainer()
            : throw new \RuntimeException('waffle-commons/container is missing.');

        return new Container($inner, $security);
    }
}
