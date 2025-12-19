<?php

declare(strict_types=1);

namespace WaffleTests\Abstract;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Log\NullLogger;
use Waffle\Abstract\AbstractKernel;
use Waffle\Commons\Contracts\Config\ConfigInterface;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\Security\SecurityInterface;
use Waffle\Exception\Container\ContainerException;
use Waffle\Exception\InvalidConfigurationException;
use Waffle\Exception\Container\NotFoundException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[CoversClass(AbstractKernel::class)]
#[AllowMockObjectsWithoutExpectations]
class AbstractKernelCoverageTest extends TestCase
{
    public function testPropertyHooks(): void
    {
        $kernel = new class(new NullLogger()) extends AbstractKernel {};
        
        $config = $this->createMock(ConfigInterface::class);
        $kernel->config = $config;
        static::assertSame($config, $kernel->config);

        $container = $this->createMock(ContainerInterface::class);
        $kernel->container = $container;
        static::assertSame($container, $kernel->container);

        // System is protected(set), so we can get it but not set it publicly? 
        // Wait, AbstractKernel Definition: protected(set) null|System $system = null { get => $this->system; set => $this->system = $value; }
        // If it is protected(set), we can't set it from outside.
        // But we can get it.
        // To test setter, we need a method in anonymous class or assume internal setting works.
        // But AbstractKernel has `set => $this->system = $value;` which is the setter hook.
        // It's used by `handle` -> `boot` -> `configure` -> `this->system = new System(...)`.
        
        // Environment
        // private string $environment { get => ... set => ... }
        // It is private!
        // We cannot access it directly from outside.
        // But `boot()` sets it.
        // Code: `get => $this->environment;` - assumes access? 
        // Usually, private visibility applies to the property.
        // If the property is private, public access is forbidden.
        // The hooks don't change visibility unless specified?
        // PHP 8.4 Property Hooks: Visibility can be defined on hooks.
        // The code:
        /*
            private string $environment = Constant::ENV_PROD {
                get => $this->environment;
                set => $this->environment = $value;
            }
        */
        // If property is private, hooks are private unless marked public?
        // Actually, if it works in `AbstractKernelTest`, maybe it is accessible? 
        // No, `AbstractKernelTest` uses `WebKernel` which might expose it?
        // Let's verify `system`.
        // `protected(set)` means protected setter, public getter (implied public property with protected set)?
        // Wait, syntax `protected(set)` is PHP 8.4 asymmetric visibility.
        // `public null|System $system` ... `protected(set)`
        // Code: `protected(set) null|System $system = null`
        // Visibility of property itself determines GET visibility (default public if not specified? No `protected(set)` means public get, protected set).
        
        // So I can read `$kernel->system`.
        static::assertNull($kernel->system);
    }

    public function testConfigureThrowsExceptionIfConfigMissing(): void
    {
        $kernel = new class(new NullLogger()) extends AbstractKernel {};
        
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Configuration not initialized');

        $kernel->configure();
    }

    public function testConfigureThrowsExceptionIfSecurityMissing(): void
    {
        $kernel = new class(new NullLogger()) extends AbstractKernel {};
        $kernel->setConfiguration($this->createMock(ConfigInterface::class));
        
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Security implementation not provided');

        $kernel->configure();
    }

    public function testConfigureThrowsExceptionIfInnerContainerMissing(): void
    {
        $kernel = new class(new NullLogger()) extends AbstractKernel {};
        $kernel->setConfiguration($this->createMock(ConfigInterface::class));
        $kernel->setSecurity($this->createMock(SecurityInterface::class));
        
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('No Container implementation provided');

        $kernel->configure();
    }

    public function testConfigureThrowsExceptionIfInnerContainerInvalid(): void
    {
        $kernel = new class(new NullLogger()) extends AbstractKernel {};
        $kernel->setConfiguration($this->createMock(ConfigInterface::class));
        $kernel->setSecurity($this->createMock(SecurityInterface::class));
        
        $innerContainer = $this->createMock(PsrContainerInterface::class); // Not Waffle ContainerInterface
        $kernel->setContainerImplementation($innerContainer);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('The injected container must implement');

        $kernel->configure();
    }

    public function testConfigureSuccessWithValidDeps(): void
    {
        $kernel = new class(new NullLogger()) extends AbstractKernel {
            // Override System creation to avoid actual System boot if possible,
            // or ensure System boot works. 
            // System needs security and kernel.
            // System::boot(kernel) calls kernel methods?
        };

        $configMock = $this->createMock(ConfigInterface::class);
        $configMock->method('getString')->willReturn(null); // Return null for paths to skip ContainerFactory scanning

        $securityMock = $this->createMock(SecurityInterface::class);
        $containerMock = $this->createMock(ContainerInterface::class);

        $kernel->setConfiguration($configMock);
        $kernel->setSecurity($securityMock);
        $kernel->setContainerImplementation($containerMock);

        // We also need APP_ROOT defined. (It is defined in test bootstrap).

        $kernel->configure();

        static::assertSame($containerMock, $kernel->container);
        static::assertNotNull($kernel->system);
    }
}
