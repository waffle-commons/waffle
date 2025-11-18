<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use ReflectionMethod;
use Throwable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Waffle\Core\Config;
use Waffle\Core\Constant;
use Waffle\Core\Container;
use Waffle\Core\Security;
use Waffle\Core\System;
use Waffle\Core\View;
use Waffle\Exception\Container\ContainerException;
use Waffle\Exception\Container\NotFoundException;
use Waffle\Exception\InvalidConfigurationException;
use Waffle\Exception\RouteNotFoundException;
use Waffle\Factory\ContainerFactory;
use Waffle\Interface\ContainerInterface;
use Waffle\Interface\KernelInterface;
use Waffle\Trait\ReflectionTrait;

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

            // --- Routing Logic (Bridge to existing Router) ---
            // The existing Router is not yet PSR-7 compliant, so we manually match
            // the PSR-7 request against the routes loaded by the system.

            $path = $request->getUri()->getPath();
            $routes = $this->system->getRouter()?->getRoutes() ?? [];
            $matchedRoute = null;
            $routeParams = [];

            // Simple matching logic (placeholder for full Router integration)
            foreach ($routes as $route) {
                $routePath = $route[Constant::PATH];

                // Transform route path like /users/{id} to regex #^/users/([^/]+)$#
                $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', $routePath);
                $pattern = '#^' . str_replace('/', '\/', $pattern) . '$#';

                if (preg_match($pattern, $path, $matches)) {
                    $matchedRoute = $route;
                    array_shift($matches); // Remove full match
                    $routeParams = $matches; // Remaining matches are parameter values
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
                // Auto-register controller if missing (lazy loading)
                $this->container->set($controllerClass, $controllerClass);
            }

            /** @var object $controller */
            $controller = $this->container->get($controllerClass);

            // Resolve arguments (Basic support: injects route params or services)
            $refMethod = new ReflectionMethod($controller, $method);
            $args = [];
            $paramIndex = 0;

            foreach ($refMethod->getParameters() as $param) {
                $type = $param->getType();
                if ($type && !$type->isBuiltin() && $this->container->has($type->getName())) {
                    // Inject service from container
                    $args[] = $this->container->get($type->getName());
                } elseif (isset($routeParams[$paramIndex])) {
                    // Inject route parameter (simple positional mapping for now)
                    $args[] = $routeParams[$paramIndex];
                    $paramIndex++;
                }
            }

            /** @var View $view */
            $view = $refMethod->invokeArgs($controller, $args);

            // --- Response Creation ---
            // Convert View data to JSON response
            $json = json_encode($view->data, JSON_THROW_ON_ERROR);

            $response = $this->createResponse(200);
            $response->getBody()->write($json);
            $response->getBody()->rewind();

            return $response->withHeader('Content-Type', 'application/json');

        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Helper to create a response using available factories or concrete classes.
     */
    private function createResponse(int $code = 200): ResponseInterface
    {
        // 1. Try PSR-17 Factory from Container (Best practice)
        if ($this->container && $this->container->has(ResponseFactoryInterface::class)) {
            /** @var ResponseFactoryInterface $factory */
            $factory = $this->container->get(ResponseFactoryInterface::class);
            return $factory->createResponse($code);
        }

        // 2. Fallback: Try to instantiate Waffle's HTTP Response directly
        // This allows the framework to work out-of-the-box if waffle-commons/http is installed
        $waffleResponseClass = 'Waffle\\Commons\\Http\\Response';
        if (class_exists($waffleResponseClass)) {
            /** @var ResponseInterface */
            return new $waffleResponseClass($code);
        }

        throw new \RuntimeException('No Response implementation found. Please install waffle-commons/http or a PSR-17 factory.');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function boot(): self
    {
        /** @var string $root */
        $root = APP_ROOT;

        // Define environment files order.
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
                    // Skip comments
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

        $this->container = new Container(security: $security);

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

    /**
     * Handles exceptions by converting them into a JSON ResponseInterface.
     */
    private function handleException(Throwable $e): ResponseInterface
    {
        $data = [
            'error' => true,
            'message' => $e->getMessage(),
        ];

        if ($this->environment === Constant::ENV_DEV) {
            $data['trace'] = $e->getTraceAsString();
        }

        $code = ($e instanceof RouteNotFoundException) ? 404 : 500;

        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR);
            $response = $this->createResponse($code);
            $response->getBody()->write($json);
            $response->getBody()->rewind();
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Throwable $critical) {
            // Ultimate fallback if JSON encoding or response creation fails
            // We assume createResponse(500) works, if not, the script will crash which is expected for critical failure
            $response = $this->createResponse(500);
            $response->getBody()->write('{"error": "Critical System Error"}');
            return $response;
        }
    }
}
