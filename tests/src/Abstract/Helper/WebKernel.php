<?php

declare(strict_types=1);

namespace WaffleTests\Abstract\Helper;

use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Waffle\Commons\Contracts\Config\ConfigInterface;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\Pipeline\MiddlewareStackInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Core\System;
use Waffle\Kernel;

/**
 * Test double for the concrete Kernel.
 *
 * ARCH-03: production {@see \Waffle\Abstract\AbstractKernel} no longer exposes
 * `set*()` collaborator setters — every required collaborator is injected at
 * construction. This test-only double keeps THIN convenience setters that simply
 * reassign the (now mutable, non-readonly) kernel fields, so the existing kernel
 * tests keep their incremental build style without a wholesale rewrite. The
 * constructor builds sensible test defaults; `configure()` is overridden to skip
 * the ContainerFactory directory scan.
 */
class WebKernel extends Kernel
{
    public function __construct(
        string $configDir,
        string $environment,
        ?ContainerInterface $container = null,
        ?PsrContainerInterface $innerContainer = null,
        LoggerInterface $logger = new NullLogger(),
    ) {
        $resolved =
            $container ?? (
                $innerContainer instanceof ContainerInterface ? $innerContainer : null
            ) ?? self::defaultContainer();

        parent::__construct(
            config: self::testConfig($configDir, $environment),
            container: $resolved,
            security: self::defaultSecurity(),
            middlewareStack: new FakeMiddlewareStack(),
            logger: $logger,
        );
    }

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

    public function setContainerImplementation(PsrContainerInterface $container): void
    {
        if ($container instanceof ContainerInterface) {
            $this->container = $container;
        }
    }

    public function setDeps(PsrContainerInterface $innerContainer): void
    {
        $this->setContainerImplementation($innerContainer);
    }

    #[\Override]
    public function configure(): void
    {
        if ($this->booted) {
            return;
        }

        // Test double: skip the standard ContainerFactory directory scan; wire only
        // the minimal boot (System + default terminal handler), then lock.
        $this->system = new System(security: $this->security)->boot(kernel: $this);
        $this->registerDefaultTerminalHandler();
        if (method_exists($this->container, 'lock')) {
            $this->container->lock();
        }

        $this->booted = true;
    }

    public static function defaultConfig(): ConfigInterface
    {
        return self::testConfig('', '');
    }

    private static function testConfig(string $configDir, string $environment): ConfigInterface
    {
        return new class($configDir, $environment) implements ConfigInterface {
            public function __construct(
                private readonly string $dir,
                private readonly string $env,
            ) {}

            #[\Override]
            public function getInt(string $key, ?int $default = null): ?int
            {
                return 10;
            }

            #[\Override]
            public function getString(string $key, ?string $default = null): ?string
            {
                return match ($key) {
                    'waffle.env' => $this->env,
                    'waffle.config_dir' => $this->dir,
                    default => null,
                };
            }

            #[\Override]
            public function getArray(string $key, ?array $default = null): ?array
            {
                return [];
            }

            #[\Override]
            public function getBool(string $key, ?bool $default = null): ?bool
            {
                return false;
            }
        };
    }

    public static function defaultSecurity(): SecurityInterface
    {
        return new class implements SecurityInterface {
            #[\Override]
            public function analyze(object $object, array $expectations = []): void {}
        };
    }

    public static function defaultContainer(): ContainerInterface
    {
        return new class implements ContainerInterface {
            /** @var array<string, mixed> */
            private array $services = [];

            #[\Override]
            public function get(string $id): mixed
            {
                return $this->services[$id] ?? null;
            }

            #[\Override]
            public function has(string $id): bool
            {
                return array_key_exists($id, $this->services);
            }

            #[\Override]
            public function set(string $id, mixed $concrete): void
            {
                // Don't let the ContainerFactory scan overwrite a pre-wired object
                // with a discovered class-name string.
                if (array_key_exists($id, $this->services) && is_object($this->services[$id]) && is_string($concrete)) {
                    return;
                }
                $this->services[$id] = $concrete;
            }

            #[\Override]
            public function reset(): void
            {
                $this->services = [];
            }
        };
    }
}
