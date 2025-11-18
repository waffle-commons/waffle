<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use ReflectionMethod;
use Throwable;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
            // Ensure the kernel is booted and configured
            $this->boot()->configure();

            if ($this->container === null) {
                throw new ContainerException('Container not initialized.');
            }

            if ($this->system === null) {
                throw new NotFoundException('System not initialized.');
            }

            // --- Routing Logic (Bridge to existing Router) ---
            // The existing Router is not yet fully PSR-7 compliant regarding matching.
            // We bridge the gap by manually matching the PSR-7 request path against the loaded routes.

            $path = $request->getUri()->getPath();
            $routes = $this->system->getRouter()?->getRoutes() ?? [];
            $matchedRoute = null;
            $routeParams = [];

            foreach ($routes as $route) {
                $routePath = $route[Constant::PATH];

                // Convert route path (e.g., /users/{id}) to regex
                // This is a simplified matching logic for the Alpha version.
                $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', $routePath);
                $pattern = '#^' . str_replace('/', '\/', $pattern) . '$#';

                if (preg_match($pattern, $path, $matches)) {
                    $matchedRoute = $route;
                    array_shift($matches); // Remove the full match
                    $routeParams = $matches; // Remaining items are the parameter values
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
                // Auto-register the controller if it's not already in the container
                $this->container->set($controllerClass, $controllerClass);
            }

            /** @var object $controller */
            $controller = $this->container->get($controllerClass);

            // Resolve Controller Arguments
            // We currently support injecting services (via type hint) or route parameters (via position/name placeholder).
            $refMethod = new ReflectionMethod($controller, $method);
            $args = [];
            $paramIndex = 0;

            foreach ($refMethod->getParameters() as $param) {
                $type = $param->getType();

                // 1. Try to inject a service from the container
                if ($type && !$type->isBuiltin() && $this->container->has($type->getName())) {
                    $args[] = $this->container->get($type->getName());
                }
                // 2. Try to inject a route parameter (primitive type)
                elseif (isset($routeParams[$paramIndex])) {
                    // In a full implementation, we would cast to int/string based on type hint.
                    $val = $routeParams[$paramIndex];
                    if ($type && $type->getName() === 'int') {
                        $val = (int) $val;
                    }
                    $args[] = $val;
                    $paramIndex++;
                }
                // 3. Default / Optional parameters are handled by PHP if not provided
            }

            /** @var View $view */
            $view = $refMethod->invokeArgs($controller, $args);

            // --- Response Creation ---
            // Convert the View data object into a JSON response
            $json = json_encode($view->data, JSON_THROW_ON_ERROR);

            $response = $this->createResponse(200);
            $response->getBody()->write($json);
            $response->getBody()->rewind();

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-Powered-By', 'Waffle Framework');

        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Helper to create a PSR-7 Response.
     * It tries to use a PSR-17 Factory if available, or falls back to Waffle's implementation.
     */
    private function createResponse(int $code = 200): ResponseInterface
    {
        // 1. Try PSR-17 Factory from Container (Preferred)
        if ($this->container && $this->container->has(ResponseFactoryInterface::class)) {
            /** @var ResponseFactoryInterface $factory */
            $factory = $this->container->get(ResponseFactoryInterface::class);
            return $factory->createResponse($code);
        }

        // 2. Fallback: Direct instantiation of Waffle's HTTP Response
        $waffleResponseClass = 'Waffle\\Commons\\Http\\Response';
        if (class_exists($waffleResponseClass)) {
            /** @var ResponseInterface */
            return new $waffleResponseClass($code);
        }

        throw new \RuntimeException('No Response implementation found. Please install waffle-commons/http or provide a PSR-17 factory.');
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
     * Handles exceptions by converting them into a valid JSON ResponseInterface.
     */
    private function handleException(Throwable $e): ResponseInterface
    {
        $data = [
            'error' => true,
            'message' => $e->getMessage(),
        ];

        if ($this->environment === Constant::ENV_DEV) {
            $data['trace'] = $e->getTraceAsString();
            $data['file'] = $e->getFile();
            $data['line'] = $e->getLine();
        }

        $code = ($e instanceof RouteNotFoundException) ? 404 : 500;

        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR);
            $response = $this->createResponse($code);
            $response->getBody()->write($json);
            $response->getBody()->rewind();
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Throwable $critical) {
            // Critical fallback if JSON encoding fails or Response cannot be created
            $response = $this->createResponse(500);
            $response->getBody()->write('{"error": "Critical System Error", "details": "Exception handling failed."}');
            return $response->withHeader('Content-Type', 'application/json');
        }
    }
}
