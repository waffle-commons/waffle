<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Throwable;
use Waffle\Core\Cli;
use Waffle\Core\Config;
use Waffle\Core\Constant;
use Waffle\Core\Container;
use Waffle\Core\Request;
use Waffle\Core\Response;
use Waffle\Core\Security;
use Waffle\Core\System;
use Waffle\Core\View;
use Waffle\Exception\RouteNotFoundException;
use Waffle\Exception\SecurityException;
use Waffle\Factory\ContainerFactory;
use Waffle\Interface\CliInterface;
use Waffle\Interface\ContainerInterface;
use Waffle\Interface\KernelInterface;
use Waffle\Interface\RequestInterface;
use Waffle\Trait\MicrokernelTrait;
use Waffle\Trait\ReflectionTrait;

abstract class AbstractKernel implements KernelInterface
{
    use MicrokernelTrait;
    use ReflectionTrait;

    private string $environment = Constant::ENV_PROD {
        get => $this->environment;
        set => $this->environment = $value;
    }

    public Config $config {
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

    public function handle(): void
    {
        try {
            $this->boot()->configure();

            $handler = $this->isCli() ? $this->createCliFromRequest() : $this->createRequestFromGlobals();

            $this->run(handler: $handler);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    #[\Override]
    public function boot(): self
    {
        /** @var string $root */
        $root = APP_ROOT;
        // The .env file is now only for environment variables, not framework config.
        // In a real production setup, these would be set by the server environment.
        if (file_exists($root . '/.env')) {
            $lines = file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
        $this->environment = $_ENV['APP_ENV'] ?? 'prod';

        return $this;
    }

    #[\Override]
    public function configure(): self
    {
        $root = APP_ROOT . DIRECTORY_SEPARATOR . APP_CONFIG;
        $this->config = new Config(
            configDir: $root,
            environment: $this->environment,
        );

        $security = new Security(cfg: $this->config);

        $this->container = new Container(security: $security);

        $containerFactory = new ContainerFactory();
        $services = $this->config->get(key: 'waffle.paths.services');
        if (is_string($services)) {
            $containerFactory->create(
                container: $this->container,
                directory: APP_ROOT . DIRECTORY_SEPARATOR . $services,
            );
        }
        $controllers = $this->config->get(key: 'waffle.paths.controllers');
        if (is_string($controllers)) {
            $containerFactory->create(
                container: $this->container,
                directory: APP_ROOT . DIRECTORY_SEPARATOR . $controllers,
            );
        }

        $this->system = new System(security: $security)->boot(kernel: $this);

        return $this;
    }

    /**
     * @throws SecurityException
     */
    #[\Override]
    public function createRequestFromGlobals(): RequestInterface
    {
        $req = new Request(container: $this->container);
        if ($this->system instanceof System) {
            $router = $this->system->getRouter();
            if (null !== $router && !$req->isCli()) {
                $routes = $router->getRoutes();
                /**
                 * @var array{
                 *      classname: string,
                 *      method: non-empty-string,
                 *      arguments: array<non-empty-string, string>,
                 *      path: string,
                 *      name: non-falsy-string
                 *  } $route
                 */
                foreach ($routes as $route) {
                    if ($router->match(
                        container: $this->container,
                        req: $req,
                        route: $route,
                    )) {
                        $req->setCurrentRoute(route: $route);
                        break; // Stop after the first match
                    }
                }
            }
        }

        return $req;
    }

    #[\Override]
    public function createCliFromRequest(): CliInterface
    {
        // TODO(@supa-chayajin): Handle CLI command from request

        return new Cli(
            container: $this->container,
            cli: false,
        );
    }

    #[\Override]
    public function run(CliInterface|RequestInterface $handler): void
    {
        $handler->process()->render();
    }

    private function handleException(Throwable $e): void
    {
        // Exception Handler Hardening:
        // Create a failsafe container if the main one doesn't exist yet.
        $failsafeContainer = $this->container;
        if (null === $failsafeContainer) {
            $config = new Config(
                configDir: APP_ROOT . '/app',
                environment: 'prod',
                failsafe: true,
            );
            $security = new Security(cfg: $config);
            $failsafeContainer = new Container(security: $security);
        }

        $handler = $this->isCli()
            ? new Cli(
                container: $failsafeContainer,
                cli: true,
            ) : new Request(
                container: $failsafeContainer,
                cli: false,
            );
        $statusCode = 500;

        $data = [
            'message' => 'An unexpected error occurred.',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        if ($e instanceof RouteNotFoundException) {
            $statusCode = 404;
            $data = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }

        http_response_code($statusCode);
        new Response(handler: $handler)->throw(view: new View(data: $data));
    }
}
