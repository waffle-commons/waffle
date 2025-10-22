<?php

declare(strict_types=1);

namespace Waffle\Abstract;

use Throwable;
use Waffle\Core\Config;
use Waffle\Core\Constant;
use Waffle\Core\Container;
use Waffle\Core\Request;
use Waffle\Core\Response;
use Waffle\Core\Security;
use Waffle\Core\System;
use Waffle\Core\View;
use Waffle\Enum\AppMode;
use Waffle\Enum\Failsafe;
use Waffle\Exception\Container\ContainerException;
use Waffle\Exception\Container\NotFoundException;
use Waffle\Exception\InvalidConfigurationException;
use Waffle\Exception\RenderingException;
use Waffle\Exception\RouteNotFoundException;
use Waffle\Factory\CliFactory;
use Waffle\Factory\ContainerFactory;
use Waffle\Factory\RequestFactory;
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
     * @throws RenderingException
     */
    public function handle(): void
    {
        try {
            $this->boot()->configure();

            if ($this->container === null) {
                throw new ContainerException();
            }

            if ($this->system === null) {
                throw new NotFoundException();
            }

            $handler = $this->isCli()
                ? new CliFactory()->createFromGlobals(container: $this->container)
                : new RequestFactory()->createFromGlobals(
                    container: $this->container,
                    system: $this->system,
                );

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

        // Define the environment files in order of precedence.
        // Files loaded later will override earlier ones.
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
                    // Skip comments and invalid lines
                    if (!str_contains($line, '=') || str_starts_with(trim($line), '#')) {
                        continue;
                    }
                    // Load into all relevant superglobals
                    putenv($line);
                    [$key, $value] = explode('=', $line, 2);
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }

        // Fallback on 'prod' environment if not set.
        $appEnv = $_ENV['APP_ENV'] ?? 'prod';
        if (!isset($_ENV[Constant::APP_ENV])) {
            $_ENV[Constant::APP_ENV] = $appEnv;
        }
        $this->environment = $appEnv;

        return $this;
    }

    /**
     * @throws InvalidConfigurationException
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

    #[\Override]
    public function run(CliInterface|RequestInterface $handler): void
    {
        $handler->process()->render();
    }

    /**
     * @throws RenderingException
     */
    private function handleException(Throwable $e): void
    {
        if ($this->isCli()) {
            fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
            exit(1);
        }

        $this->buildErrorResponse(e: $e);
    }

    private function buildErrorResponse(Throwable $e): void
    {
        // Exception Handler Hardening:
        $container = $this->container ?? $this->createFailsafeContainer();

        $handler = new Request(
            container: $container,
            cli: AppMode::WEB,
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

    private function createFailsafeContainer(): ContainerInterface
    {
        /** @var string $root */
        $root = APP_ROOT;
        // Create a failsafe container if the main one doesn't exist yet.
        $config = new Config(
            configDir: $root . '/app',
            environment: 'prod',
            failsafe: Failsafe::ENABLED,
        );
        $security = new Security(cfg: $config);

        return new Container(security: $security);
    }
}
